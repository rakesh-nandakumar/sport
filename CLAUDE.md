# EntryPoint.lk — agent notes

Laravel 13 / PHP 8.5 / Livewire 4 multi-vendor sports & activity booking marketplace. See README.md for the full guide.

## Environment (this machine)
- Use PHP 8.5 from winget, not XAMPP's 8.2: prepend
  `C:\Users\Rakesh\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe` to PATH (the user's shell profiles already do this).
- DB is SQLite (`database/database.sqlite`). Seed with `php artisan migrate:fresh --seed`.
- Run tests with `php artisan test`; format with `vendor/bin/pint`.
- Browser checks: Playwright (`playwright-core` + system Chrome, `channel: 'chrome'`) against `php artisan serve` works well; the Bash tool truncates very long heredocs, so write big files with the Write tool.
- Scheduler: `bookings:expire-holds` runs every minute (`php artisan schedule:work` locally).

## Where things live
- Booking engine: `app/Services/{Availability,Pricing,Booking}Service.php` (per-block capacity, session days for after-midnight venues, bank-transfer hold expiry)
- Payment methods + priority: `app/Enums/PaymentMethod.php` (`isIntegrated()` = PayAtVenue, BankTransfer; `isAvailable()` also checks Site settings)
- Site settings: `app/Support/Settings.php` + `setting()` helper; admin UI at `/admin/settings`
- Booking wizard **and checkout modal**: `app/Livewire/BookingBuilder.php` + `resources/views/livewire/booking-builder.blade.php` (there is no separate /checkout page)
- Vendor moderation: `app/Models/VendorProfile.php`, `App\Enums\VendorStatus`, `Admin\VendorController`; `Venue::live()` = approved + vendor active
- Personalization (location, recently viewed, activity ranking): `app/Services/PersonalizationService.php`
- Static master data (districts + coords, business types): `config/entrypoint.php`
- Gap/weakness report for the owner: `docs/PRODUCTION-READINESS.md`
- Public layout: `resources/views/layouts/app.blade.php` (Tailwind Play CDN + `public/css/styles.css` + `public/css/theme.css`)
- Dashboard layout: `resources/views/layouts/dashboard.blade.php` (SB Admin / Bootstrap from `public/assets`)
- Seed data (realistic Sri Lankan venues, `@entrypoint.lk` accounts): `database/seeders/`
- Brand assets: `public/images/brand/` (wordmark + icon, black/white); untouched source at `resources/brand/entrypoint-logo-source.png`; favicons/app-icons at the `public/` root (see README §11).

## Conventions
- Venues route by `slug`, bookings by `reference` (EPT-XXXXXX).
- `role:` middleware takes a comma list of `App\Enums\Role` case names.
- Money helper `lkr()` and `minutes_label()` are in `app/Support/helpers.php`.
