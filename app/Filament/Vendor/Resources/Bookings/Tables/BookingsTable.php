<?php

namespace App\Filament\Vendor\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\Venue;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->recordUrl(fn (Booking $record) => BookingResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('starts_at')
                    ->label('When')
                    ->date('D d M')
                    ->description(fn (Booking $record) => $record->starts_at->format('h:i A').' – '.$record->ends_at->format('h:i A'))
                    ->sortable(),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->description(fn (Booking $record) => $record->venue->name.' · '.$record->option->name)
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Booking $record) => $record->customer_phone)
                    ->searchable(),
                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->formatStateUsing(fn (Booking $record) => $record->payment_method->label())
                    ->description(fn (Booking $record) => $record->payment_status->label()),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Booking $record) => $record->status->label().($record->isLocked() ? ' 🔒' : ''))
                    ->color(fn (BookingStatus $state) => match ($state) {
                        BookingStatus::Pending => 'warning',
                        BookingStatus::Confirmed => 'success',
                        BookingStatus::Completed => 'info',
                        BookingStatus::Cancelled, BookingStatus::Bumped => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => lkr((float) $state))
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('venue_id')
                    ->label('Venue')
                    ->options(fn () => Venue::where('user_id', auth()->id())->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->options(collect(BookingStatus::cases())->mapWithKeys(fn (BookingStatus $s) => [$s->value => $s->label()])),
                Filter::make('date')
                    ->schema([
                        DatePicker::make('date'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['date'] ?? null, fn (Builder $q, $date) => $q->whereDate('starts_at', $date))),
                TernaryFilter::make('pending_payment')
                    ->label('Awaiting verification')
                    ->placeholder('Any payment status')
                    ->trueLabel('Awaiting verification')
                    ->falseLabel('Not awaiting verification')
                    ->queries(
                        true: fn (Builder $q) => $q->where('payment_status', PaymentStatus::PendingVerification),
                        false: fn (Builder $q) => $q->where('payment_status', '!=', PaymentStatus::PendingVerification),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                BookingResource::confirmAction(),
                BookingResource::markPaidAction(),
                BookingResource::completeAction(),
                BookingResource::noShowAction(),
                BookingResource::cancelAction(),
            ])
            ->emptyStateHeading('No bookings yet');
    }
}
