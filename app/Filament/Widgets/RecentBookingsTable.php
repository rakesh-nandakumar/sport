<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentBookingsTable extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent bookings')
            ->query(Booking::query()->with(['user', 'venue', 'service'])->latest())
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Booking $record) => $record->user?->email)
                    ->searchable(),
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus $state) => $state->label())
                    ->color(fn (BookingStatus $state) => match ($state) {
                        BookingStatus::Pending => 'warning',
                        BookingStatus::Confirmed => 'success',
                        BookingStatus::Completed => 'info',
                        BookingStatus::Cancelled, BookingStatus::Bumped => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state) => $state->label())
                    ->color(fn (PaymentStatus $state) => match ($state) {
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::PendingVerification => 'warning',
                        PaymentStatus::Refunded => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => lkr((float) $state)),
            ]);
    }
}
