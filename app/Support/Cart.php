<?php

namespace App\Support;

use App\Models\Package;
use Illuminate\Support\Collection;

class Cart
{
    private const SESSION_KEY = 'cart';

    /** @return array<int, int> */
    public static function ids(): array
    {
        return array_values(array_unique(session(self::SESSION_KEY, [])));
    }

    public static function add(int $packageId): void
    {
        $ids = self::ids();

        if (! in_array($packageId, $ids, true)) {
            $ids[] = $packageId;
        }

        session([self::SESSION_KEY => $ids]);
    }

    public static function remove(int $packageId): void
    {
        session([self::SESSION_KEY => array_values(array_diff(self::ids(), [$packageId]))]);
    }

    public static function has(int $packageId): bool
    {
        return in_array($packageId, self::ids(), true);
    }

    public static function count(): int
    {
        return count(self::ids());
    }

    /** @return Collection<int, Package> */
    public static function packages(): Collection
    {
        $ids = self::ids();

        if (empty($ids)) {
            return collect();
        }

        $packages = Package::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $packages->get($id))->filter()->values();
    }

    public static function total(): float
    {
        return (float) self::packages()->sum('annual_price');
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
