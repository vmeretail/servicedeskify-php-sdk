<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Enums;

enum IncidentPriority: int
{
    case P1 = 1;
    case P2 = 2;
    case P3 = 3;
    case P4 = 4;
    case P5 = 5;

    public static function fromApiValue(mixed $value): ?self
    {
        if (is_numeric($value)) {
            return self::tryFrom((int) $value);
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            'critical' => self::P1,
            'high' => self::P2,
            'moderate' => self::P3,
            'low' => self::P4,
            'very low' => self::P5,
            default => null,
        };
    }
}
