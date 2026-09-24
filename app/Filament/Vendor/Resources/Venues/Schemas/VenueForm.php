<?php

namespace App\Filament\Vendor\Resources\Venues\Schemas;

use App\Models\Venue;
use App\Models\VenueHour;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class VenueForm
{
    public static function configure(Schema $schema): Schema
    {
        $profile = fn () => auth()->user()->vendorProfile;

        return $schema
            ->components([
                Section::make('Status')
                    ->visible(fn (?Venue $record) => $record?->exists)
                    ->schema([
                        Placeholder::make('visibility')
                            ->label('Customer visibility')
                            ->content(function (?Venue $record) {
                                if (! $record) {
                                    return '—';
                                }

                                return match (true) {
                                    $record->isLive() => new HtmlString('<span class="fi-badge fi-color-success">Live — customers can see and book this venue</span>'),
                                    $record->is_approved => new HtmlString('<span class="fi-badge fi-color-warning">Hidden — waiting for your vendor account to be activated</span>'),
                                    default => new HtmlString('<span class="fi-badge fi-color-gray">Hidden — waiting for admin approval</span>'),
                                };
                            }),
                    ]),
                Section::make('Basics')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->default(fn () => $profile()?->business_name),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->required()
                            ->rules(['regex:/^0\d{9}$/'])
                            ->validationMessages(['regex' => 'Enter a valid 10-digit number, e.g. 0112345678.'])
                            ->placeholder('0112345678')
                            ->default(fn () => $profile()?->contact_phone),
                        TextInput::make('tagline')
                            ->maxLength(160)
                            ->placeholder("e.g. Colombo's biggest indoor futsal & badminton complex")
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(4)
                            ->maxLength(5000)
                            ->default(fn () => $profile()?->description)
                            ->columnSpanFull(),
                        TextInput::make('address')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => $profile() ? trim($profile()->address_line1.($profile()->address_line2 ? ', '.$profile()->address_line2 : '')) : null),
                        TextInput::make('city')
                            ->required()
                            ->maxLength(80)
                            ->default(fn () => $profile()?->city ?? 'Colombo'),
                        Select::make('district')
                            ->options(array_combine(array_keys(config('entrypoint.districts')), array_keys(config('entrypoint.districts'))))
                            ->required()
                            ->searchable()
                            ->default(fn () => $profile()?->district ?? 'Colombo'),
                        TextInput::make('postal_code')
                            ->label('Postal code')
                            ->rules(['digits:5'])
                            ->default(fn () => $profile()?->postal_code),
                        TextInput::make('latitude')
                            ->numeric()
                            ->minValue(5.5)
                            ->maxValue(10.5)
                            ->rules(['required_with:longitude'])
                            ->validationMessages(['min' => 'The map pin must be inside Sri Lanka.', 'max' => 'The map pin must be inside Sri Lanka.'])
                            ->placeholder('e.g. 6.88340')
                            ->default(fn () => $profile()?->latitude)
                            ->helperText('Powers "near you" search and the Google Maps link. Right-click a spot in Google Maps and copy the coordinates.'),
                        TextInput::make('longitude')
                            ->numeric()
                            ->minValue(79)
                            ->maxValue(82.5)
                            ->rules(['required_with:latitude'])
                            ->validationMessages(['min' => 'The map pin must be inside Sri Lanka.', 'max' => 'The map pin must be inside Sri Lanka.'])
                            ->placeholder('e.g. 79.86600')
                            ->default(fn () => $profile()?->longitude),
                        TextInput::make('email')
                            ->email()
                            ->default(fn () => $profile()?->business_email),
                        TextInput::make('website')
                            ->url()
                            ->placeholder('https://')
                            ->default(fn () => $profile()?->website),
                    ]),
                Section::make('Opening hours')
                    ->description('Services inherit these unless they set their own.')
                    ->schema([
                        Repeater::make('hours')
                            ->hiddenLabel()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(4)
                            ->default(fn (?Venue $record) => static::defaultHours($record))
                            ->itemLabel(fn (array $state): string => VenueHour::DAYS[$state['day'] ?? 0] ?? '')
                            ->schema([
                                Hidden::make('day'),
                                Placeholder::make('day_label')
                                    ->hiddenLabel()
                                    ->content(fn (Get $get): string => VenueHour::DAYS[$get('day')] ?? ''),
                                TimePicker::make('opens_at')
                                    ->seconds(false),
                                TimePicker::make('closes_at')
                                    ->seconds(false),
                                Toggle::make('is_closed')
                                    ->label('Closed'),
                            ]),
                    ]),
                Section::make('Amenities')
                    ->schema([
                        CheckboxList::make('amenities')
                            ->hiddenLabel()
                            ->options(fn () => array_combine(setting('venues.amenities'), setting('venues.amenities')))
                            ->columns(2),
                    ]),
                Section::make('Bank details')
                    ->description('Shown to customers paying by bank transfer.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bank_name')->label('Bank')->maxLength(80)->placeholder('Commercial Bank'),
                        TextInput::make('bank_branch')->label('Branch')->maxLength(80),
                        TextInput::make('bank_account_name')->label('Account name')->maxLength(120),
                        TextInput::make('bank_account_number')->label('Account number')->maxLength(40),
                    ]),
                Section::make('Cover photo')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('cover_upload')
                            ->label('Upload photo')
                            ->image()
                            ->disk('public')
                            ->directory('venues')
                            ->maxSize(4096)
                            ->helperText('Drag & drop or paste an image, JPG/PNG/WebP up to 4 MB.'),
                        Placeholder::make('current_cover')
                            ->label('Current photo')
                            ->content(fn (?Venue $record) => $record?->cover_image
                                ? new HtmlString('<img src="'.e($record->coverUrl()).'" alt="" style="height:80px;border-radius:8px">')
                                : '—')
                            ->visible(fn (?Venue $record) => filled($record?->cover_image)),
                    ]),
            ]);
    }

    /**
     * @return array<int, array{day: int, opens_at: ?string, closes_at: ?string, is_closed: bool}>
     */
    public static function defaultHours(?Venue $venue): array
    {
        return collect(range(0, 6))->map(function (int $day) use ($venue) {
            $hours = $venue?->hoursFor($day);

            return [
                'day' => $day,
                'opens_at' => $hours?->opens_at ? substr($hours->opens_at, 0, 5) : '08:00',
                'closes_at' => $hours?->closes_at ? substr($hours->closes_at, 0, 5) : '22:00',
                'is_closed' => (bool) ($hours?->is_closed),
            ];
        })->all();
    }

    /**
     * Strip the non-column fields (hours, cover upload) from the data going into Venue::create/update,
     * and resolve the cover photo. Hours are persisted separately via syncHours() from the page's
     * afterCreate()/afterSave() hook, once the venue has an id.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyCoverData(array $data): array
    {
        if (filled($data['cover_upload'] ?? null)) {
            $data['cover_image'] = $data['cover_upload'];
        }

        unset($data['cover_upload'], $data['current_cover'], $data['hours'], $data['visibility']);
        $data['amenities'] = array_values($data['amenities'] ?? []);

        return $data;
    }

    /**
     * @param  array<int, array{day: int, opens_at: ?string, closes_at: ?string, is_closed: bool}>  $hours
     */
    public static function syncHours(Venue $venue, array $hours): void
    {
        foreach ($hours as $row) {
            VenueHour::updateOrCreate(
                ['venue_id' => $venue->id, 'day_of_week' => $row['day']],
                [
                    'opens_at' => $row['opens_at'] ?? null,
                    'closes_at' => $row['closes_at'] ?? null,
                    'is_closed' => (bool) ($row['is_closed'] ?? false),
                ],
            );
        }
    }
}
