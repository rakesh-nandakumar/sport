<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ExpireBookingHolds extends Command
{
    protected $signature = 'bookings:expire-holds';

    protected $description = 'Release unverified bank-transfer bookings whose verification window has closed';

    public function handle(BookingService $bookings): int
    {
        $count = $bookings->expireStaleHolds();
        $this->info($count ? "Expired {$count} unverified hold(s)." : 'Nothing to expire.');

        return self::SUCCESS;
    }
}
