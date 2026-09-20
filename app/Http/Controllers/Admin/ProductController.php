<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['seller.sellerProfile', 'category'])->withTrashed();

        if ($status = $request->string('status')->toString()) {
            $status === 'deleted'
                ? $query->onlyTrashed()
                : $query->where('status', $status)->withoutTrashed();
        } else {
            $query->withoutTrashed();
        }

        if ($sellerId = $request->integer('seller_id')) {
            $query->where('seller_id', $sellerId);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($term = $request->string('q')->trim()->toString()) {
            $query->where('title', 'like', "%{$term}%");
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        $categories = Category::orderBy('name')->get();
        $sellers = SellerProfile::where('application_status', 'approved')
            ->orderBy('store_name')->get();

        return view('admin.products.index', compact('products', 'categories', 'sellers'));
    }

    /**
     * Quick-toggle from the list view — full editing (title, price, deals,
     * files, everything) happens through the shared Seller\ProductController
     * edit/update, which admins now have access to via authorizeOwner().
     */
    public function toggleFeatured(Product $product)
    {
        $product->update(['is_featured' => ! $product->is_featured]);

        return back()->with('success', $product->is_featured
            ? "\"{$product->title}\" is now featured on the homepage."
            : "\"{$product->title}\" removed from featured.");
    }
}
