# Sportee

Sportee is a booking marketplace for indoor sports and gaming venues — think futsal courts, PS5 lounges, pool halls, and board-game cafés — all listed, browsed, and booked from one platform. Venue owners list their space and its bookable units, customers search and reserve time slots, and admins oversee the whole marketplace from an analytics dashboard.

Built with **Laravel 10**, **Livewire 3** (+ Volt), **Tailwind CSS**, **FullCalendar**, and **Chart.js**, on a MySQL database.

---

## How it works

1. **A venue owner registers** and creates a listing (an *Indoor*) — name, location, description, contact info, photos/gallery, and default weekly opening hours.
2. **The owner adds Resources** to that listing — the actual bookable units inside the venue (e.g. "PS5 Booth 1", "Court A", "Pool Table 2"). Each resource has an **Activity type** (futsal, PS5, pool/snooker, board games, tennis, etc.) which drives type-specific fields (surface, floodlighting, controller count, game library, table size...), a pricing unit (per hour / per session / per game / per person), capacity, and its own opening-hours override.
3. **Customers browse the homepage**, search/filter venues by activity or keyword, and open a venue page to see its resources, gallery, hours, and reviews.
4. **Customers book a resource** through a live availability widget: pick a resource, see a calendar of already-booked slots (via FullCalendar), choose a time (or quantity, for non-time-based pricing like "per game"), optionally pick bookable extras (e.g. a game from the game library), and the total price is calculated live before confirming.
5. **Owners manage bookings** from their own dashboard — a calendar view of all reservations for their venue, KPIs (bookings today/this week/this month), and the ability to log walk-in/phone bookings directly on behalf of a customer.
6. **Admins** (SuperAdministrator role) get a platform-wide analytics dashboard: total venues, users, customers, bookings, and tournaments, with month-by-month charts, plus the ability to inspect any venue's bookings and manage user accounts/roles.
7. Alongside venue bookings, users can also **create and browse tournaments** (title, date, entry fee, capacity, contact info), **leave comments/reviews** on venue pages, and **subscribe to a newsletter** (Mailchimp integration).

---

## User roles

Roles are stored as an integer `role_id` on the `users` table (`App\Enums\Role`):

| Role | Value | What they can do |
|---|---|---|
| **SuperAdministrator** | 1 | Full platform analytics dashboard, manage all users/roles, inspect any venue's bookings |
| **User** (venue owner) | 2 | Create/edit/delete venue listings, manage resources, view their own booking calendar & stats, book on behalf of walk-in customers |
| **moderator** | 3 | Reserved role (defined, not yet wired to a distinct dashboard) |
| **MarketingManager** | 4 | Reserved role |
| **Customer** | 5 | Register, browse venues, book resources, view booking history, cancel bookings, comment |

Registration has two separate flows — `/register` (venue owner) and `/register/customer` (customer) — which set `role_id` accordingly.

---

## Core functionality

### Venue listings (Indoors)
- Full CRUD for venue listings, scoped to the owning user (`IndoorController`)
- Cover photo + multi-image gallery upload
- Per-weekday opening/closing hours (Mon–Sun), used as the default for all of that venue's resources
- Search & filter by keyword (title/tags/location) or activity type (`Indoor::scopeFilters`)
- Public venue page with location, description, photo gallery, hours, and reviews

### Bookable resources
- Each venue has many **Resources** — the actual unit being booked (a court, a console booth, a table)
- Resources are typed by **Activity** (11 built-in types: futsal, football, cricket, badminton, tennis, basketball, volleyball, squash, pickleball, PS5/console gaming, pool/snooker/billiards, table tennis, board games, "other") — see [config/activities.php](config/activities.php)
- Each activity type declares its own custom fields (e.g. surface type, floodlighting, controller count, table size) and a default pricing unit
- Pricing units: **per hour**, **per session**, **per game/frame**, **per person** — time-based units drive a start/end time picker, quantity-based units drive a quantity stepper
- Resources can expose a **bookable list field** (e.g. a game library) customers pick from when booking
- Resources can override the venue's default opening hours per weekday
- Owners manage resources through a dedicated Livewire component (`ManageResources`) with inline create/edit/delete

### Booking
- Live **FullCalendar** availability view per resource, showing existing bookings so customers can't double-book a slot
- Booking widget (`BookingWidget` Livewire component) live-calculates the total price as the customer selects a time range or quantity, and any selected extras
- Booking validation and creation is centralized in `BookingService`, which is shared by both the customer-facing booking flow and the owner's "book on behalf of a walk-in customer" flow
- Customers can cancel their own bookings
- Customers see a full booking history page

### Tournaments
- Full CRUD for tournaments (title, date, number of players, entry fee, contact number, description, photo)
- Public tournament listing on the homepage and a dedicated tournament detail page
- Owners manage their own created tournaments from a "manage tournaments" page

### Reviews / comments
- Authenticated users can comment on a venue's page; comments require login

### Admin analytics
- Platform-wide dashboard: total venues, users, customers, bookings, tournaments, bookings made today/this month
- Chart.js visualizations: bookings over the last 30 days, new customers over the last 30 days, customers per month
- Per-venue drill-down showing that venue's own booking calendar and stats

### Owner analytics
- Per-venue KPIs: total bookings, bookings this week/this month/today
- Booking volume by month, rendered as a chart
- Calendar view of all bookings for the venue

### Newsletter
- Homepage footer newsletter signup, wired to Mailchimp via `spatie/laravel-newsletter`

### Notifications
- A notifications page for the logged-in user (`App\SystemMessageNotification`)

---

## Pages

| Route | Page | Who |
|---|---|---|
| `/` | Homepage — hero, activity showcase, featured venues, upcoming tournaments, newsletter signup | Everyone |
| `/home/{indoor}` | Venue detail — gallery, hours, resources, reviews, booking widget | Everyone (booking requires login) |
| `/home/create` | Create a venue listing | Owners |
| `/home/{indoor}/edit` | Edit a venue listing | Owner (own venue) |
| `/home/manage` | "My venues" management list | Owners |
| `/home/{indoor}/resources` | Manage a venue's bookable resources | Owner (own venue) |
| `/clients/{indoor}` | Owner's booking dashboard for one venue — calendar, KPIs, book-for-customer | Owner (own venue) |
| `/client-dashboard` | Owner's cross-venue analytics dashboard | Owners |
| `/tournament/create` | Create a tournament | Authenticated users |
| `/tournament/manage` | "My tournaments" management list | Authenticated users |
| `/tournament/{tournament}/edit` | Edit a tournament | Owner (own tournament) |
| `/home/tournament/{tournament}` | Tournament detail page | Everyone |
| `/customer/history` | Booking history | Customers |
| `/notifications` | Notifications | Authenticated users |
| `/login`, `/register`, `/register/customer` | Auth — login, owner signup, customer signup | Guests |
| `/admin/dashboard` | Admin shell | SuperAdministrator |
| `/admin/analysis` | Platform-wide analytics dashboard | SuperAdministrator |
| `/admin/view-users` | User management (list, edit role, delete) | SuperAdministrator |
| `/admin/indoors/{indoor}` | Admin view into a single venue's bookings/stats | SuperAdministrator |

---

## Data model

```
User ──┬── Indoor (venue) ──┬── Resource (bookable unit) ── Activity (type: futsal, PS5, pool, ...)
       │                    ├── Comment
       │                    └── Booking ── Resource, User
       ├── Tournament
       └── Booking
```

- **User** — auth + `role_id` (owner / customer / admin / ...)
- **Indoor** — a venue listing, owned by a User, has default weekly hours
- **Resource** — a bookable unit inside a venue (typed by Activity), with its own pricing unit, capacity, custom fields, and optional hour overrides
- **Activity** — a booking type (futsal, PS5, pool/snooker, board games, etc.) with a config-driven field schema
- **Booking** — a reservation of a Resource by a User, with start/finish time or unit quantity, selected options, status, and total price
- **Tournament** — an event listing owned by a User
- **Comment** — a review left by a User on an Indoor

---

## Getting started

```bash
composer install
npm install

cp .env.example .env   # if you don't already have a .env
php artisan key:generate

# configure DB_* in .env, then:
php artisan migrate
php artisan db:seed     # seeds the built-in activity types

npm run build            # or `npm run dev` for hot-reload while developing
php artisan serve
```

Convenience routes also exist for quick setup without the CLI: `/migrate` and `/seed` (run migrations/seeders over HTTP — intended for local/dev use only).

### Running tests

```bash
php artisan test
```
