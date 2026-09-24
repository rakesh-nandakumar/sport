<?php

namespace App\Filament\Vendor\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Booking')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('reference')->copyable(),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (BookingStatus $state) => $state->label())
                            ->color(fn (BookingStatus $state) => match ($state) {
                                BookingStatus::Pending => 'warning',
                                BookingStatus::Confirmed => 'success',
                                BookingStatus::Completed => 'info',
                                BookingStatus::Cancelled, BookingStatus::Bumped => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('total')->formatStateUsing(fn ($state) => lkr((float) $state)),
                        TextEntry::make('service.name')->label('Service')->state(fn (Booking $record) => $record->service->name.' · '.$record->option->name.($record->game ? ' · 🎮 '.$record->game->name : '')),
                        TextEntry::make('venue.name')->label('Venue'),
                        TextEntry::make('when')->label('When')->state(fn (Booking $record) => $record->timeRangeLabel().' ('.$record->durationLabel().')'),
                        TextEntry::make('customer_name')->label('Customer')->state(fn (Booking $record) => $record->customer_name.' · '.$record->customer_phone),
                        TextEntry::make('user.email')->label('Email'),
                        TextEntry::make('players')->placeholder('—'),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('nic_front_path')
                            ->label('NIC front')
                            ->state(fn (Booking $record) => $record->nic_front_path ? 'View NIC front' : 'Not provided')
                            ->url(fn (Booking $record) => $record->nic_front_path ? route('bookings.identity.show', [$record, 'front']) : null)
                            ->openUrlInNewTab()
                            ->visible(fn (Booking $record) => $record->payment_method->value === 'pay_at_venue'),
                        TextEntry::make('nic_back_path')
                            ->label('NIC back')
                            ->state(fn (Booking $record) => $record->nic_back_path ? 'View NIC back' : 'Not provided')
                            ->url(fn (Booking $record) => $record->nic_back_path ? route('bookings.identity.show', [$record, 'back']) : null)
                            ->openUrlInNewTab()
                            ->visible(fn (Booking $record) => $record->payment_method->value === 'pay_at_venue'),
                        TextEntry::make('created_at')->label('Placed')->dateTime('d M Y, h:i A'),
                        TextEntry::make('vendor_confirmed_at')->label('Confirmed')->dateTime('d M Y, h:i A')->placeholder('Not yet'),
                        TextEntry::make('hold_status')
                            ->label('Verification hold')
                            ->visible(fn (Booking $record) => $record->isAwaitingVerification())
                            ->state(fn (Booking $record) => 'Verify within '.$record->holdMinutesLeft().' min (by '.$record->hold_expires_at?->format('h:i A').')')
                            ->badge()
                            ->color(fn (Booking $record) => $record->holdMinutesLeft() <= 5 ? 'danger' : 'warning'),
                        TextEntry::make('cancel_reason')
                            ->label(fn (Booking $record) => $record->status->label())
                            ->visible(fn (Booking $record) => filled($record->cancelled_at))
                            ->state(fn (Booking $record) => $record->cancelled_at?->format('d M Y, h:i A').($record->cancel_reason ? ' — '.$record->cancel_reason : '').($record->bumpedBy ? ' (replaced by '.$record->bumpedBy->reference.')' : ''))
                            ->columnSpanFull(),
                    ]),
                Section::make('Price breakdown')
                    ->schema([
                        TextEntry::make('price_breakdown')
                            ->hiddenLabel()
                            ->state(fn (Booking $record) => collect($record->price_breakdown ?? [])
                                ->map(fn (array $line) => "{$line['label']}: ".lkr((float) $line['amount']))
                                ->push('Total: '.lkr((float) $record->total))
                                ->all())
                            ->listWithLineBreaks()
                            ->columnSpanFull(),
                    ]),
                Section::make('Payments')
                    ->visible(fn (Booking $record) => $record->payments->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('method')->formatStateUsing(fn ($state) => $state?->label()),
                                TextEntry::make('amount')->formatStateUsing(fn ($state) => lkr((float) $state)),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (PaymentStatus $state) => $state->label())
                                    ->color(fn (PaymentStatus $state) => match ($state) {
                                        PaymentStatus::Paid => 'success',
                                        PaymentStatus::PendingVerification => 'warning',
                                        PaymentStatus::Refunded => 'info',
                                        default => 'gray',
                                    }),
                                TextEntry::make('reference')->placeholder('—'),
                                TextEntry::make('proof_path')
                                    ->label('Slip')
                                    ->placeholder('—')
                                    ->formatStateUsing(fn () => 'View slip')
                                    ->url(fn (Payment $record) => $record->proofUrl())
                                    ->visible(fn (Payment $record) => filled($record->proof_path))
                                    ->openUrlInNewTab()
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
