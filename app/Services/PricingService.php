<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceOption;
use Carbon\CarbonInterface;

class PricingService
{
    /**
     * Price a booking slot-by-slot so peak-hour rates apply only to the slots they cover.
     *
     * @return array{unit_price: float, subtotal: float, discount: float, total: float, lines: list<array{label: string, amount: float}>}
     */
    public function quote(Service $service, ServiceOption $option, CarbonInterface $start, int $slots): array
    {
        $base = (float) $option->price_per_slot;
        $rates = $service->rates;
        $lines = [];
        $regular = 0;
        $surcharges = [];

        for ($i = 0; $i < $slots; $i++) {
            $slotStart = $start->copy()->addMinutes($i * $service->slot_minutes);
            $rate = $rates->first(fn ($r) => $r->appliesTo($slotStart));
            $regular++;

            if ($rate && (float) $rate->multiplier !== 1.0) {
                $extra = round($base * ((float) $rate->multiplier - 1), 2);
                $surcharges[$rate->name] = ($surcharges[$rate->name] ?? 0) + $extra;
            }
        }

        $lines[] = [
            'label' => "{$regular} × {$service->slotLabel()} · {$option->name}",
            'amount' => round($base * $regular, 2),
        ];

        foreach ($surcharges as $name => $amount) {
            $lines[] = ['label' => $name, 'amount' => round($amount, 2)];
        }

        $subtotal = round(array_sum(array_column($lines, 'amount')), 2);

        return [
            'unit_price' => $base,
            'subtotal' => $subtotal,
            'discount' => 0.0,
            'total' => $subtotal,
            'lines' => $lines,
        ];
    }
}
