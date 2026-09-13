<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use App\Models\Game;
use App\Models\Service;
use App\Models\ServiceOption;
use App\Models\ServiceRate;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request, Venue $venue): View
    {
        $this->authorizeOwner($request, $venue);

        return view('vendor.services.index', [
            'venue' => $venue,
            'services' => $venue->services()->with(['activityType', 'options', 'games'])->withCount('bookings')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request, Venue $venue): View
    {
        $this->authorizeOwner($request, $venue);

        return $this->form($venue, new Service(['slot_minutes' => 60, 'min_slots' => 1, 'buffer_minutes' => 0, 'lead_time_minutes' => 60, 'is_active' => true]));
    }

    public function store(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorizeOwner($request, $venue);
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $venue, $data) {
            $service = $venue->services()->create($this->serviceAttributes($request, $data));
            $this->syncChildren($service, $data);
        });

        return redirect()->route('vendor.venues.services.index', $venue)->with('message', 'Service added.');
    }

    public function edit(Request $request, Venue $venue, Service $service): View
    {
        $this->authorizeOwner($request, $venue);
        abort_unless($service->venue_id === $venue->id, 404);
        $service->load(['options', 'rates', 'games']);

        return $this->form($venue, $service);
    }

    public function update(Request $request, Venue $venue, Service $service): RedirectResponse
    {
        $this->authorizeOwner($request, $venue);
        abort_unless($service->venue_id === $venue->id, 404);
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $service, $data) {
            $service->update($this->serviceAttributes($request, $data));
            $this->syncChildren($service, $data);
        });

        return back()->with('message', 'Service updated.');
    }

    public function destroy(Request $request, Venue $venue, Service $service): RedirectResponse
    {
        $this->authorizeOwner($request, $venue);
        abort_unless($service->venue_id === $venue->id, 404);
        $service->delete();

        return redirect()->route('vendor.venues.services.index', $venue)->with('message', 'Service removed.');
    }

    protected function form(Venue $venue, Service $service): View
    {
        return view('vendor.services.form', [
            'venue' => $venue,
            'service' => $service,
            'activityTypes' => ActivityType::orderBy('sort_order')->get(),
            'games' => Game::with('activityType')->orderBy('name')->get()->groupBy('activity_type_id'),
            'selectedGames' => $service->exists ? $service->games->pluck('id')->all() : [],
            'options' => $service->exists ? $service->options : collect([new ServiceOption(['name' => 'Standard', 'capacity' => 1, 'is_default' => true])]),
            'rates' => $service->exists ? $service->rates : collect(),
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'slot_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'min_slots' => ['required', 'integer', 'min:1', 'max:48'],
            'max_slots' => ['nullable', 'integer', 'min:1', 'max:48', 'gte:min_slots'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'lead_time_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:200'],
            'opens_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
            'games' => ['nullable', 'array'],
            'games.*' => ['integer', 'exists:games,id'],
            'options' => ['required', 'array', 'min:1'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.name' => ['required', 'string', 'max:80'],
            'options.*.description' => ['nullable', 'string', 'max:160'],
            'options.*.price_per_slot' => ['required', 'numeric', 'min:0'],
            'options.*.capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'rates' => ['nullable', 'array'],
            'rates.*.id' => ['nullable', 'integer'],
            'rates.*.name' => ['required_with:rates.*.multiplier', 'string', 'max:60'],
            'rates.*.days' => ['nullable', 'array'],
            'rates.*.days.*' => ['integer', 'between:0,6'],
            'rates.*.starts_at' => ['required_with:rates.*.name', 'date_format:H:i'],
            'rates.*.ends_at' => ['required_with:rates.*.name', 'date_format:H:i'],
            'rates.*.multiplier' => ['required_with:rates.*.name', 'numeric', 'min:0.1', 'max:5'],
            'default_option' => ['nullable', 'integer'],
        ]);
    }

    protected function serviceAttributes(Request $request, array $data): array
    {
        $attrs = collect($data)->only([
            'activity_type_id', 'name', 'description', 'slot_minutes', 'min_slots', 'max_slots',
            'buffer_minutes', 'lead_time_minutes', 'max_players', 'opens_at', 'closes_at',
        ])->all();
        $attrs['is_active'] = (bool) ($data['is_active'] ?? false);

        if ($request->hasFile('image')) {
            $attrs['image'] = $request->file('image')->store('services', 'public');
        }

        return $attrs;
    }

    protected function syncChildren(Service $service, array $data): void
    {
        $keepOptions = [];
        foreach (array_values($data['options']) as $i => $row) {
            $option = ! empty($row['id']) ? $service->options()->find($row['id']) : null;
            $option = $option ?: new ServiceOption(['service_id' => $service->id]);
            $option->fill([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'price_per_slot' => $row['price_per_slot'],
                'capacity' => $row['capacity'],
                'sort_order' => $i,
                'is_default' => (int) ($data['default_option'] ?? 0) === $i,
            ])->save();
            $keepOptions[] = $option->id;
        }
        $service->options()->whereNotIn('id', $keepOptions)->delete();

        $keepRates = [];
        foreach ($data['rates'] ?? [] as $row) {
            if (empty($row['name'])) {
                continue;
            }
            $rate = ! empty($row['id']) ? $service->rates()->find($row['id']) : null;
            $rate = $rate ?: new ServiceRate(['service_id' => $service->id]);
            $rate->fill([
                'name' => $row['name'],
                'days' => array_map('intval', $row['days'] ?? []),
                'starts_at' => $row['starts_at'],
                'ends_at' => $row['ends_at'],
                'multiplier' => $row['multiplier'],
            ])->save();
            $keepRates[] = $rate->id;
        }
        $service->rates()->whereNotIn('id', $keepRates)->delete();

        $service->games()->sync($data['games'] ?? []);
    }

    protected function authorizeOwner(Request $request, Venue $venue): void
    {
        abort_unless($venue->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
    }
}
