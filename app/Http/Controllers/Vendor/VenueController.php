<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenueHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        return view('vendor.venues.index', [
            'venues' => $request->user()->venues()->withCount(['services', 'bookings'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('vendor.venues.form', [
            'venue' => new Venue($this->defaultsFromProfile()),
            'hours' => collect(range(0, 6))->mapWithKeys(fn ($d) => [$d => ['opens_at' => '08:00', 'closes_at' => '22:00', 'is_closed' => false]]),
            'amenities' => setting('venues.amenities'),
            'districts' => array_keys(config('entrypoint.districts')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        // Vendor activation is the main gate (Venue::scopeLive); a super admin can additionally require
        // per-venue approval from Site settings.
        $data['is_approved'] = ! setting('venues.require_approval') || $request->user()->isAdmin();

        if ($request->hasFile('cover')) {
            $data['cover_image'] = $request->file('cover')->store('venues', 'public');
        }

        $venue = Venue::create($data);
        $this->syncHours($venue, $request->input('hours', []));

        return redirect()->route('vendor.venues.services.index', $venue)
            ->with('message', $venue->is_approved
                ? 'Venue created. Now add the services customers can book.'
                : 'Venue created and sent for approval. Add the services customers can book meanwhile.');
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
            'amenities' => setting('venues.amenities'),
            'districts' => array_keys(config('entrypoint.districts')),
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
            'district' => ['required', Rule::in(array_keys(config('entrypoint.districts')))],
            'postal_code' => ['nullable', 'digits:5'],
            'latitude' => ['nullable', 'numeric', 'between:5.5,10.5', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:79,82.5', 'required_with:latitude'],
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
            'latitude.between' => 'The map pin must be inside Sri Lanka.',
            'longitude.between' => 'The map pin must be inside Sri Lanka.',
        ]);

        unset($data['cover'], $data['hours']);
        $data['amenities'] = array_values($data['amenities'] ?? []);
        $data['latitude'] = $data['latitude'] ?? null;
        $data['longitude'] = $data['longitude'] ?? null;

        return $data;
    }

    /** Pre-fill a new venue from the vendor's application so they don't retype the address. */
    protected function defaultsFromProfile(): array
    {
        $profile = auth()->user()->vendorProfile;
        if (! $profile) {
            return ['city' => 'Colombo', 'district' => 'Colombo'];
        }

        return [
            'name' => $profile->business_name,
            'address' => trim($profile->address_line1.($profile->address_line2 ? ', '.$profile->address_line2 : '')),
            'city' => $profile->city,
            'district' => $profile->district,
            'postal_code' => $profile->postal_code,
            'latitude' => $profile->latitude,
            'longitude' => $profile->longitude,
            'phone' => $profile->contact_phone,
            'email' => $profile->business_email,
            'website' => $profile->website,
            'description' => $profile->description,
        ];
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
