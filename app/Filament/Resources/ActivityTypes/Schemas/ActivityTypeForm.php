<?php

namespace App\Filament\Resources\ActivityTypes\Schemas;

use App\Filament\Forms\Components\IconPicker;
use App\Models\ActivityType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ActivityTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basics')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(60),
                        IconPicker::make('icon')
                            ->required()
                            ->default('fa-solid fa-medal')
                            ->maxLength(60)
                            ->helperText('Pick a Font Awesome icon, or type a class directly, e.g. fa-solid fa-futbol.'),
                        TextInput::make('unit_label')
                            ->label('Unit label')
                            ->required()
                            ->default('Court')
                            ->maxLength(30)
                            ->helperText('What one bookable unit is called: Court, Lane, Station, Table…'),
                        ColorPicker::make('color')
                            ->required()
                            ->default('#4f46e5'),
                        Textarea::make('description')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        TextInput::make('default_slot_minutes')
                            ->label('Default slot length (minutes)')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(480)
                            ->required()
                            ->default(60),
                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(999)
                            ->required()
                            ->default(0),
                        Toggle::make('requires_game')
                            ->label('Customers pick a game')
                            ->default(false)
                            ->helperText('For gaming activities where a title must be chosen.'),
                        Toggle::make('is_featured')
                            ->label('Feature on the home page')
                            ->default(false),
                    ]),
                Section::make('Tile photo')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image_upload')
                            ->label('Upload photo')
                            ->image()
                            ->disk('public')
                            ->directory('activity-types')
                            ->maxSize(4096)
                            ->helperText('Drag & drop or paste an image. Stored on the public disk.'),
                        TextInput::make('image_url')
                            ->label('…or an image URL / path')
                            ->maxLength(500)
                            ->formatStateUsing(fn (mixed $state, ?ActivityType $record) => $record && Str::startsWith($record->image ?? '', ['http', '/']) ? $record->image : null)
                            ->helperText('For files that are not on the storage disk, e.g. /images/activities/futsal.jpg'),
                        Placeholder::make('current_image')
                            ->label('Current image')
                            ->content(fn (?ActivityType $record) => $record?->imageUrl()
                                ? new HtmlString('<img src="'.e($record->imageUrl()).'" alt="" style="height:80px;border-radius:8px">')
                                : '—')
                            ->visible(fn (?ActivityType $record) => filled($record?->image))
                            ->columnSpanFull(),
                        Toggle::make('remove_image')
                            ->label('Remove the current image')
                            ->visible(fn (?ActivityType $record) => filled($record?->image))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * An upload wins over a pasted URL/path, which wins over the remove toggle.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyImageData(array $data): array
    {
        if (filled($data['image_upload'] ?? null)) {
            $data['image'] = $data['image_upload'];
        } elseif (filled($data['image_url'] ?? null)) {
            $data['image'] = $data['image_url'];
        } elseif (! empty($data['remove_image'])) {
            $data['image'] = null;
        }

        unset($data['image_upload'], $data['image_url'], $data['remove_image']);

        return $data;
    }
}
