<?php

namespace App\Filament\Vendor\Widgets;

use App\Enums\PaymentStatus;
use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiringHoldsWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $venueIds = auth()->user()->venues()->pluck('id');

        return Booking::whereIn('venue_id', $venueIds)
            ->active()
            ->whereNotNull('hold_expires_at')
            ->whereNull('vendor_confirmed_at')
            ->where('payment_status', PaymentStatus::PendingVerification)
            ->exists();
    }

    public function table(Table $table): Table
    {
        $venueIds = auth()->user()->venues()->pluck('id');

        return $table
            ->heading('Bank transfers waiting for your verification')
            ->query(
                Booking::whereIn('venue_id', $venueIds)
                    ->active()
                    ->whereNotNull('hold_expires_at')
                    ->whereNull('vendor_confirmed_at')
                    ->where('payment_status', PaymentStatus::PendingVerification)
                    ->with(['service', 'payments'])
                    ->orderBy('hold_expires_at')
            )
            ->paginated(false)
            ->recordUrl(fn (Booking $record) => BookingResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference')->fontFamily('mono'),
                TextColumn::make('customer_name')->label('Customer'),
                TextColumn::make('service.name')->label('Service')
                    ->description(fn (Booking $record) => $record->starts_at->format('D d M, h:i A')),
                TextColumn::make('total')->formatStateUsing(fn ($state) => lkr((float) $state)),
                TextColumn::make('slip')
                    ->label('Slip')
                    ->state(fn (Booking $record) => $record->payments->last()?->proof_path ? 'Uploaded' : 'Not yet')
                    ->badge()
                    ->color(fn (Booking $record) => $record->payments->last()?->proof_path ? 'success' : 'gray'),
                TextColumn::make('hold_expires_at')
                    ->label('Time left')
                    ->state(fn (Booking $record) => $record->holdMinutesLeft().' min left')
                    ->badge()
                    ->color(fn (Booking $record) => $record->holdMinutesLeft() <= 5 ? 'danger' : 'warning'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
