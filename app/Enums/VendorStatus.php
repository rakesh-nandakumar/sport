<?php

namespace App\Enums;

enum VendorStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Rejected => 'Rejected',
        };
    }

    public function bsBadge(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning text-dark',
            self::Active => 'bg-success',
            self::Suspended => 'bg-secondary',
            self::Rejected => 'bg-danger',
        };
    }

    /** Plain-language explanation shown on the vendor's own dashboard. */
    public function vendorMessage(): string
    {
        return match ($this) {
            self::Pending => 'Our team is reviewing your application. You can set up your venues and services now; they go live as soon as you are approved (usually within 1–2 working days).',
            self::Active => 'Your account is active. Your venues are visible to customers.',
            self::Suspended => 'Your account has been suspended. Your venues are hidden from customers and new bookings are paused. Contact support to resolve this.',
            self::Rejected => 'Unfortunately your application was not approved. See the note from our team below and contact support if you believe this is a mistake.',
        };
    }
}
