<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a short text excerpt from an uploaded document to an LLM for
 * ADVISORY flags only — never a pass/fail gate, never shown to buyers, and
 * never a substitute for the admin actually looking at the file. This is
 * explicitly NOT a copyright determination: no model can reliably tell
 * whether content was stolen, and claiming otherwise would be dishonest.
 * What it CAN reasonably flag: a copyright notice in the text that doesn't
 * match the seller's name (worth a human second look), or content that's
 * mostly placeholder/lorem-ipsum text despite being submitted as a finished
 * product (worth checking it's really finished).
 *
 * Entirely optional — if ANTHROPIC_API_KEY isn't set in .env, this returns
 * null immediately and nothing downstream breaks or blocks on it.
 */
class AiContentReviewer
{
    public function review(string $textSnippet, Product $product): ?string
    {
        $apiKey = config('services.anthropic.api_key');

        if (! $apiKey || trim($textSnippet) === '') {
            return null;
        }

        $sellerName = $product->seller->sellerProfile->store_name ?? $product->seller->name;

        $prompt = <<<PROMPT
        You are assisting a marketplace admin who is manually reviewing a product submission before approving it. You are given a short text excerpt extracted from the uploaded file, NOT the whole document.

        Product title: {$product->title}
        Submitted by seller: {$sellerName}

        Your job is narrow: point out anything in this excerpt an admin should personally double-check before approving — nothing more. Do NOT claim to determine whether this is copyright infringement; you cannot know that from an excerpt, and you must not imply otherwise. Specifically look for:
        1. A copyright notice, author name, or company name in the text that does NOT match the seller name above (worth a manual check — could mean nothing, or could mean this wasn't originally made by this seller).
        2. Content that reads as unfinished placeholder text (e.g. heavy "lorem ipsum", "TODO", bracketed placeholders) despite being submitted as a finished product.
        3. Content that seems unrelated to the product title (may indicate the wrong file was uploaded).

        If none of these apply, say so plainly in one short sentence. Keep your entire response under 80 words. Do not add disclaimers about being an AI — the admin already knows this is an automated advisory note.

        Excerpt:
        {$textSnippet}
        PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(20)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model', 'claude-3-5-haiku-20241022'),
                'max_tokens' => 200,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($response->failed()) {
                Log::warning('AI content review request failed: ' . $response->body());
                return null;
            }

            return $response->json('content.0.text');
        } catch (\Throwable $e) {
            Log::warning('AI content review errored: ' . $e->getMessage());
            return null;
        }
    }
}
