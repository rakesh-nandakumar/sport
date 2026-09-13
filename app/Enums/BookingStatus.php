<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Bumped = 'bumped';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Bumped => 'Replaced',
            self::NoShow => 'No show',
        };
    }

    /** Tailwind badge classes for the public site. */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Confirmed => 'bg-emerald-100 text-emerald-800',
            self::Completed => 'bg-sky-100 text-sky-800',
            self::Cancelled, self::Bumped => 'bg-rose-100 text-rose-800',
            self::NoShow => 'bg-gray-200 text-gray-700',
        };
    }

    /** Bootstrap badge classes for the dashboards. */
    public function bsBadge(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning text-dark',
            self::Confirmed => 'bg-success',
            self::Completed => 'bg-info text-dark',
            self::Cancelled, self::Bumped => 'bg-danger',
            self::NoShow => 'bg-secondary',
        };
    }

    /** Statuses that still occupy a time slot. */
    public static function active(): array
    {
        return [self::Pending->value, self::Confirmed->value];
    }
}
