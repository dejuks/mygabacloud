<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();

        $latest = Product::published()
            ->with(['seller', 'category'])
            ->latest('published_at')
            ->take(8)
            ->get();

        $bestSellers = Product::published()
            ->with(['seller', 'category'])
            ->orderByDesc('sales_count')
            ->take(8)
            ->get();

        $topRated = Product::published()
            ->with(['seller', 'category'])
            ->where('reviews_count', '>', 0)
            ->orderByDesc('average_rating')
            ->take(4)
            ->get();

        $featured = Product::published()
            ->with(['seller', 'category'])
            ->where('is_featured', true)
            ->take(8)
            ->get();

        return view('home', compact('categories', 'latest', 'bestSellers', 'topRated', 'featured'));
    }
}
