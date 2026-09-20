<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRestoreRequest;
use App\Notifications\ProductReviewed;
use Illuminate\Http\Request;

class ProductApprovalController extends Controller
{
    public function index()
    {
        $products = Product::pendingReview()
            ->with(['seller.sellerProfile', 'category', 'files'])
            ->oldest('updated_at')
            ->paginate(15);

        $restoreRequests = ProductRestoreRequest::with(['product', 'seller'])
            ->where('status', 'pending')
            ->oldest()
            ->get();

        return view('admin.products-pending', compact('products', 'restoreRequests'));
    }

    public function approveRestore(ProductRestoreRequest $productRestoreRequest)
    {
        if ($productRestoreRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $product = Product::onlyTrashed()->find($productRestoreRequest->product_id);

        if (! $product) {
            return back()->with('error', 'This product no longer exists or was already restored.');
        }

        $product->restore();

        // Restoring always requires a fresh review before going live again —
        // same safety posture as any other newly-submitted product,
        // regardless of what it was approved as before deletion.
        $product->update([
            'status' => 'pending_review',
            'rejection_reason' => null,
        ]);

        $productRestoreRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        \App\Support\ActivityLogger::log('product.restored', $product,
            "Restored product \"{$product->title}\" for {$productRestoreRequest->seller->name} — sent back to review queue.");

        return back()->with('success', "\"{$product->title}\" restored and sent back to the review queue.");
    }

    public function rejectRestore(Request $request, ProductRestoreRequest $productRestoreRequest)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($productRestoreRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $productRestoreRequest->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Restore request rejected.');
    }

    public function show(Product $product)
    {
        $product->load(['seller.sellerProfile', 'category', 'images', 'files', 'tags']);

        return view('admin.product-review', compact('product'));
    }

    public function approve(Product $product)
    {
        // Don't publish a product whose file hasn't cleared malware scanning
        $unscanned = $product->files()->where('scan_status', '!=', 'clean')->exists();

        if ($unscanned) {
            return back()->with('error', 'This product has files that are not marked clean. Scan them first.');
        }

        $product->update([
            'status' => 'approved',
            'published_at' => $product->published_at ?? now(),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        \App\Support\SafeNotifier::send($product->seller, new ProductReviewed($product, approved: true));
        \App\Support\ActivityLogger::log('product.approved', $product, "Approved product \"{$product->title}\" by {$product->seller->name}.");

        return back()->with('success', "\"{$product->title}\" is now live.");
    }

    public function reject(Request $request, Product $product)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $product->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        \App\Support\SafeNotifier::send($product->seller, new ProductReviewed($product, approved: false));
        \App\Support\ActivityLogger::log('product.rejected', $product, "Rejected product \"{$product->title}\": {$data['rejection_reason']}");

        return back()->with('success', 'Product rejected and the seller has been notified.');
    }

    /**
     * Manually mark an uploaded file clean. In production this is done by an
     * automated ClamAV job; this is the admin override.
     */
    public function markFileClean(Product $product, int $fileId)
    {
        $product->files()->where('id', $fileId)->update(['scan_status' => 'clean']);

        return back()->with('success', 'File marked as clean.');
    }

    /**
     * Lets the admin actually look at the file before approving. PDFs
     * render inline in the browser's native PDF viewer (Content-Disposition:
     * inline). Every other format (docx/pptx/xlsx/zip/rar) has no browser-
     * native preview — this serves it as a download instead, since faking
     * an in-browser preview for those would be more confusing than useful.
     */
    public function previewFile(Product $product, \App\Models\ProductFile $file)
    {
        abort_unless($file->product_id === $product->id, 404);

        $disk = \Illuminate\Support\Facades\Storage::disk($file->disk);

        abort_unless($disk->exists($file->path), 404);

        $extension = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return $disk->response($file->path, $file->original_name, [
                'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
            ]);
        }

        return $disk->download($file->path, $file->original_name);
    }
}
