<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\ActivityType;
use App\Models\Booking;
use App\Models\Venue;
use App\Models\VenueView;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

/**
 * Everything that makes the public site feel personal: where the visitor is, what they looked at
 * recently, what they book most. Works for guests (session + cookie) and logged-in users (stored
 * on the user so it follows them across devices).
 */
class PersonalizationService
{
    public const SESSION_KEY = 'ep.location';

    public const COOKIE = 'ep_loc';

    /** @return array{lat: float, lng: float, label: ?string}|null */
    public function location(Request $request): ?array
    {
        if ($loc = $request->session()->get(self::SESSION_KEY)) {
            return $loc;
        }

        $user = $request->user();
        if ($user?->hasLocation()) {
            return $this->remember($request, $user->latitude, $user->longitude, $user->location_label, persist: false);
        }

        $cookie = $request->cookie(self::COOKIE);
        if ($cookie && preg_match('/^(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)(?:,(.*))?$/', $cookie, $m)) {
            return $this->remember($request, (float) $m[1], (float) $m[2], $m[3] ?? null, persist: false);
        }

        return null;
    }

    /** @return array{lat: float, lng: float, label: ?string} */
    public function remember(Request $request, float $lat, float $lng, ?string $label = null, bool $persist = true): array
    {
        $loc = ['lat' => round($lat, 5), 'lng' => round($lng, 5), 'label' => $label ?: $this->nearestDistrict($lat, $lng)];
        $request->session()->put(self::SESSION_KEY, $loc);

        if ($persist) {
            Cookie::queue(self::COOKIE, "{$loc['lat']},{$loc['lng']},{$loc['label']}", 60 * 24 * 30);
            $request->user()?->forceFill([
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
                'location_label' => $loc['label'],
                'location_updated_at' => now(),
            ])->save();
        }

        return $loc;
    }

    public function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
        Cookie::queue(Cookie::forget(self::COOKIE));
        $request->user()?->forceFill(['latitude' => null, 'longitude' => null, 'location_label' => null, 'location_updated_at' => null])->save();
    }

    public function nearestDistrict(float $lat, float $lng): string
    {
        $best = null;
        $bestDistance = PHP_FLOAT_MAX;
        foreach (config('entrypoint.districts') as $name => [$dLat, $dLng]) {
            $d = ($dLat - $lat) ** 2 + ($dLng - $lng) ** 2;
            if ($d < $bestDistance) {
                $bestDistance = $d;
                $best = $name;
            }
        }

        return $best ?? 'Sri Lanka';
    }

    public const RECENT_KEY = 'ep.recent';

    public function recordView(Request $request, Venue $venue): void
    {
        // Session copy for guests (survives without a DB lookup); DB row for members and analytics.
        $recent = collect($request->session()->get(self::RECENT_KEY, []))->prepend($venue->id)->unique()->take(20)->values()->all();
        $request->session()->put(self::RECENT_KEY, $recent);

        VenueView::create([
            'venue_id' => $venue->id,
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'viewed_at' => now(),
        ]);
    }

    /** Distinct venue ids most recently viewed by this visitor, newest first. */
    public function recentlyViewedVenueIds(Request $request, int $limit = 6): Collection
    {
        $ids = collect($request->session()->get(self::RECENT_KEY, []));

        if ($user = $request->user()) {
            $ids = $ids->concat(
                VenueView::where('user_id', $user->id)->orderByDesc('viewed_at')->limit(200)->pluck('venue_id')
            );
        }

        return $ids->unique()->take($limit)->values();
    }

    /**
     * How much this visitor cares about each activity type: bookings weigh most, recent views less.
     *
     * @return array<int, float> activity_type_id => score
     */
    public function activityAffinity(Request $request): array
    {
        $scores = [];

        $viewedVenueIds = $this->recentlyViewedVenueIds($request, 30);
        if ($viewedVenueIds->isNotEmpty()) {
            $typeIds = Venue::whereIn('id', $viewedVenueIds)->with('services:id,venue_id,activity_type_id')->get()
                ->flatMap(fn (Venue $v) => $v->services->pluck('activity_type_id'));
            foreach ($typeIds as $id) {
                $scores[$id] = ($scores[$id] ?? 0) + 1;
            }
        }

        if ($user = $request->user()) {
            $booked = Booking::where('user_id', $user->id)
                ->whereNotIn('status', [BookingStatus::Cancelled->value, BookingStatus::Bumped->value, BookingStatus::Expired->value])
                ->join('services', 'services.id', '=', 'bookings.service_id')
                ->pluck('services.activity_type_id');
            foreach ($booked as $id) {
                $scores[$id] = ($scores[$id] ?? 0) + 3;
            }
        }

        return $scores;
    }

    /**
     * Attach `distance_km` to each venue and return them nearest-first. Venues without a pin sort last.
     */
    public function sortByDistance(Collection $venues, ?array $location): Collection
    {
        if (! $location) {
            return $venues;
        }

        return $venues
            ->each(fn (Venue $v) => $v->setAttribute('distance_km', $v->distanceFrom($location['lat'], $location['lng'])))
            ->sortBy(fn (Venue $v) => $v->distance_km ?? PHP_FLOAT_MAX)
            ->values();
    }

    /**
     * Order activity types for the "browse" grid: things this visitor books/views first, then the
     * activities with a venue closest to them, then the admin's sort order.
     */
    public function rankActivityTypes(Collection $types, Request $request, ?array $location): Collection
    {
        $affinity = $this->activityAffinity($request);
        $nearest = [];

        if ($location) {
            $venues = Venue::live()->withCoordinates()->with('services:id,venue_id,activity_type_id')->get();
            foreach ($venues as $venue) {
                $d = $venue->distanceFrom($location['lat'], $location['lng']);
                foreach ($venue->services->pluck('activity_type_id')->unique() as $typeId) {
                    $nearest[$typeId] = min($nearest[$typeId] ?? PHP_FLOAT_MAX, $d);
                }
            }
        }

        return $types
            ->each(function (ActivityType $t) use ($affinity, $nearest) {
                $t->setAttribute('affinity', $affinity[$t->id] ?? 0);
                $t->setAttribute('nearest_km', isset($nearest[$t->id]) && $nearest[$t->id] !== PHP_FLOAT_MAX ? $nearest[$t->id] : null);
            })
            ->sortBy([
                fn ($a, $b) => $b->affinity <=> $a->affinity,
                fn ($a, $b) => ($a->nearest_km ?? PHP_FLOAT_MAX) <=> ($b->nearest_km ?? PHP_FLOAT_MAX),
                fn ($a, $b) => $a->sort_order <=> $b->sort_order,
            ])
            ->values();
    }
}
