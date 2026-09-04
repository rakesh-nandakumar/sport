@extends('layout')
@section('content')

<div class="mx-auto max-w-2xl px-6 pb-16" style="padding-top: calc(var(--header-h) + 2.5rem);">
    <div class="mb-8 text-center">
        <span class="eyebrow">Stay in the loop</span>
        <h1 class="wrappermain !pt-0">Notifications</h1>
    </div>

    @livewire('UserNotification')
</div>

@endsection
