<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenueHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenueController extends Controller
{
    public const AMENITIES = ['Parking', 'Changing rooms', 'Showers', 'Cafeteria', 'Air conditioning', 'Floodlights', 'Equipment rental', 'Wi-Fi', 'First aid', 'Spectator seating'];

    public function index(Request $request): View
    {
        return view('vendor.venues.index', [
            'venues' => $request->user()->venues()->withCount(['services', 'bookings'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('vendor.venues.form', [
            'venue' => new Venue(['city' => 'Colombo']),
            'hours' => collect(range(0, 6))->mapWithKeys(fn ($d) => [$d => ['opens_at' => '08:00', 'closes_at' => '22:00', 'is_closed' => false]]),
            'amenities' => self::AMENITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['is_approved'] = true; // auto-approve for now; admins can un-approve from the admin panel

        if ($request->hasFile('cover')) {
            $data['cover_image'] = $request->file('cover')->store('venues', 'public');
        }

        $venue = Venue::create($data);
        $this->syncHours($venue, $request->input('hours', []));

        return redirect()->route('vendor.venues.services.index', $venue)
            ->with('message', 'Venue created. Now add the services customers can book.');
    }

    public function edit(Request $request, Venue $venue): View
    {
        $this->authorizeOwner($request, $venue);
        $venue->load('hours');

        return view('vendor.venues.form', [
            'venue' => $venue,
            'hours' => collect(range(0, 6))->mapWithKeys(function ($d) use ($venue) {
                $h = $venue->hoursFor($d);

                return [$d => [
                    'opens_at' => $h?->opens_at ? substr($h->opens_at, 0, 5) : '08:00',
                    'closes_at' => $h?->closes_at ? substr($h->closes_at, 0, 5) : '22:00',
                    'is_closed' => (bool) $h?->is_closed,
                ]];
            }),
            'amenities' => self::AMENITIES,
        ]);
    }

    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorizeOwner($request, $venue);
        $data = $this->validated($request);

        if ($request->hasFile('cover')) {
            $data['cover_image'] = $request->file('cover')->store('venues', 'public');
        }

        $venue->update($data);
        $this->syncHours($venue, $request->input('hours', []));

        return back()->with('message', 'Venue updated.');
    }

    public function destroy(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorizeOwner($request, $venue);
        $venue->delete();

        return redirect()->route('vendor.venues.index')->with('message', 'Venue deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'email' => ['nullable', 'email'],
            'website' => ['nullable', 'url'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:60'],
            'bank_name' => ['nullable', 'string', 'max:80'],
            'bank_branch' => ['nullable', 'string', 'max:80'],
            'bank_account_name' => ['nullable', 'string', 'max:120'],
            'bank_account_number' => ['nullable', 'string', 'max:40'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ], [
            'phone.regex' => 'Enter a valid 10-digit number, e.g. 0112345678.',
        ]);

        unset($data['cover'], $data['hours']);
        $data['amenities'] = array_values($data['amenities'] ?? []);

        return $data;
    }

    protected function syncHours(Venue $venue, array $hours): void
    {
        foreach (range(0, 6) as $day) {
            $row = $hours[$day] ?? [];
            VenueHour::updateOrCreate(
                ['venue_id' => $venue->id, 'day_of_week' => $day],
                [
                    'opens_at' => $row['opens_at'] ?? null,
                    'closes_at' => $row['closes_at'] ?? null,
                    'is_closed' => (bool) ($row['is_closed'] ?? false),
                ],
            );
        }
    }

    protected function authorizeOwner(Request $request, Venue $venue): void
    {
        abort_unless($venue->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
    }
}
