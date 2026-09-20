<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualPaymentProof;
use App\Models\Product;
use App\Services\OrderFulfilmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ManualPaymentController extends Controller
{
    public function __construct(protected OrderFulfilmentService $fulfilment)
    {
    }

    public function index()
    {
        $pending = ManualPaymentProof::with('order.buyer')
            ->where('status', 'pending')
            ->oldest()
            ->paginate(15);

        // Fraud check: has this exact reference (TxID / transfer note) been
        // used on a DIFFERENT, already-approved payment before? A buyer
        // reusing someone else's receipt screenshot, or resubmitting one
        // real transfer against two separate orders, both show up here.
        $duplicateFlags = [];
        foreach ($pending as $proof) {
            if (! $proof->reference_note) {
                continue;
            }
            $reused = ManualPaymentProof::where('method', $proof->method)
                ->where('reference_note', $proof->reference_note)
                ->where('status', 'approved')
                ->where('id', '!=', $proof->id)
                ->exists();
            if ($reused) {
                $duplicateFlags[$proof->id] = true;
            }
        }

        $history = ManualPaymentProof::with('order.buyer', 'reviewedBy')
            ->whereIn('status', ['approved', 'rejected', 'revoked'])
            ->latest('reviewed_at')
            ->paginate(15, ['*'], 'history_page');

        return view('admin.manual-payments', compact('pending', 'history', 'duplicateFlags'));
    }

    /**
     * Shows the receipt image inline. Streamed the same way product
     * downloads are — through a controller, never a public URL — since the
     * private disk holds real payment receipts.
     */
    public function showProof(ManualPaymentProof $manualPaymentProof)
    {
        $disk = Storage::disk('private');

        abort_unless($disk->exists($manualPaymentProof->proof_path), 404);

        return $disk->response($manualPaymentProof->proof_path);
    }

    public function approve(ManualPaymentProof $manualPaymentProof)
    {
        if ($manualPaymentProof->status !== 'pending') {
            return back()->with('error', 'This proof has already been reviewed.');
        }

        $order = $manualPaymentProof->order;
        $snapshot = $order->cart_snapshot ?? [];

        $items = collect($snapshot)->map(fn ($row) => [
            'product' => Product::find($row['product_id']),
            'license_type' => $row['license_type'],
            'price' => $row['price'],
        ])->filter(fn ($row) => $row['product'] !== null)->all();

        if (empty($items)) {
            return back()->with('error', 'This order has no items to fulfil — cannot approve.');
        }

        try {
            $this->fulfilment->fulfil(
                $order,
                $items,
                'manual_' . $manualPaymentProof->method . '_' . $order->id . '_' . Str::random(8),
                ['method' => $manualPaymentProof->method, 'reference_note' => $manualPaymentProof->reference_note]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Manual payment fulfilment failed', ['error' => $e->getMessage()]);

            // fulfil() runs inside its own DB transaction, so nothing partial
            // was committed — the order is still safely 'pending' and this
            // proof is still 'pending', so it's safe to just retry after fixing.
            return back()->with('error',
                'Could not complete this order — your database schema may be out of date. '
                . 'Run "php artisan migrate" and try approving again.'
            );
        }

        $manualPaymentProof->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        \App\Support\ActivityLogger::log('payment.approved', $manualPaymentProof,
            "Approved {$manualPaymentProof->method} payment of \${$order->grand_total} for order {$order->order_number}.");

        return back()->with('success', 'Payment approved — the buyer now has access.');
    }

    public function reject(Request $request, ManualPaymentProof $manualPaymentProof)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($manualPaymentProof->status !== 'pending') {
            return back()->with('error', 'This proof has already been reviewed.');
        }

        $manualPaymentProof->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $manualPaymentProof->order->update(['status' => 'failed']);

        \App\Support\ActivityLogger::log('payment.rejected', $manualPaymentProof,
            "Rejected {$manualPaymentProof->method} payment for order {$manualPaymentProof->order->order_number}: {$data['admin_note']}");

        return back()->with('success', 'Payment proof rejected.');
    }

    /**
     * Revokes an already-approved manual payment — e.g. the CBE/Telebirr
     * transfer turns out to be fake, or the USDT never actually arrived on
     * closer inspection. Unlike the free-unlock revoke, real money was
     * involved here, so this reuses OrderFulfilmentService::refund() per
     * order item: it revokes each license AND reverses the seller's wallet
     * credit, so the seller isn't left holding commission for a payment
     * that didn't really happen.
     */
    public function revoke(Request $request, ManualPaymentProof $manualPaymentProof)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($manualPaymentProof->status !== 'approved') {
            return back()->with('error', 'Only an approved payment can be revoked.');
        }

        $order = $manualPaymentProof->order;

        foreach ($order->items as $item) {
            $this->fulfilment->refund($item, 'revoke_' . $manualPaymentProof->id . '_' . $item->id . '_' . Str::random(6));
        }

        $manualPaymentProof->update([
            'status' => 'revoked',
            'admin_note' => $data['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        \App\Support\ActivityLogger::log('payment.revoked', $manualPaymentProof,
            "Revoked payment for order {$order->order_number}: {$data['admin_note']}");

        return back()->with('success', "Payment revoked — {$order->buyer->name}'s access has been removed and the seller's earnings reversed.");
    }
}
