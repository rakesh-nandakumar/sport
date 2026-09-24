<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOption extends Model
{
    protected $fillable = ['service_id', 'name', 'description', 'price_per_slot', 'capacity', 'is_default', 'sort_order'];

    protected function casts(): array
    {
        return [
            'price_per_slot' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
