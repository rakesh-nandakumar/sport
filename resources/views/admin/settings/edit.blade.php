@extends('layouts.dashboard')
@section('title', 'Site settings')

@section('content')
<h1 class="display fs-1 mb-0">Site settings</h1>
<p class="text-muted">Platform-wide switches. Changes apply immediately.</p>

<form method="POST" action="{{ route('admin.settings.update') }}" class="row g-4">
    @csrf @method('PUT')

    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Payment methods</div>
            <div class="card-body">
                <p class="small text-muted">Customers see every method in the checkout modal; switched-off ones show as unavailable. Online gateways stay "Coming soon" until their integration is built — they cannot be enabled from here.</p>
                @foreach($methods as $m)
                    <div class="form-check form-switch mb-2 d-flex align-items-center gap-2">
                        <input class="form-check-input" type="checkbox" name="payments_enabled[]" value="{{ $m->value }}" id="pm-{{ $m->value }}"
                               @checked(in_array($m->value, $settings['payments.enabled'] ?? [], true)) @disabled(! $m->isIntegrated())>
                        <label class="form-check-label" for="pm-{{ $m->value }}">
                            <i class="{{ $m->icon() }} me-1 text-muted"></i>{{ $m->label() }}
                            <span class="badge {{ $m->isIntegrated() ? 'bg-success' : 'bg-secondary' }} ms-1">{{ $m->isIntegrated() ? 'Integrated' : 'Not integrated' }}</span>
                            <span class="badge bg-light text-dark ms-1">priority {{ $m->priority() }}/3</span>
                        </label>
                    </div>
                @endforeach
                @error('payments_enabled')<div class="text-danger small">{{ $message }}</div>@enderror
                <hr>
                <label class="form-label">Bank-transfer verification window (minutes)</label>
                <input type="number" name="bank_transfer_hold_minutes" value="{{ old('bank_transfer_hold_minutes', $settings['payments.bank_transfer_hold_minutes']) }}" min="5" max="1440" class="form-control" style="max-width:160px" required>
                <div class="form-text">A bank-transfer booking holds the slot for this long. If the venue hasn't verified the slip by then, the booking expires and the slot is released. Keep it short so slots aren't blocked by transfers that never arrive.</div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Moderation</div>
            <div class="card-body">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="vendors_require_activation" value="0">
                    <input class="form-check-input" type="checkbox" name="vendors_require_activation" value="1" id="vra" @checked(old('vendors_require_activation', $settings['vendors.require_activation']))>
                    <label class="form-check-label" for="vra">New vendors must be activated by a Super Administrator before their venues are visible</label>
                </div>
                <div class="form-check form-switch">
                    <input type="hidden" name="venues_require_approval" value="0">
                    <input class="form-check-input" type="checkbox" name="venues_require_approval" value="1" id="vrq" @checked(old('venues_require_approval', $settings['venues.require_approval']))>
                    <label class="form-check-label" for="vrq">Every new venue also needs an admin approval (Admin → Venues → Approve)</label>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Booking & discovery</div>
            <div class="card-body row g-3">
                <div class="col-md-6"><label class="form-label">Booking window (days ahead)</label><input type="number" name="max_days_ahead" value="{{ old('max_days_ahead', $settings['bookings.max_days_ahead']) }}" min="1" max="90" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">"Near you" radius (km)</label><input type="number" name="nearby_km" value="{{ old('nearby_km', $settings['location.nearby_km']) }}" min="1" max="500" class="form-control" required></div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Amenities list</div>
            <div class="card-body">
                <textarea name="amenities" rows="10" class="form-control" required>{{ old('amenities', implode("\n", $settings['venues.amenities'])) }}</textarea>
                <div class="form-text">One per line. Vendors tick these on their venue form.</div>
            </div>
        </div>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Support contact</div>
            <div class="card-body row g-3">
                <div class="col-12"><label class="form-label">Support email</label><input type="email" name="support_email" value="{{ old('support_email', $settings['site.support_email']) }}" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Support phone</label><input name="support_phone" value="{{ old('support_phone', $settings['site.support_phone']) }}" class="form-control" required></div>
            </div>
        </div>
        <button class="btn btn-danger w-100 py-2">Save settings</button>
    </div>
</form>
@endsection
