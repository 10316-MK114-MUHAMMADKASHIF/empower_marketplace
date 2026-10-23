<?php

namespace App\Services;

use App\Models\Setting;

/** Admin-editable options for the client "Talk to a specialist" booking dialog, stored in the
 *  settings table with these defaults used until an admin saves something. */
class SpecialistCallSettings
{
    public const DEFAULT_TOPICS = [
        'A question in the intake',
        'Choosing the right package',
        'Pricing or billing',
        'Something else',
    ];

    public static function enabled(): bool
    {
        return Setting::read('calls_enabled') !== '0';
    }

    /** @return array<int, string> */
    public static function topics(): array
    {
        return self::list('call_topics', self::DEFAULT_TOPICS);
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    private static function list(string $key, array $default): array
    {
        $decoded = json_decode(Setting::read($key) ?? '', true);

        return is_array($decoded) && $decoded !== [] ? array_values($decoded) : $default;
    }
}
