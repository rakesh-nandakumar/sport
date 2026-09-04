@extends('user-dashboard-layout')
@section('content')

    <section class="container mx-auto p-6 font-mono">
        <header class="flex justify-between items-center mb-6">
            <h1 class="text-3xl text-center font-bold uppercase">
                Bookable Units - {{ $indoor->title }}
            </h1>
            <a href="/home/manage" class="text-blue-400 text-base font-semibold py-2.5 px-6 border-2 border-white rounded hover:bg-white hover:text-black transition duration-300 ease-in-out">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </header>

        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-lg p-6 bg-white">
            @livewire('manage-resources', ['indoor' => $indoor])
        </div>
    </section>

@endsection
