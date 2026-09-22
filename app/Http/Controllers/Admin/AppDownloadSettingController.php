<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppDownloadSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin page: "Website > Mobile App Download". One settings row, editable
 * from a single form. Swap the `admin.*` layout/middleware for whatever your
 * existing admin panel uses (I could not see it in the repo).
 */
class AppDownloadSettingController extends Controller
{
    public function edit()
    {
        return view('admin.app-download.edit', [
            'settings' => AppDownloadSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled'  => ['nullable', 'boolean'],
            'heading'     => ['required', 'string', 'max:120'],
            'subheading'  => ['nullable', 'string', 'max:255'],

            'android_enabled' => ['nullable', 'boolean'],
            'android_source'  => ['required_if:android_enabled,1', 'in:apk,play_store,amazon,custom_url'],
            // 'mimes:apk' is unreliable here: Laravel guesses the type from the file's
            // content, and a real .apk (a ZIP container) is often detected as
            // application/zip or application/octet-stream and gets rejected even
            // though it's a genuine, valid APK. Checking the extension by hand avoids
            // that false rejection; max is a 500 MB ceiling.
            'android_apk'     => [
                'nullable', 'file', 'max:512000',
                function (string $attribute, $value, \Closure $fail) {
                    if (strtolower($value->getClientOriginalExtension()) !== 'apk') {
                        $fail('The android apk field must be a file of type: apk.');
                    }
                },
            ],
            'android_version_name'   => ['nullable', 'string', 'max:30'],
            'android_play_store_url' => ['nullable', 'url', 'max:500'],
            'android_amazon_url'     => ['nullable', 'url', 'max:500'],
            'android_custom_url'     => ['nullable', 'url', 'max:500'],

            'ios_enabled'       => ['nullable', 'boolean'],
            'ios_app_store_url' => ['nullable', 'required_if:ios_enabled,1', 'url', 'max:500'],

            'remove_apk' => ['nullable', 'boolean'],
        ]);

        $settings = AppDownloadSetting::current();

        $settings->fill([
            'is_enabled'             => (bool) ($data['is_enabled'] ?? false),
            'heading'                => $data['heading'],
            'subheading'             => $data['subheading'] ?? null,
            'android_enabled'        => (bool) ($data['android_enabled'] ?? false),
            'android_source'         => $data['android_source'] ?? $settings->android_source,
            'android_version_name'   => $data['android_version_name'] ?? null,
            'android_play_store_url' => $data['android_play_store_url'] ?? null,
            'android_amazon_url'     => $data['android_amazon_url'] ?? null,
            'android_custom_url'     => $data['android_custom_url'] ?? null,
            'ios_enabled'            => (bool) ($data['ios_enabled'] ?? false),
            'ios_app_store_url'      => $data['ios_app_store_url'] ?? null,
            'updated_at_by_admin'    => now(),
        ]);

        if ($request->boolean('remove_apk')) {
            $settings->deleteApkFile();
            $settings->android_apk_path = null;
            $settings->android_apk_original_name = null;
            $settings->android_apk_size = null;
        }

        if ($request->hasFile('android_apk')) {
            $file = $request->file('android_apk');

            // Old APK removed only after the new one is safely stored, so a failed
            // upload never leaves the live download link broken.
            $oldPath = $settings->android_apk_path;

            $stored = $file->store('app-releases', 'local'); // NOT public disk: served through the counted route below

            $settings->android_apk_path          = $stored;
            $settings->android_apk_original_name = 'gabacloud-' . Str::slug($settings->android_version_name ?: 'latest') . '.apk';
            $settings->android_apk_size          = $file->getSize();

            if ($oldPath && $oldPath !== $stored && Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        }

        $settings->save();
        AppDownloadSetting::forgetCache();

        return back()->with('status', 'App download settings saved.');
    }

    /** Public route: streams the APK and counts the download. No login required. */
    public function downloadApk(): StreamedResponse
    {
        $settings = AppDownloadSetting::current();

        abort_unless(
            $settings->is_enabled && $settings->android_enabled
                && $settings->android_source === 'apk' && $settings->android_apk_path,
            404,
        );
        abort_unless(Storage::disk('local')->exists($settings->android_apk_path), 404, 'The app file is missing. Please contact support.');

        $settings->increment('android_download_count');
        AppDownloadSetting::forgetCache();

        return Storage::disk('local')->download(
            $settings->android_apk_path,
            $settings->android_apk_original_name ?: 'gabacloud.apk',
            ['Content-Type' => 'application/vnd.android.package-archive'],
        );
    }
}