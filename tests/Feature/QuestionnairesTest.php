<?php

namespace Tests\Feature;

use App\Enums\IntakeUploadType;
use App\Models\Questionnaire;
use App\Support\Questionnaires;
use Database\Seeders\QuestionnaireSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnairesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuestionnaireSeeder::class);
    }

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

    // ── Visibility ──────────────────────────────────────────────────────────

    public function test_a_hidden_questionnaire_is_excluded_from_every_tier(): void
    {
        Questionnaire::where('upload_type', IntakeUploadType::HipaaPrivacyQuestionnaire)->update(['is_visible' => false]);

        foreach (['essential', 'professional', 'advanced', 'complete'] as $tier) {
            $types = Questionnaires::forTiers([$tier])->pluck('uploadType');

            $this->assertFalse($types->contains(IntakeUploadType::HipaaPrivacyQuestionnaire), "Tier {$tier} should not see the hidden questionnaire.");
            $this->assertCount(3, $types);
        }
    }

    public function test_an_unhidden_questionnaire_reappears(): void
    {
        Questionnaire::where('upload_type', IntakeUploadType::HipaaPrivacyQuestionnaire)->update(['is_visible' => false]);
        Questionnaire::where('upload_type', IntakeUploadType::HipaaPrivacyQuestionnaire)->update(['is_visible' => true]);

        $types = Questionnaires::forTiers(['essential'])->pluck('uploadType');
        $this->assertTrue($types->contains(IntakeUploadType::HipaaPrivacyQuestionnaire));
        $this->assertCount(4, $types);
    }

    public function test_seeded_questionnaires_have_the_expected_default_required_flag(): void
    {
        $required = Questionnaire::where('upload_type', IntakeUploadType::ComplianceEthicsQuestionnaire)->firstOrFail();
        $this->assertTrue($required->is_visible);
        $this->assertTrue($required->is_required);

        $optional = Questionnaire::where('upload_type', IntakeUploadType::HipaaPrivacyQuestionnaire)->firstOrFail();
        $this->assertTrue($optional->is_visible);
        $this->assertFalse($optional->is_required);
    }

    // ── setVisibility() / required reassignment ────────────────────────────

    public function test_hiding_the_required_questionnaire_promotes_the_next_visible_one(): void
    {
        $promoted = Questionnaires::setVisibility(IntakeUploadType::ComplianceEthicsQuestionnaire, false);

        $this->assertNotNull($promoted);
        $this->assertSame(IntakeUploadType::HipaaBusinessAssociateQuestionnaire, $promoted['uploadType']);

        $result = Questionnaires::forTiers(['essential'])->firstWhere(
            fn (array $q) => $q['uploadType'] === IntakeUploadType::HipaaBusinessAssociateQuestionnaire
        );
        $this->assertTrue($result['required']);

        $this->assertDatabaseHas('questionnaires', [
            'upload_type' => IntakeUploadType::HipaaBusinessAssociateQuestionnaire->value,
            'is_required' => true,
        ]);
    }

    public function test_hiding_a_non_required_questionnaire_promotes_nothing(): void
    {
        $promoted = Questionnaires::setVisibility(IntakeUploadType::HipaaPrivacyQuestionnaire, false);

        $this->assertNull($promoted);
        $this->assertDatabaseMissing('questionnaires', [
            'upload_type' => IntakeUploadType::HipaaBusinessAssociateQuestionnaire->value,
            'is_required' => true,
        ]);

        $required = Questionnaires::forTiers(['essential'])->firstWhere(fn (array $q) => $q['required']);
        $this->assertSame(IntakeUploadType::ComplianceEthicsQuestionnaire, $required['uploadType']);
    }

    public function test_hiding_every_questionnaire_in_turn_ends_with_nothing_required_and_no_error(): void
    {
        foreach (Questionnaire::all()->pluck('upload_type') as $type) {
            Questionnaires::setVisibility($type, false);
        }

        $this->assertCount(0, Questionnaires::forTiers(['essential']));
    }

    /**
     * `is_required` is now a plain, admin-set column (no more "catalog default" behind it), so
     * reshowing a previously-hidden questionnaire does NOT automatically reclaim required status
     * from whatever was promoted in its absence — required only moves when the currently-required
     * item itself is hidden. An admin who wants it back just checks "Required" on the edit form.
     */
    public function test_reshowing_a_formerly_required_questionnaire_does_not_reclaim_required_status(): void
    {
        Questionnaires::setVisibility(IntakeUploadType::ComplianceEthicsQuestionnaire, false);
        Questionnaires::setVisibility(IntakeUploadType::ComplianceEthicsQuestionnaire, true);

        $original = Questionnaire::where('upload_type', IntakeUploadType::ComplianceEthicsQuestionnaire)->firstOrFail();
        $promoted = Questionnaire::where('upload_type', IntakeUploadType::HipaaBusinessAssociateQuestionnaire)->firstOrFail();

        $this->assertTrue($original->is_visible);
        $this->assertFalse($original->is_required);
        $this->assertTrue($promoted->is_required);

        // Only one questionnaire is ever required at a time, regardless of which one it is.
        $this->assertSame(1, Questionnaire::where('is_required', true)->count());
    }

    /**
     * Regression test for a bug where hiding the promoted questionnaire itself (rather than
     * re-showing the original required one) left its `is_required` override in place forever —
     * so if it ever became visible again later, it showed as a second permanently-"Required" row
     * alongside whatever was promoted after it.
     */
    public function test_hiding_a_promoted_questionnaire_and_later_reshowing_it_does_not_leave_it_stuck_required(): void
    {
        Questionnaires::setVisibility(IntakeUploadType::ComplianceEthicsQuestionnaire, false); // promotes HIPAA BA
        Questionnaires::setVisibility(IntakeUploadType::HipaaBusinessAssociateQuestionnaire, false); // promotes HIPAA Privacy
        Questionnaires::setVisibility(IntakeUploadType::HipaaBusinessAssociateQuestionnaire, true); // shown again, still not required
        Questionnaires::setVisibility(IntakeUploadType::ComplianceEthicsQuestionnaire, true); // shown again, still not required

        $this->assertFalse(Questionnaire::where('upload_type', IntakeUploadType::ComplianceEthicsQuestionnaire)->firstOrFail()->is_required);
        $this->assertFalse(Questionnaire::where('upload_type', IntakeUploadType::HipaaBusinessAssociateQuestionnaire)->firstOrFail()->is_required);
        $this->assertTrue(Questionnaire::where('upload_type', IntakeUploadType::HipaaPrivacyQuestionnaire)->firstOrFail()->is_required);
        $this->assertSame(1, Questionnaire::where('is_required', true)->count());
    }
}
