# Sportee — agent notes

Laravel 13 / PHP 8.5 / Livewire 4 multi-vendor sports & activity booking marketplace. See README.md for the full guide.

## Environment (this machine)
- Use PHP 8.5 from winget, not XAMPP's 8.2: prepend
  `C:\Users\Rakesh\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe` to PATH (the user's shell profiles already do this).
- DB is SQLite (`database/database.sqlite`). Seed with `php artisan migrate:fresh --seed`.
- Run tests with `php artisan test`; format with `vendor/bin/pint`.

## Where things live
- Booking engine: `app/Services/{Availability,Pricing,Booking}Service.php`
- Payment methods + priority: `app/Enums/PaymentMethod.php` (only PayAtVenue and BankTransfer are live)
- Booking wizard: `app/Livewire/BookingBuilder.php` + `resources/views/livewire/booking-builder.blade.php`
- Public layout: `resources/views/layouts/app.blade.php` (Tailwind Play CDN + `public/css/styles.css` + `public/css/sportee.css`)
- Dashboard layout: `resources/views/layouts/dashboard.blade.php` (SB Admin / Bootstrap from `public/assets`)
- Seed data (realistic Sri Lankan venues): `database/seeders/`

## Conventions
- Venues route by `slug`, bookings by `reference` (SPT-XXXXXX).
- `role:` middleware takes a comma list of `App\Enums\Role` case names.
- Money helper `lkr()` and `minutes_label()` are in `app/Support/helpers.php`.
