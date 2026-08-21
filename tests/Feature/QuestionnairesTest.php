<?php

namespace Tests\Feature;

use App\Support\Questionnaires;
use Tests\TestCase;

class QuestionnairesTest extends TestCase
{
    public function test_every_tier_gets_all_four_questionnaires(): void
    {
        foreach (['essential', 'professional', 'advanced', 'complete'] as $tier) {
            $files = Questionnaires::forTiers([$tier])->pluck('file');

            $this->assertCount(4, $files, "Tier {$tier} should see all 4 questionnaires.");
            $this->assertTrue($files->contains('Compliance and Ethics Practice Workflow Questionnaire.docx'));
            $this->assertTrue($files->contains('HIPAA Business Associate Practice Workflow Questionnaire.docx'));
            $this->assertTrue($files->contains('HIPAA Privacy Practice Workflow Questionnaire.docx'));
            $this->assertTrue($files->contains('HIPAA Security Practice Workflow Questionnaire.docx'));
        }
    }

    public function test_url_points_into_the_manuals_directory(): void
    {
        $url = Questionnaires::url('HIPAA Security Practice Workflow Questionnaire.docx');

        $this->assertStringContainsString('/Manuals/Questionnaires/', $url);
        $this->assertStringContainsString('HIPAA%20Security%20Practice%20Workflow%20Questionnaire', $url);
    }
}
