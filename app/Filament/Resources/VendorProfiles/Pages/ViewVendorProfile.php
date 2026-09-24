<?php

namespace App\Filament\Resources\VendorProfiles\Pages;

use App\Filament\Resources\VendorProfiles\VendorProfileResource;
use App\Models\VendorProfile;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorProfile extends ViewRecord
{
    protected static string $resource = VendorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            VendorProfileResource::impersonateAction(),
            VendorProfileResource::changeStatusAction(),
            VendorProfileResource::activateAction(),
            Action::make('brDocument')
                ->label('Business registration')
                ->icon('heroicon-o-document-text')
                ->url(fn (VendorProfile $record) => route('admin.vendors.document', [$record, 'br']))
                ->openUrlInNewTab()
                ->visible(fn (VendorProfile $record) => filled($record->br_document_path)),
            Action::make('nicDocument')
                ->label('Owner NIC')
                ->icon('heroicon-o-identification')
                ->url(fn (VendorProfile $record) => route('admin.vendors.document', [$record, 'nic']))
                ->openUrlInNewTab()
                ->visible(fn (VendorProfile $record) => filled($record->nic_document_path)),
        ];
    }
}
