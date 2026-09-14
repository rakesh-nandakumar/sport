<?php

namespace App\Filament\Resources\ActivityTypes\Pages;

use App\Filament\Resources\ActivityTypes\ActivityTypeResource;
use App\Filament\Resources\ActivityTypes\Schemas\ActivityTypeForm;
use Filament\Resources\Pages\CreateRecord;

class CreateActivityType extends CreateRecord
{
    protected static string $resource = ActivityTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ActivityTypeForm::applyImageData($data);
    }
}
