<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Super-admin site settings: payment methods, hold timers, moderation switches, master lists. */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => Settings::all(),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payments_enabled' => ['nullable', 'array'],
            'payments_enabled.*' => [Rule::enum(PaymentMethod::class)],
            'bank_transfer_hold_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'vendors_require_activation' => ['nullable', 'boolean'],
            'venues_require_approval' => ['nullable', 'boolean'],
            'max_days_ahead' => ['required', 'integer', 'min:1', 'max:90'],
            'nearby_km' => ['required', 'integer', 'min:1', 'max:500'],
            'amenities' => ['required', 'string', 'max:2000'],
            'support_email' => ['required', 'email'],
            'support_phone' => ['required', 'regex:/^0\d{9}$/'],
        ]);

        // Only integrated methods can be switched on; the gateways stay "coming soon" until wired up.
        $enabled = collect($data['payments_enabled'] ?? [])
            ->map(fn ($v) => PaymentMethod::from($v))
            ->filter(fn (PaymentMethod $m) => $m->isIntegrated())
            ->map->value
            ->values()
            ->all();
        if (empty($enabled)) {
            return back()->withErrors(['payments_enabled' => 'At least one payment method must stay enabled or nobody can book.'])->withInput();
        }

        Settings::set('payments.enabled', $enabled);
        Settings::set('payments.bank_transfer_hold_minutes', (int) $data['bank_transfer_hold_minutes']);
        Settings::set('vendors.require_activation', (bool) ($data['vendors_require_activation'] ?? false));
        Settings::set('venues.require_approval', (bool) ($data['venues_require_approval'] ?? false));
        Settings::set('bookings.max_days_ahead', (int) $data['max_days_ahead']);
        Settings::set('location.nearby_km', (int) $data['nearby_km']);
        Settings::set('venues.amenities', collect(preg_split('/\r?\n/', $data['amenities']))->map(fn ($a) => trim($a))->filter()->unique()->values()->all());
        Settings::set('site.support_email', $data['support_email']);
        Settings::set('site.support_phone', $data['support_phone']);

        return back()->with('message', 'Settings saved.');
    }
}
