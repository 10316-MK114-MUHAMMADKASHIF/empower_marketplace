<?php

namespace App\Support;

use App\Enums\IntakeUploadType;
use App\Models\Questionnaire;
use Illuminate\Support\Collection;

/**
 * Thin wrapper over the questionnaires table, kept intentionally array-shaped (not raw Eloquent
 * models) so client-facing call sites (⚡portal.blade.php) that already destructure
 * {file, title, description, tiers, uploadType, required} don't need to change now that the
 * catalog is admin-managed instead of a hardcoded array.
 */
class Questionnaires
{
    private const DIRECTORY = 'Manuals/Questionnaires';

    /** @return Collection<int, array{file: string, title: string, description: string, tiers: ?array<int, string>, uploadType: IntakeUploadType, required: bool, isVisible: bool}> */
    private static function catalog(): Collection
    {
        return Questionnaire::all()->map(fn (Questionnaire $q) => [
            'file' => basename($q->questionnaire_file_path),
            'title' => $q->title,
            'description' => $q->description,
            'tiers' => $q->tiers,
            'uploadType' => $q->upload_type,
            'required' => $q->is_required,
            'isVisible' => $q->is_visible,
        ]);
    }

    /**
     * @param  array<int, string>  $tierValues
     * @return Collection<int, array{file: string, title: string, description: string, tiers: ?array<int, string>, uploadType: IntakeUploadType, required: bool}>
     */
    public static function forTiers(array $tierValues): Collection
    {
        return self::catalog()
            ->filter(fn (array $q) => $q['tiers'] === null || array_intersect($q['tiers'], $tierValues))
            ->filter(fn (array $q) => $q['isVisible'])
            ->map(fn (array $q) => array_diff_key($q, ['isVisible' => true]))
            ->values();
    }

    /**
     * The only way admin-side code should change a questionnaire's visibility. Re-syncs the
     * required overrides afterward in both directions: hiding the sole required questionnaire
     * among the visible set promotes another still-visible one, and showing a previously-required
     * questionnaire back clears any such promotion — so exactly one required questionnaire is ever
     * in effect, never zero and never more than one left stale.
     *
     * Returns the catalog entry that got newly promoted, or null if nothing needed to change.
     *
     * @return array{file: string, title: string, description: string, tiers: ?array<int, string>, uploadType: IntakeUploadType, required: bool}|null
     */
    public static function setVisibility(IntakeUploadType $uploadType, bool $isVisible): ?array
    {
        Questionnaire::where('upload_type', $uploadType)->update(['is_visible' => $isVisible]);

        return self::syncRequiredOverride();
    }

    /**
     * Finds whichever single visible questionnaire currently satisfies "at least one required"
     * (promoting one only if none does), then clears `is_required` on every *other* row —
     * including ones not currently visible — so a promotion from an earlier toggle never lingers
     * as a second permanently-"Required" row once it's no longer needed.
     *
     * Returns the catalog entry that got newly promoted, or null if nothing needed to change.
     *
     * @return array{file: string, title: string, description: string, tiers: ?array<int, string>, uploadType: IntakeUploadType, required: bool}|null
     */
    private static function syncRequiredOverride(): ?array
    {
        $visible = self::catalog()->filter(fn (array $q) => $q['isVisible']);

        $satisfiedBy = $visible->first(fn (array $q) => $q['required'] === true);

        $promoted = null;

        if ($satisfiedBy === null && $visible->isNotEmpty()) {
            $satisfiedBy = $promoted = $visible->first();
            Questionnaire::where('upload_type', $promoted['uploadType'])->update(['is_required' => true]);
        }

        Questionnaire::where('is_required', true)
            ->when($satisfiedBy !== null, fn ($query) => $query->where('upload_type', '!=', $satisfiedBy['uploadType']->value))
            ->update(['is_required' => false]);

        return $promoted;
    }

    public static function url(string $filename): string
    {
        return asset(self::DIRECTORY.'/'.rawurlencode($filename));
    }
}
