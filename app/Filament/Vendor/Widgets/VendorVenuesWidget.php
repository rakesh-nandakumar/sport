<?php

namespace App\Filament\Vendor\Widgets;

use App\Filament\Vendor\Resources\Venues\VenueResource;
use App\Models\Venue;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class VendorVenuesWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Your venues')
            ->query(Venue::where('user_id', auth()->id())->withCount('services'))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->description(fn (Venue $record) => $record->city.' · '.$record->services_count.' services · '.($record->isLive() ? 'Live' : ($record->is_approved ? 'Awaiting account activation' : 'Awaiting approval'))),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Manage')
                    ->url(fn (Venue $record) => VenueResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('No venues yet');
    }
}
