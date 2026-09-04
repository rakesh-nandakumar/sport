@php
    $showcaseImages = [
        'futsal' => 'images/CR7.png',
        'football' => 'images/Uni sports center.png',
        'basketball' => 'images/activities/basketball.jpg',
        'badminton' => 'images/activities/badminton.jpg',
        'cricket' => 'images/activities/cricket.jpg',
        'tennis' => 'images/activities/tennis.jpg',
        'volleyball' => 'images/activities/volleyball.jpg',
        'squash' => 'images/activities/squash.jpg',
        'pickleball' => 'images/activities/pickleball.jpg',
        'table_tennis' => 'images/activities/table_tennis.jpg',
        'pool_snooker' => 'images/activities/pool_snooker.jpg',
        'ps5' => 'images/activities/ps5.jpg',
        'board_games' => 'images/activities/board_games.jpg',
    ];

    $showcaseTypes = collect(config('activities.types'))
        ->only(array_keys($showcaseImages))
        ->sortBy(fn ($type, $slug) => array_search($slug, array_keys($showcaseImages), true));
@endphp

<section id="activities" class="mx-auto max-w-screen-xl px-5 pt-16 pb-4">
    <div class="mb-10 text-center">
        <span class="eyebrow">Every game, one app</span>
        <h2 class="wrappermain">Way More Than Football</h2>
        <p class="section-subtext">Futsal, PS5 tournaments, snooker nights, board games and more &mdash; whatever you're into, there's an Indoor for it.</p>
    </div>

    <div class="scrollbar-thin -mx-5 flex snap-x gap-4 overflow-x-auto px-5 pb-4 scroll-smooth">
        @foreach ($showcaseTypes as $slug => $type)
            <a href="{{ url('/?activity=' . $slug) }}"
               class="group relative h-56 w-44 flex-none snap-start overflow-hidden rounded-2xl shadow-md ring-1 ring-black/5 transition-all duration-200 hover:-translate-y-1 hover:shadow-xl sm:h-64 sm:w-52">
                <img src="{{ asset($showcaseImages[$slug]) }}" alt="{{ $type['name'] }}"
                     class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/10 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 flex items-center gap-2 p-3.5">
                    <i class="bx {{ $type['icon'] }} text-xl text-brand-300"></i>
                    <span class="text-sm font-semibold leading-tight text-white">{{ $type['name'] }}</span>
                </div>
            </a>
        @endforeach
    </div>
</section>
