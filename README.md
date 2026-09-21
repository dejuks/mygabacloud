# Gaba Cloud – Mobile API layer (v1)

New, isolated JSON API for the Android app. The Blade website, its web
controllers, `routes/web.php` and session login are **not modified**.

## What is added (all new files)

| File | Purpose |
|---|---|
| `routes/api.php` | All URLs under `/api/v1`, route names `api.v1.*` |
| `app/Http/Controllers/Api/V1/AuthController.php` | Register, login, logout, me, forgot/reset password, resend verification (Sanctum tokens) |
| `.../CatalogController.php` | Categories, product list (search/filter/sort/paginate), product detail |
| `.../ReviewController.php` | List reviews, post a review (verified buyers only) |
| `.../LibraryController.php` | Purchased items, signed download link, ZIP download |
| `app/Http/Resources/Api/V1/*` | JSON shape of each model (the only place field names are mapped) |
| `app/Http/Middleware/EnsureApiUserActive.php` | Blocks suspended/banned users on API calls |

## Install (4 steps)

```bash
# 1. Sanctum + routes/api.php registration (Laravel 12)
php artisan install:api      # answer "yes" to run migrations
```
Copy this package's `routes/api.php` over the one it generated, and copy the
`app/` folder into your project.

**2. Add one trait to `app/Models/User.php`** (the only existing file touched):
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable /* implements MustVerifyEmail */
{
    use HasApiTokens, HasFactory, Notifiable;   // add HasApiTokens
```
> Your README says the package overwrites `User.php` on install. If you re-copy
> the package later, re-add `HasApiTokens`.

**3. Make API errors always JSON** in `bootstrap/app.php` (keep your existing
middleware-alias code from `bootstrap-middleware.md`; only add this block):
```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->shouldRenderJsonWhen(
        fn ($request, $e) => $request->is('api/*') || $request->expectsJson()
    );
})
```
Also confirm `withRouting(...)` now has both `web:` and `api:` entries.

**4. Optional token lifetime** – `config/sanctum.php`: `'expiration' => 60 * 24 * 30,` (30 days).

Verify nothing collides:
```bash
php artisan route:list --path=api
```

## Endpoints

| Method | URL | Auth | Notes |
|---|---|---|---|
| POST | `/api/v1/auth/register` | – | `name, email, password, password_confirmation, device_name?` |
| POST | `/api/v1/auth/login` | – | Returns `token`. `403 code=email_unverified` if not verified |
| POST | `/api/v1/auth/forgot-password` | – | Reset email link opens the website page |
| POST | `/api/v1/auth/reset-password` | – | `token, email, password, password_confirmation` |
| GET | `/api/v1/auth/me` | Bearer | |
| POST | `/api/v1/auth/logout` | Bearer | Revokes this device's token |
| POST | `/api/v1/auth/email/resend` | Bearer | |
| GET | `/api/v1/categories` | – | |
| GET | `/api/v1/products` | – | `category, q, sort=popular\|newest\|price_asc\|price_desc, min_price, max_price, per_page, page` |
| GET | `/api/v1/products/{slug}` | – | |
| GET/POST | `/api/v1/products/{slug}/reviews` | POST needs Bearer | |
| GET | `/api/v1/library` | Bearer | Purchased items + license keys |
| POST | `/api/v1/library/{productId}/download-link` | Bearer | Returns 5-minute signed URL |
| GET | `/api/v1/library/{productId}/download` | signed URL | Streams the ZIP |

Send `Accept: application/json` and `Authorization: Bearer <token>` from Android.

```bash
curl -X POST https://gabacloud.com/api/v1/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"buyer@marketplace.test","password":"password","device_name":"pixel"}'
```

## Verified against your database (v1.1)

Checked against `gabaclou_db` from the SQL dump you uploaded:

| Topic | Rule used by the API |
|---|---|
| Who owns a product | `licenses.buyer_id = user` and `licenses.status = 'active'` |
| Public product | `products.status = 'approved'` and `deleted_at IS NULL` |
| Prices | `regular_price`; `sale_price` only while `sale_ends_at` is empty or in the future |
| Ratings | `average_rating` / `reviews_count` columns (recalculated after an API review) |
| Downloads | newest `product_files` row with `scan_status = 'clean'` and `integrity_status != 'corrupted'`, read from that row's `disk` and `path` |
| Reviews | need an active licence with an `order_item_id` in a paid order (reviews.order_item_id is NOT NULL) |
| Accounts | `users.status` must be `active` (checked at login and on every protected call) |
| Categories | `is_active = 1`, ordered by `sort_order`; a parent shows its sub-categories' products |

## Still assumed (could not see the PHP code)

- Model classes `App\Models\{Product, Category, License, Review}` exist. (`License` and `Category` are confirmed by earlier runs.)
- The `private` disk configured in `config/filesystems.php` holds the product files.
- Thumbnails are served from `/storage/<thumbnail>` (public disk + `storage:link`).
- Your website's own download controller may apply extra rules (e.g. `duplicate_of_file_id`,
  refunded order items). If so, add the same conditions in
  `LibraryController::findDownloadableFile()`.
- The API does not increase `views_count` or write `activity_logs` rows. Say so if you want that.

## Not built yet (needs the real code to do safely)

Checkout/orders (Stripe, PayPal, manual CBE/Telebirr/USDT with screenshot
upload), coupons, cart, free-unlock (YouTube) requests, seller dashboard /
product upload / wallet / payouts, and push notifications. These all call
existing services (`OrderFulfilmentService`, the gateway classes), so I need
their real signatures before writing controllers that reuse them instead of
duplicating money logic.
