<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'role_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role_id' => Role::class,
        ];
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function role(): Role
    {
        return $this->role_id ?? Role::Customer;
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role(), $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role() === Role::SuperAdministrator;
    }

    public function isVendor(): bool
    {
        return $this->role() === Role::Vendor;
    }

    public function isCustomer(): bool
    {
        return $this->role() === Role::Customer;
    }

    public function isStaff(): bool
    {
        return $this->role()->isStaff();
    }

    public function dashboardUrl(): string
    {
        return match (true) {
            $this->isAdmin(), $this->isStaff() => route('admin.dashboard'),
            $this->isVendor() => route('vendor.dashboard'),
            default => route('bookings.index'),
        };
    }
}
