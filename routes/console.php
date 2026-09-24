<?php

use Illuminate\Support\Facades\Schedule;

// Unverified bank-transfer holds are released automatically once their window closes.
// Production needs `php artisan schedule:run` in cron (or `schedule:work`) for this to fire.
Schedule::command('bookings:expire-holds')->everyMinute()->withoutOverlapping();
