<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'indoor_id',
        'activity_id',
        'name',
        'description',
        'photo',
        'capacity',
        'rate',
        'pricing_unit',
        'min_duration_minutes',
        'slot_increment_minutes',
        'custom_fields',
        'opening_hours',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'opening_hours' => 'array',
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** Bookable list fields (e.g. the game library) the customer may pick from at booking time. */
    public function bookableOptions(): array
    {
        $options = [];

        $fields = $this->activity?->fields() ?? [];

        foreach ($fields as $key => $field) {
            if (($field['bookable'] ?? false) !== true || ! isset($field['type']) || $field['type'] !== 'list') {
                continue;
            }

            $options[$key] = [
                'label' => $field['label'] ?? $key,
                'select_label' => $field['select_label'] ?? null,
                'values' => $this->custom_fields[$key] ?? [],
            ];
        }

        return $options;
    }

    /** Hours for that weekday: resource override, else venue default. Null = closed. */
    public function hoursFor(CarbonInterface $date): ?array
    {
        $day = strtolower($date->format('l'));
        $hours = $this->opening_hours[$day] ?? null;

        if (is_array($hours) && ($hours['open'] ?? null) && ($hours['close'] ?? null)) {
            return $hours;
        }

        if ($this->relationLoaded('indoor')) {
            return $this->indoor?->hoursFor($date);
        }

        if ($this->exists && $this->indoor_id !== null) {
            $this->load('indoor');

            return $this->indoor?->hoursFor($date);
        }

        return null;
    }

    public function indoor(): BelongsTo
    {
        return $this->belongsTo(Indoor::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
