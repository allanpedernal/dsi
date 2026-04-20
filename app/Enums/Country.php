<?php

namespace App\Enums;

/**
 * ISO 3166-1 alpha-2 country codes supported for customer addresses.
 */
enum Country: string
{
    case UnitedStates = 'US';
    case Australia = 'AU';
    case NewZealand = 'NZ';

    /** Human-readable label for display. */
    public function label(): string
    {
        return match ($this) {
            self::UnitedStates => 'United States',
            self::Australia => 'Australia',
            self::NewZealand => 'New Zealand',
        };
    }

    /**
     * List of {value, label} pairs suitable for a `<select>` input.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }

    /**
     * Raw enum values for validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
