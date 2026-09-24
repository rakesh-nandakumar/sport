<?php

namespace App\Filament\Resources\VendorProfiles\Tables;

use App\Enums\VendorStatus;
use App\Filament\Resources\VendorProfiles\VendorProfileResource;
use App\Models\VendorProfile;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VendorProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (VendorProfile $record) => VendorProfileResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->description(fn (VendorProfile $record) => $record->registration_number),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->description(fn (VendorProfile $record) => $record->user?->email)
                    ->searchable(),
                TextColumn::make('city')
                    ->description(fn (VendorProfile $record) => $record->district)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (VendorStatus $state) => $state->label())
                    ->color(fn (VendorStatus $state) => match ($state) {
                        VendorStatus::Pending => 'warning',
                        VendorStatus::Active => 'success',
                        VendorStatus::Suspended => 'gray',
                        VendorStatus::Rejected => 'danger',
                    }),
                TextColumn::make('venues_count')
                    ->label('Venues')
                    ->state(fn (VendorProfile $record) => $record->user?->venues()->count() ?? 0)
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Applied')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn (VendorStatus $status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
                VendorProfileResource::impersonateAction(),
                VendorProfileResource::activateAction(),
                VendorProfileResource::changeStatusAction(),
            ]);
    }
}
