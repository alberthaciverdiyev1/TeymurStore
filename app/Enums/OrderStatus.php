<?php

namespace App\Enums;

enum OrderStatus: int
{
    case PLACED = 0;
    case PROCESSING = 1;
    case SHIPPED = 2;
    case DELIVERED = 3;

    public function label(): string
    {
        return match ($this) {
            self::PLACED => __('Order Placed'),
            self::PROCESSING => __('Processing'),
            self::SHIPPED => __('Shipped'),
            self::DELIVERED => __('Delivered'),
        };
    }

    public static function fromInt(int $value): self
    {
        return match ($value) {
            0 => self::PLACED,
            1 => self::PROCESSING,
            2 => self::SHIPPED,
            3 => self::DELIVERED,
            default => throw new \InvalidArgumentException("Invalid OrderStatus value: {$value}"),
        };
    }

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'order placed', 'placed' => self::PLACED,
            'processing' => self::PROCESSING,
            'shipped' => self::SHIPPED,
            'delivered' => self::DELIVERED,
            default => throw new \InvalidArgumentException("Invalid OrderStatus string: {$value}"),
        };
    }
}
