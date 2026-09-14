<?php

namespace App\Filament\Vendor\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingBookingsWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $venueIds = auth()->user()->venues()->pluck('id');

        return $table
            ->heading('Upcoming bookings')
            ->query(
                Booking::whereIn('venue_id', $venueIds)
                    ->active()
                    ->where('starts_at', '>=', now())
                    ->with(['service', 'venue', 'option'])
                    ->orderBy('starts_at')
            )
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Booking $record) => BookingResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('starts_at')
                    ->label('When')
                    ->date('D d M')
                    ->description(fn (Booking $record) => $record->starts_at->format('h:i A')),
                TextColumn::make('service.name')
                    ->description(fn (Booking $record) => $record->customer_name),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus $state) => $state->label())
                    ->color(fn (BookingStatus $state) => match ($state) {
                        BookingStatus::Pending => 'warning',
                        BookingStatus::Confirmed => 'success',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No upcoming bookings');
    }
}
