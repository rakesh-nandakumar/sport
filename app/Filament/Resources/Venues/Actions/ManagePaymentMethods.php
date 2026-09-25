<?php

namespace App\Filament\Resources\Venues\Actions;

use App\Enums\PaymentMethod;
use App\Models\Venue;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;

class ManagePaymentMethods
{
    public static function make(): Action
    {
        return Action::make('managePaymentMethods')
            ->label('Payment methods')
            ->icon('heroicon-o-credit-card')
            ->visible(fn () => auth()->user()?->isAdmin() === true)
            ->modalHeading(fn (Venue $record) => 'Payment methods for '.$record->name)
            ->modalDescription('Choose the methods allowed for this vendor at this venue. Site-wide disabled methods remain unavailable at checkout.')
            ->fillForm(fn (Venue $record) => [
                'allowed_payment_methods' => $record->allowed_payment_methods ?? array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::available()),
            ])
            ->schema([
                CheckboxList::make('allowed_payment_methods')
                    ->label('Allowed payment methods')
                    ->options(collect(PaymentMethod::cases())->filter(fn (PaymentMethod $method) => $method->isIntegrated())->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->label()]))
                    ->required(),
            ])
            ->action(function (Venue $record, array $data): void {
                abort_unless(auth()->user()?->isAdmin(), 403);
                $allowed = collect($data['allowed_payment_methods'])
                    ->filter(fn ($value) => is_string($value) && PaymentMethod::tryFrom($value)?->isIntegrated())
                    ->unique()->values()->all();
                abort_if($allowed === [], 422);
                // Deliberately excluded from mass assignment in vendor forms.
                $record->allowed_payment_methods = $allowed;
                $record->save();
                Notification::make()->title('Payment methods updated')->success()->send();
            });
    }
}
