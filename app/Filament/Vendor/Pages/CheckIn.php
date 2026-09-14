<?php

namespace App\Filament\Vendor\Pages;

use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Services\BookingService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class CheckIn extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Scan check-in';

    protected static ?string $title = 'Scan check-in';

    protected static ?string $slug = 'check-in';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.vendor.pages.check-in';

    public ?string $code = null;

    public ?Booking $booking = null;

    public function mount(): void
    {
        if ($code = request()->query('code')) {
            $this->resolve((string) $code);
        }
    }

    /** Manual "find booking" form submit. */
    public function lookup(): void
    {
        if (blank($this->code)) {
            return;
        }

        $this->resolve($this->code);
    }

    /** Called by the camera scanner with the raw decoded QR text. */
    public function scan(string $raw): void
    {
        $this->resolve($raw);
    }

    public function scanAnother(): void
    {
        $this->booking = null;
        $this->code = null;
    }

    public function checkIn(): void
    {
        abort_unless($this->booking, 404);

        try {
            app(BookingService::class)->checkIn($this->booking, auth()->user());

            Notification::make()
                ->success()
                ->title("Checked in {$this->booking->customer_name} for {$this->booking->reference}.")
                ->send();

            $this->booking->refresh()->load('checkedInBy');
        } catch (SlotUnavailableException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    protected function resolve(string $raw): void
    {
        $code = $this->extractCode($raw);

        $booking = $this->scopedBookings()
            ->where(fn (Builder $q) => $q->where('qr_token', $code)->orWhere('reference', strtoupper($code)))
            ->first();

        if (! $booking) {
            Notification::make()->danger()->title('No booking found for that code.')->send();

            return;
        }

        $this->booking = $booking->load(['venue', 'service', 'option', 'game', 'user', 'checkedInBy']);
        $this->code = null;
    }

    /**
     * The camera hands over the raw decoded QR text, which is the booking's check-in URL
     * (…/check-in?code=TOKEN). Manual entry is a bare token or plain reference (EPT-XXXXXX).
     */
    protected function extractCode(string $raw): string
    {
        $raw = trim($raw);

        if (preg_match('/[?&]code=([^&]+)/', $raw, $matches)) {
            return urldecode($matches[1]);
        }

        if (str_contains($raw, '/')) {
            return trim(strrchr($raw, '/'), '/');
        }

        return $raw;
    }

    /**
     * Scoped to the requesting vendor's own venues, so a booking at another vendor's venue is
     * indistinguishable from one that doesn't exist at all.
     */
    protected function scopedBookings(): Builder
    {
        $user = auth()->user();

        return Booking::query()->when(
            ! $user->isAdmin(),
            fn (Builder $q) => $q->whereIn('venue_id', $user->venues()->pluck('id')),
        );
    }
}
