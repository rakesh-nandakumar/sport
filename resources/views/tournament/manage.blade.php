@extends('user-dashboard-layout')
@section('content')

    <script src="https://cdn.tailwindcss.com"></script>

    <section class="container mx-auto p-6 font-mono">
        <header class="flex justify-between items-center mb-6">
            <h1 class="text-3xl text-center font-bold uppercase">
                Manage Your Tournaments
            </h1>
            <a href="/tournament/create" class="text-blue-400 text-base font-semibold py-2.5 px-6 border-2 border-white rounded hover:bg-white hover:text-black transition duration-300 ease-in-out">
                <i class="fa-solid fa-plus"></i> Add Event
            </a>
        </header>

        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-lg">
            <div class="w-full overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="text-md font-semibold tracking-wide text-left text-gray-900 bg-gray-100 uppercase border-b border-gray-600">
                        <th class="px-4 py-3">Tournament</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white">
                    @unless ($tournaments->isEmpty())
                        @foreach ($tournaments as $tournament)
                            <tr class="text-gray-700">
                                <td class="px-4 py-3 border">
                                    <a href="/tournament/{{ $tournament->id }}" class="text-blue-500 hover:underline">
                                        {{ $tournament->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 border">
                                    <div class="flex space-x-4">
                                        <a href="/tournament/{{ $tournament->id }}/edit" class="text-blue-400 px-3 py-2 rounded hover:bg-blue-100">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <form action="{{ route('tournament.destroy', $tournament->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-500 px-3 py-2 rounded hover:bg-red-100">
                                                <i class="fa-solid fa-trash-can"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" class="px-4 py-3 border text-center">You have no Tournaments yet.</td>
                        </tr>
                    @endunless
                    </tbody>
                </table>
            </div>
        </div>
    </section>

@endsection
