<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\License;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The buyer's purchased items (/library on the website).
 */
class LibraryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $licenses = License::query()
            ->where('user_id', $request->user()->id)
            ->with('product.category')
            ->latest()
            ->paginate(20);

        return LicenseResource::collection($licenses);
    }

    /** Step 1: authenticated call that returns a 5-minute signed URL. */
    public function downloadLink(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();
        $this->assertOwns($user->id, $productId);

        $url = URL::temporarySignedRoute(
            'api.v1.library.download',
            now()->addMinutes(5),
            ['productId' => $productId, 'u' => $user->id],
        );

        return response()->json(['url' => $url, 'expires_in' => 300]);
    }

    /** Step 2: the signed URL streams the ZIP. Ownership is re-checked. */
    public function download(Request $request, int $productId): StreamedResponse
    {
        $this->assertOwns((int) $request->query('u'), $productId);

        $product = Product::findOrFail($productId);
        [$disk, $path] = $this->resolveDownloadableFile($product);

        $name = Str::slug(data_get($product, 'title') ?? data_get($product, 'name') ?? 'download') . '.zip';

        return Storage::disk($disk)->download($path, $name);
    }

    private function assertOwns(int $userId, int $productId): void
    {
        $owns = License::where('user_id', $userId)->where('product_id', $productId)->exists();
        abort_unless($owns, 403, 'You do not own this product.');
    }

    /**
     * !! SECURITY-CRITICAL: mirror the website's download rules here. !!
     * The README says uploads are malware-scanned and stay "pending" until
     * cleared, so the web download controller almost certainly refuses files
     * that are not marked clean. Copy that check (and the latest-version
     * lookup, download counter, etc.) so the app cannot bypass it.
     * Placeholder below assumes products.file_path on the "private" disk.
     */
    private function resolveDownloadableFile(Product $product): array
    {
        $path = data_get($product, 'file_path');

        abort_if(! $path || ! Storage::disk('private')->exists($path), 404, 'File not available.');

        return ['private', $path];
    }
}
