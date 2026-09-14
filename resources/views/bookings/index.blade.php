@extends('layouts.app')
@section('title', 'My bookings · EntryPoint.lk')

@section('content')
<section class="mx-auto max-w-4xl px-4 pb-16">
    <h1 class="display text-4xl md:text-5xl text-gray-900">My bookings</h1>
    <p class="text-gray-500">Everything you've booked, newest first.</p>

    <div class="mt-8 space-y-4">
        @forelse($bookings as $booking)
            <a href="{{ route('bookings.show', $booking) }}" class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md sm:flex-row sm:items-center">
                <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-gray-900 text-white">
                    <span class="text-[11px] uppercase">{{ $booking->starts_at->format('M') }}</span>
                    <span class="text-2xl font-bold leading-none">{{ $booking->starts_at->format('d') }}</span>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900">{{ $booking->service->name }} <span class="font-normal text-gray-500">· {{ $booking->option->name }}</span></p>
                    <p class="text-sm text-gray-500">{{ $booking->venue->name }} · {{ $booking->starts_at->format('h:i A') }} – {{ $booking->ends_at->format('h:i A') }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $booking->status->badge() }}">{{ $booking->status->label() }}</span>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $booking->payment_status->badge() }}">{{ $booking->payment_status->label() }}</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600">{{ $booking->payment_method->label() }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-gray-900">{{ lkr($booking->total) }}</p>
                    <p class="text-xs text-gray-400">{{ $booking->reference }}</p>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 p-12 text-center text-gray-500">
                <i class="fa-regular fa-calendar-xmark text-3xl"></i>
                <p class="mt-3">You haven't booked anything yet.</p>
                <a href="{{ route('venues.index') }}" class="btn-brand mt-4">Find a venue</a>
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $bookings->links() }}</div>
</section>
@endsection
