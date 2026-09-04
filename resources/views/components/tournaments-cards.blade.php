@props(['tourn'])

<a href="/home/tournament/{{ $tourn->id }}"
   class="group relative flex h-[420px] w-full flex-col justify-end overflow-hidden rounded-2xl bg-gray-200 bg-cover bg-center text-left shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
   style="background-image:url({{ $tourn->photo ? asset('storage/' . $tourn->photo) : asset('/images/CR7.png') }});">

    <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/40 to-transparent"></div>

    <div class="absolute left-0 right-0 top-0 mx-4 mt-4 flex items-center justify-between">
        <span class="rounded-full bg-accent-500 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white shadow">Tournament</span>
        <div class="flex flex-col items-center rounded-lg bg-white/95 px-2.5 py-1.5 leading-none text-gray-900 shadow">
            <span class="text-lg font-bold">{{ date('d', strtotime($tourn->tournamentDate)) }}</span>
            <span class="text-[10px] font-medium uppercase">{{ date('M', strtotime($tourn->tournamentDate)) }}</span>
        </div>
    </div>

    <div class="relative z-10 p-5">
        <h3 class="text-base font-semibold leading-snug text-white group-hover:underline">{{ $tourn->title }}</h3>
        <p class="mt-1 text-sm text-gray-200">{{ $tourn->noOFplayers }} V {{ $tourn->noOFplayers }}</p>
    </div>
</a>
