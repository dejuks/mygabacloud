<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::published()->with(['seller', 'category']);

        if ($term = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('short_description', 'like', "%{$term}%")
                  ->orWhere('framework', 'like', "%{$term}%");
            });
        }

        if ($categorySlug = $request->string('category')->toString()) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                // Include child categories so "PHP Scripts" also returns its subcategories
                $ids = $category->children()->pluck('id')->push($category->id);
                $query->whereIn('category_id', $ids);
            }
        }

        if ($request->filled('min_price')) {
            $query->where('regular_price', '>=', $request->float('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('regular_price', '<=', $request->float('max_price'));
        }

        if ($request->filled('framework')) {
            $query->where('framework', $request->string('framework')->toString());
        }

        if ($request->filled('min_rating')) {
            $query->where('average_rating', '>=', $request->float('min_rating'));
        }

        $query->when($request->string('sort')->toString(), function ($q, $sort) {
            match ($sort) {
                'price_low' => $q->orderBy('regular_price'),
                'price_high' => $q->orderByDesc('regular_price'),
                'rating' => $q->orderByDesc('average_rating'),
                'popular' => $q->orderByDesc('sales_count'),
                default => $q->latest('published_at'),
            };
        }, fn ($q) => $q->latest('published_at'));

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();

        $frameworks = Product::published()
            ->whereNotNull('framework')
            ->distinct()
            ->pluck('framework');

        return view('products.index', compact('products', 'categories', 'frameworks'));
    }

    public function show(Product $product)
    {
        abort_unless(
            $product->status === 'approved' && $product->published_at,
            404
        );

        $product->increment('views_count');

        $product->load([
            'seller.sellerProfile',
            'category',
            'tags',
            'images',
            'changelogs',
            'reviews.user',
        ]);

        $related = Product::published()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        // Has this user bought it, and can they still review it?
        $purchasedItem = null;
        if (auth()->check()) {
            $purchasedItem = OrderItem::where('seller_id', '!=', auth()->id())
                ->where('product_id', $product->id)
                ->where('status', 'completed')
                ->whereHas('order', fn ($q) => $q->where('buyer_id', auth()->id())->where('status', 'paid'))
                ->whereDoesntHave('review')
                ->first();
        }

        return view('products.show', compact('product', 'related', 'purchasedItem'));
    }

    public function storeReview(Request $request, Product $product)
    {
        $data = $request->validate([
            'order_item_id' => ['required', 'exists:order_items,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = OrderItem::with('order')->findOrFail($data['order_item_id']);

        // Verify the reviewer actually bought this exact product
        if ($item->order->buyer_id !== auth()->id()
            || $item->product_id !== $product->id
            || $item->status !== 'completed') {
            throw ValidationException::withMessages([
                'rating' => 'You can only review products you have purchased.',
            ]);
        }

        if ($item->review()->exists()) {
            throw ValidationException::withMessages([
                'rating' => 'You have already reviewed this purchase.',
            ]);
        }

        Review::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'order_item_id' => $item->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $this->recalculateRating($product);

        return back()->with('success', 'Thanks for your review.');
    }

    protected function recalculateRating(Product $product): void
    {
        $product->update([
            'average_rating' => round((float) $product->reviews()->avg('rating'), 2),
            'reviews_count' => $product->reviews()->count(),
        ]);

        $profile = $product->seller->sellerProfile;

        if ($profile) {
            $avg = Product::where('seller_id', $product->seller_id)
                ->where('reviews_count', '>', 0)
                ->avg('average_rating');

            $profile->update(['average_rating' => round((float) $avg, 2)]);
        }
    }
}
