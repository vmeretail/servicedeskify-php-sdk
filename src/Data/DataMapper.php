<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;
use Throwable;

final class DataMapper
{
    public static function string(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public static function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    public static function boolean(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }

    public static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, 'UTC');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function array(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }
}
