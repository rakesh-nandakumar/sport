<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        $venues = Venue::approved()
            ->with(['services.activityType', 'services.options'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->search($request->string('q')->toString())
            ->forActivity($request->string('activity')->toString())
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->string('city')))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view('venues.index', [
            'venues' => $venues,
            'activityTypes' => ActivityType::orderBy('sort_order')->get(),
            'cities' => Venue::approved()->distinct()->orderBy('city')->pluck('city'),
            'filters' => $request->only(['q', 'activity', 'city']),
        ]);
    }

    public function show(Venue $venue): View
    {
        abort_unless($venue->is_approved || auth()->user()?->id === $venue->user_id || auth()->user()?->isAdmin(), 404);

        $venue->load([
            'hours',
            'services' => fn ($q) => $q->active()->with(['activityType', 'options', 'games']),
            'reviews.user',
        ]);

        return view('venues.show', [
            'venue' => $venue,
            'servicesByActivity' => $venue->services->groupBy(fn ($s) => $s->activityType->name),
        ]);
    }
}
