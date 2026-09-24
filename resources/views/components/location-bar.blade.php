@props(['location' => null])

{{-- "Near you" control: shows the current location label, lets the visitor use GPS or pick a district. --}}
<div class="flex flex-wrap items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm" x-data="{ picking: false, busy: false }">
    <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-soft text-brand"><i class="fa-solid fa-location-crosshairs"></i></span>
    @if($location)
        <span class="text-gray-700">Showing distances from <strong class="text-gray-900">{{ $location['label'] }}</strong></span>
    @else
        <span class="text-gray-700">Find venues <strong class="text-gray-900">near you</strong></span>
    @endif
    <div class="ml-auto flex flex-wrap items-center gap-2">
        <button type="button" @click="busy = true; window.epRequestLocation().finally(() => busy = false)" :disabled="busy" class="rounded-full border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:border-gray-400 disabled:opacity-50">
            <i class="fa-solid" :class="busy ? 'fa-spinner fa-spin' : 'fa-location-arrow'"></i> {{ $location ? 'Update' : 'Use my location' }}
        </button>
        <form method="POST" action="{{ route('location.store') }}" class="inline-flex items-center gap-1">
            @csrf
            <select name="district" onchange="this.form.submit()" class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700">
                <option value="">Pick a district…</option>
                @foreach(array_keys(config('entrypoint.districts')) as $district)
                    <option value="{{ $district }}" @selected(($location['label'] ?? null) === $district)>{{ $district }}</option>
                @endforeach
            </select>
        </form>
        @if($location)
            <form method="POST" action="{{ route('location.destroy') }}">@csrf @method('DELETE')<button class="text-xs text-gray-400 hover:text-gray-700" title="Stop using my location"><i class="fa-solid fa-xmark"></i></button></form>
        @endif
    </div>
</div>
