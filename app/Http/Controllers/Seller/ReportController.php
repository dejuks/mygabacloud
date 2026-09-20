<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $sellerId = auth()->id();

        $stats = [
            'total_revenue' => (float) OrderItem::where('seller_id', $sellerId)->where('status', 'completed')->sum('seller_earning'),
            'total_sales' => OrderItem::where('seller_id', $sellerId)->where('status', 'completed')->count(),
            'total_products' => Product::where('seller_id', $sellerId)->count(),
            'live_products' => Product::where('seller_id', $sellerId)->where('status', 'approved')->count(),
        ];

        $stats['average_sale'] = $stats['total_sales'] > 0
            ? $stats['total_revenue'] / $stats['total_sales']
            : 0;

        // --- 30-day revenue trend (seller's own earnings, not gross price) ---
        $raw = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(seller_earning) as revenue, COUNT(*) as sales')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chartDates = collect(range(0, 29))->map(fn ($i) => now()->subDays(29 - $i)->format('Y-m-d'));

        $revenueTrend = [
            'labels' => $chartDates->map(fn ($d) => Carbon::parse($d)->format('M j'))->all(),
            'revenue' => $chartDates->map(fn ($d) => (float) ($raw[$d]->revenue ?? 0))->all(),
            'sales' => $chartDates->map(fn ($d) => (int) ($raw[$d]->sales ?? 0))->all(),
        ];

        // --- Revenue by category, this seller's products only ---
        $categoryRevenue = OrderItem::where('order_items.seller_id', $sellerId)
            ->where('order_items.status', 'completed')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category, SUM(order_items.seller_earning) as revenue')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->get()
            ->pluck('revenue', 'category');

        // --- Top products by revenue ---
        $topProducts = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->select('product_id', DB::raw('SUM(seller_earning) as revenue'), DB::raw('COUNT(*) as sale_count'))
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->take(8)
            ->with('product:id,title')
            ->get();

        return view('seller.reports', compact('stats', 'revenueTrend', 'categoryRevenue', 'topProducts'));
    }
}
