# EntryPoint.lk — book any sport, any time

EntryPoint.lk is a multi-vendor marketplace for booking sports and leisure activities in Sri Lanka. Venue owners (vendors) list anything bookable by the block — futsal courts, cricket nets, badminton halls, PS5 stations, paintball sessions, swimming lanes, bowling lanes, studio hire — with their own block size, minimum duration, gaps, seat/unit types and peak-hour pricing. Customers pick a venue, build a plan, see the price instantly, and choose how to pay.

Built on **Laravel 13 · PHP 8.5 · Livewire 4**, with Tailwind (public site) and [Filament 5](https://filamentphp.com) (the admin panel *and* the vendor panel — see §7–8).

---

## 1. Quick start

```bash
composer install
cp .env.example .env            # already done if you cloned this repo with an .env
php artisan key:generate
touch database/database.sqlite  # SQLite by default; see "Switching to MySQL"
php artisan migrate:fresh --seed
php artisan storage:link        # serves uploaded photos & bank slips
php artisan serve               # http://127.0.0.1:8000
php artisan schedule:work       # (second terminal) releases unverified bank-transfer holds every minute
```

`npm install` is only needed if you want to build the Vite assets; the public site currently loads Tailwind from the Play CDN so the app runs without a JS build step (see §9 for production hardening).

### Demo accounts (password: `password`)

| Role      | Email                  | What to try |
|-----------|------------------------|-------------|
| Admin     | admin@entrypoint.lk       | `/admin` — platform stats, **Vendors** (activate/suspend/reject applications), venues, users, activity types (with tile photos), games, **Site settings** |
| Vendor    | vendor@entrypoint.lk      | `/vendor` — owns **CR7 Futsal Arena** (futsal + football) and **Club Fusion Gaming Lounge** (PS5, PC, VR) |
| Customer  | customer@entrypoint.lk    | Browse, build a booking, pay from the checkout modal, upload a bank slip, cancel |

Nine more vendors (`ciel@`, `misfits@`, `sportsworld@`, `unisports@`, `levelup@`, `strikezone@`, `galle@`, `aqua@`, `jaffna@` — all `@entrypoint.lk`) own the other seeded venues. All seeded vendors are pre-activated; a vendor who registers through `/register/vendor` starts as *Pending review*.

### Run the tests

```bash
php artisan test
```

- `tests/Feature/BookingFlowTest.php` — public pages, the Livewire builder and its checkout modal (pay-at-venue and bank transfer with slip upload), priority/bump rules, capacity, vendor confirmation, cancellation, vendor & admin panels, service creation, registration.
- `tests/Feature/AvailabilityEdgeCasesTest.php` — the engine edge cases: duration capped by the next booking, start times that can't fit the minimum, interchangeable units checked per block, buffers, closing time, after-midnight venues, peak rates ending at midnight, lead time / booking window, bank-transfer hold expiry, settings-driven payment methods, stale selections.
- `tests/Feature/VendorModerationTest.php` — vendor application (KYC fields + documents), pending → active → suspended visibility, role restrictions, site settings, activity-type photos.
- `tests/Feature/PersonalizationTest.php` — location prompt/storage, nearest-first sorting, activity tile ranking, recently viewed, saved location for members.

---

## 2. What was migrated and why it was rebuilt

The original app was a Laravel 10 "indoor futsal" booking site with football-specific terminology baked into models (`Indoor`), views, and a single hourly price per venue. It has been rebuilt as a generic marketplace on Laravel 13:

| Old                          | New                                                              |
|------------------------------|------------------------------------------------------------------|
| `Indoor` (one price/venue)   | `Venue` → many `Service`s, each with `ServiceOption`s and `ServiceRate`s |
| Hard-coded "futsal"          | `ActivityType` catalogue (12 seeded, admin can add any)          |
| `Booking` with start/finish  | `Booking` with slots, price breakdown, payment method/status, priority |
| `Tournament`, `Comment`      | Dropped (out of scope); `Review` added for venue ratings         |
| `userController` mixed auth  | `AuthController`, role-aware redirects, vendor self-registration |
| FullCalendar drag booking    | Livewire **BookingBuilder** wizard with live pricing             |
| Kernel.php middleware        | `bootstrap/app.php` (`role:` alias, guest/user redirects)        |
| Blade admin controllers + SB Admin vendor dashboard | Filament 5 **admin** and **vendor** panels (own resources, widgets, actions) |

The look and feel of the public site was kept: Poppins/Bebas Neue type, dark glass header, red brand accent. Both dashboards (admin and vendor) were later rebuilt on Filament 5, replacing the original SB Admin/Bootstrap screens — see §7–8.

---

## 3. Domain model

```
User (role_id: SuperAdministrator=1, Vendor=2, Moderator=3, MarketingManager=4, Customer=5,
      latitude/longitude/location_label — last known location for "near you")
 ├─ VendorProfile (business_name, business_type, registration_number, owner_nic, contact_*, address, district,
 │                 lat/lng, website/socials, description, br_document_path, nic_document_path (private disk),
 │                 status: pending | active | suspended | rejected, review_notes, reviewed_by/at, terms_accepted_at)
 └─ Venue (slug, address, city, district, postal_code, latitude, longitude, hours[7], amenities, bank details, is_approved, is_featured)
     ├─ VenueHour (day_of_week 0–6, opens_at, closes_at, is_closed)
     ├─ Review (rating 1–5, comment)
     └─ Service (activity_type_id, slot_minutes, min_slots, max_slots, buffer_minutes,
        │        lead_time_minutes, max_players, opens_at/closes_at override, is_active)
         ├─ ServiceOption  (name, price_per_slot, capacity, is_default)   e.g. "Standard seat ×8", "VIP pod ×2"
         ├─ ServiceRate    (days[], starts_at, ends_at, multiplier)       e.g. "Evening peak ×1.25"
         └─ games (pivot)  → Game (activity_type_id, name, platform, max_players)

ActivityType (name, slug, icon, color, image (tile photo), unit_label, requires_game, default_slot_minutes)

Booking (reference EPT-XXXXXX, user, venue, service, option, game?, starts_at, ends_at, slots,
         unit_price, subtotal, total, price_breakdown[], status, payment_method, payment_status,
         priority, customer_name/phone, notes, vendor_confirmed_at, hold_expires_at, bumped_by_booking_id)
 └─ Payment (method, amount, status, reference, proof_path, verified_by/at)

Setting (key, value JSON)  — super-admin site settings, see app/Support/Settings.php for keys + defaults
VenueView (venue, user?, session_id, viewed_at) — "recently viewed" + activity affinity
```

A venue is **live** (`Venue::live()`) only when it is approved *and* its owner is an active vendor (or an admin). Every public query uses that scope.

Key files:

- `app/Services/AvailabilityService.php` — builds the start-time grid for a *session day* (a venue open 10:00–02:00 keeps the 01:00 slot on the previous day), counts units used per block (respecting the vendor's **buffer**), marks each start as `bookable` only if the minimum duration fits, and reports `max_blocks` from every start.
- `app/Services/PricingService.php` — prices slot-by-slot so peak multipliers apply only to the blocks they cover; returns the line items shown at checkout.
- `app/Services/BookingService.php` — the booking engine: validates rules, checks capacity **block by block** (identical units are interchangeable), resolves priority conflicts, starts the bank-transfer hold timer, expires stale holds, creates bookings/payments, and sends notifications.
- `app/Services/PersonalizationService.php` — visitor location (session/cookie/user), recently viewed venues, activity affinity, distance sorting.
- `app/Livewire/BookingBuilder.php` — the "customize your plan" wizard **and the checkout modal** (details, payment method, bank reference + slip upload).
- `app/Enums/PaymentMethod.php` — which methods are integrated, their priority; whether they are *enabled* comes from Site settings.
- `app/Support/Settings.php` — the site-settings registry (`setting('key')` helper) with defaults.

---

## 4. Booking rules (how the rate is calculated)

Every service is booked in **blocks** (`slot_minutes`, e.g. 30/60/90/120). The vendor sets:

| Field                | Meaning |
|----------------------|---------|
| `slot_minutes`       | Smallest bookable block. Start times are aligned to it from the opening time. |
| `min_slots`/`max_slots` | Minimum and maximum blocks in one booking. |
| `buffer_minutes`     | Gap the vendor wants between consecutive bookings (cleaning/reset). |
| `lead_time_minutes`  | How far ahead a booking must be placed. |
| `opens_at`/`closes_at` | Optional override of the venue's daily hours (supports closing after midnight, e.g. 10:00–02:00). |
| Options              | Seat/court/unit types with their own `price_per_slot` and `capacity` (identical units bookable in parallel). |
| Rates                | Optional multipliers by weekday and time window (`1.25` = +25 %, `0.8` = off-peak discount). |

**Total = Σ over blocks (option price × applicable multiplier)**. The breakdown is stored on the booking (`price_breakdown`) and shown on the builder, checkout and confirmation pages.

---

## 5. Payment methods and slot priority

`PaymentMethod` enum:

| Method          | Status        | Priority | Behaviour |
|-----------------|---------------|----------|-----------|
| Pay at Venue    | **Integrated** (on/off in Site settings) | 1 | Holds the slot. Only one direct hold per unit. Can be replaced by any higher-priority booking. |
| Bank Transfer   | **Integrated** (on/off in Site settings) | 2 | Creates a `Payment` in *pending verification* and starts a **hold timer** (`payments.bank_transfer_hold_minutes`, default 20). The customer enters the bank reference and uploads the slip in the checkout modal (or later on the confirmation page). If the vendor hasn't verified by the deadline the booking becomes **Expired** and the slot is released (`bookings:expire-holds`, scheduled every minute, plus an opportunistic sweep before new reservations). Verified → **paid, confirmed, locked**. |
| Card            | Coming soon   | 3        | Shown in the modal as unavailable; cannot be enabled until integrated; rejected server-side. |
| Koko            | Coming soon   | 3        | ” |
| Mint Pay        | Coming soon   | 3        | ” |
| PayEasy         | Coming soon   | 3        | ” |

Conflict resolution (`BookingService::reserve`, inside a transaction with row locks):

1. Find bookings that still hold a unit anywhere in the requested range (including buffer) for the same service + option. Expired bank-transfer holds are ignored.
2. **For every block** of the request: bookings with priority **≥** the new one are *blocking*; those with lower priority are *bumpable*.
3. If blocking ≥ option capacity in any block → `SlotUnavailableException` ("no longer available").
4. Otherwise, in each over-capacity block, the weakest (lowest priority, then most recent) bumpable bookings are set to **Replaced** (`bumped`) with `bumped_by_booking_id`, and their customers are notified. Only as many holds as needed are bumped.

Two things raise a booking to priority 3 (locked): the vendor pressing **Confirm & lock** (e.g. after phoning the customer) or a **verified payment** (bank transfer verified / cash marked as paid). A locked booking can never be bumped.

To switch a gateway on later: add it to `PaymentMethod::isIntegrated()`, implement the redirect in `BookingBuilder::placeBooking()`, and call `BookingService::markPaid()` from the gateway callback. Then enable it in Admin → Site settings. Everything else (priority, locking, UI badges) already keys off the enum.

---

## 6. Customer journey

1. **Home** — hero search, a **location bar** (the browser is asked for location once per session; a district picker is the fallback), **browse-by-activity photo tiles** ordered by what the visitor plays and what's closest, **Near you**, **Book again** (members), **Recently viewed**, popular venues, how-it-works, vendor pitch.
2. **Venues** (`/venues`) — filters + activity chip row + sort (nearest / featured / top rated / name); cards show distance, activity tags, rating, "from" price.
3. **Venue page** — services grouped by activity; every card shows **all rates** (each option's price and every peak/off-peak multiplier) and **today's start-time strip** (open vs taken), plus block size, min/max duration, games and a *Book now* CTA; opening hours, amenities, contact and map link in the sidebar.
4. **Build your plan** (`/book/{service}`) — Livewire wizard: option → game (if applicable) → date strip → start-time grid (greyed when booked, past, inside notice or too short for the minimum; shows "n left" / "max 2 hr") → duration stepper capped by the next booking → live price. Guests are sent to login and returned here.
5. **Checkout modal** — opens on *Checkout*: details (name, phone, notes), every payment method (available / temporarily off / coming soon, with priority), and for Bank Transfer the venue's bank details, the hold-timer notice, bank reference and slip upload. *Place booking* reserves the slot.
6. **Confirmation** (`/my-bookings/EPT-…`) — status + payment badges, details, price lines, "what happens next", live **hold countdown** for bank transfers, bank details and slip (re-)upload, cancel, call venue. Expired bookings explain what happened and offer *Book again*.
7. **My bookings** and **Alerts** (database notifications for placed / confirmed / verified / expired / cancelled / replaced).

---

## 7. Vendor panel (`/vendor`, Filament)

Built with [Filament 5](https://filamentphp.com) — its own panel (`App\Providers\Filament\VendorPanelProvider`), separate from the admin panel but sharing the same `/login`. Vendors (and Super Administrators, who can also **sign in as a vendor** from Admin → Vendors) are routed here automatically; `App\Http\Controllers\Vendor\*` and the old SB Admin dashboard views no longer exist.

- **Account status banner** — pending / active / suspended / rejected with the admin's note, plus a nudge to create a first venue. Pending vendors can set everything up; nothing is public until an admin activates them. A "signed in as this vendor" bar with a **Back to admin** button appears when a staff member is impersonating.
- **Dashboard** — stat cards (today/this week/revenue/transfers-to-verify/unconfirmed holds), a **FullCalendar** week view (events are fetched straight from the widget over Livewire, no separate JSON route), a **"Bank transfers waiting for your verification"** table with minutes left, an upcoming-bookings table, and a venue shortcut list.
- **Venues** — a Filament resource, scoped to the signed-in vendor's own venues: basics, district, postal code, map pin (lat/lng), cover photo, 7-day opening hours, amenities (list managed in Site settings), bank details. A **Services** tab (relation manager) on the venue's edit page manages what's bookable at that venue. Venues go live as soon as the vendor is active unless *venues.require_approval* is switched on.
- **Services** (per venue, from the Services tab) — activity type (defaults the block size and shows the games list when the type requires a game), booking rules, an options & pricing repeater ("units" = capacity, one option flagged default), a peak-hour rate repeater, photo, active toggle.
- **Bookings** — a Filament resource, scoped to the vendor's own venues, with venue/status/date/awaiting-verification filters; row and detail-page actions: **Confirm & lock**, **Mark as paid** (verifies transfer or records cash), **Completed / No-show** (after end time), **Cancel** with reason. Slip images open from the payment list.
- **Scan check-in** — a custom Filament page: a camera QR scanner (`html5-qrcode`) plus manual reference/token entry, both resolving through the same vendor-scoped lookup as the booking's QR code (`Booking::checkinUrl()`).

## 8. Admin panel (`/admin`, Filament)

Built with [Filament 5](https://filamentphp.com). Sign in through the normal `/login` page — staff (Super Administrator, Moderator, Marketing Manager) are taken to the panel; everyone else is blocked.

- **Dashboard** — stat cards (venues, vendors awaiting review, customers, bookings, paid revenue), a 30-day bookings chart and the latest bookings.
- **Vendors** — every business that applied: filter by status, search, open the application (business type, BR number, owner NIC, contacts, address + map, socials, description, activities, venues) and open the **private documents** (BR certificate, NIC copy). Super admins **activate / suspend / reject** with a note that the vendor sees, and can **sign in as the vendor** to help them.
- **Venues** — approve/hide, feature/unfeature, view details, delete; shows whether the owner is an active vendor.
- **Users** — search, filter by role, change role, delete (you cannot delete your own account).
- **Activity types** — name, unit label, a searchable **Font Awesome icon picker** (browse/search ~1,400 icons instead of typing a class name), colour, **tile photo** (drag & drop, clipboard paste or path), default block, "customers pick a game", featured, sort order.
- **Games** — titles per gaming activity (platform, max players). Vendors attach them to services.
- **Site settings** (super admin) — enabled payment methods, bank-transfer verification window, vendor activation / venue approval switches, booking window, "near you" radius, amenities list, support contact.

Moderators and Marketing Managers can manage venues, the catalogue and users; only Super Administrators can change vendor status and site settings.

---

## 9. Production notes

- **Database** — switch to MySQL by setting `DB_CONNECTION=mysql` and the `DB_*` vars in `.env`, then `php artisan migrate --seed`. (The commented MySQL lines in `.env.example` are ready.)
- **Sessions/cache/queue** — set to `file`/`file`/`sync` for zero-config local use. For production use `database` or `redis` and run a queue worker if you move notifications to queues.
- **Assets** — the public site loads Tailwind via the Play CDN to preserve the original theme exactly. For production, move to the Vite build already scaffolded (`resources/css/app.css`, `@vite` in `layouts/app.blade.php`, `npm run build`) and audit Tailwind v4 class changes.
- **Uploads** — venue/service photos and bank slips go to `storage/app/public` (`php artisan storage:link`). Every upload field supports drag & drop, clipboard paste (Ctrl+V) and click-to-browse (`resources/views/components/file-drop.blade.php`). Use S3 by pointing `FILESYSTEM_DISK` at the `s3` disk.
- **Scheduler** — `bookings:expire-holds` must run every minute (`* * * * * php artisan schedule:run` in cron, or `php artisan schedule:work`). Without it, expired holds still free their slot (queries ignore them) but stay "pending" in lists until the next reservation/listing triggers the sweep.
- **Approval flow** — vendors must be activated from Admin → Vendors (switch: `vendors.require_activation`); per-venue approval is optional (`venues.require_approval`). Both live in Site settings.
- **Vendor documents** — stored on the `local` (private) disk under `vendor-documents/`; served only to staff via `admin.vendors.document`.
- **Email verification** — password-based customer and vendor registrations receive a signed verification link and cannot book or access a workspace until it is used. Configure a real `MAIL_MAILER` and its credentials in production; `log` is only suitable for local development.
- **Pay at Venue identity** — customer sign-up offers optional front and back NIC uploads. Both files are required together; skipping them is allowed, but the customer must upload them once at their first Pay at Venue checkout. The files are retained privately on the account and reused for later Pay at Venue bookings.
- **Timezone** — `APP_TIMEZONE` defaults to `Asia/Colombo`; all opening hours, lead times and hold timers use it.
- **Roles** — the `role:` middleware (accepts a list, e.g. `role:SuperAdministrator,Moderator,MarketingManager`) still guards plain routes like the admin vendor-document download; the two Filament panels gate access themselves via `User::canAccessPanel()` (staff → `admin`, vendors and super admins → `vendor`). Enum values are stable integers so existing user rows keep working.

- **Google sign-in** — create a **Web application** OAuth client in Google Cloud Console, then set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in `.env`. Add the exact redirect URI (for example, `https://entrypoint.lk/auth/google/callback`) to Google Cloud Console's authorised redirect URIs, run `php artisan migrate`, and clear the cached configuration with `php artisan config:clear`. Google sign-in creates customer accounts and can link an existing account with the same verified Google email.

---

## 10. UI/UX decisions

- **One consistent brand** across pages: the EntryPoint.lk wordmark (white on the dark public header/footer, and on both Filament panels' topbar) plus Bebas Neue display headings, Poppins body, red (#ef4444) primary. Admin and vendor now share the same Filament design system, so a Super Administrator "signing in as a vendor" sees a workspace that already feels familiar.
- **Booking builder as a numbered wizard** with large tappable chips (44px+ targets), disabled states that explain *why* a time is unavailable, capacity hints ("8 left"), and a **sticky bottom summary on mobile** so the price and CTA are always visible.
- **Price transparency** — every screen shows the line items (blocks × option, peak surcharges) before any commitment; nothing is charged until a method is chosen.
- **Payment modal** (bottom sheet on mobile, centred card on desktop) states availability and priority for each method, and previews bank details inline so users aren't surprised later.
- **Priority is explained in plain language** on checkout, confirmation and notification copy ("Pay online next time to lock your slot"), turning a business rule into an incentive.
- **Vendor forms lead with defaults** — choosing an activity type pre-fills block size and reveals only the relevant game list; repeaters for options/rates keep advanced pricing optional.
- **Mobile nav** grows to fit all links (no fixed-height menu), and every table on the dashboards is horizontally scrollable.

See `docs/PRODUCTION-READINESS.md` for the full list of what is still missing before launch and the known weaknesses.

---

## 11. Brand assets

The brand is **EntryPoint.lk** — a wordmark with a crosshair/target mark standing in for the "O" in POINT, reflecting "find your entry point" into any sport or activity. All assets are derived from the single supplied logo (`public/images/brand/`), which ships with a transparent background:

| File | Use |
|------|-----|
| `wordmark-white.png` | Full lockup, white ink — header, footer, dashboard topbar (all dark backgrounds) |
| `wordmark-black.png` | Full lockup, dark ink — for light backgrounds (print, light-mode surfaces) |
| `icon-white.png` / `icon-black.png` | The crosshair mark alone, transparent, either colour — compact spaces, loading states |
| `mark-white-64.png` / `mark-ink-64.png` | Small pre-sized version of the mark for inline use (e.g. email signatures) |

Favicons and app icons (`public/favicon.ico`, `favicon-16x16.png`, `favicon-32x32.png`, `apple-touch-icon.png`, `android-chrome-192x192.png`, `android-chrome-512x512.png`, `site.webmanifest`) are the crosshair mark in white on the brand-red (#ef4444) square, generated at each required size so the tab icon stays legible from 16px up. The venue "no photo" placeholder (`public/images/venue-placeholder.svg`) also uses the mark instead of a text lockup.

The untouched original (black ink, transparent background, 2172×724) is kept at `resources/brand/entrypoint-logo-source.png` — it's not served publicly, but it's the file to go back to if you ever need a different crop, size or colour than what's already exported to `public/images/brand/`.
