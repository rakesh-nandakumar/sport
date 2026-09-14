<?php

namespace App\Filament\Vendor\Resources\Venues\Pages;

use App\Filament\Vendor\Resources\Venues\Schemas\VenueForm;
use App\Filament\Vendor\Resources\Venues\VenueResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVenue extends CreateRecord
{
    protected static string $resource = VenueResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        // Vendor activation is the main gate (Venue::scopeLive); a super admin can additionally
        // require per-venue approval from Site settings.
        $data['is_approved'] = ! setting('venues.require_approval') || auth()->user()->isAdmin();

        return VenueForm::applyCoverData($data);
    }

    protected function afterCreate(): void
    {
        VenueForm::syncHours($this->getRecord(), $this->data['hours'] ?? []);
    }

    protected function getCreatedNotification(): ?Notification
    {
        $venue = $this->getRecord();

        return Notification::make()
            ->success()
            ->title('Venue created')
            ->body($venue->is_approved
                ? 'Now add the services customers can book, from the Services tab.'
                : 'Sent for approval. Add the services customers can book meanwhile, from the Services tab.');
    }
}
