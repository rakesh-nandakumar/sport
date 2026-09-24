<?php

namespace App\Filament\Resources\Venues\Tables;

use App\Enums\VendorStatus;
use App\Filament\Resources\Venues\VenueResource;
use App\Models\Venue;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VenuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Venue $record) => VenueResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Venue $record) => $record->tagline)
                    ->limit(40),
                TextColumn::make('owner.name')
                    ->label('Vendor')
                    ->description(fn (Venue $record) => $record->owner?->email)
                    ->searchable(),
                TextColumn::make('vendor_status')
                    ->label('Vendor status')
                    ->badge()
                    ->state(fn (Venue $record) => $record->owner?->vendorStatus())
                    ->formatStateUsing(fn (?VendorStatus $state) => $state?->label() ?? '—')
                    ->color(fn (?VendorStatus $state) => match ($state) {
                        VendorStatus::Pending => 'warning',
                        VendorStatus::Active => 'success',
                        VendorStatus::Suspended => 'gray',
                        VendorStatus::Rejected => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Venue $record) => $record->district),
                TextColumn::make('visibility')
                    ->label('Visibility')
                    ->badge()
                    ->state(fn (Venue $record) => $record->isLive() ? 'Live' : ($record->is_approved ? 'Vendor inactive' : 'Hidden'))
                    ->color(fn (Venue $record) => $record->isLive() ? 'success' : ($record->is_approved ? 'warning' : 'gray')),
                TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label('Approval')
                    ->placeholder('All venues')
                    ->trueLabel('Approved')
                    ->falseLabel('Hidden / not approved'),
                TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->placeholder('All venues')
                    ->trueLabel('Featured')
                    ->falseLabel('Not featured'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('toggleApproval')
                    ->label(fn (Venue $record) => $record->is_approved ? 'Hide' : 'Approve')
                    ->icon(fn (Venue $record) => $record->is_approved ? 'heroicon-o-eye-slash' : 'heroicon-o-check-circle')
                    ->color(fn (Venue $record) => $record->is_approved ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Venue $record) => $record->is_approved ? 'Hide this venue?' : 'Approve this venue?')
                    ->modalDescription(fn (Venue $record) => $record->is_approved
                        ? 'Customers will no longer see this venue until it is approved again.'
                        : 'The venue goes live as soon as its vendor account is active.')
                    ->action(function (Venue $record): void {
                        $record->update(['is_approved' => ! $record->is_approved]);

                        Notification::make()
                            ->title($record->is_approved ? "{$record->name} is now live." : "{$record->name} has been hidden from customers.")
                            ->success()
                            ->send();
                    }),
                Action::make('toggleFeatured')
                    ->label(fn (Venue $record) => $record->is_featured ? 'Unfeature' : 'Feature')
                    ->icon('heroicon-o-star')
                    ->color(fn (Venue $record) => $record->is_featured ? 'gray' : 'warning')
                    ->requiresConfirmation()
                    ->action(function (Venue $record): void {
                        $record->update(['is_featured' => ! $record->is_featured]);

                        Notification::make()
                            ->title($record->is_featured ? "{$record->name} is now featured." : "{$record->name} removed from featured.")
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->modalDescription('This deletes the venue with all its services, hours and bookings. This cannot be undone.'),
            ]);
    }
}
