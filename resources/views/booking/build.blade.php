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

    @error('slot')
        <div class="mt-4 rounded-xl bg-rose-50 p-4 text-sm text-rose-700"><i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ $message }}</div>
    @enderror

    <div class="mt-8">
        <livewire:booking-builder :service="$service" />
    </div>
</section>
@endsection
