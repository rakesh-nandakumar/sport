# Sportee — book any sport, any time

Sportee is a multi-vendor marketplace for booking sports and leisure activities in Sri Lanka. Venue owners (vendors) list anything bookable by the block — futsal courts, cricket nets, badminton halls, PS5 stations, paintball sessions, swimming lanes, bowling lanes, studio hire — with their own block size, minimum duration, gaps, seat/unit types and peak-hour pricing. Customers pick a venue, build a plan, see the price instantly, and choose how to pay.

Built on **Laravel 13 · PHP 8.5 · Livewire 4**, with Tailwind (public site) and SB Admin/Bootstrap (dashboards).

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
```

`npm install` is only needed if you want to build the Vite assets; the public site currently loads Tailwind from the Play CDN so the app runs without a JS build step (see §9 for production hardening).

### Demo accounts (password: `password`)

| Role      | Email                  | What to try |
|-----------|------------------------|-------------|
| Admin     | admin@sportee.lk       | `/admin` — platform stats, approve/feature venues, manage users, activity types and games |
| Vendor    | vendor@sportee.lk      | `/vendor` — owns **CR7 Futsal Arena** (futsal + football) and **Club Fusion Gaming Lounge** (PS5, PC, VR) |
| Customer  | customer@sportee.lk    | Browse, build a booking, checkout, upload a bank slip, cancel |

Nine more vendors (`ciel@`, `misfits@`, `sportsworld@`, `unisports@`, `levelup@`, `strikezone@`, `galle@`, `aqua@`, `jaffna@` — all `@sportee.lk`) own the other seeded venues.

### Run the tests

```bash
php artisan test
```

`tests/Feature/BookingFlowTest.php` covers the public pages, the Livewire booking builder, checkout, the priority/bump rules, capacity, vendor confirmation, slip upload, cancellation, vendor & admin panels, service creation and registration.

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

The look and feel was kept: Poppins/Bebas Neue type, dark glass header, red brand accent, SB Admin dashboards.

---

## 3. Domain model

```
User (role_id: SuperAdministrator=1, Vendor=2, Moderator=3, MarketingManager=4, Customer=5)
 └─ Venue (slug, address, city, hours[7], amenities, bank details, is_approved, is_featured)
     ├─ VenueHour (day_of_week 0–6, opens_at, closes_at, is_closed)
     ├─ Review (rating 1–5, comment)
     └─ Service (activity_type_id, slot_minutes, min_slots, max_slots, buffer_minutes,
        │        lead_time_minutes, max_players, opens_at/closes_at override, is_active)
         ├─ ServiceOption  (name, price_per_slot, capacity, is_default)   e.g. "Standard seat ×8", "VIP pod ×2"
         ├─ ServiceRate    (days[], starts_at, ends_at, multiplier)       e.g. "Evening peak ×1.25"
         └─ games (pivot)  → Game (activity_type_id, name, platform, max_players)

ActivityType (name, slug, icon, color, unit_label, requires_game, default_slot_minutes)

Booking (reference SPT-XXXXXX, user, venue, service, option, game?, starts_at, ends_at, slots,
         unit_price, subtotal, total, price_breakdown[], status, payment_method, payment_status,
         priority, customer_name/phone, notes, vendor_confirmed_at, bumped_by_booking_id)
 └─ Payment (method, amount, status, reference, proof_path, verified_by/at)
```

Key files:

- `app/Services/AvailabilityService.php` — builds the start-time grid for a day, counts units used per slot (respecting the vendor's **buffer**), and computes the maximum contiguous blocks from a start time.
- `app/Services/PricingService.php` — prices slot-by-slot so peak multipliers apply only to the blocks they cover; returns the line items shown at checkout.
- `app/Services/BookingService.php` — the booking engine: validates rules, resolves priority conflicts, creates bookings/payments, and sends notifications.
- `app/Livewire/BookingBuilder.php` — the "customize your plan" wizard.
- `app/Enums/PaymentMethod.php` — the single source of truth for which methods are live and their priority.

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
| Pay at Venue    | **Available** | 1        | Holds the slot. Only one direct hold per unit. Can be replaced by any higher-priority booking. |
| Bank Transfer   | **Available** | 2        | Creates a `Payment` in *pending verification*; customer uploads a slip; vendor verifies → **paid, confirmed, locked**. |
| Card            | Coming soon   | 3        | Shown in the modal as unavailable; rejected server-side. |
| Koko            | Coming soon   | 3        | ” |
| Mint Pay        | Coming soon   | 3        | ” |
| PayEasy         | Coming soon   | 3        | ” |

Conflict resolution (`BookingService::reserve`):

1. Find active bookings overlapping the requested range (including buffer) for the same service + option.
2. Bookings with priority **≥** the new one are *blocking*; those with lower priority are *bumpable*.
3. If blocking ≥ option capacity → `SlotUnavailableException` ("no longer available").
4. Otherwise, if capacity is exhausted, the lowest-priority bumpable bookings are set to **Replaced** (`bumped`) with `bumped_by_booking_id`, and their customers are notified.

Two things raise a booking to priority 3 (locked): the vendor pressing **Confirm & lock** (e.g. after phoning the customer) or a **verified payment** (bank transfer verified / cash marked as paid). A locked booking can never be bumped.

To switch a gateway on later: return `true` from `PaymentMethod::isAvailable()` for it, implement the gateway redirect in `CheckoutController@store`, and call `BookingService::markPaid()` from the gateway callback. Everything else (priority, locking, UI badges) already keys off the enum.

---

## 6. Customer journey

1. **Home** — hero search (keyword + activity + city), browse-by-activity chips, popular venues, how-it-works, vendor pitch.
2. **Venues** (`/venues`) — filters + activity chip row; cards show activity tags, rating, "from" price.
3. **Venue page** — services grouped by activity, each with block size, min duration, options, games and a *Book now* CTA; opening hours, amenities, contact, reviews in the sidebar.
4. **Build your plan** (`/book/{service}`) — Livewire wizard: option → game (if applicable) → date strip → start-time grid (unavailable/past/inside-notice slots greyed) → duration stepper → live price; sticky summary bar on mobile, sidebar on desktop. Guests are sent to login and returned here.
5. **Checkout** — details form (name, phone, players, notes), priority explainer, summary card. *Confirm & choose payment* opens the **payment modal**: two live methods, four "coming soon", each with priority and description. Bank details preview when Bank Transfer is selected.
6. **Confirmation** (`/my-bookings/SPT-…`) — status + payment badges, details, price lines, "what happens next", bank details and slip upload (bank transfer), cancel, call venue.
7. **My bookings** and **Alerts** (database notifications for placed / confirmed / verified / cancelled / replaced).

---

## 7. Vendor panel (`/vendor`)

- **Dashboard** — today/this week/revenue/transfers-to-verify/unconfirmed holds, FullCalendar week view fed by `/vendor/events`, upcoming bookings, venue shortcuts.
- **My venues** — create/edit venue: basics, cover photo, 7-day opening hours, amenities, bank details (shown to customers paying by transfer). New venues are live immediately; admins can hide them.
- **Services** (per venue) — activity type (defaults the block size and shows the games list when the type requires a game), booking rules, options & pricing repeater (mark one default; "units" = capacity), peak-hour rate repeater, photo, active toggle.
- **Bookings** — filter by venue/status/date/awaiting-verification; booking detail with actions: **Confirm & lock**, **Mark as paid** (verifies transfer or records cash), **Completed / No-show** (after end time), **Cancel** with reason. Slip images open from the payment list.

## 8. Admin panel (`/admin`)

- Platform stats, 30-day booking chart, bookings-by-activity doughnut, latest bookings.
- **Venues** — approve/hide, feature/unfeature, edit (via vendor form), delete.
- **Users** — search, filter by role, change role, delete.
- **Activity types** — add any activity: name, unit label (Court/Station/Lane/Session…), Font Awesome icon, colour, default block, "customers pick a game", featured, sort order.
- **Games** — titles per gaming activity (platform, max players). Vendors attach them to services.

Moderators and Marketing Managers can access the admin panel; only Super Administrators are treated as full admins in ownership checks.

---

## 9. Production notes

- **Database** — switch to MySQL by setting `DB_CONNECTION=mysql` and the `DB_*` vars in `.env`, then `php artisan migrate --seed`. (The commented MySQL lines in `.env.example` are ready.)
- **Sessions/cache/queue** — set to `file`/`file`/`sync` for zero-config local use. For production use `database` or `redis` and run a queue worker if you move notifications to queues.
- **Assets** — the public site loads Tailwind via the Play CDN to preserve the original theme exactly. For production, move to the Vite build already scaffolded (`resources/css/app.css`, `@vite` in `layouts/app.blade.php`, `npm run build`) and audit Tailwind v4 class changes.
- **Uploads** — venue/service photos and bank slips go to `storage/app/public` (`php artisan storage:link`). Use S3 by pointing `FILESYSTEM_DISK` at the `s3` disk.
- **Approval flow** — venues are auto-approved on creation (`Vendor\VenueController@store`). Set `is_approved => false` there to require admin approval.
- **Roles** — `role:` middleware accepts a list (`role:Vendor,SuperAdministrator`). Enum values are stable integers so existing user rows keep working.

---

## 10. UI/UX decisions

- **One consistent brand** across pages: dark glass header, Bebas Neue display headings, Poppins body, red (#ef4444) primary, soft grey cards. Dashboards stay on SB Admin so vendors get a familiar, dense, table-first workspace.
- **Booking builder as a numbered wizard** with large tappable chips (44px+ targets), disabled states that explain *why* a time is unavailable, capacity hints ("8 left"), and a **sticky bottom summary on mobile** so the price and CTA are always visible.
- **Price transparency** — every screen shows the line items (blocks × option, peak surcharges) before any commitment; nothing is charged until a method is chosen.
- **Payment modal** (bottom sheet on mobile, centred card on desktop) states availability and priority for each method, and previews bank details inline so users aren't surprised later.
- **Priority is explained in plain language** on checkout, confirmation and notification copy ("Pay online next time to lock your slot"), turning a business rule into an incentive.
- **Vendor forms lead with defaults** — choosing an activity type pre-fills block size and reveals only the relevant game list; repeaters for options/rates keep advanced pricing optional.
- **Mobile nav** grows to fit all links (no fixed-height menu), and every table on the dashboards is horizontally scrollable.

Ideas for the next iteration: saved favourites, reviews tied to completed bookings, vendor calendar drag-to-block (maintenance), SMS reminders via Dialog/Mobitel gateways, multi-day tournaments, and the online gateways (the enum/UI already anticipate them).
