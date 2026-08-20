<?php

namespace Tests\Feature;

use App\Support\Questionnaires;
use Tests\TestCase;

class QuestionnairesTest extends TestCase
{
    public function test_essential_tier_only_gets_universal_questionnaires(): void
    {
        $files = Questionnaires::forTiers(['essential'])->pluck('file');

        $this->assertTrue($files->contains('Client Practice Information for HIPAA and Compliance Manuals 2026.docx'));
        $this->assertTrue($files->contains('Employee Handbook Questionnaire 20260528 DRAFT.docx'));
        $this->assertTrue($files->contains('OSHA Manual Questionnaire DRAFT 20260706.docx'));
        $this->assertFalse($files->contains('Revenue Cycle and Billing Compliance Manual Questionnaire DRAFT.docx'));
        $this->assertFalse($files->contains('Template Emergency Operations Plan Questionnaire.docx'));
    }

    public function test_complete_tier_gets_every_questionnaire(): void
    {
        $files = Questionnaires::forTiers(['complete'])->pluck('file');

        $this->assertCount(5, $files);
    }

    public function test_url_points_into_the_manuals_directory(): void
    {
        $url = Questionnaires::url('OSHA Manual Questionnaire DRAFT 20260706.docx');

        $this->assertStringContainsString('/Manuals/Questionnaire/', $url);
        $this->assertStringContainsString('OSHA%20Manual%20Questionnaire', $url);
    }
}
