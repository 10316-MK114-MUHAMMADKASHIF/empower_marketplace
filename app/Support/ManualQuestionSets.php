<?php

namespace App\Support;

use App\Enums\DocumentType;
use App\Enums\IntakeUploadType;

/**
 * Maps a questionnaire-linked compliance manual to the exact set of merge fields its
 * template expects — one answer per numbered question (e.g. cmp_01_answer..cmp_17_answer),
 * plus a handful of practice-level fields not tied to any single question.
 */
class ManualQuestionSets
{
    /** @return array{prefix: string, count: int, extra_fields: array<string, string>}|null */
    public static function forDocumentType(DocumentType $type): ?array
    {
        return match ($type) {
            DocumentType::ComplianceEthicsManual => [
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
            DocumentType::HipaaBusinessAssociateManual => [
                'prefix' => 'ba',
                'count' => 46,
                'extra_fields' => [
                    'ba_officer_name' => "The name of the practice's designated Officer responsible for Business Associate agreements",
                    'ba_officer_email' => "That Officer's email address",
                    'ba_officer_phone' => "That Officer's phone number",
                ],
            ],
            DocumentType::HipaaSecurityManual => [
                'prefix' => 'sec',
                'count' => 46,
                'extra_fields' => [
                    'security_officer_name' => "The Security Officer's full name",
                    'security_officer_email' => "The Security Officer's email address",
                    'security_officer_phone' => "The Security Officer's phone number",
                ],
            ],
            DocumentType::HipaaPrivacyPolicy => [
                'prefix' => 'prv',
                'count' => 38,
                'extra_fields' => [
                    'privacy_officer_name' => "The Privacy Officer's full name",
                    'privacy_officer_email' => "The Privacy Officer's email address",
                    'privacy_officer_phone' => "The Privacy Officer's phone number",
                ],
            ],
            default => null,
        };
    }

    /** @return array{prefix: string, count: int, extra_fields: array<string, string>}|null */
    public static function forQuestionnaireType(IntakeUploadType $uploadType): ?array
    {
        $documentType = DocumentType::forQuestionnaireType($uploadType);

        return $documentType ? self::forDocumentType($documentType) : null;
    }

    /**
     * @param  array{prefix: string, count: int, extra_fields: array<string, string>}  $schema
     * @return array<int, string>
     */
    public static function mergeFieldNames(array $schema): array
    {
        $keys = array_keys($schema['extra_fields']);

        for ($i = 1; $i <= $schema['count']; $i++) {
            $keys[] = sprintf('%s_%02d_answer', $schema['prefix'], $i);
        }

        return $keys;
    }
}
