<?php

namespace App\Filament\Resources\Venues\Schemas;

use App\Enums\PaymentMethod;
use App\Models\Venue;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VenueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Venue')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('tagline')->placeholder('—'),
                        TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('address'),
                        TextEntry::make('city'),
                        TextEntry::make('district'),
                        TextEntry::make('postal_code')->placeholder('—'),
                        TextEntry::make('phone'),
                        TextEntry::make('email')->placeholder('—'),
                        TextEntry::make('website')->placeholder('—'),
                        TextEntry::make('amenities')
                            ->badge()
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Vendor')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('owner.name')->label('Vendor account'),
                        TextEntry::make('owner.email')->label('Email'),
                        TextEntry::make('owner.vendorProfile.business_name')->label('Business name')->placeholder('No vendor profile'),
                        TextEntry::make('owner_vendor_status')
                            ->label('Vendor status')
                            ->state(fn ($record) => $record->owner?->vendorStatus()?->label() ?? '—')
                            ->badge(),
                        TextEntry::make('services_count')->label('Services')->state(fn ($record) => $record->services()->count())->badge(),
                        TextEntry::make('bookings_count')->label('Bookings')->state(fn ($record) => $record->bookings()->count())->badge(),
                    ]),
                Section::make('Moderation')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('is_approved')->label('Approved')->badge()
                            ->formatStateUsing(fn (bool $state) => $state ? 'Approved' : 'Hidden')
                            ->color(fn (bool $state) => $state ? 'success' : 'gray'),
                        TextEntry::make('is_featured')->label('Featured')->badge()
                            ->formatStateUsing(fn (bool $state) => $state ? 'Featured' : 'No')
                            ->color(fn (bool $state) => $state ? 'warning' : 'gray'),
                        TextEntry::make('is_live')->label('Customer visibility')->badge()
                            ->state(fn ($record) => $record->isLive() ? 'Live' : 'Not live')
                            ->color(fn ($record) => $record->isLive() ? 'success' : 'gray'),
                        TextEntry::make('created_at')->label('Added')->dateTime('d M Y H:i'),
                        TextEntry::make('updated_at')->label('Last updated')->dateTime('d M Y H:i'),
                    ]),
                Section::make('Bank details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('allowed_payment_methods')
                            ->label('Allowed payment methods')
                            ->state(fn (Venue $record) => $record->allowed_payment_methods === null
                                ? 'Uses site defaults'
                                : collect($record->allowed_payment_methods)->map(fn (string $value) => PaymentMethod::tryFrom($value)?->label() ?? $value)->join(', '))
                            ->helperText('Managed by the Super Administrator. Site-wide restrictions also apply.')
                            ->columnSpanFull(),
                        TextEntry::make('bank_name')->placeholder('—'),
                        TextEntry::make('bank_branch')->placeholder('—'),
                        TextEntry::make('bank_account_name')->placeholder('—'),
                        TextEntry::make('bank_account_number')->placeholder('—'),
                    ]),
            ]);
    }
}
