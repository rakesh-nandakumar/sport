<?php

namespace App\Enums;

enum Role: int
{
    case SuperAdministrator = 1;
    case Vendor = 2;
    case Moderator = 3;
    case MarketingManager = 4;
    case Customer = 5;

    public static function fromKey(string $key): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->name, $key) === 0) {
                return $case;
            }
        }

        return null;
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdministrator => 'Super Administrator',
            self::Vendor => 'Vendor',
            self::Moderator => 'Moderator',
            self::MarketingManager => 'Marketing Manager',
            self::Customer => 'Customer',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::SuperAdministrator, self::Moderator, self::MarketingManager], true);
    }
}
