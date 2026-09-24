<?php

namespace App\Filament\Vendor\Resources\Venues\RelationManagers;

use App\Filament\Vendor\Resources\Venues\Schemas\ServiceForm;
use App\Models\Service;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Services';

    public function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('activityType.name')
                    ->label('Activity')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn (Service $record) => $record->description),
                TextColumn::make('slot_minutes')
                    ->label('Block')
                    ->formatStateUsing(fn (Service $record) => $record->slotLabel()),
                TextColumn::make('price_from')
                    ->label('From')
                    ->state(fn (Service $record) => $record->priceFrom())
                    ->formatStateUsing(fn (?float $state) => $state !== null ? lkr($state) : '—'),
                TextColumn::make('options_count')
                    ->label('Options')
                    ->counts('options')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, RelationManager $livewire): Service {
                        return DB::transaction(function () use ($data, $livewire): Service {
                            $service = $livewire->getOwnerRecord()->services()->create(ServiceForm::serviceAttributes($data));
                            ServiceForm::syncChildren($service, $data);

                            return $service;
                        });
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Service $record): array => [...$data, ...ServiceForm::editData($record)])
                    ->using(function (Service $record, array $data): Service {
                        return DB::transaction(function () use ($record, $data): Service {
                            $record->update(ServiceForm::serviceAttributes($data));
                            ServiceForm::syncChildren($record, $data);

                            return $record;
                        });
                    }),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No services yet')
            ->emptyStateDescription("Add what's bookable at this venue — a court, a lane, a gaming station…");
    }
}
