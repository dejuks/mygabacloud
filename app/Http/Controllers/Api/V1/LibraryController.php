<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\License;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The buyer's purchased items.
 * Ownership rule (from the schema): licenses.buyer_id = user AND licenses.status = 'active'.
 * Downloads only serve product_files with scan_status = 'clean'.
 */
class LibraryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $licenses = License::query()
            ->where('buyer_id', $request->user()->id)
            ->where('status', 'active')
            ->latest('id')
            ->paginate(20);

        // Buyers keep seeing what they bought even if the product was later unpublished.
        $products = Product::query()->withoutGlobalScopes()
            ->whereIn('id', $licenses->getCollection()->pluck('product_id')->unique())
            ->get()->keyBy('id');

        foreach ($licenses->getCollection() as $license) {
            $license->setRelation('product', $products->get($license->product_id));
        }

        return LicenseResource::collection($licenses);
    }

    /** Step 1: authenticated call that returns a 5-minute signed URL. */
    public function downloadLink(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();
        $this->assertOwns($user->id, $productId);

        if (! $this->findDownloadableFile($productId)) {
            $hasFiles = DB::table('product_files')->where('product_id', $productId)->exists();
            abort(409, $hasFiles
                ? 'This file is still being security-checked. Please try again later.'
                : 'No downloadable file is available for this product.');
        }

        $url = URL::temporarySignedRoute(
            'api.v1.library.download',
            now()->addMinutes(5),
            ['productId' => $productId, 'u' => $user->id],
        );

        return response()->json(['url' => $url, 'expires_in' => 300]);
    }

    /** Step 2: the signed URL streams the file. Ownership and scan status are re-checked. */
    public function download(Request $request, int $productId): StreamedResponse
    {
        $this->assertOwns((int) $request->query('u'), $productId);

        $file = $this->findDownloadableFile($productId);
        abort_unless($file, 404, 'File not available.');

        $disk = Storage::disk($file->disk ?: 'private');
        abort_unless($disk->exists($file->path), 404, 'File not available.');

        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $file->original_name) ?: 'download.zip';

        return $disk->download($file->path, $name);
    }

    private function assertOwns(int $userId, int $productId): void
    {
        $owns = License::where('buyer_id', $userId)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->exists();

        abort_unless($owns, 403, 'You do not own this product.');
    }

    /**
     * Newest file that passed the malware scan and is not corrupted,
     * preferring the version the product currently advertises.
     * If your website applies extra rules (e.g. blocks duplicate_of_file_id files),
     * add the same conditions here.
     */
    private function findDownloadableFile(int $productId): ?object
    {
        $current = DB::table('products')->where('id', $productId)->value('current_version');

        return DB::table('product_files')
            ->where('product_id', $productId)
            ->where('scan_status', 'clean')
            ->where('integrity_status', '!=', 'corrupted')
            ->orderByRaw('(version = ?) DESC', [$current])
            ->orderByDesc('id')
            ->first();
    }
}
