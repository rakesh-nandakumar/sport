<?php

namespace App\Filament\Resources\Games\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('activity_type_id')
            ->columns([
                TextColumn::make('activityType.name')
                    ->label('Activity type')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('platform')
                    ->badge()
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('max_players')
                    ->label('Max players')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('activity_type_id')
                    ->label('Activity type')
                    ->relationship('activityType', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
