<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'qr_token', 'booking_order_id', 'user_id', 'venue_id', 'service_id', 'service_option_id', 'game_id',
        'starts_at', 'ends_at', 'slots', 'players', 'unit_price', 'subtotal', 'discount', 'total',
        'price_breakdown', 'currency', 'status', 'payment_method', 'payment_status', 'priority',
        'customer_name', 'customer_phone', 'notes', 'vendor_confirmed_at', 'hold_expires_at', 'cancelled_at',
        'pay_at_venue_failure_at', 'nic_front_path', 'nic_back_path', 'cancel_reason', 'bumped_by_booking_id', 'checked_in_at', 'checked_in_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'vendor_confirmed_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'pay_at_venue_failure_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'price_breakdown' => 'array',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => BookingStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $booking) {
            $booking->reference = $booking->reference ?: self::generateReference();
            $booking->qr_token = $booking->qr_token ?: self::generateQrToken();
        });
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'EPT-'.strtoupper(Str::random(6));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public static function generateQrToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('qr_token', $token)->exists());

        return $token;
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ServiceOption::class, 'service_option_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class)->orderBy('name');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(BookingOrder::class, 'booking_order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bumpedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'bumped_by_booking_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::active());
    }

    /**
     * Bookings that still occupy their slot right now: active, and not an unverified bank transfer
     * whose verification window has already closed (the sweeper marks those Expired shortly after).
     */
    public function scopeStillHolding(Builder $query): Builder
    {
        return $query->active()->where(function (Builder $w) {
            $w->whereNull('hold_expires_at')
                ->orWhere('hold_expires_at', '>', now())
                ->orWhereNotNull('vendor_confirmed_at')
                ->orWhere('payment_status', PaymentStatus::Paid->value);
        });
    }

    /** Unverified bank-transfer holds whose verification window has closed. */
    public function scopeHoldExpired(Builder $query): Builder
    {
        return $query->active()
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->where('payment_status', '!=', PaymentStatus::Paid->value)
            ->whereNull('vendor_confirmed_at')
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now());
    }

    public function scopeOverlapping(Builder $query, \DateTimeInterface $start, \DateTimeInterface $end, int $bufferMinutes = 0): Builder
    {
        $start = Carbon::instance($start)->subMinutes($bufferMinutes);
        $end = Carbon::instance($end)->addMinutes($bufferMinutes);

        return $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [BookingStatus::Pending, BookingStatus::Confirmed], true);
    }

    public function isUpcoming(): bool
    {
        return $this->isActive() && $this->starts_at->isFuture();
    }

    public function isCancellable(): bool
    {
        return $this->isActive() && $this->starts_at->isFuture();
    }

    public function isLocked(): bool
    {
        return $this->priority >= 3;
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function isManagedBy(User $user): bool
    {
        return $this->venue->user_id === $user->id || $user->isAdmin();
    }

    public function checkinUrl(): string
    {
        return route('filament.vendor.pages.check-in', ['code' => $this->qr_token]);
    }

    public function qrCodeSvg(): string
    {
        return app(QrCodeService::class)->svg($this->checkinUrl());
    }

    /** True while an unverified bank transfer is still counting down. */
    public function isAwaitingVerification(): bool
    {
        return $this->isActive()
            && $this->payment_method === PaymentMethod::BankTransfer
            && $this->payment_status !== PaymentStatus::Paid
            && $this->vendor_confirmed_at === null
            && $this->hold_expires_at !== null;
    }

    public function holdMinutesLeft(): int
    {
        if (! $this->hold_expires_at) {
            return 0;
        }

        return max(0, (int) ceil(now()->diffInSeconds($this->hold_expires_at, false) / 60));
    }

    public function durationLabel(): string
    {
        $minutes = $this->slots * $this->service->slot_minutes;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return trim(($h ? "{$h} hr" : '').($m ? " {$m} min" : ''));
    }

    public function timeRangeLabel(): string
    {
        return $this->starts_at->format('D, d M Y · h:i A').' – '.$this->ends_at->format('h:i A');
    }

    /** Priority used when competing for a slot; verified payments and vendor confirmations lock a booking. */
    public static function priorityFor(PaymentMethod $method, PaymentStatus $paymentStatus, bool $vendorConfirmed = false): int
    {
        if ($vendorConfirmed || $paymentStatus === PaymentStatus::Paid) {
            return 3;
        }

        return $method->priority();
    }
}
