<?php

namespace App\Filament\Vendor\Resources\Venues\Pages;

use App\Filament\Vendor\Resources\Venues\Schemas\VenueForm;
use App\Filament\Vendor\Resources\Venues\VenueResource;
use App\Models\Venue;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVenue extends EditRecord
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription('This deletes the venue with all its services, hours and bookings. This cannot be undone.'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Venue $venue */
        $venue = $this->getRecord();
        $data['hours'] = VenueForm::defaultHours($venue);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return VenueForm::applyCoverData($data);
    }

    protected function afterSave(): void
    {
        VenueForm::syncHours($this->getRecord(), $this->data['hours'] ?? []);
    }
}
