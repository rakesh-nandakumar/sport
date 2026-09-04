<form class="search-card" action="/" method="GET">
    <select name="activity" aria-label="Activity">
        <option value="">All activities</option>
        @foreach (config('activities.categories') as $categoryKey => $categoryLabel)
            @php
                $types = array_filter(config('activities.types'), fn ($type) => ($type['category'] ?? null) === $categoryKey);
            @endphp
            @if ($types)
                <optgroup label="{{ $categoryLabel }}">
                    @foreach ($types as $slug => $type)
                        <option value="{{ $slug }}" @selected(request('activity') === $slug)>{{ $type['name'] }}</option>
                    @endforeach
                </optgroup>
            @endif
        @endforeach
    </select>
    <input type="text" placeholder="Search by name or location…"
        name="search" value="{{ request('search') }}">
    <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>
