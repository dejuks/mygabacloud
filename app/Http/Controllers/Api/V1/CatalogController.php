<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only browsing, equivalent to the public Blade pages.
 */
class CatalogController extends Controller
{
    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->withCount(['products' => fn (Builder $q) => $this->onlyApproved($q)])
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $params = $request->validate([
            'category'  => ['nullable', 'string', 'max:100'],
            'q'         => ['nullable', 'string', 'max:100'],
            'sort'      => ['nullable', 'in:popular,newest,price_asc,price_desc'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = $this->onlyApproved(Product::query())
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if (! empty($params['category'])) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $params['category']));
        }

        if (! empty($params['q'])) {
            $term = '%' . addcslashes($params['q'], '%_\\') . '%';
            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', $term)
                ->orWhere('description', 'like', $term));
        }

        if (isset($params['min_price'])) {
            $query->where('price', '>=', $params['min_price']);
        }
        if (isset($params['max_price'])) {
            $query->where('price', '<=', $params['max_price']);
        }

        match ($params['sort'] ?? 'newest') {
            'popular'    => $query->orderByDesc('sales_count'),
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default      => $query->latest(),
        };

        return ProductResource::collection($query->paginate($params['per_page'] ?? 20)->withQueryString());
    }

    public function show(string $slug): ProductDetailResource
    {
        $product = $this->onlyApproved(Product::query())
            ->where('slug', $slug)
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->firstOrFail();

        return new ProductDetailResource($product);
    }

    /**
     * ASSUMPTION: a product is publicly visible when status = 'approved'.
     * If the web catalog uses a scope (e.g. Product::approved()), call that
     * here instead so the app and website always agree.
     */
    private function onlyApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
