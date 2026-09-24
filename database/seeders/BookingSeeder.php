<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $customers = collect([
            User::updateOrCreate(['email' => 'customer@entrypoint.lk'], ['name' => 'Sahan Jayasuriya', 'phone' => '0779876543', 'password' => 'password', 'role_id' => Role::Customer, 'email_verified_at' => now()]),
        ]);
        $names = ['Nimal Perera', 'Kavindya Fernando', 'Tharushi Silva', 'Mohamed Rizwan', 'Dilshan Madushanka', 'Ishara Wijesinghe', 'Priyanka Rajapaksa', 'Chamath Gunaratne', 'Fathima Nazeer', 'Lasith Ekanayake', 'Hasini Weerasekara', 'Ravindu Dissanayake', 'Aravinth Sivakumar', 'Nethmi Gamage', 'Yasas Amarasinghe'];
        foreach ($names as $i => $name) {
            $customers->push(User::updateOrCreate(['email' => 'customer'.($i + 1).'@example.com'], ['name' => $name, 'phone' => '07'.rand(10000000, 99999999), 'password' => 'password', 'role_id' => Role::Customer, 'email_verified_at' => now()]));
        }

        $pricing = app(PricingService::class);
        $occupied = [];

        foreach (Service::with(['options', 'rates', 'venue.hours', 'activityType', 'games'])->get() as $service) {
            $bookingsToMake = rand(4, 9);
            $attempts = 0;

            while ($bookingsToMake > 0 && $attempts < 40) {
                $attempts++;
                $dayOffset = rand(-21, 12);
                $day = today()->addDays($dayOffset);
                $window = $service->windowFor($day->dayOfWeek);
                if (! $window) {
                    continue;
                }

                [$open, $close] = $window;
                $dayOpen = Carbon::parse($day->toDateString().' '.$open);
                $dayClose = Carbon::parse($day->toDateString().' '.$close);
                if ($dayClose->lte($dayOpen)) {
                    $dayClose->addDay();
                }

                $totalSlots = intdiv($dayOpen->diffInMinutes($dayClose), $service->slot_minutes);
                $slots = rand($service->min_slots, min($service->max_slots ?? 3, $service->min_slots + 2));
                if ($totalSlots < $slots) {
                    continue;
                }
                $index = rand(max(0, intdiv($totalSlots, 4)), $totalSlots - $slots);
                $start = $dayOpen->copy()->addMinutes($index * $service->slot_minutes);
                $option = $service->options->random();

                $key = fn ($i) => "{$service->id}:{$option->id}:{$day->toDateString()}:".($index + $i);
                $free = true;
                for ($i = 0; $i < $slots; $i++) {
                    if (($occupied[$key($i)] ?? 0) >= $option->capacity) {
                        $free = false;
                        break;
                    }
                }
                if (! $free) {
                    continue;
                }
                for ($i = 0; $i < $slots; $i++) {
                    $occupied[$key($i)] = ($occupied[$key($i)] ?? 0) + 1;
                }

                $customer = $customers->random();
                $method = collect([PaymentMethod::PayAtVenue, PaymentMethod::PayAtVenue, PaymentMethod::BankTransfer])->random();
                $isPast = $start->isPast();
                $quote = $pricing->quote($service, $option, $start, $slots);

                if ($isPast) {
                    $status = collect([BookingStatus::Completed, BookingStatus::Completed, BookingStatus::Completed, BookingStatus::NoShow, BookingStatus::Cancelled])->random();
                    $paymentStatus = $status === BookingStatus::Completed ? PaymentStatus::Paid : PaymentStatus::Unpaid;
                } else {
                    $paid = rand(0, 3) === 0;
                    $status = $paid || rand(0, 2) === 0 ? BookingStatus::Confirmed : BookingStatus::Pending;
                    $paymentStatus = $paid ? PaymentStatus::Paid : ($method === PaymentMethod::BankTransfer ? PaymentStatus::PendingVerification : PaymentStatus::Unpaid);
                }
                $vendorConfirmed = $status === BookingStatus::Confirmed && $paymentStatus !== PaymentStatus::Paid;

                $booking = Booking::create([
                    'user_id' => $customer->id,
                    'venue_id' => $service->venue_id,
                    'service_id' => $service->id,
                    'service_option_id' => $option->id,
                    'game_id' => $service->games->isNotEmpty() ? $service->games->random()->id : null,
                    'starts_at' => $start,
                    'ends_at' => $start->copy()->addMinutes($slots * $service->slot_minutes),
                    'slots' => $slots,
                    'players' => $service->max_players ? rand(1, min(6, $service->max_players)) : null,
                    'unit_price' => $quote['unit_price'],
                    'subtotal' => $quote['subtotal'],
                    'discount' => 0,
                    'total' => $quote['total'],
                    'price_breakdown' => $quote['lines'],
                    'status' => $status,
                    'payment_method' => $method,
                    'payment_status' => $paymentStatus,
                    'priority' => Booking::priorityFor($method, $paymentStatus, $vendorConfirmed),
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'vendor_confirmed_at' => $vendorConfirmed ? $start->copy()->subDays(1) : null,
                    // Unverified transfers in the future are still inside their verification window
                    'hold_expires_at' => ! $isPast && $method === PaymentMethod::BankTransfer && $paymentStatus === PaymentStatus::PendingVerification && ! $vendorConfirmed
                        ? now()->addMinutes(rand(8, 240))
                        : null,
                    'cancelled_at' => $status === BookingStatus::Cancelled ? $start->copy()->subHours(6) : null,
                    'cancel_reason' => $status === BookingStatus::Cancelled ? 'Change of plans' : null,
                    'created_at' => $start->copy()->subDays(rand(1, 5)),
                ]);

                if ($method === PaymentMethod::BankTransfer || $paymentStatus === PaymentStatus::Paid) {
                    Payment::create([
                        'booking_id' => $booking->id,
                        'method' => $method,
                        'amount' => $booking->total,
                        'status' => $paymentStatus === PaymentStatus::Paid ? PaymentStatus::Paid : PaymentStatus::PendingVerification,
                        'reference' => $paymentStatus === PaymentStatus::Paid ? 'TXN'.rand(100000, 999999) : null,
                        'verified_by' => $paymentStatus === PaymentStatus::Paid ? $service->venue->user_id : null,
                        'verified_at' => $paymentStatus === PaymentStatus::Paid ? $booking->created_at->copy()->addHours(3) : null,
                    ]);
                }

                $bookingsToMake--;
            }
        }

        $comments = [
            5 => ['Fantastic surface and the floodlights are bright. Booking online was painless.', 'Staff were super helpful and the place was spotless. Will be back every week.', 'Best value in the area — peak pricing is fair and clearly shown.'],
            4 => ['Great facility, parking gets tight on weekends though.', 'Good courts, shuttles a little pricey at the counter.', 'Loved it. Wish they opened a bit earlier on Sundays.'],
            3 => ['Decent, but the changing rooms need a refresh.', 'Okay experience — our slot started 10 minutes late.'],
        ];
        foreach (Venue::all() as $venue) {
            $venue->reviews()->delete();
            foreach ($customers->random(rand(2, 5)) as $customer) {
                $rating = collect([5, 5, 4, 4, 3])->random();
                Review::create(['venue_id' => $venue->id, 'user_id' => $customer->id, 'rating' => $rating, 'comment' => collect($comments[$rating])->random(), 'created_at' => now()->subDays(rand(1, 60))]);
            }
        }
    }
}
