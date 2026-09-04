@props(['indoor'])

@foreach ($indoor->activities as $activity)
    <a href="/?activity={{ $activity->slug }}"
       class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 transition-colors hover:bg-brand-100">
        #{{ $activity->name }}
    </a>
@endforeach
