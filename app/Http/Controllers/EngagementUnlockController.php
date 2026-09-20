<?php

namespace App\Http\Controllers;

use App\Models\EngagementUnlock;
use App\Models\Product;
use Illuminate\Http\Request;

class EngagementUnlockController extends Controller
{
    public function create(Product $product)
    {
        abort_unless($product->allow_free_unlock, 404);
        abort_unless($product->status === 'approved', 404);

        if ($product->seller_id === auth()->id()) {
            return redirect()->route('products.show', $product)
                ->with('error', 'You cannot request free access to your own product.');
        }

        $alreadyOwns = auth()->user()->licenses()
            ->where('product_id', $product->id)->where('status', 'active')->exists();

        if ($alreadyOwns) {
            return redirect()->route('library.index');
        }

        $existing = EngagementUnlock::where('product_id', $product->id)
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        return view('products.unlock-request', compact('product', 'existing'));
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($product->allow_free_unlock, 404);

        // Block spamming a new request while one is already pending or approved
        $blocked = EngagementUnlock::where('product_id', $product->id)
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($blocked) {
            return back()->with('error', 'You already have a request in progress for this product.');
        }

        $data = $request->validate([
            'proof_note' => ['required', 'string', 'min:10', 'max:1000'],
            'proof_url' => ['nullable', 'url', 'max:255'],
            'proof_screenshot' => ['required', 'image', 'max:4096'],
            'youtube_handle' => ['required', 'string', 'max:100'],
            'watched_seconds' => ['nullable', 'integer', 'min:0'],
            'watched' => ['required', 'accepted'],
            'subscribed' => ['required', 'accepted'],
            'liked' => ['required', 'accepted'],
            'commented' => ['required', 'accepted'],
            'shared' => ['required', 'accepted'],
        ], [
            'proof_note.required' => 'Tell us a bit about your comment or what you did.',
            'proof_screenshot.required' => 'Upload one screenshot showing your subscribe, like, and comment.',
            'proof_screenshot.image' => 'That needs to be an image file (PNG, JPG, etc).',
            'watched.accepted' => 'Please finish watching the required amount of the video first.',
            'subscribed.accepted' => 'Please confirm every step — they\'re all required.',
            'liked.accepted' => 'Please confirm every step — they\'re all required.',
            'commented.accepted' => 'Please confirm every step — they\'re all required.',
            'shared.accepted' => 'Please confirm every step — they\'re all required.',
        ]);

        $screenshotFile = $request->file('proof_screenshot');
        $screenshotPath = $screenshotFile->store('engagement-proofs/' . auth()->id(), 'private');

        EngagementUnlock::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'proof_note' => $data['proof_note'],
            'proof_url' => $data['proof_url'] ?? null,
            'proof_screenshot_path' => $screenshotPath,
            'proof_screenshot_original_name' => $screenshotFile->getClientOriginalName(),
            'youtube_handle' => $data['youtube_handle'],
            'watched_seconds' => $data['watched_seconds'] ?? null,
            'watched' => true,
            'subscribed' => true,
            'liked' => true,
            'commented' => true,
            'shared' => true,
            'status' => 'pending',
        ]);

        return redirect()->route('products.show', $product)
            ->with('success', 'Request submitted. We will review it and unlock your download once approved.');
    }
}
