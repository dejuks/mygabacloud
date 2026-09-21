# Gaba Cloud – Mobile API layer (v1)

New, isolated JSON API for the Android app. The Blade website, its web
controllers, `routes/web.php` and session login are **not modified**.

## What is added (all new files)

| File | Purpose |
|---|---|
| `routes/api.php` | All URLs under `/api/v1`, route names `api.v1.*` |
| `app/Http/Controllers/Api/V1/AuthController.php` | Register, login, logout, me, forgot/reset password, resend verification (Sanctum tokens) |
| `.../CatalogController.php` | Categories, product list (search/filter/sort/paginate), product detail |
| `.../ReviewController.php` | List reviews, post a review (verified owners only) |
| `.../LibraryController.php` | Purchased items, signed download link, ZIP download |
| `app/Http/Resources/Api/V1/*` | JSON shape of each model (the only place field names are mapped) |

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

## Assumptions (I could not read `app/` or `routes/`)

GitHub blocked automated access to the repo's `app/` and `routes/` folders, so
I inferred the schema from the README and the website. Please treat these as
**unverified**:

- Models exist as `App\Models\{Product, Category, License, Review}`.
- `Product`: `title, slug, description, price, sale_price, extended_price, thumbnail, demo_url, status ('approved'), sales_count, file_path`; relations `category`, `reviews`, optional `seller`.
- `Category`: `name, slug`, relation `products`.
- `License`: `user_id, product_id, license_key, type`, relation `product`.
- `Review`: `user_id, product_id, rating, comment`, relation `user`.
- Product files live on the `private` disk at `products.file_path`.

Fastest way to correct them: run `php artisan model:show Product` (and the same for
`Category`, `License`, `Review`, `User`) and send me the output.

## Security note – must review before going live

`LibraryController::resolveDownloadableFile()` is a placeholder. The website
scans uploads with ClamAV and leaves files `pending` until cleared, so the web
download controller must already check that status. **Copy that check into the
API**, otherwise the app could serve a file the website would refuse.

## Not built yet (needs the real code to do safely)

Checkout/orders (Stripe, PayPal, manual CBE/Telebirr/USDT with screenshot
upload), coupons, cart, free-unlock (YouTube) requests, seller dashboard /
product upload / wallet / payouts, and push notifications. These all call
existing services (`OrderFulfilmentService`, the gateway classes), so I need
their real signatures before writing controllers that reuse them instead of
duplicating money logic.
