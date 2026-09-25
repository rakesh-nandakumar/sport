@extends('layouts.app')
@section('title', 'Book '.$service->name.' · '.$venue->name)

@section('content')
<section class="mx-auto max-w-6xl px-4 pb-16">
    <nav class="text-sm text-gray-500">
        <a href="{{ route('venues.index') }}" class="hover:text-brand">Venues</a> ›
        <a href="{{ route('venues.show', $venue) }}" class="hover:text-brand">{{ $venue->name }}</a> ›
        <span class="text-gray-800">{{ $service->name }}</span>
    </nav>

    <div class="mt-4 flex flex-col gap-4 md:flex-row md:items-center">
        <img src="{{ $service->imageUrl() }}" alt="" class="h-24 w-full rounded-2xl object-cover md:w-40">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide" style="color: {{ $service->activityType->color }}"><i class="{{ $service->activityType->icon }} mr-1"></i>{{ $service->activityType->name }}</p>
            <h1 class="display text-4xl md:text-5xl text-gray-900">{{ $service->name }}</h1>
            <p class="text-gray-500">{{ $venue->name }} · {{ $venue->city }}</p>
        </div>
    </div>

    @if($venue->services->where('is_active', true)->where('id', '!=', $service->id)->isNotEmpty())
        <div class="mt-5 rounded-2xl border border-brand/15 bg-brand/5 p-4">
            <p class="text-sm font-semibold text-gray-900"><i class="fa-solid fa-bag-shopping mr-1 text-brand"></i>Build one venue order</p>
            <p class="mt-1 text-sm text-gray-600">Add this activity, then choose another activity at {{ $venue->name }}. Everything is checked together and paid once.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($venue->services->where('is_active', true)->where('id', '!=', $service->id) as $otherService)
                    <a href="{{ route('booking.build', $otherService) }}" class="btn-ghost py-2 text-sm"><i class="{{ $otherService->activityType->icon }} mr-1"></i>Add {{ $otherService->name }}</a>
                @endforeach
            </div>
        </div>
    @endif

    @error('slot')
        <div class="mt-4 rounded-xl bg-rose-50 p-4 text-sm text-rose-700"><i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ $message }}</div>
    @enderror

    <div class="mt-8">
        <livewire:booking-builder :service="$service" />
    </div>
</section>
@endsection
