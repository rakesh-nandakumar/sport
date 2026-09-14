<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Super-admin controlled site settings. Values live in the `settings` table (JSON) and fall back to
 * the defaults below, so the app works before anything has been saved from the admin panel.
 */
class Settings
{
    public const CACHE_KEY = 'entrypoint.settings';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            // Payment methods a customer may pick at checkout. Gateways that are not integrated
            // (see PaymentMethod::isIntegrated) are ignored here even if listed.
            'payments.enabled' => [PaymentMethod::PayAtVenue->value, PaymentMethod::BankTransfer->value],
            // Minutes the vendor has to verify a bank transfer before the slot is released again.
            'payments.bank_transfer_hold_minutes' => 20,
            // Vendors must be activated by an admin before their venues are shown to customers.
            'vendors.require_activation' => true,
            // If true, every new venue also needs an admin approval before it goes live.
            'venues.require_approval' => false,
            // How many days ahead customers can book.
            'bookings.max_days_ahead' => 14,
            // Amenities vendors can tick on a venue.
            'venues.amenities' => ['Parking', 'Changing rooms', 'Showers', 'Cafeteria', 'Air conditioning', 'Floodlights', 'Equipment rental', 'Wi-Fi', 'First aid', 'Spectator seating'],
            // Radius used for "near you" sections.
            'location.nearby_km' => 25,
            'site.support_email' => 'hello@entrypoint.lk',
            'site.support_phone' => '0770000000',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::all();

        return array_key_exists($key, $all) ? $all[$key] : ($default ?? static::defaults()[$key] ?? null);
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(static::CACHE_KEY);
    }

    /** Defaults merged with whatever has been saved. */
    public static function all(): array
    {
        $stored = Cache::rememberForever(static::CACHE_KEY, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return Setting::query()->pluck('value', 'key')->all();
        });

        return array_merge(static::defaults(), $stored);
    }

    public static function flush(): void
    {
        Cache::forget(static::CACHE_KEY);
    }
}
