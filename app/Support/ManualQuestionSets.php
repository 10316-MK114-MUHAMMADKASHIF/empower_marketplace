<?php

namespace App\Support;

use App\Enums\DocumentType;
use App\Enums\IntakeUploadType;
use App\Models\Questionnaire;

/**
 * Maps a questionnaire-linked compliance manual to the exact set of merge fields its
 * template expects — one answer per numbered question (e.g. cmp_01_answer..cmp_17_answer),
 * plus a handful of practice-level fields not tied to any single question.
 *
 * The schema itself lives on the Questionnaire row (see QuestionnaireSchemaGenerator, which
 * derives it from the uploaded manual template's own merge fields) rather than being hardcoded
 * here — this class is now just the read path, kept for its existing call sites in
 * ProcessIntakeUpload/GenerateComplianceDocument and its pure mergeFieldNames() helper.
 */
class ManualQuestionSets
{
    /** @return array{prefix: string, count: int, extra_fields: array<string, string>}|null */
    public static function forQuestionnaireType(IntakeUploadType $uploadType): ?array
    {
        return Questionnaire::where('upload_type', $uploadType)->first()?->schema;
    }

    /** @return array{prefix: string, count: int, extra_fields: array<string, string>}|null */
    public static function forDocumentType(DocumentType $type): ?array
    {
        $uploadType = $type->linkedQuestionnaireType();

        return $uploadType ? self::forQuestionnaireType($uploadType) : null;
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
