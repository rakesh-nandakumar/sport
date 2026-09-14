<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'role_id', 'latitude', 'longitude', 'location_label', 'location_updated_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role_id' => Role::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'location_updated_at' => 'datetime',
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

    public function vendorProfile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
    }

    public function venueViews(): HasMany
    {
        return $this->hasMany(VenueView::class);
    }

    /**
     * Vendors are "active" once an admin has approved their application. Admin-owned venues
     * (demo/seed) and vendors created before moderation existed are treated as active when
     * activation is switched off in settings.
     */
    public function vendorStatus(): VendorStatus
    {
        if ($this->isAdmin()) {
            return VendorStatus::Active;
        }

        $profile = $this->relationLoaded('vendorProfile') ? $this->vendorProfile : $this->vendorProfile()->first();
        if ($profile) {
            return $profile->status;
        }

        return setting('vendors.require_activation') ? VendorStatus::Pending : VendorStatus::Active;
    }

    public function isActiveVendor(): bool
    {
        return $this->isVendor() && $this->vendorStatus() === VendorStatus::Active;
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
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
