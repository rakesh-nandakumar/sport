@extends('layouts.app')
@section('title', 'Notifications · Sportee')

@section('content')
<section class="mx-auto max-w-3xl px-4 pb-16">
    <div class="flex items-end justify-between">
        <div>
            <h1 class="display text-4xl md:text-5xl text-gray-900">Notifications</h1>
            <p class="text-gray-500">Booking updates, confirmations and payment alerts.</p>
        </div>
        @if(auth()->user()->unreadNotifications()->count())
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn-ghost text-sm">Mark all read</button></form>
        @endif
    </div>

    <div class="mt-8 space-y-3">
        @forelse($notifications as $n)
            @php($type = $n->data['type'] ?? 'system')
            <a href="{{ $n->data['link'] ?? '#' }}" class="flex gap-4 rounded-2xl border p-4 {{ $n->read_at ? 'border-gray-100 bg-white' : 'border-brand/30 bg-brand-soft/40' }}">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full {{ $type === 'success' ? 'bg-emerald-100 text-emerald-600' : ($type === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-gray-100 text-gray-600') }}">
                    <i class="{{ $n->data['icon'] ?? 'fa-solid fa-bell' }}"></i>
                </span>
                <div class="flex-1">
                    <p class="text-sm text-gray-800">{{ $n->data['message'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $n->created_at->diffForHumans() }}@if($n->data['booking_reference'] ?? false) · {{ $n->data['booking_reference'] }}@endif</p>
                </div>
            </a>
        @empty
            <p class="rounded-2xl border border-dashed border-gray-300 p-12 text-center text-gray-500">Nothing here yet.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
</section>
@endsection
