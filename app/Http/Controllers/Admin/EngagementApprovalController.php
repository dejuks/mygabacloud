<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EngagementUnlock;
use App\Models\License;
use Illuminate\Http\Request;

class EngagementApprovalController extends Controller
{
    public function index()
    {
        $pending = EngagementUnlock::with('product.seller', 'user')
            ->where('status', 'pending')
            ->oldest()
            ->paginate(15);

        $history = EngagementUnlock::with('product', 'user', 'reviewedBy')
            ->whereIn('status', ['approved', 'rejected', 'revoked'])
            ->latest('reviewed_at')
            ->paginate(15, ['*'], 'history_page');

        return view('admin.engagement-unlocks', compact('pending', 'history'));
    }

    /**
     * Shows the proof screenshot inline. Streamed through a controller from
     * the private disk, same as manual payment proofs and product
     * downloads — never a public URL.
     */
    public function showProof(EngagementUnlock $engagementUnlock)
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('private');

        abort_unless($engagementUnlock->proof_screenshot_path, 404);
        abort_unless($disk->exists($engagementUnlock->proof_screenshot_path), 404);

        return $disk->response($engagementUnlock->proof_screenshot_path);
    }

    /**
     * Approving issues a real License directly — this is a free giveaway,
     * so there is deliberately NO Order, NO OrderItem, and NO commission or
     * wallet credit to the seller. The seller opted in to this at the
     * product level (Product::allow_free_unlock) knowing it's promotional.
     */
    public function approve(EngagementUnlock $engagementUnlock)
    {
        if ($engagementUnlock->status !== 'pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        try {
            $license = License::create([
                'order_item_id' => null,
                'buyer_id' => $engagementUnlock->user_id,
                'product_id' => $engagementUnlock->product_id,
                'type' => 'regular',
                'status' => 'active',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Free-unlock license creation failed', ['error' => $e->getMessage()]);

            return back()->with('error',
                'Could not issue the license — your database schema may be out of date. '
                . 'Run "php artisan migrate" (this needs migration 2024_01_01_000021, which makes '
                . 'licenses.order_item_id nullable) and try approving again.'
            );
        }

        $engagementUnlock->update([
            'status' => 'approved',
            'license_id' => $license->id,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        \App\Support\ActivityLogger::log('engagement.approved', $engagementUnlock,
            "Approved free access for {$engagementUnlock->user->name} to \"{$engagementUnlock->product->title}\".");

        return back()->with('success', "Approved — {$engagementUnlock->user->name} now has free access to \"{$engagementUnlock->product->title}\".");
    }

    /**
     * Revokes access already granted — the buyer keeps the license row
     * (it stays as an auditable record of what happened) but its status
     * flips to 'revoked', which LibraryController checks before allowing
     * a download. No money was ever involved here, so unlike manual
     * payment revocation there's no wallet/commission to reverse.
     */
    public function revoke(Request $request, EngagementUnlock $engagementUnlock)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($engagementUnlock->status !== 'approved') {
            return back()->with('error', 'Only an approved request can be revoked.');
        }

        $engagementUnlock->license?->update(['status' => 'revoked']);

        $engagementUnlock->update([
            'status' => 'revoked',
            'admin_note' => $data['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        \App\Support\ActivityLogger::log('engagement.revoked', $engagementUnlock,
            "Revoked free access for {$engagementUnlock->user->name} on \"{$engagementUnlock->product->title}\": {$data['admin_note']}");

        return back()->with('success', "Access revoked for {$engagementUnlock->user->name} on \"{$engagementUnlock->product->title}\".");
    }

    public function reject(Request $request, EngagementUnlock $engagementUnlock)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($engagementUnlock->status !== 'pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $engagementUnlock->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        \App\Support\ActivityLogger::log('engagement.rejected', $engagementUnlock,
            "Rejected free access request for {$engagementUnlock->user->name} on \"{$engagementUnlock->product->title}\": {$data['admin_note']}");

        return back()->with('success', 'Request rejected.');
    }
}
