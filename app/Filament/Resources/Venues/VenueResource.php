<?php

namespace App\Filament\Resources\Venues;

use App\Filament\Resources\Venues\Pages\ListVenues;
use App\Filament\Resources\Venues\Pages\ViewVenue;
use App\Filament\Resources\Venues\Schemas\VenueInfolist;
use App\Filament\Resources\Venues\Tables\VenuesTable;
use App\Models\Venue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'venue';

    protected static ?string $pluralModelLabel = 'venues';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return VenueInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VenuesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $hidden = static::getModel()::query()->where('is_approved', false)->count();

        return $hidden > 0 ? (string) $hidden : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Venues hidden from customers';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVenues::route('/'),
            'view' => ViewVenue::route('/{record}'),
        ];
    }
}
