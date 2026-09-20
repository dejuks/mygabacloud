<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cart)
    {
    }

    public function index()
    {
        return view('cart.index', [
            'items' => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
            'discount' => $this->cart->discount(),
            'total' => $this->cart->total(),
            'coupon' => $this->cart->coupon(),
        ]);
    }

    public function add(Request $request, Product $product)
    {
        $request->validate([
            'license_type' => ['nullable', 'in:regular,extended'],
        ]);

        abort_unless($product->status === 'approved', 404);

        if ($product->seller_id === auth()->id()) {
            return back()->with('error', 'You cannot buy your own product.');
        }

        // Digital goods are owned forever — block re-buying
        if (auth()->user()->licenses()->where('product_id', $product->id)->where('status', 'active')->exists()) {
            return back()->with('error', 'You already own this product. Find it in your library.');
        }

        $this->cart->add($product, $request->input('license_type', 'regular'));

        return redirect()->route('cart.index')->with('success', 'Added to cart.');
    }

    public function remove(Product $product)
    {
        $this->cart->remove($product->id);

        return back()->with('success', 'Removed from cart.');
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $coupon = Coupon::where('code', $request->string('code')->upper())->first();

        if (! $coupon || ! $coupon->isValid()) {
            return back()->with('error', 'That coupon code is not valid.');
        }

        $this->cart->applyCoupon($coupon);

        return back()->with('success', 'Coupon applied.');
    }

    public function removeCoupon()
    {
        $this->cart->removeCoupon();

        return back()->with('success', 'Coupon removed.');
    }
}
