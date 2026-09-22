<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AppDownloadSetting extends Model
{
    protected $fillable = [
        'is_enabled', 'heading', 'subheading',
        'android_enabled', 'android_source', 'android_apk_path', 'android_apk_original_name',
        'android_apk_size', 'android_version_name',
        'android_play_store_url', 'android_amazon_url', 'android_custom_url',
        'ios_enabled', 'ios_app_store_url',
        'android_download_count', 'updated_at_by_admin',
    ];

    protected $casts = [
        'is_enabled'      => 'boolean',
        'android_enabled' => 'boolean',
        'ios_enabled'     => 'boolean',
    ];

    public const CACHE_KEY = 'app_download_settings';

    /** Always row #1. Cached because the footer partial reads this on every page. */
    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => self::firstOrCreate(['id' => 1]));
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** The URL/route the "Download for Android" button should point to, or null. */
    public function androidLink(): ?string
    {
        if (! $this->android_enabled) {
            return null;
        }

        return match ($this->android_source) {
            'apk'        => $this->android_apk_path ? route('app-download.android.apk') : null,
            'play_store' => $this->android_play_store_url ?: null,
            'amazon'     => $this->android_amazon_url ?: null,
            'custom_url' => $this->android_custom_url ?: null,
            default      => null,
        };
    }

    public function androidButtonLabel(): string
    {
        return match ($this->android_source) {
            'apk'        => 'Download APK' . ($this->android_version_name ? " (v{$this->android_version_name})" : ''),
            'play_store' => 'Get it on Google Play',
            'amazon'     => 'Available on Amazon Appstore',
            default      => 'Download for Android',
        };
    }

    public function iosLink(): ?string
    {
        return $this->ios_enabled && $this->ios_app_store_url ? $this->ios_app_store_url : null;
    }

    public function hasAnyLink(): bool
    {
        return $this->is_enabled && ($this->androidLink() !== null || $this->iosLink() !== null);
    }

    public function androidApkSizeLabel(): ?string
    {
        if (! $this->android_apk_size) {
            return null;
        }

        return number_format($this->android_apk_size / 1048576, 1) . ' MB';
    }

    public function deleteApkFile(): void
    {
        if ($this->android_apk_path && Storage::disk('local')->exists($this->android_apk_path)) {
            Storage::disk('local')->delete($this->android_apk_path);
        }
    }
}