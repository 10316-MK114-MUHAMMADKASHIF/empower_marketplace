<?php

namespace Tests\Feature;

use App\Support\QuestionnaireSchemaGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class QuestionnaireSchemaGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /** Builds a real .docx containing the given merge-field placeholders, returns its temp path. */
    private function makeTemplate(array $mergeFields): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        foreach ($mergeFields as $field) {
            $section->addText('${'.$field.'}');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'schema_test').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return $tempPath;
    }

    public function test_detects_numbered_questions_and_extra_fields_from_real_merge_fields(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'compliance_officer_name' => "The Compliance Officer's full name",
                ])]]],
            ]),
        ]);

        $path = $this->makeTemplate([
            'practice_name', // always-filled field — must be excluded from the schema
            'cmp_01_answer', 'cmp_02_answer', 'cmp_03_answer',
            'compliance_officer_name',
        ]);

        try {
            $schema = (new QuestionnaireSchemaGenerator)->generate($path, 'cmp');
        } finally {
            unlink($path);
        }

        $this->assertSame('cmp', $schema['prefix']);
        $this->assertSame(3, $schema['count']);
        $this->assertSame(['compliance_officer_name' => "The Compliance Officer's full name"], $schema['extra_fields']);
    }

    public function test_falls_back_to_a_generic_description_when_the_ai_call_fails(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([], 500)]);

        $path = $this->makeTemplate(['sec_01_answer', 'security_officer_email']);

        try {
            $schema = (new QuestionnaireSchemaGenerator)->generate($path, 'sec');
        } finally {
            unlink($path);
        }

        $this->assertSame(1, $schema['count']);
        $this->assertSame('The value for security officer email', $schema['extra_fields']['security_officer_email']);
    }

    public function test_a_template_with_no_extra_fields_skips_the_ai_call_entirely(): void
    {
        Http::fake();

        $path = $this->makeTemplate(['prv_01_answer', 'prv_02_answer']);

        try {
            $schema = (new QuestionnaireSchemaGenerator)->generate($path, 'prv');
        } finally {
            unlink($path);
        }

        $this->assertSame([], $schema['extra_fields']);
        Http::assertNothingSent();
    }
}
