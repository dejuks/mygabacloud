<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\LibraryController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Middleware\EnsureApiUserActive;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API routes  (all URLs start with /api/v1)
|--------------------------------------------------------------------------
| Separate from routes/web.php. Every route name starts with "api.v1." and
| every controller lives in App\Http\Controllers\Api\V1, so nothing here can
| collide with the existing Blade routes or controllers.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ---- Auth ----------------------------------------------------------
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register',        [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
        Route::post('login',           [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('forgot');
        Route::post('reset-password',  [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('reset');

        Route::middleware('auth:sanctum')->group(function () {
            // logout works even for suspended users so they can sign out
            Route::post('logout',       [AuthController::class, 'logout'])->name('logout');
            Route::post('email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('resend');
            Route::get('me',            [AuthController::class, 'me'])->middleware(EnsureApiUserActive::class)->name('me');
        });
    });

    // ---- Catalog (public) ----------------------------------------------
    Route::get('categories',              [CatalogController::class, 'categories'])->name('categories');
    Route::get('products',                [CatalogController::class, 'index'])->name('products.index');
    Route::get('products/{slug}',         [CatalogController::class, 'show'])->name('products.show');
    Route::get('products/{slug}/reviews', [ReviewController::class, 'index'])->name('products.reviews.index');

    // ---- Authenticated buyer -------------------------------------------
    Route::middleware(['auth:sanctum', EnsureApiUserActive::class])->group(function () {
        Route::post('products/{slug}/reviews',           [ReviewController::class, 'store'])->name('products.reviews.store');
        Route::get('library',                            [LibraryController::class, 'index'])->name('library.index');
        Route::post('library/{productId}/download-link', [LibraryController::class, 'downloadLink'])->whereNumber('productId')->name('library.link');
    });

    // Signed, short-lived URL so Android's DownloadManager needs no auth header.
    Route::get('library/{productId}/download', [LibraryController::class, 'download'])
        ->whereNumber('productId')
        ->middleware('signed')
        ->name('library.download');
});
