<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
            self::Customer => 'Customer',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
