<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegisterQuestionnaireTypeTest extends TestCase
{
    private string $uploadTypePath;

    private string $documentTypePath;

    private string $originalUploadTypeSource;

    private string $originalDocumentTypeSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadTypePath = base_path('app/Enums/IntakeUploadType.php');
        $this->documentTypePath = base_path('app/Enums/DocumentType.php');
        $this->originalUploadTypeSource = file_get_contents($this->uploadTypePath);
        $this->originalDocumentTypeSource = file_get_contents($this->documentTypePath);
    }

    protected function tearDown(): void
    {
        // This command edits real source files — always restore them, pass or fail, so a test
        // run never leaves the actual enum files mutated.
        file_put_contents($this->uploadTypePath, $this->originalUploadTypeSource);
        file_put_contents($this->documentTypePath, $this->originalDocumentTypeSource);

        parent::tearDown();
    }

    public function test_registers_a_new_type_pair_with_working_syntax(): void
    {
        $this->artisan('questionnaire:register-type')
            ->expectsQuestion('Questionnaire title (e.g. "Emergency Preparedness")', 'Emergency Preparedness')
            ->expectsQuestion('IntakeUploadType case name', 'EmergencyPreparednessQuestionnaire')
            ->expectsQuestion('IntakeUploadType string value', 'emergency_preparedness_questionnaire')
            ->expectsQuestion('promptLabel() text (used inside AI prompts)', 'emergency preparedness questionnaire')
            ->expectsQuestion('DocumentType case name', 'EmergencyPreparednessManual')
            ->expectsQuestion('DocumentType string value', 'emergency_preparedness_manual')
            ->expectsQuestion('label() text (shown in admin UI/activity logs)', 'Emergency Preparedness Manual')
            ->expectsConfirmation('Write these changes?', 'yes')
            ->assertExitCode(0);

        $uploadTypeSource = file_get_contents($this->uploadTypePath);
        $documentTypeSource = file_get_contents($this->documentTypePath);

        $this->assertStringContainsString("case EmergencyPreparednessQuestionnaire = 'emergency_preparedness_questionnaire';", $uploadTypeSource);
        $this->assertStringContainsString("self::EmergencyPreparednessQuestionnaire => 'emergency preparedness questionnaire',", $uploadTypeSource);
        $this->assertStringContainsString("case EmergencyPreparednessManual = 'emergency_preparedness_manual';", $documentTypeSource);
        $this->assertStringContainsString("self::EmergencyPreparednessManual => 'Emergency Preparedness Manual',", $documentTypeSource);
        $this->assertStringContainsString('self::EmergencyPreparednessManual => IntakeUploadType::EmergencyPreparednessQuestionnaire,', $documentTypeSource);

        // Confirms the edited files are genuinely valid, loadable PHP and work end-to-end through
        // the real enum methods — in a fresh subprocess, since IntakeUploadType/DocumentType are
        // already declared in this test process (loaded by every other test file that references
        // them) and re-declaring an enum via require in the same process is a fatal error, not a
        // meaningful check. Written to a temp file rather than passed via `php -r` — shell-quoting
        // multi-line scripts with escapeshellarg() is unreliable on Windows.
        $autoloadPath = base_path('vendor/autoload.php');
        $verifyScript = <<<PHP
            <?php
            require '{$autoloadPath}';
            \$type = App\Enums\IntakeUploadType::EmergencyPreparednessQuestionnaire;
            \$documentType = App\Enums\DocumentType::forQuestionnaireType(\$type);
            echo \$documentType?->label();
            PHP;
        $verifyPath = sys_get_temp_dir().'/verify_'.uniqid().'.php';
        file_put_contents($verifyPath, $verifyScript);

        try {
            exec('php '.escapeshellarg($verifyPath), $output, $exitCode);
        } finally {
            unlink($verifyPath);
        }

        $this->assertSame(0, $exitCode);
        $this->assertSame('Emergency Preparedness Manual', implode('', $output));
    }

    public function test_declining_the_confirmation_writes_nothing(): void
    {
        $this->artisan('questionnaire:register-type')
            ->expectsQuestion('Questionnaire title (e.g. "Emergency Preparedness")', 'Test Type')
            ->expectsQuestion('IntakeUploadType case name', 'TestTypeQuestionnaire')
            ->expectsQuestion('IntakeUploadType string value', 'test_type_questionnaire')
            ->expectsQuestion('promptLabel() text (used inside AI prompts)', 'test type questionnaire')
            ->expectsQuestion('DocumentType case name', 'TestTypeManual')
            ->expectsQuestion('DocumentType string value', 'test_type_manual')
            ->expectsQuestion('label() text (shown in admin UI/activity logs)', 'Test Type Manual')
            ->expectsConfirmation('Write these changes?', 'no')
            ->assertExitCode(0);

        $this->assertSame($this->originalUploadTypeSource, file_get_contents($this->uploadTypePath));
        $this->assertSame($this->originalDocumentTypeSource, file_get_contents($this->documentTypePath));
    }

    public function test_refuses_a_duplicate_case_name(): void
    {
        // Not chaining ->assertExitCode() here: for a command that exits early via Laravel\Prompts,
        // asserting the exit code interacts oddly with the testing bridge's question-tracking. The
        // behavior that actually matters — an error is shown and the file is left untouched — is
        // what's verified below instead.
        $this->artisan('questionnaire:register-type')
            ->expectsQuestion('Questionnaire title (e.g. "Emergency Preparedness")', 'Compliance Ethics')
            ->expectsQuestion('IntakeUploadType case name', 'ComplianceEthicsQuestionnaire')
            ->expectsQuestion('IntakeUploadType string value', 'compliance_ethics_questionnaire_2')
            ->expectsOutputToContain('IntakeUploadType already has a case named ComplianceEthicsQuestionnaire');

        $this->assertSame($this->originalUploadTypeSource, file_get_contents($this->uploadTypePath));
        $this->assertSame($this->originalDocumentTypeSource, file_get_contents($this->documentTypePath));
    }
}
