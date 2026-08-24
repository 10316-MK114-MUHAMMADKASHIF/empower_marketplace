<?php

namespace App\Jobs;

use App\Enums\AiExtractionStatus;
use App\Enums\DocumentType;
use App\Models\IntakeSubmission;
use App\Models\IntakeUpload;
use App\Support\ManualQuestionSets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class ProcessIntakeUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly IntakeUpload $upload) {}

    public function handle(): void
    {
        $this->upload->update(['ai_extraction_status' => AiExtractionStatus::Processing]);

        try {
            $data = $this->isDocx()
                ? $this->extractFromDocx()
                : $this->extractWithVision();

            $schema = $this->upload->upload_type
                ? ManualQuestionSets::forQuestionnaireType($this->upload->upload_type)
                : null;

            if ($schema !== null) {
                $data = $this->verifyAndCorrect($data, $schema);
            }

            $this->upload->update([
                'ai_extraction_status' => AiExtractionStatus::Completed,
                'ai_extracted_data' => $data,
                'processed_at' => now(),
            ]);

            $this->siblingUploads()->each(fn (IntakeUpload $sibling) => $sibling->update([
                'ai_extraction_status' => AiExtractionStatus::Completed,
                'ai_extracted_data' => $data,
                'processed_at' => now(),
            ]));
        } catch (\Throwable $e) {
            Log::error('IntakeUpload AI extraction failed', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage(),
            ]);

            $this->upload->update([
                'ai_extraction_status' => AiExtractionStatus::Failed,
                'ai_error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            $this->siblingUploads()->each(fn (IntakeUpload $sibling) => $sibling->update([
                'ai_extraction_status' => AiExtractionStatus::Failed,
                'ai_error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]));
        }

        $this->dispatchGenerationForAffectedSubmissions();
    }

    /**
     * Other IntakeUpload rows pointing at the same stored file (created when one upload
     * satisfies several orders from the same batch checkout) — no reason to re-run AI
     * extraction on an identical document once the primary upload has a result.
     */
    private function siblingUploads(): Collection
    {
        return IntakeUpload::where('storage_path', $this->upload->storage_path)
            ->where('id', '!=', $this->upload->id)
            ->whereIn('ai_extraction_status', [AiExtractionStatus::Pending, AiExtractionStatus::Processing])
            ->get();
    }

    private function dispatchGenerationForAffectedSubmissions(): void
    {
        IntakeUpload::where('storage_path', $this->upload->storage_path)
            ->pluck('intake_submission_id')
            ->unique()
            ->each(function (int $submissionId) {
                $submission = IntakeSubmission::find($submissionId);

                if ($submission?->allUploadsProcessed()) {
                    $this->dispatchDocumentGeneration($submission);
                }
            });
    }

    private function isDocx(): bool
    {
        $filename = strtolower($this->upload->original_filename ?? '');
        $mime = $this->upload->mime_type ?? '';

        return str_ends_with($filename, '.docx')
            || $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    private function extractWithVision(): array
    {
        $fileContent = Storage::disk('local')->get($this->upload->storage_path);
        $base64 = base64_encode((string) $fileContent);
        $mediaType = $this->upload->mime_type ?? 'application/pdf';

        $response = $this->openai()->post($this->openaiUrl(), [
            'model' => config('services.openai.model'),
            'response_format' => ['type' => 'json_object'],
            'messages' => [[
                'role' => 'user',
                'content' => [
                    $this->buildFilePart($base64, $mediaType),
                    ['type' => 'text', 'text' => $this->buildPrompt()],
                ],
            ]],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API error: '.$response->status());
        }

        return $this->parseJson($response->json('choices.0.message.content', ''));
    }

    private function extractFromDocx(): array
    {
        $absolutePath = Storage::disk('local')->path($this->upload->storage_path);
        $phpWord = IOFactory::load($absolutePath);
        $text = $this->extractText($phpWord);

        $response = $this->openai()->post($this->openaiUrl(), [
            'model' => config('services.openai.model'),
            'response_format' => ['type' => 'json_object'],
            'messages' => [[
                'role' => 'user',
                'content' => $this->buildPrompt()."\n\nDocument text:\n".$text,
            ]],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API error: '.$response->status());
        }

        return $this->parseJson($response->json('choices.0.message.content', ''));
    }

    /**
     * @return array{type: string, file?: array{filename: string, file_data: string}, image_url?: array{url: string}}
     */
    private function buildFilePart(string $base64, string $mediaType): array
    {
        $dataUrl = "data:{$mediaType};base64,{$base64}";

        if ($mediaType === 'application/pdf') {
            return [
                'type' => 'file',
                'file' => [
                    'filename' => $this->upload->original_filename ?? 'document.pdf',
                    'file_data' => $dataUrl,
                ],
            ];
        }

        return ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]];
    }

    private function openai(): PendingRequest
    {
        return Http::withToken(config('services.openai.key'));
    }

    private function openaiUrl(): string
    {
        return 'https://api.openai.com/v1/chat/completions';
    }

    private function extractText(PhpWord $phpWord): string
    {
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            $text .= $this->extractTextFromElements($section->getElements());
        }

        return $text;
    }

    /** @param array<int, mixed> $elements */
    private function extractTextFromElements(array $elements): string
    {
        $text = '';
        foreach ($elements as $element) {
            if ($element instanceof Table) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $text .= $this->extractTextFromElements($cell->getElements());
                    }
                }
            } elseif (method_exists($element, 'getText')) {
                $text .= $element->getText().' ';
            } elseif (method_exists($element, 'getElements')) {
                $text .= $this->extractTextFromElements($element->getElements());
            }
        }

        return $text;
    }

    private function buildPrompt(): string
    {
        $schema = $this->upload->upload_type
            ? ManualQuestionSets::forQuestionnaireType($this->upload->upload_type)
            : null;

        if ($schema !== null) {
            return $this->buildStructuredPrompt($schema);
        }

        $type = $this->upload->upload_type?->promptLabel() ?? 'practice intake';

        return <<<PROMPT
Extract all compliance-relevant information from this {$type} document and return it as a JSON object.
Include fields such as: practice_name, address, npi_number, specialty, provider_count,
services_offered, safety_programs, hazardous_materials, training_requirements, and any other
compliance-relevant data found in the document.
Return only valid JSON with no additional text or markdown formatting.
PROMPT;
    }

    /** @param array{prefix: string, count: int, extra_fields: array<string, string>} $schema */
    private function buildStructuredPrompt(array $schema): string
    {
        $label = $this->upload->upload_type->promptLabel();
        $codePrefix = strtoupper($schema['prefix']);
        $count = $schema['count'];

        $extraFieldLines = '';
        foreach ($schema['extra_fields'] as $key => $description) {
            $extraFieldLines .= "- \"{$key}\": {$description}\n";
        }

        return <<<PROMPT
This document is a {$label}. It contains {$count} numbered questions, coded {$codePrefix}-01 through {$codePrefix}-{$count}, each followed by the practice's typed-in answer, plus a practice information section.

Extract exactly the following as a JSON object with exactly these keys and no others:
- One key per question, named "{$schema['prefix']}_NN_answer" (e.g. "{$schema['prefix']}_01_answer") for each of the {$count} questions, containing the practice's answer text for that question. If a question was left unanswered (still shows placeholder instructional text such as "Click or tap here to enter text."), use an empty string for that key.
{$extraFieldLines}
Return only valid JSON with no additional text or markdown formatting.
PROMPT;
    }

    /** @param array{prefix: string, count: int, extra_fields: array<string, string>} $schema */
    private function verifyAndCorrect(array $data, array $schema): array
    {
        $response = $this->openai()->post($this->openaiUrl(), [
            'model' => config('services.openai.model'),
            'response_format' => ['type' => 'json_object'],
            'messages' => [[
                'role' => 'user',
                'content' => $this->buildVerificationPrompt($data),
            ]],
        ]);

        if ($response->failed()) {
            Log::warning('AI verification pass failed, using unverified extraction', [
                'upload_id' => $this->upload->id,
            ]);

            return $data;
        }

        $corrected = $this->parseJson($response->json('choices.0.message.content', ''));

        // Guard against a malformed verification response silently wiping out a good extraction.
        return $corrected !== [] ? $corrected : $data;
    }

    /** @param array<string, mixed> $data */
    private function buildVerificationPrompt(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are reviewing extracted answers from a compliance questionnaire. For each answer below, without changing its substantive meaning:
- If it is empty, or is unanswered placeholder text such as "Click or tap here to enter text.", replace it with exactly "[No response provided]".
- Otherwise, lightly correct spelling and grammar and remove stray extraction artifacts, but do not add, remove, or alter any substantive information.

Return the corrected answers as a JSON object with exactly the same keys as given below, and no additional text or markdown formatting.

{$json}
PROMPT;
    }

    private function parseJson(string $content): array
    {
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content) ?? $content;
        $content = preg_replace('/```\s*$/m', '', $content) ?? $content;
        $decoded = json_decode(trim($content), true);

        return is_array($decoded) ? $decoded : ['raw_text' => trim($content)];
    }

    /**
     * Generation is driven entirely by which questionnaires the client actually
     * uploaded — not by package tier. Each uploaded questionnaire type triggers
     * generation of its one matching manual; an upload with no matching manual
     * (e.g. a retired/generic intake type) triggers nothing.
     */
    private function dispatchDocumentGeneration(IntakeSubmission $submission): void
    {
        $order = $submission->order()->with('user.practice.oshaLocations')->first();
        $oshaLocations = $order->user->practice?->oshaLocations ?? collect();

        $uploadedQuestionnaireTypes = $submission->intakeUploads->map(fn ($u) => $u->upload_type)->unique();

        foreach ($uploadedQuestionnaireTypes as $uploadType) {
            $docType = DocumentType::forQuestionnaireType($uploadType);

            if ($docType === null) {
                continue;
            }

            if ($docType->isPerLocation()) {
                foreach ($oshaLocations as $location) {
                    GenerateComplianceDocument::dispatch($order, $docType, $location);
                }

                if ($oshaLocations->isEmpty()) {
                    // Generate without a location if none configured
                    GenerateComplianceDocument::dispatch($order, $docType);
                }
            } else {
                GenerateComplianceDocument::dispatch($order, $docType);
            }
        }
    }
}
