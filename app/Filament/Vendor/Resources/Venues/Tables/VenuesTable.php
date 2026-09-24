<?php

namespace App\Filament\Vendor\Resources\Venues\Tables;

use App\Models\Venue;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VenuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('')
                    ->getStateUsing(fn (Venue $record) => $record->coverUrl())
                    ->checkFileExistence(false)
                    ->imageSize(48)
                    ->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Venue $record) => $record->tagline),
                TextColumn::make('city')
                    ->searchable()
                    ->description(fn (Venue $record) => $record->district),
                TextColumn::make('visibility')
                    ->label('Visibility')
                    ->badge()
                    ->state(fn (Venue $record) => $record->isLive() ? 'Live' : ($record->is_approved ? 'Awaiting account activation' : 'Awaiting approval'))
                    ->color(fn (Venue $record) => $record->isLive() ? 'success' : 'warning'),
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('This deletes the venue with all its services, hours and bookings. This cannot be undone.'),
            ])
            ->emptyStateHeading('No venues yet')
            ->emptyStateDescription('Add your first venue to start taking bookings.');
    }
}
