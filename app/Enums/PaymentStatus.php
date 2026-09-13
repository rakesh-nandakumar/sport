<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PendingVerification = 'pending_verification';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::PendingVerification => 'Awaiting verification',
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Unpaid => 'bg-gray-200 text-gray-700',
            self::PendingVerification => 'bg-amber-100 text-amber-800',
            self::Paid => 'bg-emerald-100 text-emerald-800',
            self::Refunded => 'bg-sky-100 text-sky-800',
        };
    }

    public function bsBadge(): string
    {
        return match ($this) {
            self::Unpaid => 'bg-secondary',
            self::PendingVerification => 'bg-warning text-dark',
            self::Paid => 'bg-success',
            self::Refunded => 'bg-info text-dark',
        };
    }
}
