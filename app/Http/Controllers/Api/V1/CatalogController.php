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
 * Columns verified against the SQL dump (regular_price, average_rating, sort_order ...).
 */
class CatalogController extends Controller
{
    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn (Builder $q) => $this->onlyApproved($q)])
            ->orderBy('sort_order')
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

        $query = $this->onlyApproved(Product::query());

        if (! empty($params['category'])) {
            $category = Category::where('slug', $params['category'])->first();
            if ($category) {
                // A parent category also shows its sub-categories' products.
                $ids = Category::where('parent_id', $category->id)->pluck('id')->push($category->id);
                $query->whereIn('products.category_id', $ids);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($params['q'])) {
            $term = '%' . addcslashes($params['q'], '%_\\') . '%';
            $query->where(fn (Builder $q) => $q
                ->where('products.title', 'like', $term)
                ->orWhere('products.short_description', 'like', $term)
                ->orWhere('products.description', 'like', $term));
        }

        if (isset($params['min_price'])) {
            $query->where('products.regular_price', '>=', $params['min_price']);
        }
        if (isset($params['max_price'])) {
            $query->where('products.regular_price', '<=', $params['max_price']);
        }

        match ($params['sort'] ?? 'newest') {
            'popular'    => $query->orderByDesc('products.sales_count'),
            'price_asc'  => $query->orderBy('products.regular_price'),
            'price_desc' => $query->orderByDesc('products.regular_price'),
            default      => $query->orderByRaw('COALESCE(products.published_at, products.created_at) DESC')
                                  ->orderByDesc('products.id'),
        };

        $page = $query->paginate($params['per_page'] ?? 20)->withQueryString();
        $this->attachCategories($page->getCollection());

        return ProductResource::collection($page);
    }

    public function show(string $slug): ProductDetailResource
    {
        $product = $this->onlyApproved(Product::query())
            ->where('products.slug', $slug)
            ->firstOrFail();

        $this->attachCategories(collect([$product]));

        return new ProductDetailResource($product);
    }

    /** Publicly visible = approved and not soft-deleted. */
    private function onlyApproved(Builder $query): Builder
    {
        return $query->where('products.status', 'approved')->whereNull('products.deleted_at');
    }

    /** One extra query instead of relying on a relation name. */
    private function attachCategories($products): void
    {
        $categories = Category::whereIn('id', $products->pluck('category_id')->unique())->get()->keyBy('id');

        foreach ($products as $product) {
            $product->setRelation('category', $categories->get($product->category_id));
        }
    }
}
