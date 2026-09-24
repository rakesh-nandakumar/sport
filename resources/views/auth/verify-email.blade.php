@extends('layouts.app')
@section('title', 'Verify your email · EntryPoint.lk')

@section('content')
<section class="mx-auto max-w-md px-4 pb-16">
    <div class="rounded-3xl border border-gray-200 bg-white p-8 text-center shadow-lg">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-brand-soft text-2xl text-brand">
            <i class="fa-solid fa-envelope-circle-check"></i>
        </div>
        <h1 class="display mt-6 text-4xl text-gray-900">Check your email</h1>
        <p class="mt-3 text-sm leading-6 text-gray-600">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Open it to verify your account before booking or accessing your workspace.</p>

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button class="btn-brand w-full py-3">Resend verification email</button>
        </form>

        <p class="mt-5 text-xs leading-5 text-gray-500">Did not receive it? Check your spam folder, then request a fresh link above.</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-5">
            @csrf
            <button class="text-sm font-semibold text-brand hover:underline">Use a different account</button>
        </form>
    </div>
</section>
@endsection
