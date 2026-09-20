<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EngagementUnlock;
use App\Models\ManualPaymentProof;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'       => User::count(),
            'total_sellers'     => SellerProfile::where('application_status', 'approved')->count(),
            'pending_sellers'   => SellerProfile::where('application_status', 'pending')->count(),
            'pending_products'  => Product::pendingReview()->count(),
            'live_products'     => Product::published()->count(),
            'total_orders'      => Order::where('status', 'paid')->count(),
            'gross_revenue'     => (float) Order::where('status', 'paid')->sum('grand_total'),
            'platform_earnings' => (float) OrderItem::where('status', 'completed')->sum('commission_amount'),
            'pending_payouts'   => PayoutRequest::where('status', 'pending')->count(),
            'payout_liability'  => (float) PayoutRequest::where('status', 'pending')->sum('amount'),
            'pending_manual_payments' => ManualPaymentProof::where('status', 'pending')->count(),
            'pending_engagement'      => EngagementUnlock::where('status', 'pending')->count(),
        ];

        $recentOrders = Order::with('buyer')->where('status', 'paid')->latest()->take(10)->get();

        // --- Chart data ---

        // Revenue + order count, one row per day, last 30 days — zero-filled
        // so the chart doesn't have gaps on days with no sales.
        $raw = Order::where('status', 'paid')
            ->where('paid_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(paid_at) as date, SUM(grand_total) as revenue, COUNT(*) as orders')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chartDates = collect(range(0, 29))->map(fn ($i) => now()->subDays(29 - $i)->format('Y-m-d'));

        $revenueTrend = [
            'labels' => $chartDates->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M j'))->all(),
            'revenue' => $chartDates->map(fn ($d) => (float) ($raw[$d]->revenue ?? 0))->all(),
            'orders' => $chartDates->map(fn ($d) => (int) ($raw[$d]->orders ?? 0))->all(),
        ];

        // Payment method mix — which methods buyers actually use
        $paymentMethodMix = Order::where('status', 'paid')
            ->selectRaw('payment_method, COUNT(*) as count')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method');

        // Revenue by top-level category
        $categoryRevenue = OrderItem::where('order_items.status', 'completed')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category, SUM(order_items.price) as revenue')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->take(6)
            ->pluck('revenue', 'category');

        return view('admin.dashboard', compact('stats', 'recentOrders', 'revenueTrend', 'paymentMethodMix', 'categoryRevenue'));
    }
}
