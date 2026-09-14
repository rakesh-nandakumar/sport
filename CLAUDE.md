# EntryPoint.lk — agent notes

Laravel 13 / PHP 8.5 / Livewire 4 multi-vendor sports & activity booking marketplace. See README.md for the full guide.

## Environment (this machine)
- Use PHP 8.5 from winget, not XAMPP's 8.2: prepend
  `C:\Users\Rakesh\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe` to PATH (the user's shell profiles already do this).
- DB is SQLite (`database/database.sqlite`). Seed with `php artisan migrate:fresh --seed`.
- Run tests with `php artisan test`; format with `vendor/bin/pint`.
- Testing a non-default Filament panel (`vendor`) with `Livewire::test(...)` directly (not via an HTTP `get()`) needs `Filament::setCurrentPanel('vendor')` first, or resource/record URLs resolve against the default panel (`admin`) instead. Reset it back to `'admin'` afterwards if the same test then exercises admin-panel Livewire components.
- All Filament widgets default to `$isLazy = true` (loaded via a follow-up Livewire request), so a plain `$this->get($dashboardUrl)` in a test won't see their content — either assert via `Livewire::test(WidgetClass::class)`, or set `protected static bool $isLazy = false;` on the widget (done for every vendor dashboard widget, to match the old dashboard's always-rendered behaviour).
- Browser checks: Playwright (`playwright-core` + system Chrome, `channel: 'chrome'`) against `php artisan serve` works well; the Bash tool truncates very long heredocs, so write big files with the Write tool.
- Scheduler: `bookings:expire-holds` runs every minute (`php artisan schedule:work` locally).

## Where things live
- Booking engine: `app/Services/{Availability,Pricing,Booking}Service.php` (per-block capacity, session days for after-midnight venues, bank-transfer hold expiry)
- Payment methods + priority: `app/Enums/PaymentMethod.php` (`isIntegrated()` = PayAtVenue, BankTransfer; `isAvailable()` also checks Site settings)
- Site settings: `app/Support/Settings.php` + `setting()` helper; admin UI at `/admin/settings`
- Booking wizard **and checkout modal**: `app/Livewire/BookingBuilder.php` + `resources/views/livewire/booking-builder.blade.php` (there is no separate /checkout page)
- Vendor moderation: `app/Models/VendorProfile.php`, `App\Enums\VendorStatus`, Filament resource at `App\Filament\Resources\VendorProfiles`; `Venue::live()` = approved + vendor active
- Admin panel: **Filament 5** at `/admin` (`app/Filament`, `app/Providers/Filament/AdminPanelProvider.php`). Staff sign in via `/login`; `User::canAccessPanel()` restricts access. The old Blade admin (controllers + `resources/views/admin`) was removed.
- Vendor panel: **Filament 5** at `/vendor` (`app/Filament/Vendor`, `app/Providers/Filament/VendorPanelProvider.php`) — own Venues/Bookings resources (scoped to the signed-in vendor via `getEloquentQuery()`), a Services relation manager under Venue edit, dashboard widgets, and a custom `CheckIn` page. Vendors and super admins sign in via `/login`. The old `app/Http/Controllers/Vendor/*` controllers, `resources/views/vendor/**`, and the SB Admin dashboard layout were removed; nothing under `/vendor` is a plain route anymore.
- Impersonation ("sign in as vendor"): `app/Support/Impersonation.php`; the banner + "Back to admin" button is a panel render hook in `VendorPanelProvider`, not a Blade partial.
- Font Awesome icon picker (used by `ActivityTypeResource`'s `icon` field): `app/Filament/Forms/Components/IconPicker.php` + `resources/views/filament/forms/components/icon-picker.blade.php`; the searchable icon list is `public/data/fontawesome-solid-icons.json` (matches the FA version loaded via CDN in both panels).
- Upload dropzones (drag & drop, paste, browse): `resources/views/components/file-drop.blade.php` + `public/css/file-drop.css`; Filament uploads get the same behaviour from `FileUpload`.
- Personalization (location, recently viewed, activity ranking): `app/Services/PersonalizationService.php`
- Static master data (districts + coords, business types): `config/entrypoint.php`
- Gap/weakness report for the owner: `docs/PRODUCTION-READINESS.md`
- Public layout: `resources/views/layouts/app.blade.php` (Tailwind Play CDN + `public/css/styles.css` + `public/css/theme.css`)
- Seed data (realistic Sri Lankan venues, `@entrypoint.lk` accounts): `database/seeders/`
- Brand assets: `public/images/brand/` (wordmark + icon, black/white); untouched source at `resources/brand/entrypoint-logo-source.png`; favicons/app-icons at the `public/` root (see README §11).

## Conventions
- Venues route by `slug`, bookings by `reference` (EPT-XXXXXX).
- `role:` middleware takes a comma list of `App\Enums\Role` case names.
- Money helper `lkr()` and `minutes_label()` are in `app/Support/helpers.php`.
