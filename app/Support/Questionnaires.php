<?php

namespace App\Support;

use App\Enums\IntakeUploadType;
use Illuminate\Support\Collection;

class Questionnaires
{
    private const DIRECTORY = 'Manuals/Questionnaires';

    /** @return array<int, array{file: string, title: string, description: string, tiers: ?array<int, string>, uploadType: IntakeUploadType, required: bool}> */
    private static function catalog(): array
    {
        return [
            [
                'file' => 'Compliance and Ethics Practice Workflow Questionnaire.docx',
                'title' => 'Compliance & Ethics Questionnaire',
                'description' => 'Practice workflow details used to build your Compliance & Ethics Manual.',
                'tiers' => null,
                'uploadType' => IntakeUploadType::ComplianceEthicsQuestionnaire,
                'required' => true,
            ],
            [
                'file' => 'HIPAA Business Associate Practice Workflow Questionnaire.docx',
                'title' => 'HIPAA Business Associate Questionnaire',
                'description' => 'Practice workflow details used to build your HIPAA Business Associate Manual.',
                'tiers' => null,
                'uploadType' => IntakeUploadType::HipaaBusinessAssociateQuestionnaire,
                'required' => false,
            ],
            [
                'file' => 'HIPAA Privacy Practice Workflow Questionnaire.docx',
                'title' => 'HIPAA Privacy Questionnaire',
                'description' => 'Practice workflow details used to build your HIPAA Privacy Policy.',
                'tiers' => null,
                'uploadType' => IntakeUploadType::HipaaPrivacyQuestionnaire,
                'required' => false,
            ],
            [
                'file' => 'HIPAA Security Practice Workflow Questionnaire.docx',
                'title' => 'HIPAA Security Questionnaire',
                'description' => 'Practice workflow details used to build your HIPAA Security Manual.',
                'tiers' => null,
                'uploadType' => IntakeUploadType::HipaaSecurityQuestionnaire,
                'required' => false,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $tierValues
     * @return Collection<int, array{file: string, title: string, description: string, tiers: ?array<int, string>, uploadType: IntakeUploadType, required: bool}>
     */
    public static function forTiers(array $tierValues): Collection
    {
        return collect(self::catalog())
            ->filter(fn (array $q) => $q['tiers'] === null || array_intersect($q['tiers'], $tierValues))
            ->values();
    }

    public static function url(string $filename): string
    {
        return asset(self::DIRECTORY.'/'.rawurlencode($filename));
    }
}
