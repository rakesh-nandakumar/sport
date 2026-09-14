<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\Service;
use App\Models\Venue;
use App\Services\AvailabilityService;
use App\Services\PersonalizationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function __construct(protected PersonalizationService $personalization, protected AvailabilityService $availability) {}

    public function index(Request $request): View
    {
        $location = $this->personalization->location($request);
        $sort = $request->string('sort')->toString() ?: ($location ? 'nearest' : 'featured');

        $query = Venue::live()
            ->with(['services.activityType', 'services.options'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->search($request->string('q')->toString())
            ->forActivity($request->string('activity')->toString())
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->string('city')));

        if ($sort === 'nearest' && $location) {
            // Distance is computed in PHP (portable across SQLite/MySQL) then paginated by hand.
            $all = $this->personalization->sortByDistance($query->get(), $location);
            $page = LengthAwarePaginator::resolveCurrentPage();
            $venues = (new LengthAwarePaginator($all->forPage($page, 9)->values(), $all->count(), 9, $page, ['path' => $request->url()]))->withQueryString();
        } else {
            $venues = match ($sort) {
                'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
                'name' => $query->orderBy('name'),
                default => $query->orderByDesc('is_featured')->latest(),
            };
            $venues = $venues->paginate(9)->withQueryString();
            $this->personalization->sortByDistance(collect($venues->items()), $location); // only annotates distance
            $sort = $sort === 'nearest' ? 'featured' : $sort;
        }

        return view('venues.index', [
            'venues' => $venues,
            'activityTypes' => ActivityType::orderBy('sort_order')->get(),
            'cities' => Venue::live()->distinct()->orderBy('city')->pluck('city'),
            'filters' => $request->only(['q', 'activity', 'city']),
            'sort' => $sort,
            'location' => $location,
        ]);
    }

    public function show(Request $request, Venue $venue): View
    {
        $user = auth()->user();
        abort_unless($venue->isLive() || $user?->id === $venue->user_id || $user?->isStaff() || $user?->isAdmin(), 404);

        $venue->load([
            'hours',
            'services' => fn ($q) => $q->active()->with(['activityType', 'options', 'games', 'rates']),
            'reviews.user',
        ]);

        $this->personalization->recordView($request, $venue);
        $location = $this->personalization->location($request);
        $venue->setAttribute('distance_km', $location ? $venue->distanceFrom($location['lat'], $location['lng']) : null);

        // "Today at a glance": every service's start-time grid for today (default option), so a
        // customer can see availability and prices straight from the venue page.
        $today = today();
        $todaySlots = $venue->services->mapWithKeys(function (Service $service) use ($today, $venue) {
            $service->setRelation('venue', $venue); // hours are already loaded on $venue
            $option = $service->defaultOption();

            return [$service->id => $option ? $this->availability->slotsForDay($service, $option, $today) : collect()];
        });

        return view('venues.show', [
            'venue' => $venue,
            'servicesByActivity' => $venue->services->groupBy(fn ($s) => $s->activityType->name),
            'todaySlots' => $todaySlots,
            'location' => $location,
        ]);
    }
}
