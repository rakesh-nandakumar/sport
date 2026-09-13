<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueHour extends Model
{
    public $timestamps = false;

    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    protected $fillable = ['venue_id', 'day_of_week', 'opens_at', 'closes_at', 'is_closed'];

    protected function casts(): array
    {
        return ['is_closed' => 'boolean'];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function dayName(): string
    {
        return self::DAYS[$this->day_of_week];
    }

    public function label(): string
    {
        if ($this->is_closed || ! $this->opens_at) {
            return 'Closed';
        }

        return substr($this->opens_at, 0, 5).' – '.substr($this->closes_at, 0, 5);
    }
}
