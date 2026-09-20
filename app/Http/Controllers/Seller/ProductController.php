<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::where('seller_id', auth()->id())
            ->with('category')
            ->latest()
            ->paginate(15);

        $deletedProducts = Product::onlyTrashed()
            ->where('seller_id', auth()->id())
            ->with('category')
            ->latest('deleted_at')
            ->get();

        // Which of those already have a pending restore request, so the
        // view can show "Requested" instead of the button again.
        $pendingRestoreProductIds = \App\Models\ProductRestoreRequest::where('seller_id', auth()->id())
            ->where('status', 'pending')
            ->pluck('product_id');

        return view('seller.products.index', compact('products', 'deletedProducts', 'pendingRestoreProductIds'));
    }

    /**
     * A seller can delete their own product self-service (destroy() below),
     * but bringing a deleted product back is deliberately NOT self-service —
     * it goes to an admin, same trust boundary as everything else that
     * re-exposes a listing to buyers.
     */
    public function requestRestore(Request $request, int $productId)
    {
        $product = Product::onlyTrashed()->where('id', $productId)->firstOrFail();
        abort_unless($product->seller_id === auth()->id(), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $alreadyPending = \App\Models\ProductRestoreRequest::where('product_id', $product->id)
            ->where('status', 'pending')->exists();

        if ($alreadyPending) {
            return back()->with('error', 'You already have a pending restore request for this product.');
        }

        \App\Models\ProductRestoreRequest::create([
            'product_id' => $product->id,
            'seller_id' => auth()->id(),
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Restore request submitted. An admin will review it shortly.');
    }

    public function create()
    {
        return view('seller.products.create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'product' => new Product(),
        ]);
    }

    public function store(Request $request)
    {
        $this->guardAgainstFailedUploads($request);

        $data = $this->validateProduct($request, isCreate: true);

        $product = Product::create([
            'seller_id' => auth()->id(),
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . Str::lower(Str::random(6)),
            'short_description' => $data['short_description'],
            'description' => $data['description'],
            'regular_price' => $data['regular_price'],
            'extended_price' => $data['extended_price'] ?? null,
            'sale_price' => $data['sale_price'] ?? null,
            'sale_ends_at' => $data['sale_ends_at'] ?? null,
            'demo_url' => $data['demo_url'] ?? null,
            'framework' => $data['framework'] ?? null,
            'current_version' => $data['current_version'] ?? '1.0.0',
            'compatible_with' => $this->splitList($data['compatible_with'] ?? null),
            'allow_free_unlock' => $request->boolean('allow_free_unlock'),
            'youtube_video_url' => $request->boolean('allow_free_unlock') ? $data['youtube_video_url'] : null,
            'youtube_channel_url' => $request->boolean('allow_free_unlock') ? ($data['youtube_channel_url'] ?? null) : null,
            'required_watch_seconds' => $request->boolean('allow_free_unlock')
                ? (int) round((float) ($data['required_watch_minutes'] ?? 0) * 60)
                : 0,
            'is_featured' => auth()->user()->is_admin ? $request->boolean('is_featured') : false,
            'status' => 'draft',
        ]);

        $this->handleUploads($request, $product);
        $this->syncTags($product, $data['tags'] ?? null);

        return redirect()->route('seller.products.edit', $product)
            ->with('success', 'Product saved as draft. Submit it for review when ready.');
    }

    public function edit(Product $product)
    {
        $this->authorizeOwner($product);

        return view('seller.products.edit', [
            'product' => $product->load('images', 'files', 'tags', 'changelogs'),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwner($product);
        $this->guardAgainstFailedUploads($request);

        $isAdminEdit = $this->actingAsAdminOnOthersProduct($product);

        // Re-review is only forced when the actual deliverable (the ZIP/PDF/
        // whatever buyers download) changes — that's the real security/
        // content-risk surface, and it's what the malware scan + integrity
        // + duplicate-detection pipeline exists to re-check. Swapping a
        // thumbnail, tweaking the description, or changing the price is
        // marketing/metadata — it never needs to take a live listing down
        // for re-approval. An admin editing someone else's product never
        // re-queues either, since they're the approver in the first place.
        $wasApproved = $product->status === 'approved';
        $fileChanged = $request->hasFile('product_file');
        $shouldRequeue = $wasApproved && $fileChanged && ! $isAdminEdit;

        $data = $this->validateProduct($request, isCreate: false);

        $product->update([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'short_description' => $data['short_description'],
            'description' => $data['description'],
            'regular_price' => $data['regular_price'],
            'extended_price' => $data['extended_price'] ?? null,
            'sale_price' => $data['sale_price'] ?? null,
            'sale_ends_at' => $data['sale_ends_at'] ?? null,
            'demo_url' => $data['demo_url'] ?? null,
            'framework' => $data['framework'] ?? null,
            'current_version' => $data['current_version'] ?? $product->current_version,
            'compatible_with' => $this->splitList($data['compatible_with'] ?? null),
            'allow_free_unlock' => $request->boolean('allow_free_unlock'),
            'youtube_video_url' => $request->boolean('allow_free_unlock') ? $data['youtube_video_url'] : null,
            'youtube_channel_url' => $request->boolean('allow_free_unlock') ? ($data['youtube_channel_url'] ?? null) : null,
            'required_watch_seconds' => $request->boolean('allow_free_unlock')
                ? (int) round((float) ($data['required_watch_minutes'] ?? 0) * 60)
                : 0,
            // Never trust a seller-submitted value for this — only an
            // actual admin request can set it, regardless of what a
            // crafted form submission might include.
            'is_featured' => auth()->user()->is_admin ? $request->boolean('is_featured') : $product->is_featured,
            'status' => $shouldRequeue ? 'pending_review' : $product->status,
        ]);

        $this->handleUploads($request, $product);
        $this->syncTags($product, $data['tags'] ?? null);

        if ($request->filled('changelog_notes')) {
            $product->changelogs()->create([
                'version' => $product->current_version,
                'notes' => $request->string('changelog_notes')->toString(),
            ]);
        }

        $message = $shouldRequeue
            ? 'New file uploaded — product resubmitted for review and taken offline until approved.'
            : 'Product updated.';

        return back()->with('success', $message);
    }

    public function submitForReview(Product $product)
    {
        $this->authorizeOwner($product);

        if (! $product->files()->exists()) {
            return back()->with('error', 'Upload the product file before submitting.');
        }

        if (! $product->images()->exists()) {
            return back()->with('error', 'Upload at least one preview image before submitting.');
        }

        $product->update(['status' => 'pending_review', 'rejection_reason' => null]);

        return back()->with('success', 'Submitted for review. An admin will check it shortly.');
    }

    public function destroy(Product $product)
    {
        $this->authorizeOwner($product);

        $isAdminAction = $this->actingAsAdminOnOthersProduct($product);
        $redirectRoute = $isAdminAction ? 'admin.products.index' : 'seller.products.index';
        $title = $product->title;

        // Never hard-delete a product with sales — buyers still need downloads
        if ($product->orderItems()->exists()) {
            $product->update(['status' => 'suspended']);

            if ($isAdminAction) {
                \App\Support\ActivityLogger::log('product.unlisted', $product, "Admin unlisted \"{$title}\" (had existing sales, so it was suspended not deleted).");
            }

            return redirect()->route($redirectRoute)
                ->with('success', 'Product unlisted. Existing buyers keep access.');
        }

        $this->purgeProductFiles($product);
        $product->delete();

        if ($isAdminAction) {
            \App\Support\ActivityLogger::log('product.deleted', null, "Admin deleted product \"{$title}\".");
        }

        return redirect()->route($redirectRoute)->with('success', 'Product deleted.');
    }

    public function destroyImage(Product $product, \App\Models\ProductImage $image)
    {
        $this->authorizeOwner($product);
        abort_unless($image->product_id === $product->id, 404);

        Storage::disk('public')->delete($image->path);
        $wasPrimary = $image->is_primary;
        $image->delete();

        // Keep exactly one primary image, if any remain
        if ($wasPrimary) {
            $next = $product->images()->first();
            $next?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Image removed.');
    }

    // ---------- helpers ----------

    protected function authorizeOwner(Product $product): void
    {
        abort_unless($product->seller_id === auth()->id() || auth()->user()->is_admin, 403);
    }

    /**
     * True when an admin is editing a product they don't personally own —
     * used to decide where to redirect afterward (their own seller product
     * list would be wrong/irrelevant for that case) and whether the
     * privileged is_featured field is allowed through.
     */
    protected function actingAsAdminOnOthersProduct(Product $product): bool
    {
        return auth()->user()->is_admin && $product->seller_id !== auth()->id();
    }

    protected function validateProduct(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'short_description' => ['required', 'string', 'max:300'],
            'description' => ['required', 'string', 'min:100'],
            'regular_price' => ['required', 'numeric', 'min:1', 'max:9999'],
            'extended_price' => ['nullable', 'numeric', 'gte:regular_price', 'max:99999'],
            'sale_price' => ['nullable', 'numeric', 'min:0.01', 'lt:regular_price'],
            'sale_ends_at' => ['nullable', 'date', 'after:now'],
            'demo_url' => ['nullable', 'url', 'max:255'],
            'framework' => ['nullable', 'string', 'max:60'],
            'current_version' => ['nullable', 'string', 'max:20'],
            'compatible_with' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:255'],
            'allow_free_unlock' => ['nullable', 'boolean'],
            'youtube_video_url' => ['required_if:allow_free_unlock,1', 'nullable', 'url', 'max:255'],
            'youtube_channel_url' => ['nullable', 'url', 'max:255'],
            'required_watch_minutes' => ['nullable', 'numeric', 'min:0', 'max:120'],
            'thumbnail' => [$isCreate ? 'required' : 'nullable', 'image', 'max:2048'],
            'images' => ['nullable', 'array', 'max:6'],
            'images.*' => ['image', 'max:4096'],
            'product_file' => [
                $isCreate ? 'nullable' : 'nullable', 'file',
                'mimes:zip,rar,pdf,ppt,pptx,doc,docx,xls,xlsx',
                'max:204800',
            ],
        ], [
            'youtube_video_url.required_if' => 'A video URL is required when free access via YouTube engagement is enabled.',
        ]);
    }

    /**
     * When a file exceeds PHP's own upload_max_filesize or post_max_size
     * ini limits, PHP rejects it BEFORE Laravel's validation ever runs —
     * $request->hasFile() then just returns false, as if no file was
     * selected at all. On create, 'required' catches this with a generic
     * "field is required" message; on update, 'nullable' means it silently
     * does nothing — the seller sees no error and assumes their new
     * thumbnail saved when it didn't. This checks the raw PHP upload error
     * codes directly and surfaces what actually happened.
     */
    protected function guardAgainstFailedUploads(Request $request): void
    {
        $checks = ['thumbnail' => $request->file('thumbnail'), 'product_file' => $request->file('product_file')];

        foreach ($request->file('images', []) as $i => $image) {
            $checks["images[{$i}]"] = $image;
        }

        $reasons = [
            UPLOAD_ERR_INI_SIZE => "exceeds this server's upload_max_filesize setting (php.ini)",
            UPLOAD_ERR_FORM_SIZE => 'exceeds the maximum size allowed by the form',
            UPLOAD_ERR_PARTIAL => 'was only partially uploaded — please try again',
            UPLOAD_ERR_NO_TMP_DIR => "the server has no temporary folder configured for uploads",
            UPLOAD_ERR_CANT_WRITE => 'failed to write to disk on the server',
        ];

        foreach ($checks as $field => $file) {
            if ($file && ! $file->isValid()) {
                $reason = $reasons[$file->getError()] ?? ('upload failed (PHP error code ' . $file->getError() . ')');

                throw \Illuminate\Validation\ValidationException::withMessages([
                    $field => "Upload failed: the file {$reason}. If this is a large product ZIP, "
                        . "your server's php.ini likely needs upload_max_filesize and post_max_size "
                        . "raised (try 210M) — then restart php artisan serve.",
                ]);
            }
        }
    }

    protected function handleUploads(Request $request, Product $product): void
    {
        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $product->update([
                'thumbnail' => $request->file('thumbnail')->store('products/thumbnails', 'public'),
            ]);
        }

        foreach ($request->file('images', []) as $index => $image) {
            $product->images()->create([
                'path' => $image->store('products/gallery', 'public'),
                'is_primary' => $index === 0 && ! $product->images()->exists(),
                'sort_order' => $index,
            ]);
        }

        // The sellable ZIP goes to the PRIVATE disk, never public storage
        if ($request->hasFile('product_file')) {
            $file = $request->file('product_file');

            $path = $file->store("products/{$product->id}/files", 'private');

            $productFile = $product->files()->create([
                'disk' => 'private',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
                'version' => $product->current_version,
                'checksum' => hash_file('sha256', $file->getRealPath()),
                // 'clean' here is a LOCAL-DEV-ONLY shortcut (AUTO_CLEAN_UPLOADS=true)
                // so you can test the full purchase flow without ClamAV installed.
                // In any real deployment this must be false — the scan job below
                // is what actually clears the file.
                'scan_status' => config('marketplace.auto_clean_uploads') ? 'clean' : 'pending',
            ]);

            if (! config('marketplace.auto_clean_uploads')) {
                \App\Jobs\ScanProductFileJob::dispatch($productFile->id);
            }

            // Integrity/duplicate/AI-review checks run regardless of the
            // malware-scan setting — they're cheap, deterministic (mostly),
            // and don't depend on ClamAV being installed.
            \App\Jobs\AnalyzeProductFileJob::dispatch($productFile->id);
        }
    }

    protected function syncTags(Product $product, ?string $tagString): void
    {
        if ($tagString === null) {
            return;
        }

        $ids = collect(explode(',', $tagString))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->take(10)
            ->map(function ($name) {
                return Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                )->id;
            });

        $product->tags()->sync($ids);
    }

    protected function purgeProductFiles(Product $product): void
    {
        if ($product->thumbnail) {
            Storage::disk('public')->delete($product->thumbnail);
        }

        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        foreach ($product->files as $file) {
            Storage::disk($file->disk)->delete($file->path);
        }
    }

    protected function splitList(?string $value): ?array
    {
        if (! $value) {
            return null;
        }

        return collect(explode(',', $value))->map(fn ($v) => trim($v))->filter()->values()->all();
    }
}
