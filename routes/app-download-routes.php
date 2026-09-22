<?php

/*
|--------------------------------------------------------------------------
| Routes to add for the "Download the App" feature
|--------------------------------------------------------------------------
| Copy the two blocks below into your existing routes/web.php (or
| routes/admin.php, if you keep admin routes separate). Do not replace your
| whole web.php with this file — it's a snippet, not a full route file.
*/

use App\Http\Controllers\Admin\AppDownloadSettingController;
use Illuminate\Support\Facades\Route;

// ---- 1) Admin (put inside your existing admin-auth route group) ----------
// ASSUMPTION: your admin routes look something like:
//   Route::middleware(['auth','can:admin'])->prefix('admin')->name('admin.')->group(function () { ... });
// Add these two lines inside that group:
Route::get('app-download', [AppDownloadSettingController::class, 'edit'])->name('app-download.edit');
Route::post('app-download', [AppDownloadSettingController::class, 'update'])->name('app-download.update');

// ---- 2) Public download link (outside auth, top level of web.php) --------
Route::get('/download-app/android.apk', [AppDownloadSettingController::class, 'downloadApk'])
    ->name('app-download.android.apk');
