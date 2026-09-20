<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderFulfilmentService;
use App\Services\Payments\PayPalGateway;
use App\Services\Payments\StripeGateway;
use App\Services\Payments\TestGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cart,
        protected OrderFulfilmentService $fulfilment
    ) {
    }

    public function index()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return view('checkout.index', [
            'items' => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
            'discount' => $this->cart->discount(),
            'total' => $this->cart->total(),
            'coupon' => $this->cart->coupon(),
            'driver' => config('marketplace.payment_driver'),
        ]);
    }

    /**
     * Creates a PENDING order and hands off to the gateway.
     * No items, licenses or wallet credits are created here — that only
     * happens once payment is confirmed, inside OrderFulfilmentService.
     */
    public function process(Request $request)
    {
        $request->validate([
            'payment_method' => ['required', 'in:stripe,paypal,cbe,telebirr,usdt_manual,test'],
        ]);

        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $method = $request->string('payment_method')->toString();

        $order = Order::create([
            'buyer_id' => auth()->id(),
            'subtotal' => $this->cart->subtotal(),
            'discount_total' => $this->cart->discount(),
            'grand_total' => $this->cart->total(),
            'currency' => config('marketplace.currency', 'USD'),
            'coupon_id' => $this->cart->coupon()?->id,
            // 'test' is stored as stripe so the enum stays valid in production data
            'payment_method' => $method === 'test' ? 'stripe' : $method,
            'status' => 'pending',
        ]);

        // Snapshot the cart onto the ORDER (not the session) so fulfilment,
        // which runs later from a webhook with no session, knows what was bought.
        $order->update([
            'cart_snapshot' => $this->cart->items()->map(fn ($row) => [
                'product_id' => $row['product']->id,
                'license_type' => $row['license_type'],
                'price' => $row['price'],
            ])->all(),
        ]);

        $manualMethods = ['cbe', 'telebirr', 'usdt_manual'];

        if (in_array($method, $manualMethods, true)) {
            // No external redirect — the buyer pays outside the system (bank
            // transfer, mobile money, or a wallet-to-wallet crypto send) and
            // comes back here to upload proof. The order stays 'pending'
            // until an admin reviews and approves it.
            return redirect()->route('checkout.manual.instructions', $order);
        }

        $gateway = match ($method) {
            'stripe' => new StripeGateway(),
            'paypal' => new PayPalGateway(),
            'test' => new TestGateway(),
            default => new TestGateway(),
        };

        $redirectUrl = $gateway->createCheckout($order);

        return redirect()->away($redirectUrl);
    }

    /**
     * Shows bank/mobile-money/wallet details for CBE, Telebirr or manual
     * USDT, plus a form to upload proof of payment. Instructions are pulled
     * from PlatformSetting so they can be edited without a code change.
     */
    public function manualInstructions(Order $order)
    {
        abort_unless($order->buyer_id === auth()->id(), 403);
        abort_unless(in_array($order->payment_method, ['cbe', 'telebirr', 'usdt_manual'], true), 404);

        if ($order->status === 'paid') {
            return redirect()->route('checkout.success', $order);
        }

        $existingProof = $order->manualPaymentProof;

        return view('checkout.manual-instructions', [
            'order' => $order,
            'existingProof' => $existingProof,
            'instructions' => $this->manualInstructionsFor($order->payment_method),
            'supportContact' => \App\Models\PlatformSetting::get('payment_support_contact', ''),
        ]);
    }

    /**
     * Buyer uploads a screenshot/receipt and a reference note (sender name,
     * transaction hash, transfer reference). This does NOT fulfil the order —
     * it only queues it for admin review in Admin\ManualPaymentController.
     */
    public function submitManualProof(Request $request, Order $order)
    {
        abort_unless($order->buyer_id === auth()->id(), 403);
        abort_unless(in_array($order->payment_method, ['cbe', 'telebirr', 'usdt_manual'], true), 404);

        if ($order->status === 'paid') {
            return redirect()->route('checkout.success', $order);
        }

        $data = $request->validate([
            'proof' => ['required', 'image', 'max:4096'],
            'reference_note' => ['required', 'string', 'max:255'],
        ], [
            'reference_note.required' => $order->payment_method === 'usdt_manual'
                ? 'Enter the transaction hash (TxID) of your transfer.'
                : 'Enter the sender name or transfer reference shown on your receipt.',
        ]);

        $path = $request->file('proof')->store("manual-payments/{$order->id}", 'private');

        \App\Models\ManualPaymentProof::updateOrCreate(
            ['order_id' => $order->id],
            [
                'method' => $order->payment_method,
                'proof_path' => $path,
                'reference_note' => $data['reference_note'],
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'admin_note' => null,
            ]
        );

        $this->cart->clear();

        return redirect()->route('checkout.manual.pending', $order)
            ->with('success', 'Proof submitted. We will review it and unlock your download shortly.');
    }

    public function manualPending(Order $order)
    {
        abort_unless($order->buyer_id === auth()->id(), 403);

        if ($order->status === 'paid') {
            return redirect()->route('checkout.success', $order);
        }

        return view('checkout.manual-pending', compact('order'));
    }

    protected function manualInstructionsFor(string $method): array
    {
        return match ($method) {
            'cbe' => [
                'title' => 'Pay via Commercial Bank of Ethiopia (CBE)',
                'lines' => [
                    'Account holder' => \App\Models\PlatformSetting::get('cbe_account_holder', 'Not configured yet'),
                    'Account number' => \App\Models\PlatformSetting::get('cbe_account_number', 'Not configured yet'),
                    'Branch' => \App\Models\PlatformSetting::get('cbe_branch', ''),
                ],
                'note' => \App\Models\PlatformSetting::get('cbe_note', ''),
            ],
            'telebirr' => [
                'title' => 'Pay via Telebirr',
                'lines' => [
                    'Account holder' => \App\Models\PlatformSetting::get('telebirr_account_holder', 'Not configured yet'),
                    'Telebirr number' => \App\Models\PlatformSetting::get('telebirr_number', 'Not configured yet'),
                ],
                'note' => \App\Models\PlatformSetting::get('telebirr_note', ''),
            ],
            'usdt_manual' => [
                'title' => 'Pay via USDT (TRC20)',
                'lines' => [
                    'Account holder' => \App\Models\PlatformSetting::get('usdt_account_holder', 'Not configured yet'),
                    'Wallet address' => \App\Models\PlatformSetting::get('usdt_wallet_address', 'Not configured yet'),
                    'Currency' => 'USDT',
                    'Network' => 'TRC20 (Tron) only — sending on any other network will lose the funds',
                ],
                'note' => \App\Models\PlatformSetting::get('usdt_note', ''),
            ],
            default => ['title' => '', 'lines' => [], 'note' => ''],
        };
    }

    /**
     * TEST DRIVER ONLY. Simulates a successful gateway callback so the whole
     * fulfilment path (commission split, licenses, wallet credits) can be
     * exercised without live payment credentials.
     */
    public function completeTest(Order $order)
    {
        abort_unless(config('marketplace.payment_driver') === 'test', 403);
        abort_unless($order->buyer_id === auth()->id(), 403);

        $snapshot = $order->cart_snapshot ?? [];
        abort_if(empty($snapshot), 400, 'Order snapshot missing.');

        $items = collect($snapshot)->map(fn ($row) => [
            'product' => \App\Models\Product::findOrFail($row['product_id']),
            'license_type' => $row['license_type'],
            'price' => $row['price'],
        ])->all();

        $this->fulfilment->fulfil(
            $order,
            $items,
            'test_' . Str::uuid(),
            ['driver' => 'test']
        );

        $this->cart->clear();

        return redirect()->route('checkout.success', $order);
    }

    /**
     * The buyer lands here after approving payment on PayPal's site.
     * Captures the payment synchronously and fulfils the order immediately
     * so the buyer sees their license without waiting on a webhook. The
     * webhook handler re-processes the same event id as a no-op backstop.
     */
    public function capturePaypal(Order $order)
    {
        abort_unless($order->buyer_id === auth()->id(), 403);

        if ($order->status === 'paid') {
            return redirect()->route('checkout.success', $order);
        }

        $gateway = new PayPalGateway();
        $result = $gateway->capture($order);

        if (! $result['success']) {
            return redirect()->route('checkout.index')
                ->with('error', 'PayPal could not complete this payment. Please try again.');
        }

        $snapshot = $order->cart_snapshot ?? [];
        $items = collect($snapshot)->map(fn ($row) => [
            'product' => \App\Models\Product::find($row['product_id']),
            'license_type' => $row['license_type'],
            'price' => $row['price'],
        ])->filter(fn ($row) => $row['product'] !== null)->all();

        $this->fulfilment->fulfil(
            $order,
            $items,
            'paypal_' . $result['capture_id'],
            $result['raw']
        );

        $this->cart->clear();

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order)
    {
        abort_unless($order->buyer_id === auth()->id(), 403);

        $order->load('items.product', 'items.license');

        return view('checkout.success', compact('order'));
    }
}
