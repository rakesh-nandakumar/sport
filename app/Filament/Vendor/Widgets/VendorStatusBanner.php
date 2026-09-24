<?php

namespace App\Filament\Vendor\Widgets;

use App\Enums\VendorStatus;
use Filament\Widgets\Widget;

class VendorStatusBanner extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.vendor.widgets.vendor-status-banner';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user->vendorStatus() !== VendorStatus::Active || $user->venues()->doesntExist();
    }

    public function getVendorStatus(): VendorStatus
    {
        return auth()->user()->vendorStatus();
    }

    public function hasNoVenues(): bool
    {
        return auth()->user()->venues()->doesntExist();
    }

    public function getProfile()
    {
        return auth()->user()->vendorProfile;
    }
}
