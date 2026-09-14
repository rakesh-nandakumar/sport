<?php

namespace App\Filament\Resources\VendorProfiles\Schemas;

use App\Models\ActivityType;
use App\Models\VendorProfile;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Business')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('business_name')->label('Trading name'),
                        TextEntry::make('business_type_label')
                            ->label('Business type')
                            ->state(fn (VendorProfile $record) => $record->businessTypeLabel()),
                        TextEntry::make('registration_number')->label('Registration no.')->placeholder('—'),
                        TextEntry::make('owner_nic')->label('Owner NIC'),
                        TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('activity_types')
                            ->label('Activities')
                            ->state(fn (VendorProfile $record) => ActivityType::whereIn('id', $record->activity_type_ids ?? [])->pluck('name')->join(', ') ?: '—')
                            ->columnSpanFull(),
                        TextEntry::make('years_operating')->label('Years operating')->placeholder('—'),
                        TextEntry::make('venue_count_estimate')->label('Venues (estimate)')->placeholder('—'),
                    ]),
                Section::make('Contact & address')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('contact_person'),
                        TextEntry::make('contact_phone'),
                        TextEntry::make('alt_phone')->placeholder('—'),
                        TextEntry::make('business_email'),
                        TextEntry::make('full_address')
                            ->label('Address')
                            ->state(fn (VendorProfile $record) => $record->fullAddress())
                            ->columnSpanFull(),
                        TextEntry::make('website')->placeholder('—'),
                        TextEntry::make('facebook')->placeholder('—'),
                        TextEntry::make('instagram')->placeholder('—'),
                    ]),
                Section::make('Vendor account')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')->label('Owner account'),
                        TextEntry::make('user.email')->label('Login email'),
                        TextEntry::make('user.phone')->label('Phone')->placeholder('—'),
                        TextEntry::make('user.created_at')->label('Joined')->dateTime('d M Y'),
                        TextEntry::make('venues_count')
                            ->label('Venues listed')
                            ->state(fn (VendorProfile $record) => $record->user?->venues()->count() ?? 0)
                            ->badge(),
                        TextEntry::make('br_document_path')
                            ->label('Business registration')
                            ->formatStateUsing(fn () => 'Download')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('primary')
                            ->placeholder('Not provided')
                            ->url(fn (VendorProfile $record) => $record->br_document_path ? route('admin.vendors.document', [$record, 'br']) : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('nic_document_path')
                            ->label('Owner NIC')
                            ->formatStateUsing(fn () => 'Download')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('primary')
                            ->placeholder('Not provided')
                            ->url(fn (VendorProfile $record) => $record->nic_document_path ? route('admin.vendors.document', [$record, 'nic']) : null)
                            ->openUrlInNewTab(),
                    ]),
                Section::make('Review')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')->badge(),
                        TextEntry::make('reviewed_at')->label('Reviewed at')->dateTime('d M Y H:i')->placeholder('—'),
                        TextEntry::make('reviewer.name')->label('Reviewed by')->placeholder('—'),
                        TextEntry::make('review_notes')->label('Note to the vendor')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('terms_accepted_at')->label('Terms accepted')->dateTime('d M Y H:i')->placeholder('—'),
                    ]),
            ]);
    }
}
