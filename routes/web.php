<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\{
    HomeController,
    ProductController,
    CartController,
    CheckoutController,
    LibraryController,
    EngagementUnlockController,
    PageController,
    ProfileController
};
use App\Http\Controllers\Seller\{
    DashboardController as SellerDashboard,
    ProductController as SellerProductController,
    PayoutController as SellerPayoutController,
    ReportController as SellerReportController
};
use App\Http\Controllers\Admin\{
    DashboardController as AdminDashboard,
    ProductApprovalController,
    ProductController as AdminProductController,
    SellerApprovalController,
    PayoutApprovalController,
    SettingsController,
    CategoryController,
    ReportController,
    ManualPaymentController,
    EngagementApprovalController,
    AppDownloadSettingController
};
use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Http\Controllers\Webhooks\PayPalWebhookController;

/*
|--------------------------------------------------------------------------
| Install wizard — unreachable once storage/installed.lock exists
|--------------------------------------------------------------------------
*/
Route::middleware('installed')->prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database/test', [InstallController::class, 'testDatabase'])->name('database.test');
    Route::post('/database', [InstallController::class, 'storeDatabase'])->name('database.store');
    Route::get('/settings', [InstallController::class, 'settings'])->name('settings');
    Route::post('/settings', [InstallController::class, 'storeSettings'])->name('settings.store');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'storeAdmin'])->name('admin.store');
    Route::get('/license', [InstallController::class, 'license'])->name('license');
    Route::post('/license', [InstallController::class, 'storeLicense'])->name('license.store');
    Route::get('/finish', [InstallController::class, 'finish'])->name('finish');
    Route::post('/run', [InstallController::class, 'runInstall'])->name('run');
});

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Public license verification API - lets a sold script validate its purchase code
Route::get('/api/license/{key}', [LibraryController::class, 'verify'])->name('license.verify');

Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('pages.contact.submit');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms-of-service', [PageController::class, 'terms'])->name('pages.terms');

// Mobile app download - APK stored privately, served here so downloads can be counted
Route::get('/download-app/android.apk', [AppDownloadSettingController::class, 'downloadApk'])
    ->name('app-download.android.apk');

/*
|--------------------------------------------------------------------------
| Cart - browsable by guests, checkout requires login
|--------------------------------------------------------------------------
*/
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->middleware('auth')->name('cart.add');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

/*
|--------------------------------------------------------------------------
| Authenticated buyers (email must be verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/{order}/success', [CheckoutController::class, 'success'])->name('checkout.success');

    // TEST DRIVER ONLY - simulates a gateway callback in local development
    Route::get('/checkout/{order}/test-complete', [CheckoutController::class, 'completeTest'])
        ->name('checkout.test.complete');

    // Buyer lands here after approving payment on PayPal's site
    Route::get('/checkout/{order}/paypal/capture', [CheckoutController::class, 'capturePaypal'])
        ->name('checkout.paypal.capture');

    // Manual payment methods (CBE, Telebirr, USDT-manual): instructions -> proof upload -> pending review
    Route::get('/checkout/{order}/pay-manually', [CheckoutController::class, 'manualInstructions'])
        ->name('checkout.manual.instructions');
    Route::post('/checkout/{order}/pay-manually', [CheckoutController::class, 'submitManualProof'])
        ->name('checkout.manual.submit');
    Route::get('/checkout/{order}/pending', [CheckoutController::class, 'manualPending'])
        ->name('checkout.manual.pending');

    Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('/library/{license}/download', [LibraryController::class, 'download'])->name('library.download');

    Route::post('/products/{product}/reviews', [ProductController::class, 'storeReview'])->name('reviews.store');

    // Seller application (open to any verified user)
    Route::get('/become-a-seller', [SellerDashboard::class, 'applyForm'])->name('seller.apply.form');
    Route::post('/become-a-seller', [SellerDashboard::class, 'apply'])->name('seller.apply');

    // Free download path: complete YouTube engagement, submit for manual approval
    Route::get('/products/{product}/unlock', [EngagementUnlockController::class, 'create'])->name('unlock.create');
    Route::post('/products/{product}/unlock', [EngagementUnlockController::class, 'store'])->name('unlock.store');
});

/*
|--------------------------------------------------------------------------
| Seller area - requires an APPROVED seller profile
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'seller'])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {
        Route::get('/dashboard', [SellerDashboard::class, 'index'])->name('dashboard');

        Route::get('/products', [SellerProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [SellerProductController::class, 'create'])->name('products.create');
        Route::post('/products', [SellerProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [SellerProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('/products/{product}/images/{image}', [SellerProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::post('/products/{product}/submit', [SellerProductController::class, 'submitForReview'])->name('products.submit');
        Route::post('/products/{productId}/restore-request', [SellerProductController::class, 'requestRestore'])->name('products.restore-request');

        Route::get('/payouts', [SellerPayoutController::class, 'index'])->name('payouts.index');
        Route::get('/reports', [SellerReportController::class, 'index'])->name('reports.index');
        Route::post('/payouts', [SellerPayoutController::class, 'store'])->name('payouts.store');
    });

/*
|--------------------------------------------------------------------------
| Admin area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::post('/products/{product}/toggle-featured', [AdminProductController::class, 'toggleFeatured'])->name('products.toggle-featured');
        Route::get('/products/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [SellerProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/products/pending', [ProductApprovalController::class, 'index'])->name('products.pending');
        Route::get('/products/{product}/review', [ProductApprovalController::class, 'show'])->name('products.review');
        Route::post('/products/{product}/approve', [ProductApprovalController::class, 'approve'])->name('products.approve');
        Route::post('/products/{product}/reject', [ProductApprovalController::class, 'reject'])->name('products.reject');
        Route::post('/products/restore-requests/{productRestoreRequest}/approve', [ProductApprovalController::class, 'approveRestore'])->name('products.restore-requests.approve');
        Route::post('/products/restore-requests/{productRestoreRequest}/reject', [ProductApprovalController::class, 'rejectRestore'])->name('products.restore-requests.reject');
        Route::post('/products/{product}/files/{file}/clean', [ProductApprovalController::class, 'markFileClean'])->name('products.file.clean');
        Route::get('/products/{product}/files/{file}/preview', [ProductApprovalController::class, 'previewFile'])->name('products.file.preview');

        Route::get('/sellers', [SellerApprovalController::class, 'index'])->name('sellers.index');
        Route::post('/sellers/{sellerProfile}/approve', [SellerApprovalController::class, 'approve'])->name('sellers.approve');
        Route::post('/sellers/{sellerProfile}/reject', [SellerApprovalController::class, 'reject'])->name('sellers.reject');
        Route::post('/sellers/{sellerProfile}/commission', [SellerApprovalController::class, 'setCommission'])->name('sellers.commission');
        Route::post('/sellers/{sellerProfile}/suspend', [SellerApprovalController::class, 'suspend'])->name('sellers.suspend');
        Route::post('/sellers/{sellerProfile}/reactivate', [SellerApprovalController::class, 'reactivate'])->name('sellers.reactivate');

        Route::get('/payouts', [PayoutApprovalController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/{payoutRequest}/approve', [PayoutApprovalController::class, 'approve'])->name('payouts.approve');
        Route::post('/payouts/{payoutRequest}/paid', [PayoutApprovalController::class, 'markPaid'])->name('payouts.paid');
        Route::post('/payouts/{payoutRequest}/reject', [PayoutApprovalController::class, 'reject'])->name('payouts.reject');
        Route::post('/payouts/release-pending', [PayoutApprovalController::class, 'releasePending'])->name('payouts.release');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/manual-payments', [ManualPaymentController::class, 'index'])->name('manual-payments.index');
        Route::get('/manual-payments/{manualPaymentProof}/proof', [ManualPaymentController::class, 'showProof'])->name('manual-payments.proof');
        Route::post('/manual-payments/{manualPaymentProof}/approve', [ManualPaymentController::class, 'approve'])->name('manual-payments.approve');
        Route::post('/manual-payments/{manualPaymentProof}/reject', [ManualPaymentController::class, 'reject'])->name('manual-payments.reject');
        Route::post('/manual-payments/{manualPaymentProof}/revoke', [ManualPaymentController::class, 'revoke'])->name('manual-payments.revoke');

        Route::get('/engagement-unlocks', [EngagementApprovalController::class, 'index'])->name('engagement.index');
        Route::get('/engagement-unlocks/{engagementUnlock}/proof', [EngagementApprovalController::class, 'showProof'])->name('engagement.proof');
        Route::post('/engagement-unlocks/{engagementUnlock}/approve', [EngagementApprovalController::class, 'approve'])->name('engagement.approve');
        Route::post('/engagement-unlocks/{engagementUnlock}/reject', [EngagementApprovalController::class, 'reject'])->name('engagement.reject');
        Route::post('/engagement-unlocks/{engagementUnlock}/revoke', [EngagementApprovalController::class, 'revoke'])->name('engagement.revoke');

        // Mobile app download settings (footer "Download the App" section)
        Route::get('/app-download', [AppDownloadSettingController::class, 'edit'])->name('app-download.edit');
        Route::put('/app-download', [AppDownloadSettingController::class, 'update'])->name('app-download.update');
    });

/*
|--------------------------------------------------------------------------
| Webhooks - CSRF-exempt, verified by gateway signature in the controller
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');
Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle'])->name('webhooks.paypal');

/*
|--------------------------------------------------------------------------
| Post-login landing point
|--------------------------------------------------------------------------
| Breeze's auth controllers (RegisteredUserController, AuthenticatedSessionController,
| ConfirmablePasswordController, etc.) all redirect to route('dashboard') after
| register/login/confirm — this route must exist even though we don't use a
| single generic "dashboard" view. It just routes the person to the right place.
*/
Route::middleware('auth')->get('/dashboard', function () {
    $user = auth()->user();

    if ($user->is_admin) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->isSeller()) {
        return redirect()->route('seller.dashboard');
    }

    return redirect()->route('library.index');
})->name('dashboard');

require __DIR__ . '/auth.php';
