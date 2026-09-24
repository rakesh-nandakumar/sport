<?php

namespace App\Filament\Vendor\Resources\Venues;

use App\Filament\Vendor\Resources\Venues\Pages\CreateVenue;
use App\Filament\Vendor\Resources\Venues\Pages\EditVenue;
use App\Filament\Vendor\Resources\Venues\Pages\ListVenues;
use App\Filament\Vendor\Resources\Venues\RelationManagers\ServicesRelationManager;
use App\Filament\Vendor\Resources\Venues\Schemas\VenueForm;
use App\Filament\Vendor\Resources\Venues\Tables\VenuesTable;
use App\Models\Venue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'venue';

    protected static ?string $pluralModelLabel = 'venues';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return VenueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VenuesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ServicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVenues::route('/'),
            'create' => CreateVenue::route('/create'),
            'edit' => EditVenue::route('/{record}/edit'),
        ];
    }
}
