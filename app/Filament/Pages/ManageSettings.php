<?php

namespace App\Filament\Pages;

use App\Enums\PaymentMethod;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'payments_enabled' => array_values((array) setting('payments.enabled')),
            'bank_transfer_hold_minutes' => (int) setting('payments.bank_transfer_hold_minutes'),
            'vendors_require_activation' => (bool) setting('vendors.require_activation'),
            'venues_require_approval' => (bool) setting('venues.require_approval'),
            'max_days_ahead' => (int) setting('bookings.max_days_ahead'),
            'nearby_km' => (int) setting('location.nearby_km'),
            'amenities' => implode("\n", (array) setting('venues.amenities')),
            'support_email' => (string) setting('site.support_email'),
            'support_phone' => (string) setting('site.support_phone'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payments')
                    ->description('Which methods customers can pick at checkout, and how long a bank transfer holds a slot.')
                    ->columns(2)
                    ->schema([
                        CheckboxList::make('payments_enabled')
                            ->label('Enabled payment methods')
                            ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method) => [$method->value => "{$method->label()} — {$method->availabilityLabel()}"]))
                            ->descriptions(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->description()]))
                            ->columns(2)
                            ->required()
                            ->rules([
                                fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                                    $enabled = collect($value)
                                        ->map(fn ($selected) => PaymentMethod::tryFrom((string) $selected))
                                        ->filter(fn (?PaymentMethod $method) => $method?->isIntegrated());

                                    if ($enabled->isEmpty()) {
                                        $fail('At least one payment method must stay enabled or nobody can book.');
                                    }
                                },
                            ])
                            ->columnSpanFull(),
                        TextInput::make('bank_transfer_hold_minutes')
                            ->label('Bank-transfer verification window (minutes)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(1440)
                            ->required()
                            ->helperText('If the vendor has not verified the slip by then, the slot is released.'),
                    ]),
                Section::make('Moderation')
                    ->columns(2)
                    ->schema([
                        Toggle::make('vendors_require_activation')
                            ->label('New vendors must be activated by a Super Administrator'),
                        Toggle::make('venues_require_approval')
                            ->label('New venues must be approved before going live'),
                    ]),
                Section::make('Booking rules')
                    ->columns(2)
                    ->schema([
                        TextInput::make('max_days_ahead')
                            ->label('How far ahead customers can book (days)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->required(),
                        TextInput::make('nearby_km')
                            ->label('“Near you” radius (km)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->required(),
                    ]),
                Section::make('Venue amenities')
                    ->description('The checklist vendors tick on their venue form. One amenity per line.')
                    ->schema([
                        Textarea::make('amenities')
                            ->label('Amenities')
                            ->rows(8)
                            ->required(),
                    ]),
                Section::make('Support')
                    ->columns(2)
                    ->schema([
                        TextInput::make('support_email')
                            ->label('Support email')
                            ->email()
                            ->required(),
                        TextInput::make('support_phone')
                            ->label('Support phone')
                            ->tel()
                            ->rules(['regex:/^0\d{9}$/'])
                            ->validationMessages(['regex' => 'Enter a valid 10-digit Sri Lankan number, e.g. 0771234567.'])
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Only integrated methods can be switched on; the gateways stay "coming soon" until wired up.
        $enabled = collect($data['payments_enabled'] ?? [])
            ->map(fn ($value) => PaymentMethod::from($value))
            ->filter(fn (PaymentMethod $method) => $method->isIntegrated())
            ->map->value
            ->values()
            ->all();

        if (empty($enabled)) {
            Notification::make()
                ->danger()
                ->title('Keep at least one payment method on')
                ->body('At least one payment method must stay enabled or nobody can book.')
                ->send();

            return;
        }

        Settings::set('payments.enabled', $enabled);
        Settings::set('payments.bank_transfer_hold_minutes', (int) $data['bank_transfer_hold_minutes']);
        Settings::set('vendors.require_activation', (bool) ($data['vendors_require_activation'] ?? false));
        Settings::set('venues.require_approval', (bool) ($data['venues_require_approval'] ?? false));
        Settings::set('bookings.max_days_ahead', (int) $data['max_days_ahead']);
        Settings::set('location.nearby_km', (int) $data['nearby_km']);
        Settings::set('venues.amenities', collect(preg_split('/\r?\n/', $data['amenities']))->map(fn ($amenity) => trim($amenity))->filter()->unique()->values()->all());
        Settings::set('site.support_email', $data['support_email']);
        Settings::set('site.support_phone', $data['support_phone']);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
