<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generates and validates the server-locked license.
 *
 * How it works: at install time, we hash this server's fingerprint together
 * with a per-installation secret and store both the secret and the
 * resulting key in storage/license.json (outside the public web root, never
 * committed to git). On every request, VerifyLicense middleware recomputes
 * the current server's fingerprint and checks it still produces the same
 * key. If the code gets copied to a different server, the fingerprint
 * changes, the key no longer matches, and the app refuses to serve requests.
 *
 * Read the class docblock on ServerFingerprint for the honest limits of
 * this — it deters casual copying, it does not stop someone with server
 * access and the will to go read this file and patch it out.
 */
class LicenseManager
{
    protected string $storagePath;

    public function __construct(protected ServerFingerprint $fingerprint)
    {
        $this->storagePath = storage_path('license.json');
    }

    public function isLicensed(): bool
    {
        $license = $this->load();

        if (! $license) {
            return false;
        }

        return $this->computeKey($license['secret']) === $license['key'];
    }

    public function generate(string $buyerName = '', string $purchaseCode = ''): array
    {
        $secret = Str::random(40);
        $key = $this->computeKey($secret);

        $license = [
            'key' => $key,
            'secret' => $secret,
            'buyer_name' => $buyerName,
            'purchase_code' => $purchaseCode,
            'issued_at' => now()->toIso8601String(),
            'fingerprint_components' => $this->fingerprint->components(),
        ];

        File::put($this->storagePath, json_encode($license, JSON_PRETTY_PRINT));

        return $license;
    }

    public function load(): ?array
    {
        if (! File::exists($this->storagePath)) {
            return null;
        }

        $data = json_decode(File::get($this->storagePath), true);

        return is_array($data) ? $data : null;
    }

    protected function computeKey(string $secret): string
    {
        return hash('sha256', $this->fingerprint->hash() . '|' . $secret);
    }
}
