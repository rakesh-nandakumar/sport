<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id', 'activity_type_id', 'name', 'description', 'image',
        'slot_minutes', 'game_minutes_per_title', 'min_slots', 'max_slots', 'buffer_minutes', 'lead_time_minutes',
        'max_players', 'opens_at', 'closes_at', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ServiceOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ServiceRate::class);
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class)->orderBy('name');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function defaultOption(): ?ServiceOption
    {
        return $this->options->firstWhere('is_default', true) ?? $this->options->first();
    }

    public function requiresGame(): bool
    {
        return $this->activityType->requires_game && $this->games->isNotEmpty();
    }

    /** A short session has one title; longer sessions can include several titles in a sensible rotation. */
    public function maxGameSelections(int $slots): int
    {
        $minutes = $slots * $this->slot_minutes;
        $minutesPerTitle = max(1, (int) ($this->game_minutes_per_title ?: 45));

        return max(1, intdiv($minutes, $minutesPerTitle));
    }

    public function imageUrl(): string
    {
        if ($this->image) {
            return Str::startsWith($this->image, ['http', '/']) ? $this->image : asset('storage/'.$this->image);
        }

        return $this->venue->coverUrl();
    }

    /** Opening window for a given weekday: service override first, venue hours otherwise. */
    public function windowFor(int $dayOfWeek): ?array
    {
        if ($this->opens_at && $this->closes_at) {
            return [substr($this->opens_at, 0, 5), substr($this->closes_at, 0, 5)];
        }

        $hours = $this->venue->hoursFor($dayOfWeek);
        if (! $hours || $hours->is_closed || ! $hours->opens_at) {
            return null;
        }

        return [substr($hours->opens_at, 0, 5), substr($hours->closes_at, 0, 5)];
    }

    public function priceFrom(): ?float
    {
        return $this->options->min('price_per_slot');
    }

    public function slotLabel(): string
    {
        $m = $this->slot_minutes;

        return $m % 60 === 0 ? ($m / 60).' hr' : $m.' min';
    }
}
