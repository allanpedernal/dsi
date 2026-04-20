<?php

namespace App\Enums;

/**
 * Application user roles paired with the Spatie permission system.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Customer = 'customer';

    /** Human-readable label for display. */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
            self::Customer => 'Customer',
        };
    }

    /**
     * Raw enum values for validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
