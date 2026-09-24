<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Enums\VendorStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'email_verified_at', 'phone', 'password', 'role_id', 'google_id', 'latitude', 'longitude', 'location_label', 'location_updated_at', 'pay_at_venue_banned_at', 'pay_at_venue_ban_note', 'nic_front_path', 'nic_back_path'];

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
            'pay_at_venue_banned_at' => 'datetime',
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

    public function isPayAtVenueBanned(): bool
    {
        return $this->pay_at_venue_banned_at !== null;
    }

    public function hasNicOnFile(): bool
    {
        return filled($this->nic_front_path) && filled($this->nic_back_path);
    }

    public function payAtVenueFailureCount(): int
    {
        return $this->bookings()
            ->where('payment_method', PaymentMethod::PayAtVenue->value)
            ->whereNotNull('pay_at_venue_failure_at')
            ->count();
    }

    public function banPayAtVenue(int $failures): void
    {
        $this->update([
            'pay_at_venue_banned_at' => now(),
            'pay_at_venue_ban_note' => "Automatically restricted after {$failures} pay-at-venue cancellations or no-shows.",
        ]);
    }

    public function unbanPayAtVenue(): void
    {
        $this->update([
            'pay_at_venue_banned_at' => null,
            'pay_at_venue_ban_note' => 'Restriction lifted by an administrator on '.now()->format('d M Y, h:i A').'.',
        ]);
    }

    public function isStaff(): bool
    {
        return $this->role()->isStaff();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            // Super admins keep access to the vendor panel too, so "sign in as vendor" and direct
            // support access both work without a separate account.
            'vendor' => $this->isVendor() || $this->isAdmin(),
            default => false,
        };
    }

    public function dashboardUrl(): string
    {
        return match (true) {
            $this->isStaff() => route('filament.admin.pages.dashboard'),
            $this->isVendor() => route('filament.vendor.pages.dashboard'),
            default => route('bookings.index'),
        };
    }
}
