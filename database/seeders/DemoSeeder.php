<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Models\SellerWallet;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds a small but fully realistic digital-products marketplace:
 * 4 categories x 4 products = 16 products, each with a real thumbnail image
 * and a real downloadable ZIP (so the buy -> download flow works out of the
 * box), plus one already-completed sale so the demo buyer has something in
 * their library immediately, and a handful of reviews.
 *
 * Run with:  php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    protected string $assetsPath;

    public function run(): void
    {
        $this->assetsPath = __DIR__ . '/assets';

        $this->seedSettings();
        $admin = $this->seedAdmin();
        $buyer = $this->seedBuyer();
        $categories = $this->seedCategories();
        $sellers = $this->seedSellers();
        $products = $this->seedProducts($admin, $sellers, $categories);
        $this->seedSampleSale($buyer, $products);
        $this->seedReviews($buyer, $products);

        $this->command->info('');
        $this->command->info('Demo data seeded: ' . count($products) . ' digital products across ' . count($categories) . ' categories.');
        $this->command->info('---------------------------------------------');
        $this->command->info('Admin:  admin@marketplace.test  / password');
        $this->command->info('Buyer:  buyer@marketplace.test  / password  (already owns "Laravel SaaS Starter Kit")');
        $this->command->info('Seller: codecraft@marketplace.test / password');
        $this->command->info('---------------------------------------------');
    }

    protected function seedSettings(): void
    {
        $settings = [
            'default_commission_rate' => '30',
            'minimum_payout_amount' => '50',
            'payout_holding_days' => '14',
            'site_currency' => 'USD',

            // USDT (TRC20) — real details as provided. Double-check the
            // wallet address in the admin settings page before going live;
            // a typo here sends buyers' money somewhere unrecoverable.
            'usdt_account_holder' => 'Dejene Kasa Aelmu',
            'usdt_wallet_address' => 'TEaXhSpnYEZjpahJgXB284s3FudSVigFfH',
            'usdt_note' => 'Send USDT on the TRC20 (Tron) network only. Any other network will result in lost funds.',

            // CBE / Telebirr — left blank intentionally. No real account
            // details were provided for these; fill them in from
            // Admin > Settings before enabling them for real buyers.
            'cbe_account_holder' => '',
            'cbe_account_number' => '',
            'cbe_branch' => '',
            'cbe_note' => '',
            'telebirr_account_holder' => '',
            'telebirr_number' => '',
            'telebirr_note' => '',
            'payment_support_contact' => '',
        ];

        foreach ($settings as $key => $value) {
            PlatformSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    protected function seedAdmin(): User
    {
        return User::updateOrCreate(
            ['email' => 'admin@marketplace.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }

    protected function seedBuyer(): User
    {
        return User::updateOrCreate(
            ['email' => 'buyer@marketplace.test'],
            [
                'name' => 'Demo Buyer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * @return array<string, Category> keyed by category slug
     */
    protected function seedCategories(): array
    {
        // Digital products, not limited to code — templates, documents,
        // presentations, and design assets all belong here too.
        $definitions = [
            'php-scripts' => ['PHP Scripts', 'Backend applications, SaaS boilerplates and admin tools built in PHP.'],
            'app-templates' => ['App Templates', 'Flutter and React Native UI templates for mobile apps.'],
            'wordpress' => ['WordPress', 'Themes and plugins for WordPress and WooCommerce.'],
            'ui-kits' => ['UI Kits & Design Assets', 'Design files, icon packs and ready-made HTML templates.'],
            'documents-templates' => ['Documents & Templates', 'Business, legal and bidding document templates — Word, PDF and Excel.'],
            'presentation-templates' => ['Presentation Templates', 'PowerPoint and slide-deck templates for pitches, reports and proposals.'],
            'ebooks-guides' => ['eBooks & Guides', 'PDF guides, courses and written resources.'],
        ];

        $order = 0;
        $categories = [];

        foreach ($definitions as $slug => [$name, $description]) {
            $categories[$slug] = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => $order++,
                    'is_active' => true,
                ]
            );
        }

        return $categories;
    }

    /**
     * @return array<string, User> keyed by a short handle
     */
    protected function seedSellers(): array
    {
        $specs = [
            'codecraft' => ['CodeCraft Studio', 'codecraft@marketplace.test',
                'We build production-ready Laravel applications, SaaS boilerplates and admin panels.'],
            'pixelforge' => ['PixelForge', 'pixelforge@marketplace.test',
                'Mobile app UI templates built with Flutter and React Native, focused on clean design.'],
            'themeworks' => ['ThemeWorks', 'themeworks@marketplace.test',
                'Premium WordPress themes and WooCommerce plugins with clean, well-documented code.'],
            'assetlab' => ['AssetLab', 'assetlab@marketplace.test',
                'Design assets, icon packs and UI kits for designers and front-end developers.'],
            'docuvault' => ['DocuVault', 'docuvault@marketplace.test',
                'Business, legal and bidding document templates, plus presentation and pitch deck templates.'],
        ];

        $sellers = [];

        foreach ($specs as $handle => [$storeName, $email, $bio]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $storeName,
                    'password' => Hash::make('password'),
                    'is_seller' => true,
                    'email_verified_at' => now(),
                    'paypal_email' => $email,
                ]
            );

            SellerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name' => $storeName,
                    'slug' => Str::slug($storeName),
                    'description' => $bio,
                    'application_status' => 'approved',
                    'is_verified' => true,
                    'approved_at' => now()->subMonths(rand(2, 10)),
                ]
            );

            SellerWallet::firstOrCreate(
                ['seller_id' => $user->id],
                ['available_balance' => 0, 'pending_balance' => 0]
            );

            $sellers[$handle] = $user;
        }

        return $sellers;
    }

    /**
     * @return array<string, Product> keyed by slug, in seed order
     */
    protected function seedProducts(User $admin, array $sellers, array $categories): array
    {
        // [slug, title, short_desc, category, seller, regular, extended, framework, compatible_with]
        $specs = [
            ['laravel-saas-starter-kit', 'Laravel SaaS Starter Kit', 'Multi-tenant SaaS boilerplate with Stripe billing built in', 'php-scripts', 'codecraft', 59, 249, 'Laravel', ['PHP 8.2', 'MySQL 8', 'Laravel 11']],
            ['invoice-management-system', 'Invoice Management System', 'Create, send and track invoices with PDF export and reminders', 'php-scripts', 'codecraft', 42, 180, 'Laravel', ['PHP 8.2', 'MySQL 8']],
            ['job-board-script', 'Job Board Script', 'Post jobs, accept applications, employer and candidate dashboards', 'php-scripts', 'codecraft', 55, 230, 'Laravel', ['PHP 8.2', 'Redis']],
            ['restaurant-pos-system', 'Restaurant POS System', 'Point of sale with table management, kitchen tickets and receipts', 'php-scripts', 'codecraft', 52, 220, 'Laravel', ['PHP 8.1', 'MySQL 8']],

            ['flutter-ecommerce-app', 'Flutter E-Commerce App', 'Complete shopping app UI with cart, checkout and order tracking', 'app-templates', 'pixelforge', 45, 199, 'Flutter', ['Flutter 3.x', 'Dart 3']],
            ['react-native-food-delivery', 'React Native Food Delivery', 'Food ordering app template with live maps and order tracking', 'app-templates', 'pixelforge', 49, 210, 'React Native', ['React Native 0.73']],
            ['fitness-tracker-app-ui', 'Fitness Tracker App UI', 'Workout logging, progress charts and a clean onboarding flow', 'app-templates', 'pixelforge', 35, 150, 'Flutter', ['Flutter 3.x']],
            ['social-media-app-template', 'Social Media App Template', 'Feed, stories, chat and profile screens ready to customize', 'app-templates', 'pixelforge', 47, 195, 'React Native', ['React Native 0.73']],

            ['minimal-blog-wordpress-theme', 'Minimal Blog WordPress Theme', 'Fast, SEO-friendly blogging theme with a clean typography focus', 'wordpress', 'themeworks', 39, 149, 'WordPress', ['WordPress 6.4', 'PHP 8.0']],
            ['woocommerce-booking-plugin', 'WooCommerce Booking Plugin', 'Appointment and resource booking for WooCommerce stores', 'wordpress', 'themeworks', 35, 140, 'WordPress', ['WooCommerce 8.x']],
            ['real-estate-listings-theme', 'Real Estate Listings Theme', 'Property listings with search, filters and agent profiles', 'wordpress', 'themeworks', 44, 175, 'WordPress', ['WordPress 6.4']],
            ['membership-lms-plugin', 'Membership & LMS Plugin', 'Gated content, courses, quizzes and member dashboards', 'wordpress', 'themeworks', 48, 199, 'WordPress', ['WordPress 6.4', 'PHP 8.1']],

            ['saas-dashboard-ui-kit', 'SaaS Dashboard UI Kit', 'Figma components for building analytics and admin dashboards', 'ui-kits', 'assetlab', 29, 99, 'Figma', ['Figma']],
            ['landing-page-bundle', 'Landing Page Bundle', '12 responsive HTML landing pages for SaaS, agencies and apps', 'ui-kits', 'assetlab', 19, 79, 'HTML', ['HTML5', 'Tailwind CSS 3']],
            ['mobile-app-ui-kit', 'Mobile App UI Kit', 'Over 120 mobile screens covering onboarding, e-commerce and social', 'ui-kits', 'assetlab', 33, 129, 'Figma', ['Figma']],
            ['icon-pack-900-icons', 'Icon Pack — 900 Icons', 'A consistent, line-style icon set covering common UI needs', 'ui-kits', 'assetlab', 15, 59, 'SVG', ['SVG', 'Figma']],

            ['bidding-document-template', 'Construction Bidding Document Template', 'A complete tender/bid submission template covering scope, requirements and evaluation criteria', 'documents-templates', 'docuvault', 12, 45, 'PDF', ['PDF', 'Microsoft Word']],
            ['pitch-deck-template', 'Startup Pitch Deck Template', 'A clean, investor-ready pitch deck template covering problem, solution, market and business model', 'presentation-templates', 'docuvault', 18, 65, 'PowerPoint', ['PowerPoint 2016+', 'Google Slides']],
        ];

        $products = [];

        // Every demo product gets free-unlock enabled, so the banner and the
        // whole watch -> checklist -> approve flow is testable no matter
        // which product ends up in the cart. Uses the Blender Foundation's
        // public "Big Buck Bunny" trailer — a real, verified, openly-licensed
        // YouTube upload — purely as a stand-in. Replace with your own
        // product demo video (and a realistic required watch time — sellers
        // default to 4 minutes on the form) from the seller edit page.
        //
        // Watch time here is deliberately short (15s) purely so testing the
        // flow doesn't mean sitting through 4 minutes of video every time.
        $demoVideoUrl = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ'; // verified: "Big Buck Bunny 60fps 4K", Blender Foundation
        $demoChannelUrl = 'https://www.youtube.com/@BlenderOfficial'; // verified: Blender's official channel handle
        $demoRequiredWatchSeconds = 15;

        foreach ($specs as $i => [$slug, $title, $short, $catSlug, $sellerHandle, $price, $extended, $framework, $compat]) {
            $existing = Product::where('slug', $slug)->first();

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'seller_id' => $sellers[$sellerHandle]->id,
                    'category_id' => $categories[$catSlug]->id,
                    'title' => $title,
                    'short_description' => $short,
                    'description' => $this->description($title, $short),
                    'regular_price' => $price,
                    'extended_price' => $extended,
                    'framework' => $framework,
                    'current_version' => '1.' . rand(0, 4) . '.' . rand(0, 9),
                    'compatible_with' => $compat,
                    'demo_url' => 'https://example.com/demo/' . $slug,
                    'status' => 'approved',
                    'published_at' => now()->subDays(rand(5, 180)),
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now()->subDays(rand(5, 180)),
                    'allow_free_unlock' => true,
                    'youtube_video_url' => $demoVideoUrl,
                    'youtube_channel_url' => $demoChannelUrl,
                    'required_watch_seconds' => $demoRequiredWatchSeconds,
                    // sales_count and views_count are ONLY set here on first
                    // creation, never on re-run. Once real purchases start
                    // accumulating sales_count via OrderFulfilmentService,
                    // re-running this seeder (to pick up a new feature, reset
                    // other data, whatever) must never silently wipe that back
                    // to 0 — that would be actively destroying real activity.
                    ...($existing ? [] : ['sales_count' => 0, 'views_count' => rand(200, 6000)]),
                ]
            );

            $this->attachThumbnail($product, $slug);
            $this->attachProductFile($product, $slug);

            $product->changelogs()->firstOrCreate(
                ['version' => $product->current_version],
                ['notes' => "Initial public release.\n- Core features implemented\n- Documentation included"]
            );

            $products[$slug] = $product;
        }

        return $products;
    }

    protected function attachThumbnail(Product $product, string $slug): void
    {
        if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail)) {
            return;
        }

        $source = "{$this->assetsPath}/thumbnails/{$slug}.jpg";

        if (! file_exists($source)) {
            return;
        }

        $path = "products/thumbnails/{$slug}.jpg";
        Storage::disk('public')->put($path, file_get_contents($source));
        $product->update(['thumbnail' => $path]);

        // A couple of duplicate gallery entries so the product page's
        // thumbnail strip has more than one image to click through.
        if (! $product->images()->exists()) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }
    }

    protected function attachProductFile(Product $product, string $slug): void
    {
        if ($product->files()->where('scan_status', 'clean')->exists()) {
            return;
        }

        // Not every demo product's file is a .zip anymore — PDFs and PPTX
        // files sit in the same assets folder under their real extension —
        // so find whichever one matches this slug instead of assuming .zip.
        $matches = glob("{$this->assetsPath}/files/{$slug}.*");
        $source = $matches[0] ?? null;

        if (! $source || ! file_exists($source)) {
            return;
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $bytes = file_get_contents($source);
        $path = "products/{$product->id}/files/{$slug}.{$extension}";

        Storage::disk('private')->put($path, $bytes);

        ProductFile::create([
            'product_id' => $product->id,
            'disk' => 'private',
            'path' => $path,
            'original_name' => "{$slug}.{$extension}",
            'size_bytes' => strlen($bytes),
            'version' => $product->current_version,
            'checksum' => hash('sha256', $bytes),
            // Seed data is trusted content — mark clean directly rather than
            // routing through the malware scan job.
            'scan_status' => 'clean',
        ]);
    }

    /**
     * Gives the demo buyer a real completed purchase of the flagship product,
     * so /library and the download button work the moment you log in —
     * without having to run through checkout first.
     */
    protected function seedSampleSale(User $buyer, array $products): void
    {
        $product = $products['laravel-saas-starter-kit'];

        if (OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $buyer->id))
            ->exists()) {
            return;
        }

        $order = Order::create([
            'buyer_id' => $buyer->id,
            'subtotal' => $product->regular_price,
            'discount_total' => 0,
            'grand_total' => $product->regular_price,
            'currency' => 'USD',
            'payment_method' => 'stripe',
            'status' => 'paid',
            'paid_at' => now()->subDays(10),
            'gateway_reference' => 'seed_demo_' . Str::random(10),
        ]);

        $rate = 30.0;
        $commission = round((float) $product->regular_price * ($rate / 100), 2);
        $earning = round((float) $product->regular_price - $commission, 2);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $product->seller_id,
            'license_type' => 'regular',
            'price' => $product->regular_price,
            'commission_rate' => $rate,
            'commission_amount' => $commission,
            'seller_earning' => $earning,
            'status' => 'completed',
        ]);

        License::create([
            'order_item_id' => $item->id,
            'buyer_id' => $buyer->id,
            'product_id' => $product->id,
            'type' => 'regular',
            'status' => 'active',
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'gateway_event_id' => 'seed_evt_' . Str::random(12),
            'type' => 'payment',
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'status' => 'succeeded',
        ]);

        $wallet = SellerWallet::firstOrCreate(
            ['seller_id' => $product->seller_id],
            ['available_balance' => 0, 'pending_balance' => 0]
        );
        // Seed data: credit straight to available so the seller demo login
        // shows a withdrawable balance immediately, rather than sitting in
        // the (correct, but less demo-friendly) pending holding period.
        $wallet->increment('available_balance', $earning);
        $wallet->increment('total_earned', $earning);
        $wallet->ledgerEntries()->create([
            'type' => 'sale_credit',
            'amount' => $earning,
            'reference_type' => OrderItem::class,
            'reference_id' => $item->id,
            'note' => 'Seed demo sale',
        ]);

        // This bypasses OrderFulfilmentService::fulfil() (it's raw seed
        // data, not a real checkout), so the counter it would normally bump
        // needs bumping here instead — this IS a real completed order, so
        // sales_count should honestly reflect it.
        $product->increment('sales_count');
    }

    protected function seedReviews(User $buyer, array $products): void
    {
        $sampleReviews = [
            'laravel-saas-starter-kit' => 5,
            'flutter-ecommerce-app' => 5,
            'minimal-blog-wordpress-theme' => 4,
            'saas-dashboard-ui-kit' => 5,
        ];

        // The buyer only actually purchased the SaaS starter kit above, so
        // only that one is tied to a real order_item — the rest are
        // illustrative reviews from a second reviewer account.
        $reviewer = User::updateOrCreate(
            ['email' => 'reviewer@marketplace.test'],
            ['name' => 'Sam Reviewer', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        foreach ($sampleReviews as $slug => $rating) {
            $product = $products[$slug];

            if ($slug === 'laravel-saas-starter-kit') {
                $orderItem = OrderItem::where('product_id', $product->id)
                    ->whereHas('order', fn ($q) => $q->where('buyer_id', $buyer->id))
                    ->first();
                $reviewerUser = $buyer;
            } else {
                // Create a minimal completed purchase for the second reviewer
                // so the review still satisfies the verified-buyer constraint.
                $order = Order::firstOrCreate(
                    ['buyer_id' => $reviewer->id, 'order_number' => 'SEED-' . strtoupper($slug)],
                    [
                        'subtotal' => $product->regular_price,
                        'grand_total' => $product->regular_price,
                        'currency' => 'USD',
                        'payment_method' => 'stripe',
                        'status' => 'paid',
                        'paid_at' => now()->subDays(rand(15, 60)),
                    ]
                );

                $orderItem = OrderItem::firstOrCreate(
                    ['order_id' => $order->id, 'product_id' => $product->id],
                    [
                        'seller_id' => $product->seller_id,
                        'license_type' => 'regular',
                        'price' => $product->regular_price,
                        'commission_rate' => 30,
                        'commission_amount' => round($product->regular_price * 0.3, 2),
                        'seller_earning' => round($product->regular_price * 0.7, 2),
                        'status' => 'completed',
                    ]
                );
                $reviewerUser = $reviewer;
            }

            if (! $orderItem || $orderItem->review()->exists()) {
                continue;
            }

            Review::create([
                'product_id' => $product->id,
                'user_id' => $reviewerUser->id,
                'order_item_id' => $orderItem->id,
                'rating' => $rating,
                'comment' => $this->reviewComment($rating),
            ]);

            $product->update([
                'average_rating' => round((float) $product->reviews()->avg('rating'), 2),
                'reviews_count' => $product->reviews()->count(),
            ]);
        }
    }

    protected function reviewComment(int $rating): string
    {
        return match (true) {
            $rating >= 5 => 'Excellent code quality and the documentation made setup painless. Highly recommended.',
            $rating === 4 => 'Solid product overall. A couple of minor rough edges but support was responsive.',
            default => 'Does what it says, though the documentation could be a bit more detailed.',
        };
    }

    protected function description(string $title, string $short): string
    {
        return <<<TEXT
        {$short}

        {$title} is a production-ready codebase you can deploy today. It ships with clean,
        documented source code, a straightforward installation process, and sensible defaults
        so you can focus on your product instead of boilerplate.

        WHAT IS INCLUDED
        - Complete, commented source code
        - Installation and configuration guide
        - Six months of support and free updates

        REQUIREMENTS
        Check the compatibility list in the sidebar before purchasing.

        SUPPORT
        Questions are answered within 24 hours on business days. Bug reports are prioritised
        and patched in the next release.
        TEXT;
    }
}
