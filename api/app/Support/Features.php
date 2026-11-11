<?php

namespace App\Support;

/** Reads the feature registry in config/features.php. */
class Features
{
    /** @return array<string, array<string, mixed>> keyed by feature, each with a "group" of core or addon */
    public static function all(): array
    {
        $features = [];
        foreach (['core', 'addon'] as $group) {
            foreach (config("features.$group") as $key => $feature) {
                $features[$key] = $feature + ['group' => $group, 'locked' => false];
            }
        }

        return $features;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    public static function isLocked(string $key): bool
    {
        return (bool) (self::all()[$key]['locked'] ?? false);
    }

    /** Features a new client of this type starts with: all core, plus the usual add-ons. */
    public static function defaultsFor(string $type): array
    {
        return array_keys(array_filter(
            self::all(),
            fn (array $f) => $f['group'] === 'core' || in_array($type, $f['default_for'] ?? [], true),
        ));
    }

    /** Monthly LKR estimate from the quote page's prices, for the admin dashboard. */
    public static function monthlyEstimate(array $enabled, int $students): int
    {
        $students = max(1, $students);
        $total = self::platformFee($students) + config('features.pricing.fixed_monthly');

        foreach (self::all() as $key => $feature) {
            if ($feature['group'] === 'addon' && in_array($key, $enabled, true)) {
                $total += max($feature['monthly'] ?? 0, ($feature['per_student'] ?? 0) * $students);
            }
        }

        return $total;
    }

    public static function platformFee(int $students): int
    {
        return match (true) {
            $students <= 100 => 5500,
            $students <= 300 => 9500,
            $students <= 800 => 18000,
            default => 18000 + 20 * ($students - 800),
        };
    }
}
