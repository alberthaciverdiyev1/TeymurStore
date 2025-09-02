<?php

namespace App\Enums;

enum Gender: int
{
    case MALE = 0;
    case FEMALE = 1;
    case KIDS = 2;

    public function label(): string
    {
        return match ($this) {
            self::MALE => __('Male'),
            self::FEMALE => __('Female'),
            self::KIDS => __('Kids'),
        };
    }

    public static function fromInt(int $value): self
    {
        return match ($value) {
            0 => self::MALE,
            1 => self::FEMALE,
            2 => self::KIDS,
            default => throw new \InvalidArgumentException("Invalid Gender value: {$value}"),
        };
    }
}
