<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\SellerWallet;
use App\Notifications\SellerApplicationReviewed;
use Illuminate\Http\Request;

class SellerApprovalController extends Controller
{
    public function index()
    {
        $applications = SellerProfile::with('user')
            ->where('application_status', 'pending')
            ->oldest()
            ->paginate(15);

        $approved = SellerProfile::with('user')
            ->where('application_status', 'approved')
            ->latest('approved_at')
            ->paginate(15, ['*'], 'approved_page');

        return view('admin.sellers', compact('applications', 'approved'));
    }

    public function approve(SellerProfile $sellerProfile)
    {
        $sellerProfile->update([
            'application_status' => 'approved',
            'approved_at' => now(),
        ]);

        $sellerProfile->user->update(['is_seller' => true]);

        // Every approved seller needs a wallet from day one
        SellerWallet::firstOrCreate(
            ['seller_id' => $sellerProfile->user_id],
            ['available_balance' => 0, 'pending_balance' => 0]
        );

        \App\Support\SafeNotifier::send($sellerProfile->user, new SellerApplicationReviewed(approved: true));
        \App\Support\ActivityLogger::log('seller.approved', $sellerProfile, "Approved seller application: {$sellerProfile->store_name}.");

        return back()->with('success', "{$sellerProfile->store_name} approved.");
    }

    public function reject(Request $request, SellerProfile $sellerProfile)
    {
        $sellerProfile->update(['application_status' => 'rejected']);
        $sellerProfile->user->update(['is_seller' => false]);

        \App\Support\SafeNotifier::send($sellerProfile->user, new SellerApplicationReviewed(
            approved: false,
            reason: $request->input('reason')
        ));
        \App\Support\ActivityLogger::log('seller.rejected', $sellerProfile, "Rejected seller application: {$sellerProfile->store_name}.");

        return back()->with('success', 'Application rejected.');
    }

    /**
     * Override the platform commission rate for one seller — used for
     * volume tiers or negotiated deals.
     */
    public function setCommission(Request $request, SellerProfile $sellerProfile)
    {
        $data = $request->validate([
            'custom_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $sellerProfile->update($data);
        \App\Support\ActivityLogger::log('seller.commission_changed', $sellerProfile,
            "Set custom commission rate for {$sellerProfile->store_name} to " . ($data['custom_commission_rate'] ?? 'default') . "%.");

        return back()->with('success', 'Commission rate updated. Past sales are unaffected.');
    }

    /**
     * Suspends a seller's account. This is deliberately lighter-touch than
     * revoking their approval: their existing products and any completed
     * sales are untouched — buyers who already bought from them keep their
     * downloads — but EnsureUserIsSeller (which checks $user->status)
     * immediately blocks them from their seller dashboard, product
     * management, and payout requests until reactivated.
     */
    public function suspend(Request $request, SellerProfile $sellerProfile)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        $sellerProfile->user->update(['status' => 'suspended']);
        \App\Support\ActivityLogger::log('seller.suspended', $sellerProfile, "Suspended {$sellerProfile->store_name}: {$data['admin_note']}");

        return back()->with('success', "{$sellerProfile->store_name} suspended. Their existing products and buyer downloads are unaffected.");
    }

    public function reactivate(SellerProfile $sellerProfile)
    {
        $sellerProfile->user->update(['status' => 'active']);
        \App\Support\ActivityLogger::log('seller.reactivated', $sellerProfile, "Reactivated {$sellerProfile->store_name}.");

        return back()->with('success', "{$sellerProfile->store_name} reactivated.");
    }
}
