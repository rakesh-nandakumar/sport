@extends('layout')
@section('content')

<div class="mx-auto max-w-5xl px-6 pb-16" style="padding-top: calc(var(--header-h) + 2.5rem);">
    <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm lg:flex">
        <div class="h-64 bg-cover bg-center lg:h-auto lg:w-1/2"
             style="background-image:url({{ $tournament->photo ? asset('storage/' . $tournament->photo) : asset('/images/CR7.png') }});"></div>

        <div class="px-6 py-10 lg:w-1/2 lg:px-10 lg:py-12">
            <span class="rounded-full bg-accent-500 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Tournament</span>
            <h1 class="mt-4 font-display text-4xl tracking-wide text-gray-900">{{ $tournament->title }}</h1>
            <p class="mt-4 leading-relaxed text-gray-600">{{ $tournament->description }}</p>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-gray-50 p-3 text-center">
                    <p class="text-xs text-gray-400">Date</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800">{{ date('d M Y', strtotime($tournament->tournamentDate)) }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3 text-center">
                    <p class="text-xs text-gray-400">Format</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800">{{ $tournament->noOFplayers }} V {{ $tournament->noOFplayers }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3 text-center">
                    <p class="text-xs text-gray-400">Entry fee</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800">{{ $tournament->entry_fee }}</p>
                </div>
            </div>

            <div class="mt-8">
                <a href="#" class="btn btn-primary">Register Now</a>
            </div>
        </div>
    </div>
</div>

@endsection
