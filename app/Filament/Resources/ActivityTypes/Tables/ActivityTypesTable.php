<?php

namespace App\Filament\Resources\ActivityTypes\Tables;

use App\Models\ActivityType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Photo')
                    ->getStateUsing(fn (ActivityType $record) => $record->imageUrl() ? url($record->imageUrl()) : null)
                    ->checkFileExistence(false)
                    ->imageSize(40)
                    ->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_label')
                    ->label('Unit')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('default_slot_minutes')
                    ->label('Slot')
                    ->suffix(' min')
                    ->sortable(),
                TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('games_count')
                    ->label('Games')
                    ->counts('games')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('requires_game')
                    ->label('Games?')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Activity types that still have services listed by vendors cannot be deleted.')
                    ->action(function (ActivityType $record, DeleteAction $action): void {
                        if ($record->services()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Activity still in use')
                                ->body('Vendors still list services under this activity. Reassign them first.')
                                ->send();

                            $action->cancel();

                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Activity type deleted')
                            ->send();
                    }),
            ]);
    }
}
