<?php

namespace Database\Seeders;

use App\Enums\IntakeUploadType;
use App\Models\Questionnaire;
use Illuminate\Database\Seeder;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $questionnaires = [
            [
                'upload_type' => IntakeUploadType::ComplianceEthicsQuestionnaire,
                'title' => 'Compliance & Ethics Questionnaire',
                'description' => 'Practice workflow details used to build your Compliance & Ethics Manual.',
                'tiers' => null,
                'is_required' => true,
                'is_visible' => true,
                'questionnaire_file_path' => 'Manuals/Questionnaires/Compliance and Ethics Practice Workflow Questionnaire.docx',
                'schema' => [
                    'prefix' => 'cmp',
                    'count' => 17,
                    'extra_fields' => [
                        'compliance_officer_name' => "The Compliance Officer's full name",
                        'compliance_officer_email' => "The Compliance Officer's email address",
                        'compliance_officer_phone' => "The Compliance Officer's phone number",
                        'governing_body' => "The identification of the practice's Governing Body (board, managing partners, or owners)",
                        'compliance_committee_members' => 'The names of the Compliance Committee members listed',
                    ],
                ],
            ],
            [
                'upload_type' => IntakeUploadType::HipaaBusinessAssociateQuestionnaire,
                'title' => 'HIPAA Business Associate Questionnaire',
                'description' => 'Practice workflow details used to build your HIPAA Business Associate Manual.',
                'tiers' => null,
                'is_required' => false,
                'is_visible' => true,
                'questionnaire_file_path' => 'Manuals/Questionnaires/HIPAA Business Associate Practice Workflow Questionnaire.docx',
                'schema' => [
                    'prefix' => 'ba',
                    'count' => 46,
                    'extra_fields' => [
                        'ba_officer_name' => "The name of the practice's designated Officer responsible for Business Associate agreements",
                        'ba_officer_email' => "That Officer's email address",
                        'ba_officer_phone' => "That Officer's phone number",
                    ],
                ],
            ],
            [
                'upload_type' => IntakeUploadType::HipaaPrivacyQuestionnaire,
                'title' => 'HIPAA Privacy Questionnaire',
                'description' => 'Practice workflow details used to build your HIPAA Privacy Policy.',
                'tiers' => null,
                'is_required' => false,
                'is_visible' => true,
                'questionnaire_file_path' => 'Manuals/Questionnaires/HIPAA Privacy Practice Workflow Questionnaire.docx',
                'schema' => [
                    'prefix' => 'prv',
                    'count' => 38,
                    'extra_fields' => [
                        'privacy_officer_name' => "The Privacy Officer's full name",
                        'privacy_officer_email' => "The Privacy Officer's email address",
                        'privacy_officer_phone' => "The Privacy Officer's phone number",
                    ],
                ],
            ],
            [
                'upload_type' => IntakeUploadType::HipaaSecurityQuestionnaire,
                'title' => 'HIPAA Security Questionnaire',
                'description' => 'Practice workflow details used to build your HIPAA Security Manual.',
                'tiers' => null,
                'is_required' => false,
                'is_visible' => true,
                'questionnaire_file_path' => 'Manuals/Questionnaires/HIPAA Security Practice Workflow Questionnaire.docx',
                'schema' => [
                    'prefix' => 'sec',
                    'count' => 46,
                    'extra_fields' => [
                        'security_officer_name' => "The Security Officer's full name",
                        'security_officer_email' => "The Security Officer's email address",
                        'security_officer_phone' => "The Security Officer's phone number",
                    ],
                ],
            ],
        ];

        foreach ($questionnaires as $data) {
            Questionnaire::updateOrCreate(['upload_type' => $data['upload_type']], $data);
        }
    }
}
