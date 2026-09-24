<?php

namespace App\Filament\Resources\Games\Schemas;

use App\Models\ActivityType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('activity_type_id')
                    ->label('Activity type')
                    ->options(fn () => ActivityType::where('requires_game', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->helperText('Only activities that require a game choice can be selected.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                TextInput::make('platform')
                    ->maxLength(40)
                    ->placeholder('PS5, Xbox Series X, PC, VR…'),
                TextInput::make('max_players')
                    ->label('Max players')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(32),
            ]);
    }
}
