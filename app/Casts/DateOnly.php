<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a calendar date as a plain "Y-m-d" string.
 *
 * Laravel's built-in "date" cast persists "Y-m-d H:i:s", which makes lexical
 * comparisons against "Y-m-d" values unreliable on SQLite. Keeping a single
 * canonical format lets the overlap query compare dates as plain strings.
 *
 * @implements CastsAttributes<CarbonImmutable, DateTimeInterface|string>
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $date = $value instanceof DateTimeInterface ? $value : CarbonImmutable::parse($value);

        return $date->format('Y-m-d');
    }
}
