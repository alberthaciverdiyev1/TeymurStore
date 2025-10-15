<?php

namespace App\Enums;

enum OrderStatus: int
{
    case PLACED = 0;
    case PROCESSING = 1;
    case DELIVERED = 2;
    case RETURNED= 3;
    case WAITING_PAYMENT = 4;
    case FAILED = 5;

    public function label(): string
    {
        return match ($this) {
            self::WAITING_PAYMENT => __('Waiting Payment'),
            self::PLACED => __('Order Placed'),
            self::PROCESSING => __('Processing'),
            self::DELIVERED => __('Delivered'),
            self::RETURNED => __('Returned'),
            self::FAILED => __('Failed'),
        };
    }

    public static function fromInt(int $value): self
    {
        return match ($value) {
            0 => self::PLACED,
            1 => self::PROCESSING,
            2 => self::DELIVERED,
            3 => self::RETURNED,
            4 => self::WAITING_PAYMENT,
            5 => self::FAILED,
            default => throw new \InvalidArgumentException("Invalid OrderStatus value: {$value}"),
        };
    }

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'order placed', 'placed' => self::PLACED,
            'processing' => self::PROCESSING,
            'delivered' => self::DELIVERED,
            'returned' => self::RETURNED,
            'waiting payment' => self::WAITING_PAYMENT,
            'failed' => self::FAILED,
            default => throw new \InvalidArgumentException("Invalid OrderStatus string: {$value}"),
        };
    }
}
