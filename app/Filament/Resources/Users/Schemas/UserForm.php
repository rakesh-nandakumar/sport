<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(10)
                    ->rules(['regex:/^0\d{9}$/'])
                    ->validationMessages(['regex' => 'Enter a valid 10-digit Sri Lankan number, e.g. 0771234567.'])
                    ->placeholder('0771234567'),
                Select::make('role_id')
                    ->label('Role')
                    ->options(collect(Role::cases())->mapWithKeys(fn (Role $role) => [$role->value => $role->label()]))
                    ->required(),
            ]);
    }
}
