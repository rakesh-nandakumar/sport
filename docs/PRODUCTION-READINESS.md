# EntryPoint.lk — what is still missing before we go live

Plain-language list of gaps, risks and weak spots, so we can pick what to fix next.
Updated 14 Sep 2026 after the moderation / checkout-modal / location work.

Legend: 🔴 must fix before launch · 🟠 should fix soon after launch · 🟡 nice to have

---

## 1. Money and payments

- 🔴 **No real online payment.** Only "Pay at Venue" and "Bank Transfer" work. Card, Koko, Mint Pay and PayEasy are shown as "Coming soon" and cannot be switched on until a gateway is integrated (PayHere / WebXPay / Koko API, etc.). Until then the site cannot collect money itself.
- 🔴 **Bank transfers are verified by hand.** The vendor has to look at the slip and press "Verify". There is no automatic matching against a bank statement, so a fake slip can only be caught by the vendor.
- 🟠 **The 20-minute verification window is aggressive.** A booking made at 11 pm expires at 11:20 pm if the vendor is asleep. Options: make the window longer, pause the clock overnight, or only start the clock after the slip is uploaded and give the vendor until 1 hour before the game. The number is a site setting, so this is a policy choice, not code.
- 🟠 **An unverified bank transfer can bump a pay-at-venue hold and then expire.** The cash customer loses their slot for nothing. Better rule: only bump the cash hold once the transfer is *verified*. Not implemented yet.
- 🟠 **No commission / platform fee model.** The platform earns nothing per booking today. No vendor invoicing, no payout ledger, no settlement reports.
- 🟠 **No refunds or cancellation policies.** Cancelling a paid booking just marks it cancelled; there is no refund record, no cancellation deadline, no partial-refund rules per venue.
- 🟡 **No receipts / invoices** (PDF or email) for customers or vendors.

## 2. Booking engine — known limits

- 🟠 **Buffers cost a whole block.** A 10-minute cleaning gap on 60-minute blocks blocks the entire neighbouring hour (start times stay aligned to the grid). Fine for most venues, wasteful for small gaps. Fix idea: let a block include its buffer (e.g. 50 min play + 10 min reset) or allow off-grid start times.
- 🟠 **Double-booking under heavy load is prevented by row locks on MySQL/Postgres only.** SQLite (local dev) serialises writes anyway. Make sure production uses MySQL/Postgres.
- 🟡 **One booking = one unit.** A group wanting 3 PS5 seats must make 3 bookings. No "quantity" field.
- 🟡 **No recurring bookings** (every Tuesday 7 pm), no multi-day events / tournaments, no waiting list when a slot is full.
- 🟡 **No vendor "block-out" tool** (maintenance, private event). Vendors have to create a fake booking or close the day.
- 🟡 **Services inherit venue hours or override them with one window** — no per-day override for a single service, no public-holiday calendar.
- 🟡 **Prices are per block only.** No per-player pricing, no deposits, no packages (e.g. "5 sessions for the price of 4"), no coupons.

## 3. Vendor onboarding and trust

- 🟠 **KYC is manual and light.** We collect BR number, NIC, address and documents, but nobody checks them against a registry. There is no NIC/BR format check beyond the pattern, no phone or email verification.
- 🔴 **No email verification for anyone.** Customers and vendors can register with a wrong or fake email; password reset does not exist (see §5).
- 🟠 **Vendor cannot edit their own business profile** after applying (only the admin sees it). They also cannot re-upload documents if rejected.
- 🟡 **No audit log.** We store who approved/suspended and when, but not a history of every change (venue price edits, hours changes, cancellations by staff).
- 🟡 **Only one login per business.** No staff sub-accounts for a venue (receptionist vs owner).

## 4. Customer experience

- 🟠 **No SMS or email notifications.** All alerts are in-app only. In Sri Lanka SMS is the channel that works (Dialog/Mobitel/Hutch gateways or a service like Notify.lk / textit.biz). Booking reminders 1–2 hours before start are also missing.
- 🟠 **Reviews are not tied to real visits.** Anyone can be given a review in the seed; there is no "review after a completed booking" flow and no review form on the site at all.
- 🟡 **No favourites / saved venues**, no share links, no "invite friends to this booking".
- 🟡 **Guest checkout is not possible** — you must create an account to book.
- 🟡 **Personalisation is simple.** Ranking = bookings ×3 + views ×1, then distance. There is no "popular near you this week", no time-of-day awareness, no collaborative filtering. Good enough to start, easy to improve later.
- 🟡 **Location from a district is a rough centre point** (e.g. "Colombo" = Fort). GPS is accurate; the fallback is not.
- 🟡 **Maps are links only.** No embedded map on venue pages or a map view of search results (would need Google Maps / Leaflet + tiles).

## 5. Accounts and security

- 🔴 **No "forgot password".** Locked-out users have no way back in.
- 🔴 **No email verification / phone OTP.**
- 🟢 Done: login (10/min), registration (5/min), slip upload and location endpoints are rate-limited. Still worth adding a captcha on vendor registration if spam appears.
- 🟠 **No two-factor auth for admins.** The super-admin account controls money settings and vendor activation.
- 🟢 Done: bank slips and vendor KYC documents are on the private disk and only the customer, the venue owner and staff can open them.
- 🟠 **No CSRF/XSS review of every form, no security headers (CSP, HSTS)** configured yet.
- 🟡 **Roles are simple.** Moderator / Marketing Manager can see everything in the admin panel (read-only for vendor status and settings), but there are no finer permissions.

## 6. Data and master data

- 🟢 Done: activity types (with photos), games, amenities list, districts, payment methods, hold timer, booking window, approval switches are all configurable by the super admin (activity types/games/settings in the panel; districts in `config/entrypoint.php`).
- 🟡 **Cities are free text.** A vendor can type "Colombo 5" and another "Colombo 05"; the city filter then shows both. A managed city list (or district + suburb pick-list) would fix it.
- 🟡 **No soft deletes.** Deleting a venue or user deletes their bookings and history permanently.
- 🟡 **No database backups configured** and no data-retention policy for KYC documents.

## 7. Production setup — things that will break or be lost when we deploy as-is

- 🔴 **SQLite must become MySQL/Postgres.** SQLite is fine locally but will corrupt under concurrent traffic and does not support the row locks the booking engine relies on.
- 🔴 **The scheduler must run.** `php artisan schedule:run` every minute (cron) — otherwise expired bank-transfer holds are only cleaned up when someone books or opens a list.
- 🔴 **Sessions/cache/queue are file/sync.** Move to Redis (sessions + cache) and run a queue worker; notifications should be queued so booking is not slowed down by them.
- 🔴 **Uploads are on local disk.** Venue photos, slips, activity photos and KYC documents live in `storage/`. On a second server or after a rebuild they are gone. Use S3-compatible storage (AWS S3, DigitalOcean Spaces, Cloudflare R2).
- 🔴 **Tailwind is loaded from the Play CDN.** Slow, unsupported for production, and pages flash unstyled. Build the CSS with Vite (`npm run build`) and switch the layout to `@vite`.
- 🔴 **`APP_DEBUG=true`, `APP_KEY` and `.env` in the repo template** — set proper production env, `APP_DEBUG=false`, real `APP_URL`, HTTPS.
- 🟠 **No error monitoring** (Sentry/Flare), no uptime check, no log shipping.
- 🟠 **No CI.** Tests (46, all passing) run only by hand. Add GitHub Actions to run `php artisan test` + `pint --test` on every push.
- 🟠 **Fonts and icons come from Google Fonts / cdnjs / unpkg** — the site depends on third parties and leaks visitor IPs. Self-host for speed and privacy.
- 🟠 **No mail driver configured.** Even if we add email features, `MAIL_MAILER=log` means nothing is sent.
- 🟡 **Demo accounts are shown on the login page** — remove for production.
- 🟡 **Seed data contains made-up bank account numbers and NICs** — never run the seeders against production.

## 8. Things we tested and where the tests stop

- ✅ 46 automated tests, 332 checks: booking flow with the modal, bank transfer with slip, priority/bumping, capacity per block, gap-limited durations, minimum-duration starts, buffers, closing time, after-midnight venues, midnight peak rates, lead time, booking window, hold expiry + sweeper, settings-controlled payment methods, vendor application/activation/suspension, admin permissions, site settings, activity photos, location prompt/storage, nearest sorting, tile ranking, recently viewed.
- ✅ Real-browser run (Chrome via Playwright): home with geolocation, nearest sort, venue page rates/today strip, modal with bank transfer + upload, confirmation countdown, vendor verification, vendor application, admin activation, settings — no console or server errors.
- ❌ Not tested: mobile Safari, slow networks, screen readers, concurrent bookings from many users at once (load test), file uploads larger than a few MB, PDF slips rendering in the vendor panel, timezone behaviour of a browser outside Sri Lanka.

## 9. Suggested order of work

1. Production infrastructure (§7 🔴 items) + password reset + email/phone verification.
2. SMS/email notifications and reminders; email/phone verification.
3. Online payment gateway (PayHere first — most Sri Lankan cards + wallets) and the "bump only after verification" rule.
4. Reviews after completed bookings, favourites, embedded maps.
5. Commission/payout model, refunds and cancellation policies, invoices.
6. Quantity per booking, recurring bookings, vendor block-outs, waiting lists.
