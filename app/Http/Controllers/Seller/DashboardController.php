<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\SellerWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index()
    {
        $sellerId = auth()->id();

        $wallet = SellerWallet::firstOrCreate(
            ['seller_id' => $sellerId],
            ['available_balance' => 0, 'pending_balance' => 0]
        );

        $stats = [
            'total_products' => Product::where('seller_id', $sellerId)->count(),
            'published' => Product::where('seller_id', $sellerId)->where('status', 'approved')->count(),
            'pending' => Product::where('seller_id', $sellerId)->where('status', 'pending_review')->count(),
            'total_sales' => OrderItem::where('seller_id', $sellerId)->where('status', 'completed')->count(),
            'total_earned' => $wallet->total_earned,
        ];

        // Revenue for the last 30 days, for the dashboard chart
        $revenue = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(seller_earning) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $recentSales = OrderItem::with(['product', 'order.buyer'])
            ->where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->latest()
            ->take(10)
            ->get();

        $topProducts = Product::where('seller_id', $sellerId)
            ->orderByDesc('sales_count')
            ->take(5)
            ->get();

        return view('seller.dashboard', compact('stats', 'wallet', 'revenue', 'recentSales', 'topProducts'));
    }

    public function applyForm()
    {
        $profile = auth()->user()->sellerProfile;

        return view('seller.apply', compact('profile'));
    }

    public function apply(Request $request)
    {
        $user = $request->user();

        if ($user->sellerProfile) {
            return back()->with('error', 'You have already applied.');
        }

        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:100', Rule::unique('seller_profiles', 'store_name')],
            'description' => ['required', 'string', 'min:50', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'paypal_email' => ['required', 'email', 'max:255'],
        ]);

        SellerProfile::create([
            'user_id' => $user->id,
            'store_name' => $data['store_name'],
            'slug' => Str::slug($data['store_name']) . '-' . Str::lower(Str::random(4)),
            'description' => $data['description'],
            'website' => $data['website'] ?? null,
            'application_status' => 'pending',
        ]);

        $user->update(['paypal_email' => $data['paypal_email']]);

        return redirect()->route('seller.apply.form')
            ->with('success', 'Application submitted. We will review it shortly.');
    }
}
