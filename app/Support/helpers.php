<?php

if (! function_exists('lkr')) {
    /** Format an amount in Sri Lankan rupees, e.g. "Rs 2,500". */
    function lkr(float|int|string|null $amount, bool $cents = false): string
    {
        $amount = (float) $amount;

        return 'Rs '.number_format($amount, $cents || floor($amount) != $amount ? 2 : 0);
    }
}

if (! function_exists('minutes_label')) {
    /** "90" => "1 hr 30 min". */
    function minutes_label(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return trim(($h ? "{$h} hr" : '').($m ? " {$m} min" : '')) ?: '0 min';
    }
}
