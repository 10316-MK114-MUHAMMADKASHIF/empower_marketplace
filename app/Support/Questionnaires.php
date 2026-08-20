<?php

namespace App\Support;

use App\Enums\IntakeUploadType;
use Illuminate\Support\Collection;

class Questionnaires
{
    private const DIRECTORY = 'Manuals/Questionnaire';

    /** @return array<int, array{file: string, title: string, description: string, tiers: ?array<int, string>, uploadType: IntakeUploadType, required: bool}> */
    private static function catalog(): array
    {
        return [
            [
                'file' => 'Client Practice Information for HIPAA and Compliance Manuals 2026.docx',
                'title' => 'Practice Information Questionnaire',
                'description' => 'Core practice details used across all of your compliance documents.',
                'tiers' => null,
                'uploadType' => IntakeUploadType::PracticeIntake,
                'required' => true,
            ],
            [
                'file' => 'Employee Handbook Questionnaire 20260528 DRAFT.docx',
                'title' => 'Employee Handbook Questionnaire',
                'description' => 'Policies and details used to build your Employee Handbook.',
                'tiers' => null,
                'uploadType' => IntakeUploadType::EmployeeHandbookQuestionnaire,
                'required' => false,
            ],
            [
                'file' => 'OSHA Manual Questionnaire DRAFT 20260706.docx',
                'title' => 'OSHA Manual Questionnaire',
                'description' => 'Workplace safety details used to build your OSHA Safety Plan.',
                'tiers' => null,
                'uploadType' => IntakeUploadType::OshaQuestionnaire,
                'required' => false,
            ],
            [
                'file' => 'Revenue Cycle and Billing Compliance Manual Questionnaire DRAFT.docx',
                'title' => 'Revenue Cycle & Billing Questionnaire',
                'description' => 'Billing workflow details used to build your Revenue Cycle & Billing Compliance Manual.',
                'tiers' => ['complete'],
                'uploadType' => IntakeUploadType::RevenueCycleQuestionnaire,
                'required' => false,
            ],
            [
                'file' => 'Template Emergency Operations Plan Questionnaire.docx',
                'title' => 'Emergency Operations Questionnaire',
                'description' => 'Emergency contact and procedure details used to build your Emergency Operations Plan.',
                'tiers' => ['complete'],
                'uploadType' => IntakeUploadType::EmergencyOperationsQuestionnaire,
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
