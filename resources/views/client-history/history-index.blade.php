@extends('layout')
@section('content')

<div class="mx-auto max-w-5xl px-6 pb-16" style="padding-top: calc(var(--header-h) + 2.5rem);">
    <div class="mb-8 text-center">
        <span class="eyebrow">Your activity</span>
        <h1 class="wrappermain !pt-0">My Bookings</h1>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="w-full overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="px-5 py-3.5">Indoor</th>
                    <th class="px-5 py-3.5">Start Time</th>
                    <th class="px-5 py-3.5">Finish Time</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5 text-right">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse ($bookings as $booking)
                    @php
                        $currentTime = now();
                        $startTime = \Carbon\Carbon::parse($booking->start_time);
                        $canCancel = $currentTime->diffInHours($startTime, false) >= 5;
                    @endphp
                    <tr class="text-sm text-gray-700">
                        <td class="px-5 py-4 font-medium text-gray-900">{{ $booking->indoor->title }}</td>
                        <td class="px-5 py-4">{{ $booking->start_time }}</td>
                        <td class="px-5 py-4">{{ $booking->finish_time }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700">{{ $booking->comments }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if ($canCancel)
                                <form action="{{ route('cancel-booking', $booking->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="rounded-full bg-red-50 px-3.5 py-1.5 text-xs font-semibold text-red-600 transition-colors hover:bg-red-100">Cancel</button>
                                </form>
                            @else
                                <span class="rounded-full bg-gray-100 px-3.5 py-1.5 text-xs font-medium text-gray-400">Cannot cancel</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-16 text-center text-gray-500">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                <i class="fa-regular fa-calendar-days text-xl"></i>
                            </div>
                            No bookings found yet.
                            <a href="/" class="ml-1 font-semibold text-brand-600 hover:underline">Find an Indoor</a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
