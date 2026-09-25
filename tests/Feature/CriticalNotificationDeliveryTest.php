<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingNotification;
use App\Notifications\CriticalNotification;
use App\Services\BookingService;
use Carbon\Carbon;
use Database\Seeders\ActivityTypeSeeder;
use Database\Seeders\VenueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CriticalNotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected Service $court;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-14 09:00:00');

        $this->seed([ActivityTypeSeeder::class, VenueSeeder::class]);
        $this->customer = User::factory()->create(['role_id' => Role::Customer]);
        $this->court = Service::where('name', 'Court A (Main)')->firstOrFail();
    }

    public function test_booking_and_payment_events_use_both_database_and_mail_channels(): void
    {
        Notification::fake();
        $bookings = app(BookingService::class);
        $vendor = $this->court->venue->owner;

        $payAtVenue = $bookings->reserve(
            $this->customer,
            $this->court,
            $this->court->defaultOption(),
            Carbon::parse('2026-09-15 10:00:00'),
            1,
            PaymentMethod::PayAtVenue,
            $this->details(),
        );

        $this->assertCriticalSentTo($this->customer, 'placed at');
        $this->assertCriticalSentTo($vendor, 'New Pay at Venue booking');

        $bookings->vendorConfirm($payAtVenue);
        $this->assertCriticalSentTo($this->customer, 'confirmed and locked');

        $bankTransfer = $bookings->reserve(
            $this->customer,
            $this->court,
            $this->court->defaultOption(),
            Carbon::parse('2026-09-15 12:00:00'),
            1,
            PaymentMethod::BankTransfer,
            $this->details(),
        );
        $bookings->attachProof($bankTransfer, 'payment-proofs/test/slip.jpg', 'TXN-100');

        $this->assertCriticalSentTo($vendor, 'Bank transfer slip uploaded');
        $this->assertSystemOnlySentTo($this->customer, 'slip');

        $bookings->markPaid($bankTransfer, $vendor, 'TXN-100');
        $this->assertCriticalSentTo($this->customer, 'Payment verified');
    }

    public function test_cancellations_replacements_expiry_and_pay_at_venue_consequences_are_critical(): void
    {
        Notification::fake();
        $bookings = app(BookingService::class);
        $vendor = $this->court->venue->owner;
        $admin = User::factory()->create(['role_id' => Role::SuperAdministrator]);
        $otherCustomer = User::factory()->create(['role_id' => Role::Customer]);

        $provisional = $bookings->reserve(
            $this->customer,
            $this->court,
            $this->court->defaultOption(),
            Carbon::parse('2026-09-15 10:00:00'),
            1,
            PaymentMethod::PayAtVenue,
            $this->details(),
        );
        $bookings->reserve(
            $otherCustomer,
            $this->court,
            $this->court->defaultOption(),
            Carbon::parse('2026-09-15 10:00:00'),
            1,
            PaymentMethod::BankTransfer,
            $this->details(),
        );

        $this->assertCriticalSentTo($this->customer, 'was replaced');
        $this->assertSame('bumped', $provisional->fresh()->status->value);

        for ($day = 16; $day <= 18; $day++) {
            $booking = $bookings->reserve(
                $this->customer,
                $this->court,
                $this->court->defaultOption(),
                Carbon::parse("2026-09-{$day} 10:00:00"),
                1,
                PaymentMethod::PayAtVenue,
                $this->details(),
            );
            $bookings->cancel($booking, $this->customer, 'Cannot attend');
        }

        $this->assertCriticalSentTo($vendor, 'cancelled by the customer');
        $this->assertCriticalSentTo($this->customer, 'cancellation is confirmed');
        $this->assertCriticalSentTo($this->customer, 'Pay at Venue has been restricted');
        $this->assertSystemOnlySentTo($admin, 'automatically restricted');

        $bankTransfer = $bookings->reserve(
            $otherCustomer,
            $this->court,
            $this->court->defaultOption(),
            Carbon::parse('2026-09-20 10:00:00'),
            1,
            PaymentMethod::BankTransfer,
            $this->details(),
        );
        Carbon::setTestNow('2026-09-14 09:21:00');
        $bookings->expireStaleHolds();

        $this->assertCriticalSentTo($otherCustomer, 'Bank transfer hold expired');
        $this->assertCriticalSentTo($vendor, 'verification deadline missed');
        $this->assertSame('expired', $bankTransfer->fresh()->status->value);
    }

    public function test_non_critical_check_in_and_completion_events_stay_in_the_notification_centre_only(): void
    {
        Notification::fake();
        $bookings = app(BookingService::class);
        $booking = $bookings->reserve(
            $this->customer,
            $this->court,
            $this->court->defaultOption(),
            Carbon::parse('2026-09-15 10:00:00'),
            1,
            PaymentMethod::PayAtVenue,
            $this->details(),
        );

        $bookings->checkIn($booking, $this->court->venue->owner);
        $this->assertSystemOnlySentTo($this->customer, 'checked in');

        $bookings->complete($booking);
        $this->assertSystemOnlySentTo($this->customer, 'marked as completed');
    }

    public function test_critical_notification_builds_a_clear_actionable_email(): void
    {
        $booking = new Booking(['reference' => 'EPT-EMAIL1']);
        $notification = new CriticalNotification(
            'Payment verified — booking confirmed',
            'Your booking is confirmed.',
            'success',
            $booking,
            'https://entrypoint.test/my-bookings/EPT-EMAIL1',
            'View booking',
            ['Your payment was verified.', 'Your booking is confirmed and locked in.'],
        );

        $mail = $notification->toMail($this->customer);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame('Payment verified — booking confirmed', $mail->subject);
        $this->assertSame('Hello '.$this->customer->name.',', $mail->greeting);
        $this->assertSame('View booking', $mail->actionText);
        $this->assertSame('https://entrypoint.test/my-bookings/EPT-EMAIL1', $mail->actionUrl);
        $this->assertContains('Your payment was verified.', $mail->introLines);
    }

    protected function assertCriticalSentTo(User $recipient, string $subject): void
    {
        Notification::assertSentTo(
            $recipient,
            CriticalNotification::class,
            fn (CriticalNotification $notification, array $channels): bool => $channels === ['database', 'mail']
                && str_contains($notification->subject, $subject),
        );
    }

    protected function assertSystemOnlySentTo(User $recipient, string $message): void
    {
        Notification::assertSentTo(
            $recipient,
            BookingNotification::class,
            fn (BookingNotification $notification, array $channels): bool => $channels === ['database']
                && str_contains($notification->message, $message),
        );
    }

    protected function details(): array
    {
        return [
            'customer_name' => 'Test Customer',
            'customer_phone' => '0771234567',
            'nic_front_path' => 'identity-documents/test/front.jpg',
            'nic_back_path' => 'identity-documents/test/back.jpg',
        ];
    }
}
