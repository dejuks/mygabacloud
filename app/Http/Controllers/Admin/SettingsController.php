<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\LicenseManager;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(LicenseManager $licenseManager)
    {
        $settings = PlatformSetting::pluck('value', 'key');
        $license = $licenseManager->load();
        $licenseValid = $licenseManager->isLicensed();

        return view('admin.settings', compact('settings', 'license', 'licenseValid'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'default_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimum_payout_amount' => ['required', 'numeric', 'min:0'],
            'payout_holding_days' => ['required', 'integer', 'min:0', 'max:180'],
            'site_currency' => ['required', 'string', 'size:3'],

            // Manual payment instructions shown to buyers at checkout —
            // all optional, but the checkout page shows "Not configured yet"
            // for any that are blank, so fill these in before enabling those
            // payment methods for real buyers.
            'cbe_account_holder' => ['nullable', 'string', 'max:255'],
            'cbe_account_number' => ['nullable', 'string', 'max:255'],
            'cbe_branch' => ['nullable', 'string', 'max:255'],
            'cbe_note' => ['nullable', 'string', 'max:1000'],

            'telebirr_account_holder' => ['nullable', 'string', 'max:255'],
            'telebirr_number' => ['nullable', 'string', 'max:255'],
            'telebirr_note' => ['nullable', 'string', 'max:1000'],

            'usdt_account_holder' => ['nullable', 'string', 'max:255'],
            'usdt_wallet_address' => ['nullable', 'string', 'max:255'],
            'usdt_note' => ['nullable', 'string', 'max:1000'],

            'payment_support_contact' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data as $key => $value) {
            PlatformSetting::set($key, (string) $value);
        }

        \App\Support\ActivityLogger::log('settings.updated', null, 'Updated platform settings.');

        return back()->with('success', 'Settings saved. Existing sales keep their original rates.');
    }
}
