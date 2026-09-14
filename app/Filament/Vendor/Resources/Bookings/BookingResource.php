<?php

namespace App\Filament\Vendor\Resources\Bookings;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Filament\Vendor\Resources\Bookings\Pages\ListBookings;
use App\Filament\Vendor\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Vendor\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Vendor\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use App\Services\BookingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?string $modelLabel = 'booking';

    protected static ?string $pluralModelLabel = 'bookings';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('venue_id', auth()->user()->venues()->pluck('id'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $needsAttention = static::getEloquentQuery()
            ->whereIn('status', BookingStatus::active())
            ->where(function (Builder $q) {
                $q->where('status', BookingStatus::Pending)
                    ->orWhere('payment_status', PaymentStatus::PendingVerification);
            })
            ->count();

        return $needsAttention > 0 ? (string) $needsAttention : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function confirmAction(): Action
    {
        return Action::make('confirm')
            ->label('Confirm & lock slot')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('success')
            ->visible(fn (Booking $record) => $record->isActive() && ! $record->vendor_confirmed_at)
            ->requiresConfirmation()
            ->modalDescription('Use after calling the customer. A locked slot can no longer be replaced by a paid booking.')
            ->action(function (Booking $record): void {
                app(BookingService::class)->vendorConfirm($record);

                Notification::make()->success()->title('Booking confirmed and locked.')->send();
            });
    }

    public static function markPaidAction(): Action
    {
        return Action::make('markPaid')
            ->label(fn (Booking $record) => $record->isAwaitingVerification() ? 'Verify transfer & lock' : 'Mark as paid')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('primary')
            ->visible(fn (Booking $record) => $record->isActive() && $record->payment_status !== PaymentStatus::Paid)
            ->schema([
                TextInput::make('reference')
                    ->label('Receipt / transfer reference')
                    ->maxLength(80)
                    ->placeholder('optional'),
            ])
            ->modalDescription(fn (Booking $record) => $record->isAwaitingVerification()
                ? 'Check the slip against your bank statement first — after '.$record->hold_expires_at?->format('h:i A').' this hold expires automatically.'
                : 'Verifies a bank transfer or records cash taken at the counter. Confirms and locks the booking.')
            ->action(function (Booking $record, array $data): void {
                app(BookingService::class)->markPaid($record, auth()->user(), $data['reference'] ?? null);

                Notification::make()->success()->title('Payment recorded. Booking is confirmed.')->send();
            });
    }

    public static function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel booking')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Booking $record) => $record->isActive())
            ->requiresConfirmation()
            ->modalDescription('Cancel this booking? The customer will be notified.')
            ->schema([
                TextInput::make('reason')
                    ->label('Reason shown to the customer')
                    ->maxLength(255),
            ])
            ->action(function (Booking $record, array $data): void {
                app(BookingService::class)->cancel($record, auth()->user(), $data['reason'] ?? null);

                Notification::make()->success()->title('Booking cancelled and the customer notified.')->send();
            });
    }

    public static function completeAction(): Action
    {
        return Action::make('complete')
            ->label('Completed')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('info')
            ->visible(fn (Booking $record) => $record->isActive() && $record->ends_at->isPast())
            ->action(function (Booking $record): void {
                app(BookingService::class)->complete($record, noShow: false);

                Notification::make()->success()->title('Marked as completed.')->send();
            });
    }

    public static function noShowAction(): Action
    {
        return Action::make('noShow')
            ->label('No show')
            ->icon(Heroicon::OutlinedUserMinus)
            ->color('gray')
            ->visible(fn (Booking $record) => $record->isActive() && $record->ends_at->isPast())
            ->action(function (Booking $record): void {
                app(BookingService::class)->complete($record, noShow: true);

                Notification::make()->success()->title('Marked as no-show.')->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'view' => ViewBooking::route('/{record}'),
        ];
    }
}
