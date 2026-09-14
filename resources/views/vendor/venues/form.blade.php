@extends('layouts.dashboard')
@section('title', $venue->exists ? 'Edit '.$venue->name : 'Add venue')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h1 class="display fs-1 mb-0">{{ $venue->exists ? $venue->name : 'Add a venue' }}</h1>
        <p class="text-muted mb-0">Business details, opening hours and bank details for transfers.</p>
    </div>
    @if($venue->exists)
        <form method="POST" action="{{ route('vendor.venues.destroy', $venue) }}" onsubmit="return confirm('Delete this venue and all its services and bookings?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete venue</button></form>
    @endif
</div>

<form method="POST" action="{{ $venue->exists ? route('vendor.venues.update', $venue) : route('vendor.venues.store') }}" enctype="multipart/form-data">
    @csrf
    @if($venue->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">Basics</div>
                <div class="card-body row g-3">
                    <div class="col-md-8"><label class="form-label">Venue name</label><input name="name" value="{{ old('name', $venue->name) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $venue->phone) }}" class="form-control" placeholder="0112345678" required></div>
                    <div class="col-12"><label class="form-label">Tagline</label><input name="tagline" value="{{ old('tagline', $venue->tagline) }}" class="form-control" placeholder="e.g. Colombo's biggest indoor futsal & badminton complex"></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="5" class="form-control">{{ old('description', $venue->description) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Address</label><input name="address" value="{{ old('address', $venue->address) }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">City / town</label><input name="city" value="{{ old('city', $venue->city) }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">District</label>
                        <select name="district" class="form-select" required>
                            <option value="">Select…</option>
                            @foreach($districts as $d)<option value="{{ $d }}" @selected(old('district', $venue->district) === $d)>{{ $d }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Postal code</label><input name="postal_code" value="{{ old('postal_code', $venue->postal_code) }}" class="form-control" maxlength="5" inputmode="numeric" placeholder="00500"></div>
                    <div class="col-md-9" x-data="{ lat: @js(old('latitude', $venue->latitude)), lng: @js(old('longitude', $venue->longitude)), busy: false }">
                        <label class="form-label">Map pin <span class="text-muted small">— powers "near you" search and the Google Maps link</span></label>
                        <div class="input-group">
                            <input name="latitude" x-model="lat" class="form-control" placeholder="Latitude e.g. 6.88340">
                            <input name="longitude" x-model="lng" class="form-control" placeholder="Longitude e.g. 79.86600">
                            <button type="button" class="btn btn-outline-secondary" :disabled="busy" @click="busy = true; navigator.geolocation.getCurrentPosition(p => { lat = p.coords.latitude.toFixed(6); lng = p.coords.longitude.toFixed(6); busy = false }, () => busy = false)" title="Use my current position"><i class="fa-solid" :class="busy ? 'fa-spinner fa-spin' : 'fa-location-crosshairs'"></i></button>
                            <a class="btn btn-outline-secondary" :href="lat && lng ? 'https://www.google.com/maps/search/?api=1&query=' + lat + ',' + lng : 'https://www.google.com/maps'" target="_blank" rel="noopener" title="Check on Google Maps"><i class="fa-solid fa-map"></i></a>
                        </div>
                        <div class="form-text">Right-click a spot in Google Maps and copy the coordinates, or press the crosshair while standing at the venue.</div>
                    </div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $venue->email) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Website</label><input type="url" name="website" value="{{ old('website', $venue->website) }}" class="form-control" placeholder="https://"></div>
                    <div class="col-12">
                        <label class="form-label">Cover photo</label>
                        <input type="file" name="cover" accept="image/*" class="form-control">
                        @if($venue->cover_image)<img src="{{ $venue->coverUrl() }}" class="mt-2 rounded" style="height:90px;object-fit:cover" alt="">@endif
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">Opening hours <span class="text-muted fw-normal small">— services inherit these unless they set their own</span></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Day</th><th>Opens</th><th>Closes</th><th>Closed</th></tr></thead>
                            <tbody>
                            @foreach(\App\Models\VenueHour::DAYS as $i => $day)
                                <tr>
                                    <td class="fw-semibold">{{ $day }}</td>
                                    <td><input type="time" name="hours[{{ $i }}][opens_at]" value="{{ old("hours.$i.opens_at", $hours[$i]['opens_at']) }}" class="form-control form-control-sm" style="max-width:140px"></td>
                                    <td><input type="time" name="hours[{{ $i }}][closes_at]" value="{{ old("hours.$i.closes_at", $hours[$i]['closes_at']) }}" class="form-control form-control-sm" style="max-width:140px"></td>
                                    <td><input type="hidden" name="hours[{{ $i }}][is_closed]" value="0"><input type="checkbox" name="hours[{{ $i }}][is_closed]" value="1" class="form-check-input" @checked(old("hours.$i.is_closed", $hours[$i]['is_closed']))></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">Amenities</div>
                <div class="card-body">
                    @foreach($amenities as $a)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="amenities[]" value="{{ $a }}" id="am{{ $loop->index }}" @checked(in_array($a, old('amenities', $venue->amenities ?? [])))><label class="form-check-label" for="am{{ $loop->index }}">{{ $a }}</label></div>
                    @endforeach
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">Bank details <span class="text-muted fw-normal small">— shown to customers paying by transfer</span></div>
                <div class="card-body row g-2">
                    <div class="col-12"><label class="form-label small">Bank</label><input name="bank_name" value="{{ old('bank_name', $venue->bank_name) }}" class="form-control form-control-sm" placeholder="Commercial Bank"></div>
                    <div class="col-12"><label class="form-label small">Branch</label><input name="bank_branch" value="{{ old('bank_branch', $venue->bank_branch) }}" class="form-control form-control-sm"></div>
                    <div class="col-12"><label class="form-label small">Account name</label><input name="bank_account_name" value="{{ old('bank_account_name', $venue->bank_account_name) }}" class="form-control form-control-sm"></div>
                    <div class="col-12"><label class="form-label small">Account number</label><input name="bank_account_number" value="{{ old('bank_account_number', $venue->bank_account_number) }}" class="form-control form-control-sm"></div>
                </div>
            </div>

            <button class="btn btn-danger w-100 py-2">{{ $venue->exists ? 'Save changes' : 'Create venue' }}</button>
        </div>
    </div>
</form>
@endsection
