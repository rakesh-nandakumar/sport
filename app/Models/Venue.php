<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'slug', 'tagline', 'description', 'address', 'city', 'district', 'postal_code',
        'latitude', 'longitude', 'phone', 'email', 'website', 'cover_image', 'gallery', 'amenities',
        'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number',
        'is_approved', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'allowed_payment_methods' => 'array',
            'amenities' => 'array',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $venue) {
            $base = Str::slug($venue->name);
            $slug = $base;
            $i = 2;
            while (static::where('slug', $slug)->exists()) {
                $slug = "{$base}-{$i}";
                $i++;
            }
            $venue->slug = $venue->slug ?: $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hours(): HasMany
    {
        return $this->hasMany(VenueHour::class)->orderBy('day_of_week');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function bookingOrders(): HasMany
    {
        return $this->hasMany(BookingOrder::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /**
     * What customers may see: approved venues whose vendor has been activated by an admin.
     * Venues owned by admins (seed/demo) and vendors without a profile are shown only while
     * activation is switched off in settings.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->approved()->whereHas('owner', function (Builder $owner) {
            $owner->where('role_id', Role::SuperAdministrator->value)
                ->orWhereHas('vendorProfile', fn (Builder $p) => $p->where('status', VendorStatus::Active->value));

            if (! setting('vendors.require_activation')) {
                $owner->orWhereDoesntHave('vendorProfile');
            }
        });
    }

    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhere('tagline', 'like', "%{$term}%")
                ->orWhereHas('services', fn (Builder $s) => $s->where('name', 'like', "%{$term}%"));
        });
    }

    public function scopeForActivity(Builder $query, ?string $activitySlug): Builder
    {
        if (! $activitySlug) {
            return $query;
        }

        return $query->whereHas('services.activityType', fn (Builder $q) => $q->where('slug', $activitySlug));
    }

    public function coverUrl(): string
    {
        if (! $this->cover_image) {
            return asset('images/venue-placeholder.svg');
        }

        return Str::startsWith($this->cover_image, ['http', '/'])
            ? $this->cover_image
            : asset('storage/'.$this->cover_image);
    }

    public function hoursFor(int $dayOfWeek): ?VenueHour
    {
        return $this->hours->firstWhere('day_of_week', $dayOfWeek);
    }

    public function averageRating(): float
    {
        return round((float) $this->reviews()->avg('rating'), 1);
    }

    public function priceFrom(): ?float
    {
        return ServiceOption::whereIn('service_id', $this->services()->select('id'))->min('price_per_slot');
    }

    public function hasBankDetails(): bool
    {
        return (bool) ($this->bank_name && $this->bank_account_number);
    }

    public function allowsPaymentMethod(PaymentMethod $method): bool
    {
        return $method->isAvailable()
            && ($this->allowed_payment_methods === null || in_array($method->value, $this->allowed_payment_methods, true));
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /** Great-circle distance in km from a point, or null when the venue has no pin. */
    public function distanceFrom(?float $lat, ?float $lng): ?float
    {
        if ($lat === null || $lng === null || ! $this->hasCoordinates()) {
            return null;
        }

        $earth = 6371;
        $dLat = deg2rad($this->latitude - $lat);
        $dLng = deg2rad($this->longitude - $lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad($this->latitude)) * sin($dLng / 2) ** 2;

        return round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }

    public function isLive(): bool
    {
        return $this->is_approved && $this->owner->vendorStatus() === VendorStatus::Active;
    }

    public function mapsUrl(): string
    {
        if ($this->hasCoordinates()) {
            return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
        }

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($this->name.' '.$this->address.' '.$this->city);
    }
}
