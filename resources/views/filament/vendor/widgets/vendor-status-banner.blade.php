@php
    $status = $this->getVendorStatus();
    $profile = $this->getProfile();
@endphp

<div class="space-y-4">
    @if($status !== \App\Enums\VendorStatus::Active)
        <x-filament::section
            :icon="$status === \App\Enums\VendorStatus::Pending ? 'heroicon-o-clock' : 'heroicon-o-no-symbol'"
            :icon-color="$status === \App\Enums\VendorStatus::Pending ? 'warning' : 'danger'"
        >
            <div class="space-y-1">
                <p class="fi-section-header-heading text-base font-semibold">Account status: {{ $status->label() }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $status->vendorMessage() }}</p>
                @if($profile?->review_notes)
                    <p class="text-sm border-l-2 border-gray-300 pl-2 dark:border-white/10">Note from EntryPoint.lk: {{ $profile->review_notes }}</p>
                @endif
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Questions? <a href="mailto:{{ setting('site.support_email') }}" class="underline">{{ setting('site.support_email') }}</a> · {{ setting('site.support_phone') }}
                </p>
            </div>
        </x-filament::section>
    @endif

    @if($this->hasNoVenues())
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
            <p class="text-sm">
                You haven't added a venue yet.
                <a href="{{ \App\Filament\Vendor\Resources\Venues\VenueResource::getUrl('create') }}" class="font-semibold underline">Create your first venue</a>
                to start taking bookings.
            </p>
        </x-filament::section>
    @endif
</div>
