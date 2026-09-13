<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\Venue;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $activityTypes = ActivityType::query()
            ->withCount(['services' => fn ($q) => $q->active()->whereHas('venue', fn ($v) => $v->approved())])
            ->orderBy('sort_order')
            ->get();

        $featured = Venue::approved()
            ->with(['services.activityType', 'services.options'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('is_featured')
            ->latest()
            ->take(6)
            ->get();

        $cities = Venue::approved()->distinct()->orderBy('city')->pluck('city');

        return view('home', compact('activityTypes', 'featured', 'cities'));
    }
}
