<?php

namespace App\Enums;

/**
 * Lifecycle status of a sale transaction.
 */
enum SaleStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /** Human-readable label for display. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Value-to-label map suitable for a `<select>` input.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
