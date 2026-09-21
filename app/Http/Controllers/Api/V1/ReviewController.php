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

class ReviewController extends Controller
{
    public function index(string $slug): AnonymousResourceCollection
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $reviews = Review::query()
            ->where('product_id', $product->id)
            ->with('user:id,name')
            ->latest()
            ->paginate(20);

        return ReviewResource::collection($reviews);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $user    = $request->user();

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        // Verified purchasers only, same rule as the website.
        $owns = License::where('user_id', $user->id)->where('product_id', $product->id)->exists();
        abort_unless($owns, 403, 'Only customers who own this product can review it.');

        if (Review::where('user_id', $user->id)->where('product_id', $product->id)->exists()) {
            return response()->json(['message' => 'You have already reviewed this product.'], 409);
        }

        // ASSUMPTION: the text column is "comment". If it is "body", change the key below.
        $review = Review::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'rating'     => $data['rating'],
            'comment'    => $data['comment'] ?? null,
        ]);

        return (new ReviewResource($review->load('user:id,name')))
            ->response()
            ->setStatusCode(201);
    }
}
