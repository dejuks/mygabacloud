<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\License;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(string $slug): AnonymousResourceCollection
    {
        $product = $this->publicProduct($slug);

        return ReviewResource::collection(
            $this->reviewQuery()->where('reviews.product_id', $product->id)
                ->orderByDesc('reviews.id')
                ->paginate(20)
        );
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $product = $this->publicProduct($slug);
        $user    = $request->user();

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        // reviews.order_item_id is NOT NULL: only real purchases can be reviewed
        // (free-unlock licences have no order item).
        $license = License::where('buyer_id', $user->id)
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->whereNotNull('order_item_id')
            ->first();

        $paid = $license && DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.id', $license->order_item_id)
            ->where('order_items.status', 'completed')
            ->whereIn('orders.status', ['paid', 'partially_refunded'])
            ->exists();

        abort_unless($paid, 403, 'Only customers who purchased this product can review it.');

        if (DB::table('reviews')->where('user_id', $user->id)->where('product_id', $product->id)->exists()) {
            return response()->json(['message' => 'You have already reviewed this product.'], 409);
        }

        // Model save (not raw insert) so any observers on Review still run.
        $review = new Review();
        $review->forceFill([
            'product_id'    => $product->id,
            'user_id'       => $user->id,
            'order_item_id' => $license->order_item_id,
            'rating'        => $data['rating'],
            'comment'       => $data['comment'] ?? null,
        ])->save();

        // Keep the denormalised counters on products in sync (safe to repeat).
        $stats = DB::table('reviews')->where('product_id', $product->id)
            ->selectRaw('COUNT(*) as c, AVG(rating) as a')->first();
        DB::table('products')->where('id', $product->id)->update([
            'reviews_count'  => (int) $stats->c,
            'average_rating' => round((float) $stats->a, 2),
        ]);

        $created = $this->reviewQuery()->where('reviews.id', $review->id)->first();

        return (new ReviewResource($created))->response()->setStatusCode(201);
    }

    private function publicProduct(string $slug): object
    {
        $product = DB::table('products')
            ->where('slug', $slug)->where('status', 'approved')->whereNull('deleted_at')
            ->first(['id']);

        abort_unless($product, 404, 'Product not found.');

        return $product;
    }

    private function reviewQuery()
    {
        return DB::table('reviews')
            ->join('users', 'users.id', '=', 'reviews.user_id')
            ->select('reviews.id', 'reviews.rating', 'reviews.comment', 'reviews.created_at', 'users.name as author_name');
    }
}
