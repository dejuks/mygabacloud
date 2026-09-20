<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\SellerWallet;
use App\Notifications\PayoutProcessed;
use Illuminate\Http\Request;

class PayoutApprovalController extends Controller
{
    public function index()
    {
        $pending = PayoutRequest::with('seller.sellerProfile')
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->oldest()
            ->paginate(15);

        $history = PayoutRequest::with('seller')
            ->whereIn('status', ['paid', 'rejected'])
            ->latest('processed_at')
            ->paginate(15, ['*'], 'history_page');

        return view('admin.payouts', compact('pending', 'history'));
    }

    public function approve(PayoutRequest $payoutRequest)
    {
        if ($payoutRequest->status !== 'pending') {
            return back()->with('error', 'This request is no longer pending.');
        }

        $wallet = SellerWallet::where('seller_id', $payoutRequest->seller_id)->first();

        if (! $wallet || $wallet->available_balance < $payoutRequest->amount) {
            return back()->with('error', 'The seller no longer has sufficient available balance.');
        }

        $payoutRequest->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
        ]);

        \App\Support\ActivityLogger::log('payout.approved', $payoutRequest,
            "Approved payout request of \${$payoutRequest->amount} for {$payoutRequest->seller->name}.");

        return back()->with('success', 'Approved. Send the money, then mark it paid.');
    }

    /**
     * Called once the money has actually left — via PayPal Payouts API or a
     * manual bank transfer. This is the point the wallet is debited.
     */
    public function markPaid(Request $request, PayoutRequest $payoutRequest)
    {
        $data = $request->validate([
            'gateway_payout_id' => ['nullable', 'string', 'max:255'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        if (! in_array($payoutRequest->status, ['approved', 'processing'])) {
            return back()->with('error', 'Approve the request before marking it paid.');
        }

        $wallet = SellerWallet::where('seller_id', $payoutRequest->seller_id)->firstOrFail();

        if ($wallet->available_balance < $payoutRequest->amount) {
            return back()->with('error', 'Insufficient balance — cannot complete this payout.');
        }

        $wallet->debitPayout($payoutRequest);

        $payoutRequest->update([
            'status' => 'paid',
            'gateway_payout_id' => $data['gateway_payout_id'] ?? null,
            'admin_note' => $data['admin_note'] ?? null,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        \App\Support\SafeNotifier::send($payoutRequest->seller, new PayoutProcessed($payoutRequest));
        \App\Support\ActivityLogger::log('payout.paid', $payoutRequest,
            "Marked payout of \${$payoutRequest->amount} as paid for {$payoutRequest->seller->name}.");

        return back()->with('success', 'Payout completed and wallet debited.');
    }

    public function reject(Request $request, PayoutRequest $payoutRequest)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        $payoutRequest->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'],
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        // No wallet change — the money was never debited
        \App\Support\ActivityLogger::log('payout.rejected', $payoutRequest,
            "Rejected payout request of \${$payoutRequest->amount} for {$payoutRequest->seller->name}: {$data['admin_note']}");

        return back()->with('success', 'Payout request rejected.');
    }

    /**
     * Manual on-demand version of the same release the scheduled command
     * runs automatically every day — useful for testing, or for releasing
     * something early without waiting for the next scheduled run.
     */
    public function releasePending()
    {
        $released = app(\App\Services\HeldEarningsReleaser::class)->release();

        return back()->with('success', "Released {$released} held earnings to available balance.");
    }
}
