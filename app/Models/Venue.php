<?php

namespace App\Models;

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
        'user_id', 'name', 'slug', 'tagline', 'description', 'address', 'city', 'district',
        'phone', 'email', 'website', 'cover_image', 'gallery', 'amenities',
        'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number',
        'is_approved', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'amenities' => 'array',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
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
}
