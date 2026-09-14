<?php

namespace App\Filament\Vendor\Resources\Bookings\Pages;

use App\Filament\Vendor\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BookingResource::confirmAction(),
            BookingResource::markPaidAction(),
            BookingResource::completeAction(),
            BookingResource::noShowAction(),
            BookingResource::cancelAction(),
        ];
    }
}
