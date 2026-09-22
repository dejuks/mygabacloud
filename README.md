# "Download the App" section (footer + admin panel)

A self-contained feature: an admin page to configure the download button,
and a partial you can drop into the footer or any page. It does not touch
your existing files except the two spots in "Wire it in" below.

## What it does

- Admin turns the whole section on/off, sets a heading/subheading.
- **Android:** admin picks one source — upload an APK to the server, or paste
  a Google Play / Amazon Appstore / other URL. Only one is shown to visitors,
  whichever is selected.
- **iOS:** App Store URL only (Apple doesn't allow direct APK-style installs).
- APK downloads are **counted** and shown to the admin.
- The APK is stored on the **private** disk and served through a counted
  route, not as a public static file — this stops search engines and random
  crawlers from finding and hot-linking an old APK.

## 1. Install

Copy these into your Laravel project, keeping the same folder paths:

```
database/migrations/2026_09_22_045215_create_app_download_settings_table.php
app/Models/AppDownloadSetting.php
app/Http/Controllers/Admin/AppDownloadSettingController.php
resources/views/admin/app-download/edit.blade.php
resources/views/partials/download-app-badge.blade.php
```

Then:

```bash
php artisan migrate
```

`routes/app-download-routes.php` is **not** meant to be copied as-is — it's a
snippet. Open it and copy its two blocks into your real `routes/web.php`
(see "Wire it in" below).

## 2. Wire it in (the two things I can't do without seeing your code)

**a) Admin routes.** I don't know your admin route group or middleware. Add
inside your existing admin-auth group:
```php
Route::get('app-download', [AppDownloadSettingController::class, 'edit'])->name('app-download.edit');
Route::post('app-download', [AppDownloadSettingController::class, 'update'])->name('app-download.update');
```
Add the public route at the top level of `web.php` (no auth):
```php
Route::get('/download-app/android.apk', [AppDownloadSettingController::class, 'downloadApk'])
    ->name('app-download.android.apk');
```

**b) Admin layout.** `edit.blade.php` starts with `@extends('layouts.admin')`
and a `@section('content')`. If your admin panel's layout file has a
different name or section, change that one line.

**c) Add a link in your admin nav/sidebar** to `route('admin.app-download.edit')`
(the route name depends on your `name()` prefix — adjust to match).

## 3. Show it in the footer

In your footer Blade file, add one line:
```blade
@include('partials.download-app-badge')
```
For a smaller version elsewhere (e.g. a homepage banner):
```blade
@include('partials.download-app-badge', ['style' => 'compact'])
```
The section is invisible automatically until an admin turns it on and sets
at least one working link.

## 4. Use it

Go to the admin page you linked in step 2c:
1. Check **"Show the download section on the website"**.
2. Under Android, choose a source:
   - **Upload an APK** — pick the `.apk` file (up to 500 MB), optionally set a version name.
   - **Google Play / Amazon Appstore / Other URL** — paste the link.
3. Optionally enable iOS and paste the App Store link.
4. Save. The footer badge updates immediately (settings are cached and the
   cache is cleared on save).

## Notes

- **Amazon Appstore:** there's no separate "Amazon" app format — it's the
  same APK, submitted through Amazon's developer console. The Amazon field
  here is just a link to your Amazon Appstore listing page.
- **500&nbsp;MB upload limit** is set in the controller's validation
  (`max:512000` in KB). Your server's `upload_max_filesize` and
  `post_max_size` in `php.ini` must also allow a file that large, or the
  upload will fail before Laravel sees it.
- **Changed my mind on APK signing:** nothing here checks that the uploaded
  file is actually a valid signed APK. Consider only uploading builds you
  generated from **Build → Generate Signed App Bundle/APK** in Android Studio.
- If you'd rather keep the APK on the **public** disk with a plain static
  link (no download counter, no ability to swap files without re-uploading
  under the same name), that's a simpler variant — tell me and I'll adjust it.
