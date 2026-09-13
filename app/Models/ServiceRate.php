<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRate extends Model
{
    protected $fillable = ['service_id', 'name', 'days', 'starts_at', 'ends_at', 'multiplier'];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'multiplier' => 'decimal:2',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function appliesTo(CarbonInterface $slotStart): bool
    {
        if (! in_array($slotStart->dayOfWeek, $this->days ?? [], true)) {
            return false;
        }

        $time = $slotStart->format('H:i');

        return $time >= substr($this->starts_at, 0, 5) && $time < substr($this->ends_at, 0, 5);
    }
}
