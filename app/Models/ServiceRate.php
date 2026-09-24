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
        $from = substr($this->starts_at, 0, 5);
        $to = substr($this->ends_at, 0, 5);

        // "18:00 – 00:00" means until midnight; "22:00 – 02:00" wraps past midnight.
        if ($to === '00:00') {
            $to = '24:00';
        }
        if ($to <= $from) {
            return $time >= $from || $time < $to;
        }

        return $time >= $from && $time < $to;
    }
}
