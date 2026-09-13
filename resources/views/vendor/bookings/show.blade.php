@extends('layouts.dashboard')
@section('title', 'Booking '.$booking->reference)

@section('content')
<nav class="small text-muted mb-1"><a href="{{ route('vendor.bookings.index') }}">Bookings</a> › {{ $booking->reference }}</nav>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="display fs-1 mb-1">{{ $booking->reference }}</h1>
        <span class="badge {{ $booking->status->bsBadge() }}">{{ $booking->status->label() }}</span>
        <span class="badge {{ $booking->payment_status->bsBadge() }}">{{ $booking->payment_status->label() }}</span>
        <span class="badge bg-dark">{{ $booking->payment_method->label() }} · priority {{ $booking->priority }}/3</span>
        @if($booking->isLocked())<span class="badge bg-success"><i class="fa-solid fa-lock me-1"></i>Locked</span>@endif
    </div>
    <div class="fs-3 fw-bold">{{ lkr($booking->total) }}</div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Service</dt><dd class="col-sm-8">{{ $booking->service->name }} · {{ $booking->option->name }}@if($booking->game) · <i class="fa-solid fa-gamepad"></i> {{ $booking->game->name }}@endif</dd>
                    <dt class="col-sm-4">Venue</dt><dd class="col-sm-8">{{ $booking->venue->name }}</dd>
                    <dt class="col-sm-4">When</dt><dd class="col-sm-8">{{ $booking->timeRangeLabel() }} ({{ $booking->durationLabel() }})</dd>
                    <dt class="col-sm-4">Customer</dt><dd class="col-sm-8">{{ $booking->customer_name }} · <a href="tel:{{ $booking->customer_phone }}">{{ $booking->customer_phone }}</a><div class="small text-muted">{{ $booking->user->email }}@if($booking->players) · {{ $booking->players }} players @endif</div></dd>
                    @if($booking->notes)<dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $booking->notes }}</dd>@endif
                    <dt class="col-sm-4">Placed</dt><dd class="col-sm-8">{{ $booking->created_at->format('d M Y, h:i A') }}</dd>
                    @if($booking->vendor_confirmed_at)<dt class="col-sm-4">Confirmed</dt><dd class="col-sm-8">{{ $booking->vendor_confirmed_at->format('d M Y, h:i A') }}</dd>@endif
                    @if($booking->cancelled_at)<dt class="col-sm-4">{{ $booking->status->label() }}</dt><dd class="col-sm-8">{{ $booking->cancelled_at->format('d M Y, h:i A') }}@if($booking->cancel_reason) — {{ $booking->cancel_reason }}@endif @if($booking->bumpedBy) (by <a href="{{ route('vendor.bookings.show', $booking->bumpedBy) }}">{{ $booking->bumpedBy->reference }}</a>)@endif</dd>@endif
                </dl>
                <hr>
                @foreach($booking->price_breakdown ?? [] as $line)
                    <div class="d-flex justify-content-between small text-muted"><span>{{ $line['label'] }}</span><span>{{ lkr($line['amount']) }}</span></div>
                @endforeach
                <div class="d-flex justify-content-between fw-bold mt-2"><span>Total</span><span>{{ lkr($booking->total) }}</span></div>
            </div>
        </div>

        @if($booking->payments->isNotEmpty())
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Payments</div>
                <div class="list-group list-group-flush">
                    @foreach($booking->payments as $p)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between"><span>{{ $p->method->label() }} · {{ lkr($p->amount) }}</span><span class="badge {{ $p->status->bsBadge() }}">{{ $p->status->label() }}</span></div>
                            <div class="small text-muted">Ref: {{ $p->reference ?: '—' }}@if($p->verified_at) · verified by {{ $p->verifier?->name }} on {{ $p->verified_at->format('d M, h:i A') }}@endif</div>
                            @if($p->proof_path)<a href="{{ $p->proofUrl() }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2"><i class="fa-solid fa-file-image me-1"></i>View slip</a>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Actions</div>
            <div class="card-body d-grid gap-3">
                @if($booking->isActive())
                    @if(! $booking->vendor_confirmed_at)
                        <form method="POST" action="{{ route('vendor.bookings.confirm', $booking) }}">@csrf
                            <button class="btn btn-success w-100"><i class="fa-solid fa-lock me-1"></i>Confirm &amp; lock slot</button>
                            <div class="form-text">Use after calling the customer. A locked slot can no longer be replaced by a paid booking.</div>
                        </form>
                    @endif
                    @if($booking->payment_status !== \App\Enums\PaymentStatus::Paid)
                        <form method="POST" action="{{ route('vendor.bookings.paid', $booking) }}">@csrf
                            <input name="reference" class="form-control form-control-sm mb-2" placeholder="Receipt / transfer reference (optional)">
                            <button class="btn btn-primary w-100"><i class="fa-solid fa-money-check-dollar me-1"></i>Mark as paid</button>
                            <div class="form-text">Verifies a bank transfer or records cash taken at the counter. Confirms and locks the booking.</div>
                        </form>
                    @endif
                    @if($booking->ends_at->isPast())
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('vendor.bookings.complete', $booking) }}" class="flex-fill">@csrf<button class="btn btn-outline-info w-100">Completed</button></form>
                            <form method="POST" action="{{ route('vendor.bookings.complete', $booking) }}" class="flex-fill">@csrf<input type="hidden" name="no_show" value="1"><button class="btn btn-outline-secondary w-100">No-show</button></form>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('vendor.bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking? The customer will be notified.')">@csrf
                        <input name="reason" class="form-control form-control-sm mb-2" placeholder="Reason shown to the customer">
                        <button class="btn btn-outline-danger w-100">Cancel booking</button>
                    </form>
                @else
                    <p class="text-muted mb-0">This booking is {{ Str::lower($booking->status->label()) }}. No further actions.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
