<?php

namespace App\Enums;

enum Country: string
{
    case UnitedStates = 'US';
    case Australia = 'AU';
    case NewZealand = 'NZ';

    public function label(): string
    {
        return match ($this) {
            self::UnitedStates => 'United States',
            self::Australia => 'Australia',
            self::NewZealand => 'New Zealand',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
