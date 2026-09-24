<?php

namespace App\Filament\Vendor\Resources\Venues\Schemas;

use App\Models\ActivityType;
use App\Models\Game;
use App\Models\Service;
use App\Models\ServiceOption;
use App\Models\ServiceRate;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ServiceForm
{
    public const DAY_LETTERS = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('What is it?')
                    ->columns(2)
                    ->schema([
                        Select::make('activity_type_id')
                            ->label('Activity type')
                            ->options(fn () => ActivityType::orderBy('sort_order')->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->searchable()
                            ->helperText('Missing one? Ask a Super Administrator to add it under Catalogue → Activity types.')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $type = ActivityType::find($get('activity_type_id'));
                                if ($type && ! $get('slot_minutes')) {
                                    $set('slot_minutes', $type->default_slot_minutes);
                                }
                            }),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->placeholder('e.g. Court A · PS5 Station 1 · Lane 3 · Paintball Session'),
                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(2000)
                            ->placeholder('Surface, size, equipment included, rules…')
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label('Photo')
                            ->image()
                            ->disk('public')
                            ->directory('services')
                            ->maxSize(4096),
                        TextInput::make('max_players')
                            ->label('Max players')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(200)
                            ->placeholder('optional'),
                        Toggle::make('is_active')
                            ->label('Accepting bookings')
                            ->default(true),
                    ]),
                Section::make('Booking rules')
                    ->columns(4)
                    ->schema([
                        TextInput::make('slot_minutes')
                            ->label('Block size (min)')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(480)
                            ->required()
                            ->default(60)
                            ->helperText('Smallest bookable unit.'),
                        TextInput::make('min_slots')
                            ->label('Min blocks')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(48)
                            ->required()
                            ->default(1),
                        TextInput::make('max_slots')
                            ->label('Max blocks')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(48)
                            ->gte('min_slots')
                            ->placeholder('no limit'),
                        TextInput::make('buffer_minutes')
                            ->label('Gap between bookings (min)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(120)
                            ->required()
                            ->default(0)
                            ->helperText('Cleaning / reset time.'),
                        TextInput::make('lead_time_minutes')
                            ->label('Minimum notice (min)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10080)
                            ->required()
                            ->default(60)
                            ->helperText('How far ahead customers must book.'),
                        TimePicker::make('opens_at')
                            ->label('Opens at (override)')
                            ->seconds(false),
                        TimePicker::make('closes_at')
                            ->label('Closes at (override)')
                            ->seconds(false)
                            ->helperText('Leave blank to use venue hours.'),
                    ]),
                Section::make('Options & pricing')
                    ->description('Seat types, court sizes, packages.')
                    ->schema([
                        Repeater::make('options')
                            ->hiddenLabel()
                            ->addActionLabel('Add option')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->columns(5)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->default([[
                                'name' => 'Standard', 'description' => null, 'price_per_slot' => null, 'capacity' => 1, 'is_default' => true,
                            ]])
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(80)
                                    ->placeholder('Standard'),
                                TextInput::make('description')
                                    ->maxLength(160)
                                    ->placeholder('optional'),
                                TextInput::make('price_per_slot')
                                    ->label('Price / block (Rs)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(50)
                                    ->required(),
                                TextInput::make('capacity')
                                    ->label('Units')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->default(1)
                                    ->required()
                                    ->helperText('Identical units — 3 PS5 stations under one option means 3 customers can book the same time.'),
                                Toggle::make('is_default')
                                    ->label('Default'),
                            ]),
                    ]),
                Section::make('Peak-hour rates')
                    ->description('Optional multipliers by day & time. 1.25 = 25% more, 0.8 = 20% off-peak.')
                    ->schema([
                        Repeater::make('rates')
                            ->hiddenLabel()
                            ->addActionLabel('Add rate')
                            ->columns(5)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->schema([
                                TextInput::make('name')
                                    ->maxLength(60)
                                    ->placeholder('Weekend evenings'),
                                CheckboxList::make('days')
                                    ->hiddenLabel()
                                    ->options(fn () => self::DAY_LETTERS)
                                    ->columns(4)
                                    ->bulkToggleable(),
                                TimePicker::make('starts_at')
                                    ->seconds(false),
                                TimePicker::make('ends_at')
                                    ->seconds(false),
                                TextInput::make('multiplier')
                                    ->numeric()
                                    ->minValue(0.1)
                                    ->maxValue(5)
                                    ->step(0.05)
                                    ->default(1.25),
                            ]),
                    ]),
                Section::make('Games available')
                    ->description('For gaming activities.')
                    ->visible(fn (Get $get) => (bool) ActivityType::find($get('activity_type_id'))?->requires_game)
                    ->schema([
                        CheckboxList::make('games')
                            ->hiddenLabel()
                            ->options(fn (Get $get) => Game::where('activity_type_id', $get('activity_type_id'))->orderBy('name')->pluck('name', 'id'))
                            ->descriptions(fn (Get $get) => Game::where('activity_type_id', $get('activity_type_id'))->orderBy('name')->pluck('platform', 'id'))
                            ->columns(2)
                            ->bulkToggleable(),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function serviceAttributes(array $data): array
    {
        return collect($data)->only([
            'activity_type_id', 'name', 'description', 'image', 'slot_minutes', 'min_slots', 'max_slots',
            'buffer_minutes', 'lead_time_minutes', 'max_players', 'opens_at', 'closes_at', 'is_active',
        ])->all();
    }

    /**
     * Create/replace the options, rates and games belonging to a service from the repeater state.
     * The "default" option is the first row ticked "Default" (or the first row, if none is).
     *
     * @param  array<string, mixed>  $data
     */
    public static function syncChildren(Service $service, array $data): void
    {
        $optionRows = array_values($data['options'] ?? []);
        $defaultIndex = collect($optionRows)->search(fn (array $row) => ! empty($row['is_default']));
        $defaultIndex = $defaultIndex === false ? 0 : $defaultIndex;

        $keepOptions = [];
        foreach ($optionRows as $i => $row) {
            $option = ! empty($row['id']) ? $service->options()->find($row['id']) : null;
            $option = $option ?: new ServiceOption(['service_id' => $service->id]);
            $option->fill([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'price_per_slot' => $row['price_per_slot'],
                'capacity' => $row['capacity'],
                'sort_order' => $i,
                'is_default' => $i === $defaultIndex,
            ])->save();
            $keepOptions[] = $option->id;
        }
        $service->options()->whereNotIn('id', $keepOptions ?: [0])->delete();

        $keepRates = [];
        foreach ($data['rates'] ?? [] as $row) {
            if (empty($row['name'])) {
                continue;
            }
            $rate = ! empty($row['id']) ? $service->rates()->find($row['id']) : null;
            $rate = $rate ?: new ServiceRate(['service_id' => $service->id]);
            $rate->fill([
                'name' => $row['name'],
                'days' => array_map('intval', $row['days'] ?? []),
                'starts_at' => $row['starts_at'],
                'ends_at' => $row['ends_at'],
                'multiplier' => $row['multiplier'],
            ])->save();
            $keepRates[] = $rate->id;
        }
        $service->rates()->whereNotIn('id', $keepRates ?: [0])->delete();

        $service->games()->sync($data['games'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public static function editData(Service $service): array
    {
        $service->loadMissing(['options', 'rates', 'games']);

        return [
            'options' => $service->options->map(fn (ServiceOption $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'description' => $o->description,
                'price_per_slot' => (float) $o->price_per_slot,
                'capacity' => $o->capacity,
                'is_default' => $o->is_default,
            ])->all(),
            'rates' => $service->rates->map(fn (ServiceRate $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'days' => $r->days,
                'starts_at' => substr($r->starts_at, 0, 5),
                'ends_at' => substr($r->ends_at, 0, 5),
                'multiplier' => (float) $r->multiplier,
            ])->all(),
            'games' => $service->games->pluck('id')->all(),
        ];
    }
}
