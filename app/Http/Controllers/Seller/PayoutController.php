<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\PlatformSetting;
use App\Models\SellerWallet;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function index()
    {
        $wallet = SellerWallet::firstOrCreate(
            ['seller_id' => auth()->id()],
            ['available_balance' => 0, 'pending_balance' => 0]
        );

        $payouts = PayoutRequest::where('seller_id', auth()->id())
            ->latest()
            ->paginate(15);

        $ledger = $wallet->ledgerEntries()->latest()->take(25)->get();

        $minimum = (float) PlatformSetting::get('minimum_payout_amount', 50);

        return view('seller.payouts', compact('wallet', 'payouts', 'ledger', 'minimum'));
    }

    public function store(Request $request)
    {
        $wallet = SellerWallet::where('seller_id', auth()->id())->firstOrFail();
        $minimum = (float) PlatformSetting::get('minimum_payout_amount', 50);

        $data = $request->validate([
            'amount' => ['required', 'numeric', "min:{$minimum}", 'max:' . $wallet->available_balance],
            'method' => ['required', 'in:paypal,bank_transfer'],
            'paypal_email' => ['required_if:method,paypal', 'nullable', 'email'],
            'bank_details' => ['required_if:method,bank_transfer', 'nullable', 'string', 'max:500'],
        ], [
            'amount.max' => 'You cannot request more than your available balance.',
            'amount.min' => "The minimum payout is \${$minimum}.",
        ]);

        // Block a second request while one is still in flight
        $pending = PayoutRequest::where('seller_id', auth()->id())
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->exists();

        if ($pending) {
            return back()->with('error', 'You already have a payout request in progress.');
        }

        PayoutRequest::create([
            'seller_id' => auth()->id(),
            'amount' => $data['amount'],
            'method' => $data['method'],
            'destination_details' => $data['method'] === 'paypal'
                ? ['paypal_email' => $data['paypal_email']]
                : ['bank_details' => $data['bank_details']],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Payout requested. An admin will process it shortly.');
    }
}
