<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Calendar
{
    public static function parseMonth(?string $value): Carbon
    {
        if ($value && preg_match('/^(\d{4})-(\d{2})$/', $value, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
            return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
        }

        return now()->startOfMonth()->startOfDay();
    }

    /**
     * Bazar ertəsindən başlayan həftələr (hər həftə 7 gün).
     *
     * @return array<int, array<int, Carbon>>
     */
    public static function weeks(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];
        $d = $start->copy();
        while ($d <= $end) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $d->copy();
                $d->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /** @return array<int, Carbon> */
    public static function days(Carbon $month): array
    {
        $days = [];
        for ($d = $month->copy()->startOfMonth(); $d->month === $month->month; $d->addDay()) {
            $days[] = $d->copy();
        }

        return $days;
    }

    public static function group(Collection $posts): Collection
    {
        return $posts->groupBy(fn ($p) => $p->publish_date->toDateString());
    }
}
