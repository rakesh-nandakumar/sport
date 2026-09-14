<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\ActivityType;
use App\Models\Booking;
use App\Models\Venue;
use App\Services\PersonalizationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, PersonalizationService $personalization): View
    {
        $location = $personalization->location($request);
        $venueLoads = ['services.activityType', 'services.options'];

        $activityTypes = $personalization->rankActivityTypes(
            ActivityType::query()
                ->withCount(['services' => fn ($q) => $q->active()->whereHas('venue', fn ($v) => $v->live())])
                ->orderBy('sort_order')
                ->get(),
            $request,
            $location,
        );

        $nearby = collect();
        if ($location) {
            $nearby = $personalization
                ->sortByDistance(Venue::live()->withCoordinates()->with($venueLoads)->withAvg('reviews', 'rating')->withCount('reviews')->get(), $location)
                ->filter(fn (Venue $v) => $v->distance_km !== null && $v->distance_km <= (int) setting('location.nearby_km'))
                ->take(6);
        }

        $recentIds = $personalization->recentlyViewedVenueIds($request, 4);
        $recentlyViewed = $recentIds->isEmpty() ? collect() : $personalization->sortByDistance(
            Venue::live()->whereIn('id', $recentIds)->with($venueLoads)->withAvg('reviews', 'rating')->withCount('reviews')->get()
                ->sortBy(fn (Venue $v) => $recentIds->search($v->id))->values(),
            null,
        );

        $bookAgain = collect();
        if ($user = $request->user()) {
            $bookAgain = Booking::where('user_id', $user->id)
                ->whereIn('status', [BookingStatus::Completed->value, BookingStatus::Confirmed->value])
                ->with(['service.activityType', 'service.venue', 'option'])
                ->orderByDesc('starts_at')
                ->get()
                ->filter(fn (Booking $b) => $b->service?->is_active && $b->service->venue->isLive())
                ->unique('service_id')
                ->take(3)
                ->values();
        }

        $featured = $personalization->sortByDistance(
            Venue::live()->with($venueLoads)->withAvg('reviews', 'rating')->withCount('reviews')
                ->orderByDesc('is_featured')->latest()->take($location ? 12 : 6)->get(),
            $location,
        )->take(6);

        $cities = Venue::live()->distinct()->orderBy('city')->pluck('city');

        return view('home', compact('activityTypes', 'featured', 'cities', 'location', 'nearby', 'recentlyViewed', 'bookAgain'));
    }
}
