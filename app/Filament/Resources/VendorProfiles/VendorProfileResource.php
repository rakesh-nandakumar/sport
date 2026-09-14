<?php

namespace App\Filament\Resources\VendorProfiles;

use App\Enums\VendorStatus;
use App\Filament\Resources\VendorProfiles\Pages\ListVendorProfiles;
use App\Filament\Resources\VendorProfiles\Pages\ViewVendorProfile;
use App\Filament\Resources\VendorProfiles\Schemas\VendorProfileInfolist;
use App\Filament\Resources\VendorProfiles\Tables\VendorProfilesTable;
use App\Models\VendorProfile;
use App\Notifications\BookingNotification;
use App\Support\Impersonation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class VendorProfileResource extends Resource
{
    protected static ?string $model = VendorProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?string $slug = 'vendors';

    protected static ?string $recordTitleAttribute = 'business_name';

    protected static ?string $navigationLabel = 'Vendors';

    protected static ?string $modelLabel = 'vendor';

    protected static ?string $pluralModelLabel = 'vendors';

    protected static ?int $navigationSort = 0;

    public static function infolist(Schema $schema): Schema
    {
        return VendorProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorProfilesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()->where('status', VendorStatus::Pending->value)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Vendor applications waiting for review';
    }

    /** Activate / suspend / reject with a note the vendor can read on their dashboard. */
    public static function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->color('warning')
            ->visible(fn () => auth()->user()?->isAdmin())
            ->schema([
                Select::make('status')
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn (VendorStatus $status) => [$status->value => $status->label()]))
                    ->default(fn (VendorProfile $record) => $record->status->value)
                    ->required()
                    ->live(),
                Textarea::make('review_notes')
                    ->label('Note to the vendor')
                    ->helperText('Shown to the vendor on their dashboard. Required when suspending or rejecting.')
                    ->requiredIf('status', [VendorStatus::Suspended->value, VendorStatus::Rejected->value])
                    ->maxLength(1000),
            ])
            ->action(function (VendorProfile $record, array $data): void {
                static::applyStatus($record, VendorStatus::from($data['status']), $data['review_notes'] ?? null);
            });
    }

    public static function activateAction(): Action
    {
        return Action::make('activate')
            ->label('Activate')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (VendorProfile $record) => auth()->user()?->isAdmin() && $record->status !== VendorStatus::Active)
            ->requiresConfirmation()
            ->modalHeading('Activate this vendor?')
            ->modalDescription('Their venues become visible to customers immediately.')
            ->action(fn (VendorProfile $record) => static::applyStatus($record, VendorStatus::Active, null));
    }

    /** One-click "sign in as this vendor"; the dashboard layout shows a banner with a way back. */
    public static function impersonateAction(): Action
    {
        return Action::make('impersonate')
            ->label('Sign in as vendor')
            ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
            ->color('gray')
            ->visible(fn (VendorProfile $record) => Impersonation::allowed(auth()->user())
                && ! Impersonation::active()
                && $record->user?->isVendor())
            ->requiresConfirmation()
            ->modalHeading(fn (VendorProfile $record) => "Sign in as {$record->user?->name}?")
            ->modalDescription('You will see the vendor dashboard exactly as they do. Anything you change is saved under their account. A banner lets you switch back.')
            ->modalSubmitActionLabel('Sign in')
            ->action(function (VendorProfile $record, Action $action) {
                Impersonation::start(auth()->user(), $record->user);

                $action->redirect(route('filament.vendor.pages.dashboard'));
            });
    }

    protected static function applyStatus(VendorProfile $record, VendorStatus $status, ?string $notes): void
    {
        $record->update([
            'status' => $status,
            'review_notes' => $notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $message = match ($status) {
            VendorStatus::Active => "Your vendor account for {$record->business_name} has been activated. Your venues are now visible to customers.",
            VendorStatus::Suspended => "Your vendor account has been suspended. Your venues are hidden until this is resolved. Note from our team: {$notes}",
            VendorStatus::Rejected => "Your vendor application was not approved. Note from our team: {$notes}",
            VendorStatus::Pending => 'Your vendor account has been put back into review.',
        };

        $record->user->notify(new BookingNotification(
            $message,
            $status === VendorStatus::Active ? 'success' : 'danger',
            null,
            route('filament.vendor.pages.dashboard'),
        ));

        Notification::make()
            ->title("{$record->business_name} is now {$status->label()}.")
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorProfiles::route('/'),
            'view' => ViewVendorProfile::route('/{record}'),
        ];
    }
}
