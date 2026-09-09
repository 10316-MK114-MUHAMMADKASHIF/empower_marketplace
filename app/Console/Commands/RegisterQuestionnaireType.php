<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

/**
 * Scaffolds the 3 code arms a genuinely new questionnaire type needs before it can be registered
 * through the admin Questionnaires CRUD (⚡questionnaire-form.blade.php): an IntakeUploadType case
 * + its promptLabel() arm, a DocumentType case + its label() arm, and the linkedQuestionnaireType()
 * arm connecting the two. Everything else (schema, files, tier visibility) is already fully
 * admin-driven — see app/Support/QuestionnaireSchemaGenerator.php and plan.md's audit of exactly
 * why these 3 arms are the only remaining code-level requirement.
 *
 * Edits app/Enums/IntakeUploadType.php and app/Enums/DocumentType.php via anchored string
 * replacement against their known, current structure — not a general-purpose PHP code mutator.
 * If either file's structure has changed since this command was written, it aborts with a clear
 * error rather than guessing where to insert.
 */
#[Signature('questionnaire:register-type')]
#[Description('Scaffolds a new IntakeUploadType/DocumentType case pair for a brand-new questionnaire type')]
class RegisterQuestionnaireType extends Command
{
    private const UPLOAD_TYPE_PATH = 'app/Enums/IntakeUploadType.php';

    private const DOCUMENT_TYPE_PATH = 'app/Enums/DocumentType.php';

    // The last case/match-arm line in each file's "active questionnaire" group — new entries are
    // inserted immediately after these, before the ClientDocumentForReview/PolishedClientDocument
    // block that must stay last.
    private const UPLOAD_TYPE_CASE_ANCHOR = "    case HipaaSecurityQuestionnaire = 'hipaa_security_questionnaire';";

    private const UPLOAD_TYPE_LABEL_ANCHOR = "            self::HipaaSecurityQuestionnaire => 'HIPAA security practice workflow questionnaire',";

    private const DOCUMENT_TYPE_CASE_ANCHOR = "    case HipaaSecurityManual = 'hipaa_security_manual';";

    private const DOCUMENT_TYPE_LABEL_ANCHOR = "            self::HipaaSecurityManual => 'HIPAA Security Manual',";

    private const DOCUMENT_TYPE_LINK_ANCHOR = '            self::HipaaSecurityManual => IntakeUploadType::HipaaSecurityQuestionnaire,';

    public function handle(): int
    {
        $uploadTypePath = base_path(self::UPLOAD_TYPE_PATH);
        $documentTypePath = base_path(self::DOCUMENT_TYPE_PATH);

        $uploadTypeSource = file_get_contents($uploadTypePath);
        $documentTypeSource = file_get_contents($documentTypePath);

        $title = text('Questionnaire title (e.g. "Emergency Preparedness")', required: true);

        $uploadTypeCase = text('IntakeUploadType case name', default: Str::studly($title).'Questionnaire', required: true);
        $uploadTypeValue = text('IntakeUploadType string value', default: Str::snake($title).'_questionnaire', required: true);

        if (str_contains($uploadTypeSource, "case {$uploadTypeCase} ") || str_contains($uploadTypeSource, "'{$uploadTypeValue}'")) {
            $this->components->error("IntakeUploadType already has a case named {$uploadTypeCase} or value {$uploadTypeValue}.");

            return self::FAILURE;
        }

        $promptLabel = text('promptLabel() text (used inside AI prompts)', default: Str::lower($title).' questionnaire', required: true);

        $documentTypeCase = text('DocumentType case name', default: Str::studly($title).'Manual', required: true);
        $documentTypeValue = text('DocumentType string value', default: Str::snake($title).'_manual', required: true);

        if (str_contains($documentTypeSource, "case {$documentTypeCase} ") || str_contains($documentTypeSource, "'{$documentTypeValue}'")) {
            $this->components->error("DocumentType already has a case named {$documentTypeCase} or value {$documentTypeValue}.");

            return self::FAILURE;
        }

        $label = text('label() text (shown in admin UI/activity logs)', default: $title.' Manual', required: true);

        foreach ([
            [self::UPLOAD_TYPE_PATH, self::UPLOAD_TYPE_CASE_ANCHOR],
            [self::UPLOAD_TYPE_PATH, self::UPLOAD_TYPE_LABEL_ANCHOR],
            [self::DOCUMENT_TYPE_PATH, self::DOCUMENT_TYPE_CASE_ANCHOR],
            [self::DOCUMENT_TYPE_PATH, self::DOCUMENT_TYPE_LABEL_ANCHOR],
            [self::DOCUMENT_TYPE_PATH, self::DOCUMENT_TYPE_LINK_ANCHOR],
        ] as [$path, $anchor]) {
            $source = $path === self::UPLOAD_TYPE_PATH ? $uploadTypeSource : $documentTypeSource;

            if (! str_contains($source, $anchor)) {
                $this->components->error(
                    "{$path} doesn't match the structure this command expects — it may have been edited ".
                    'since this command was written. Add the enum cases and match arms by hand this time.'
                );

                return self::FAILURE;
            }
        }

        $this->components->info('About to add:');
        $this->line("  {$uploadTypePath}");
        $this->line("    case {$uploadTypeCase} = '{$uploadTypeValue}';");
        $this->line("    self::{$uploadTypeCase} => '{$promptLabel}',  (inside promptLabel())");
        $this->line("  {$documentTypePath}");
        $this->line("    case {$documentTypeCase} = '{$documentTypeValue}';");
        $this->line("    self::{$documentTypeCase} => '{$label}',  (inside label())");
        $this->line("    self::{$documentTypeCase} => IntakeUploadType::{$uploadTypeCase},  (inside linkedQuestionnaireType())");

        if (! confirm('Write these changes?', default: true)) {
            $this->components->warn('Aborted — nothing was changed.');

            return self::SUCCESS;
        }

        $uploadTypeSource = str_replace(
            self::UPLOAD_TYPE_CASE_ANCHOR,
            self::UPLOAD_TYPE_CASE_ANCHOR."\n    case {$uploadTypeCase} = '{$uploadTypeValue}';",
            $uploadTypeSource
        );
        $uploadTypeSource = str_replace(
            self::UPLOAD_TYPE_LABEL_ANCHOR,
            self::UPLOAD_TYPE_LABEL_ANCHOR."\n            self::{$uploadTypeCase} => '{$promptLabel}',",
            $uploadTypeSource
        );
        file_put_contents($uploadTypePath, $uploadTypeSource);

        $documentTypeSource = str_replace(
            self::DOCUMENT_TYPE_CASE_ANCHOR,
            self::DOCUMENT_TYPE_CASE_ANCHOR."\n    case {$documentTypeCase} = '{$documentTypeValue}';",
            $documentTypeSource
        );
        $documentTypeSource = str_replace(
            self::DOCUMENT_TYPE_LABEL_ANCHOR,
            self::DOCUMENT_TYPE_LABEL_ANCHOR."\n            self::{$documentTypeCase} => '{$label}',",
            $documentTypeSource
        );
        $documentTypeSource = str_replace(
            self::DOCUMENT_TYPE_LINK_ANCHOR,
            self::DOCUMENT_TYPE_LINK_ANCHOR."\n            self::{$documentTypeCase} => IntakeUploadType::{$uploadTypeCase},",
            $documentTypeSource
        );
        file_put_contents($documentTypePath, $documentTypeSource);

        foreach ([$uploadTypePath, $documentTypePath] as $path) {
            exec('php -l '.escapeshellarg($path), $output, $exitCode);

            if ($exitCode !== 0) {
                $this->components->error("Syntax error after editing {$path} — please check it by hand:\n".implode("\n", $output));

                return self::FAILURE;
            }
        }

        $this->components->info('Done. Both enums updated and syntax-checked.');
        $this->line('Next: run vendor/bin/pint --dirty, then go to /admin/questionnaires to create the');
        $this->line("Questionnaire row for \"{$uploadTypeCase}\" (upload the questionnaire + manual template —");
        $this->line('its schema is detected automatically).');

        return self::SUCCESS;
    }
}
