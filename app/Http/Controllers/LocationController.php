<?php

namespace App\Http\Controllers;

use App\Services\PersonalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Stores the visitor's location (browser geolocation or a picked district) for "near you" sorting. */
class LocationController extends Controller
{
    public function __construct(protected PersonalizationService $personalization) {}

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'lat' => ['required_without:district', 'nullable', 'numeric', 'between:-90,90'],
            'lng' => ['required_without:district', 'nullable', 'numeric', 'between:-180,180'],
            'district' => ['nullable', Rule::in(array_keys(config('entrypoint.districts')))],
        ]);

        if (! empty($data['district'])) {
            [$lat, $lng] = config('entrypoint.districts')[$data['district']];
            $loc = $this->personalization->remember($request, $lat, $lng, $data['district']);
        } else {
            $loc = $this->personalization->remember($request, (float) $data['lat'], (float) $data['lng']);
        }

        if ($request->expectsJson()) {
            return response()->json($loc);
        }

        return back()->with('message', 'Showing venues near '.$loc['label'].'.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->personalization->forget($request);

        return back();
    }
}
