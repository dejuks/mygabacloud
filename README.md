# Digital Products Marketplace (Codester-style) — Laravel

A multi-vendor marketplace for selling digital products: PHP scripts, app
templates, WordPress themes, UI kits. Sellers upload products, admins approve
them, buyers purchase and download, and the platform takes a commission on
every sale.

**This is source code to drop into a fresh Laravel project — not a standalone
runnable app.** Follow the install steps below.

---

## Install

### 1. Create a Laravel project

```bash
composer create-project laravel/laravel marketplace
cd marketplace
```

### 2. Copy this package in

Copy the contents of this archive over your project root, merging the
`app/`, `database/`, `resources/`, `routes/` and `config/` folders.

This overwrites `app/Models/User.php` and `routes/web.php` — that is intended.

### 3. Configure `.env`

```ini
DB_CONNECTION=mysql
DB_DATABASE=marketplace
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Test mode completes payments instantly, no gateway credentials needed.
PAYMENT_DRIVER=test
AUTO_CLEAN_UPLOADS=true

# Email verification needs a working mailer. Use 'log' to read
# verification links out of storage/logs/laravel.log instead.
MAIL_MAILER=log
```

### 4. Register the middleware

Follow `bootstrap-middleware.md` — this adds the `seller` and `admin` route
middleware aliases and exempts webhooks from CSRF.

### 5. Add the private disk

Copy `config/filesystems.php` from this package over your project's own copy
of that file — it's the full Laravel default with the `private` disk added
for sellable product files.

### 6. Run the installer

```bash
chmod +x install.sh
./install.sh
```

Or manually:

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
php artisan storage:link
php artisan migrate
php artisan db:seed --class=DemoSeeder
php artisan serve
```

---

## Demo accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@marketplace.test | password |
| Buyer | buyer@marketplace.test | password |
| Seller | codecraft@marketplace.test | password |
| Seller | pixelforge@marketplace.test | password |
| Seller | themeworks@marketplace.test | password |
| Seller | assetlab@marketplace.test | password |

`DemoSeeder` builds a fully digital-goods catalog: **4 categories x 4 products
= 16 products** (PHP Scripts, App Templates, WordPress, UI Kits & Design
Assets), each with:

- a real generated thumbnail image (not a broken image link)
- a real downloadable ZIP with README/LICENSE files inside, stored on the
  private disk exactly the way a real upload would be
- realistic sales/view counts, a changelog entry, and category-appropriate
  pricing with regular + extended license tiers

It also seeds **one already-completed purchase** — the demo buyer already
owns "Laravel SaaS Starter Kit" — so `/library` has something to download
and the seller's wallet shows a real available balance, without you having
to run through checkout first. A handful of products have reviews attached
so rating stars aren't empty on first load.

Re-running the seeder is safe — it's idempotent (`updateOrCreate` /
`firstOrCreate` throughout), so running `php artisan db:seed --class=DemoSeeder`
again won't create duplicates.

---

## Try the full flow

1. **Browse** — open `/`, click through categories and products
2. **Buy** — log in as the buyer, add a product to cart, check out
   (test mode completes instantly)
3. **Download** — go to `/library`, see your license key and download button
4. **Sell** — log in as the seller, visit `/seller/dashboard`, see the sale
   and the commission split in the wallet
5. **Approve** — log in as admin, visit `/admin/products/pending` to review
   and approve new submissions
6. **Payout** — as the seller request a payout, as admin approve and mark it
   paid, and watch the wallet debit

---

## What is built

**Buyers** — registration with email verification, browse and search with
filters, cart with coupons, checkout, license keys, secure downloads, reviews
(verified purchasers only).

**Sellers** — application and approval flow, dashboard with sales stats,
product CRUD with private file uploads, changelogs, wallet with pending and
available balances, payout requests.

**Admins** — dashboard with revenue and liability figures, product review
queue with approve/reject and rejection reasons, seller application approvals,
per-seller commission overrides, payout processing, platform settings.

**Money** — commission is snapshotted per sale so rate changes never rewrite
history; every wallet movement is written to an immutable ledger; earnings sit
in a pending balance through a configurable holding period before they can be
withdrawn.

---

## Payment methods

Five ways to pay, three different levels of automation:

- **Card (Stripe)** and **PayPal** — fully automatic, as described above. Mastercard needs no
  separate integration; Stripe handles all major card networks already.
- **CBE Bank Transfer**, **Telebirr**, and **USDT (TRC20)** — these are manual-verification
  methods: the buyer sees payment instructions (pulled from `PlatformSetting`, editable at
  Admin > Settings), pays externally, uploads a screenshot + reference/TxID, and the order sits
  as `pending` until an admin approves it from Admin > Manual payments. Approving calls the same
  `OrderFulfilmentService` as Stripe/PayPal — commission math, license issuance, and seller wallet
  credit all work identically regardless of which payment method was used.

  **Why these three are manual, not automatic:** CBE and Telebirr have no public self-serve API for
  individual sellers — real automation there requires signing up with a licensed Ethiopian payment
  aggregator (Chapa, SantimPay, ArifPay, etc.). Sending USDT to a personal wallet address (as opposed
  to a custodial payment processor like NOWPayments) similarly can't be auto-confirmed without either
  holding private keys or running a blockchain-watching service. Manual review is the honest,
  practical version of this — it's also how most African/Ethiopian marketplaces actually handle it.

  **Before enabling these for real buyers:** go to Admin > Settings and fill in your real CBE and
  Telebirr account details — they're blank by default. The USDT (TRC20) details are pre-seeded from
  what was provided (`Dejene Kasa Aelmu`, `TEaXhSpnYEZjpahJgXB284s3FudSVigFfH`) — double-check that
  address in the settings page before going live, since a typo sends funds somewhere unrecoverable.

- **`CryptoGateway`** (`app/Services/Payments/CryptoGateway.php`) is also included but not wired into
  any route — it's a fully-automated USDT TRC20/BEP20 option via NOWPayments' hosted invoice API, for
  if/when you want to move off manual verification. See the class's docblock for the `.env` keys it needs.

## Free download path (YouTube engagement)

Sellers can opt any product into a free-access path: buyers watch a video, subscribe, like, comment,
and share, then submit a description of what they did for manual admin approval (Admin > Free
unlocks). There's no API that proves a YouTube subscribe or like happened, so this is inherently an
honor-system + admin-judgment flow — the UI is upfront about that with both the seller and the buyer.
Approving issues a real license with **no** order, no commission, and no wallet credit — it's a
promotional giveaway the seller opted into, not a sale.

## Admin panel

Redesigned with a proper sidebar layout (`resources/views/layouts/admin.blade.php`) — a dark gradient
sidebar with icons, live pending-count badges pulled fresh on every page load (no risk of a controller
forgetting to pass them), and consistent card styling across every admin page.

## Stripe & PayPal implementation details

- **Stripe** — `StripeGateway` opens a Checkout Session; `StripeWebhookController`
  verifies the signature and fulfils the order on `checkout.session.completed`.
- **PayPal** — `PayPalGateway` opens a PayPal order via the Orders v2 REST API
  (no extra Composer package — it uses Laravel's own `Http` client). The buyer
  approves on PayPal's site, is redirected back to `checkout.paypal.capture`,
  which captures the payment and fulfils the order immediately. The
  `PayPalWebhookController` re-processes the same event as an idempotent
  backstop in case the redirect never completes, and verifies every webhook
  against PayPal's signature-verification endpoint before trusting it.

Follow `config/services.paypal.patch.md` for the config keys and sandbox
credentials setup for both gateways.

Set `PAYMENT_DRIVER=test` locally (no credentials needed, orders complete
instantly) and `stripe` or leave the checkout page showing both real options
once your keys are in `.env` — the checkout view already renders Stripe and
PayPal as radio choices when the driver isn't `test`.

## Malware scanning

`ScanProductFileJob` is a queued job that shells out to ClamAV's `clamscan` on
every uploaded product ZIP. It is dispatched automatically on upload whenever
`AUTO_CLEAN_UPLOADS=false`. If ClamAV isn't installed on the machine running
the queue worker, the file is left in `pending` — it is never silently marked
safe — and shows up in the admin product-review queue for a manual "Mark file
clean" override.

To enable it: install ClamAV (`apt install clamav clamav-daemon` on Ubuntu,
then `freshclam` to update virus definitions), run a queue worker
(`php artisan queue:work`), and set `AUTO_CLEAN_UPLOADS=false`.

---

## Going to production

These are still required:

1. **Set `PAYMENT_DRIVER` away from `test`** and put real Stripe/PayPal keys
   in `.env` — see `config/services.paypal.patch.md`.

2. **Set `AUTO_CLEAN_UPLOADS=false`** and make sure a queue worker with
   ClamAV installed is actually running — otherwise files sit `pending`
   forever and never reach the buyer.

3. **Move file storage to S3.** Local disk does not survive redeploys and will
   not scale. Change the `private` disk to the `s3` driver.

4. **Schedule the holding-period release.** `PayoutApprovalController::releasePending()`
   is currently a manual admin button. Move it into a scheduled command
   (`php artisan schedule` + a small Artisan command wrapping the same logic).

5. **Run queues properly.** Notifications, webhook processing and file
   scanning should not run synchronously. Set `QUEUE_CONNECTION=redis` and
   run `php artisan queue:work` under a process manager (Supervisor/Horizon).

6. **Add search.** The browse page uses SQL `LIKE`, which will not hold up past
   a few thousand products. Laravel Scout with Meilisearch is the drop-in fix.

---

## Not included

Admin refund-initiation UI (refunds arrive via webhook once a gateway
processes one, but there's no "refund this order" button for admins yet),
seller review replies in the UI, messaging between buyers and sellers, and
automated tests. The database schema and service layer support all of these
— they're additive, not structural, changes.
