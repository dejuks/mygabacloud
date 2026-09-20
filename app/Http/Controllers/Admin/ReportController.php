<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // --- Products ---
        $productStats = [
            'total' => Product::withTrashed()->count(),
            'by_status' => Product::withTrashed()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'deleted' => Product::onlyTrashed()->count(),
        ];

        $topProductsBySales = Product::published()
            ->orderByDesc('sales_count')
            ->take(10)
            ->get(['id', 'title', 'sales_count', 'seller_id']);

        $topProductsByRevenue = OrderItem::where('status', 'completed')
            ->select('product_id', DB::raw('SUM(price) as revenue'), DB::raw('COUNT(*) as sale_count'))
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->take(10)
            ->with('product:id,title')
            ->get();

        $categoryPerformance = Category::withCount(['products' => fn ($q) => $q->published()])
            ->whereNull('parent_id')
            ->orderByDesc('products_count')
            ->get(['id', 'name']);

        // --- Sellers ---
        $sellerStats = [
            'total_approved' => SellerProfile::where('application_status', 'approved')->count(),
            'pending_applications' => SellerProfile::where('application_status', 'pending')->count(),
            'suspended' => User::where('is_seller', true)->where('status', 'suspended')->count(),
        ];

        $topSellersByEarnings = SellerProfile::where('application_status', 'approved')
            ->with('user.wallet')
            ->get()
            ->sortByDesc(fn ($s) => $s->user->wallet->total_earned ?? 0)
            ->take(10)
            ->values();

        $totalCommissionCollected = (float) OrderItem::where('status', 'completed')->sum('commission_amount');
        $totalSellerEarnings = (float) OrderItem::where('status', 'completed')->sum('seller_earning');

        // --- Users ---
        $userStats = [
            'total' => User::count(),
            'buyers_only' => User::where('is_seller', false)->count(),
            'sellers' => User::where('is_seller', true)->count(),
            'admins' => User::where('is_admin', true)->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'new_last_30_days' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'new_last_7_days' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $registrationTrend = User::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // --- Activity log (paginated, filterable — the "user logs" part) ---
        $logQuery = ActivityLog::with('user')->latest('created_at');

        if ($actionFilter = $request->string('log_action')->toString()) {
            $logQuery->where('action', $actionFilter);
        }

        if ($userFilter = $request->integer('log_user_id')) {
            $logQuery->where('user_id', $userFilter);
        }

        $activityLog = $logQuery->paginate(25, ['*'], 'log_page')->withQueryString();

        $distinctActions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $logUsers = User::whereIn('id', ActivityLog::select('user_id')->distinct()->whereNotNull('user_id'))
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.reports', compact(
            'productStats', 'topProductsBySales', 'topProductsByRevenue', 'categoryPerformance',
            'sellerStats', 'topSellersByEarnings', 'totalCommissionCollected', 'totalSellerEarnings',
            'userStats', 'registrationTrend',
            'activityLog', 'distinctActions', 'logUsers'
        ));
    }
}
