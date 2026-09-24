<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('role_id')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?Role $state) => $state?->label() ?? '—')
                    ->color(fn (?Role $state) => match ($state) {
                        Role::SuperAdministrator => 'danger',
                        Role::Vendor => 'warning',
                        Role::Moderator => 'info',
                        Role::MarketingManager => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('venues_count')
                    ->label('Venues')
                    ->counts('venues')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('pay_at_venue_banned_at')
                    ->label('Pay at Venue')
                    ->state(fn (User $record) => $record->isPayAtVenueBanned() ? 'Restricted' : 'Available')
                    ->description(fn (User $record) => $record->pay_at_venue_ban_note ?: null)
                    ->badge()
                    ->color(fn (User $record) => $record->isPayAtVenueBanned() ? 'danger' : 'success'),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->label('Role')
                    ->options(collect(Role::cases())->mapWithKeys(fn (Role $role) => [$role->value => $role->label()])),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('restorePayAtVenue')
                    ->label('Restore Pay at Venue')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn (User $record) => auth()->user()?->isAdmin() && $record->isPayAtVenueBanned())
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record) => 'Lift the Pay at Venue restriction for '.$record->name.'? Their account note will record that an administrator restored access.')
                    ->action(function (User $record): void {
                        $record->unbanPayAtVenue();
                        Notification::make()->success()->title('Pay at Venue restored for '.$record->name.'.')->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (User $record) => $record->id !== auth()->id()),
            ]);
    }
}
