@extends('layouts.app')
@section('title', 'My orders · EntryPoint.lk')

@section('content')
<section class="mx-auto max-w-4xl px-4 pb-16">
    <h1 class="display text-4xl text-gray-900 md:text-5xl">My orders</h1>
    <p class="text-gray-500">Review every booking, continue a payment, or open the QR code for check-in.</p>

    <nav class="mt-6 flex gap-2 overflow-x-auto pb-1" aria-label="Order categories">
        @foreach($filters as $key => $label)
            <a href="{{ route('bookings.index', $key === 'all' ? [] : ['filter' => $key]) }}"
               class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition {{ $filter === $key ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <div class="mt-6 space-y-4">
        @forelse($bookings as $booking)
            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-gray-900 text-white">
                        <span class="text-[11px] uppercase">{{ $booking->starts_at->format('M') }}</span>
                        <span class="text-2xl font-bold leading-none">{{ $booking->starts_at->format('d') }}</span>
                    </div>
                    <a href="{{ route('bookings.show', $booking) }}" class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900">{{ $booking->service->name }} <span class="font-normal text-gray-500">· {{ $booking->option->name }}</span></p>
                        <p class="text-sm text-gray-500">{{ $booking->venue->name }} · {{ $booking->starts_at->format('D, h:i A') }} – {{ $booking->ends_at->format('h:i A') }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $booking->status->badge() }}">{{ $booking->status->label() }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $booking->payment_status->badge() }}">{{ $booking->payment_status->label() }}</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600">{{ $booking->payment_method->label() }}</span>
                        </div>
                    </a>
                    <div class="shrink-0 sm:text-right">
                        <p class="text-lg font-bold text-gray-900">{{ lkr($booking->total) }}</p>
                        <p class="text-xs text-gray-400">{{ $booking->reference }}</p>
                        @if($booking->isAwaitingVerification())
                            <a href="{{ route('bookings.show', $booking) }}#payment" class="mt-2 inline-flex rounded-lg bg-brand px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-dark">Upload slip &amp; pay</a>
                        @elseif($booking->payment_method === \App\Enums\PaymentMethod::PayAtVenue && $booking->isActive())
                            <a href="{{ route('bookings.show', $booking) }}#payment" class="mt-2 inline-flex rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-200">Pay at venue</a>
                        @else
                            <a href="{{ route('bookings.show', $booking) }}" class="mt-2 inline-flex text-xs font-semibold text-brand hover:underline">View order</a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 p-12 text-center text-gray-500">
                <i class="fa-regular fa-calendar-xmark text-3xl"></i>
                <p class="mt-3">No {{ strtolower($filters[$filter]) }} found.</p>
                <a href="{{ route('venues.index') }}" class="btn-brand mt-4">Find a venue</a>
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $bookings->links() }}</div>
</section>
@endsection
