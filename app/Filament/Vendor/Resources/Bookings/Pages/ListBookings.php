<?php

namespace App\Filament\Vendor\Resources\Bookings\Pages;

use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Services\BookingService;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function mount(): void
    {
        parent::mount();

        // Release any unverified bank-transfer holds whose window has closed, same as the
        // scheduler does every minute — keeps the list accurate even between scheduler ticks.
        app(BookingService::class)->expireStaleHolds();
    }
}
