<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    public function index()
    {
        $licenses = auth()->user()
            ->licenses()
            ->with(['product.seller', 'product.files', 'orderItem.order'])
            ->where('status', 'active')
            ->latest()
            ->paginate(12);

        return view('library.index', compact('licenses'));
    }

    /**
     * Streams the product file from the PRIVATE disk. The file path is never
     * exposed to the browser — the only way in is through a valid, active
     * license belonging to the authenticated user.
     */
    public function download(License $license)
    {
        abort_unless($license->buyer_id === auth()->id(), 403);
        abort_unless($license->status === 'active', 403, 'This license has been revoked.');

        // Stop bulk scraping of the whole catalogue from one account
        $key = 'download:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            abort(429, 'Too many downloads. Try again in an hour.');
        }
        RateLimiter::hit($key, 3600);

        $file = $license->product->files()
            ->where('scan_status', 'clean')
            ->latest()
            ->first();

        abort_if(! $file, 404, 'No downloadable file is available for this product yet.');

        $disk = Storage::disk($file->disk);

        abort_unless($disk->exists($file->path), 404, 'File is missing. Please contact support.');

        $license->increment('activations_count');

        return $disk->download($file->path, $file->original_name);
    }

    /**
     * Public license verification endpoint — lets a sold script phone home to
     * confirm its purchase code, the way Envato's API works.
     */
    public function verify(string $key)
    {
        $license = License::with('product')->where('license_key', $key)->first();

        if (! $license || $license->status !== 'active') {
            return response()->json(['valid' => false], 404);
        }

        return response()->json([
            'valid' => true,
            'product' => $license->product->title,
            'license_type' => $license->type,
            'issued_at' => $license->created_at->toIso8601String(),
        ]);
    }
}
