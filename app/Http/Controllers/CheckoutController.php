<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Exceptions\OutOfStockException;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Display the single-page checkout.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $cartData = $this->cartService->toArray();

        // Redirect if cart is empty
        if (empty($cartData['items'])) {
            return redirect()->route('cart.index')->with('info', 'Your shopping bag is empty. Please select a fragrance to proceed.');
        }

        // Validate current stock
        $stockErrors = $this->cartService->validateStock();
        if (!empty($stockErrors)) {
            return redirect()->route('cart.index')->with('error', implode(' ', $stockErrors));
        }

        // Generate / store fresh idempotency token in session
        $idempotencyToken = Str::random(32);
        session()->put('checkout_idempotency_token', $idempotencyToken);

        return Inertia::render('Checkout', [
            'cart' => $cartData,
            'idempotencyToken' => $idempotencyToken,
            'deliveryFeeAddis' => $this->cartService->deliveryFeeAddis(),
            'freeDeliveryThreshold' => $this->cartService->freeDeliveryThreshold(),
            'seo' => [
                'title' => 'Guest Checkout | Soni Perfume Addis Ababa',
                'description' => 'Complete your perfume order. Pay in cash, Telebirr, or CBE upon delivery in Addis Ababa.',
            ],
        ]);
    }

    /**
     * Place the order atomically in a single transaction.
     */
    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        // 1. Spam Honeypot check
        if ($request->filled('website')) {
            Log::warning('Honeypot triggered during checkout from IP ' . $request->ip());
            return back()->with('error', 'Spam detected. Order rejected.');
        }

        // 2. Rate limiting (5 orders per hour per IP)
        $ip = $request->ip() ?: 'unknown';
        $rateKey = 'place-order:' . $ip;

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return back()->with('error', "Too many checkout attempts. Please wait {$seconds} seconds before placing another order.");
        }

        // 3. Idempotency double-submit check
        $submittedToken = $request->input('idempotency_token');
        if ($submittedToken && session()->has('processed_token_' . $submittedToken)) {
            $existingOrderNumber = session('processed_token_' . $submittedToken);
            return redirect()->route('order.thank-you', ['order_number' => $existingOrderNumber]);
        }

        // 4. Verify cart has items
        $cartItems = $this->cartService->items();
        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('error', 'Your shopping bag is empty.');
        }

        // 5. Execute transaction with retry on database lock (up to 3 times)
        try {
            $order = retry(3, function () use ($request, $cartItems) {
                return DB::transaction(function () use ($request, $cartItems) {
                    $deliveryMethod = $request->input('delivery_method', 'delivery');
                    $subtotal = $this->cartService->subtotal();
                    $deliveryFee = $this->cartService->deliveryFee($deliveryMethod);
                    $total = $subtotal + $deliveryFee;

                    // A. Atomically decrement stock for each variant
                    foreach ($cartItems as $item) {
                        $variantId = $item['variant_id'];
                        $qty = (int) $item['quantity'];

                        $affected = DB::update(
                            'UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?',
                            [$qty, $variantId, $qty]
                        );

                        if ($affected === 0) {
                            throw new OutOfStockException(
                                "Sorry, \"{$item['product_name']}\" ({$item['label']}) just went out of stock or does not have enough stock available."
                            );
                        }
                    }

                    // B. Compute order_number per-day sequence inside the transaction
                    $todayPrefix = 'SP-' . date('Ymd');
                    $latestToday = Order::where('order_number', 'LIKE', "{$todayPrefix}-%")
                        ->lockForUpdate()
                        ->latest('id')
                        ->first();

                    if ($latestToday && preg_match('/-(\d{4})$/', $latestToday->order_number, $matches)) {
                        $nextSequence = str_pad((int) $matches[1] + 1, 4, '0', STR_PAD_LEFT);
                    } else {
                        $nextSequence = '0001';
                    }
                    $orderNumber = "{$todayPrefix}-{$nextSequence}";

                    $address = $deliveryMethod === 'pickup'
                        ? 'Store Pickup (Bole, Addis Ababa)'
                        : (string) $request->input('address');

                    // C. Create Order
                    $order = Order::create([
                        'order_number' => $orderNumber,
                        'customer_name' => trim((string) $request->input('customer_name')),
                        'phone' => $request->normalizePhone('phone'),
                        'alt_phone' => $request->normalizePhone('alt_phone'),
                        'city' => trim((string) $request->input('city', 'Addis Ababa')),
                        'address' => $address,
                        'notes' => $request->input('notes'),
                        'delivery_method' => $deliveryMethod,
                        'subtotal' => $subtotal,
                        'delivery_fee' => $deliveryFee,
                        'total' => $total,
                        'status' => 'new',
                        'ip_address' => $request->ip(),
                    ]);

                    // D. Create snapshot OrderItems
                    foreach ($cartItems as $item) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'product_variant_id' => $item['variant_id'],
                            'product_name' => $item['product_name'],
                            'variant_label' => $item['label'],
                            'unit_price' => $item['unit_price'],
                            'quantity' => $item['quantity'],
                            'line_total' => $item['line_total'],
                        ]);
                    }

                    // E. Clear session cart
                    $this->cartService->clear();

                    // F. Dispatch OrderPlaced event with afterCommit
                    OrderPlaced::dispatch($order);

                    return $order;
                });
            }, 100);
        } catch (OutOfStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Order placement exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'We encountered an issue finalizing your order. Please try again.');
        }

        // Record rate limit hit only on success
        RateLimiter::hit($rateKey, 3600);

        // Mark idempotency token as processed
        if ($submittedToken) {
            session()->put('processed_token_' . $submittedToken, $order->order_number);
        }

        // Record placed order in session for thank-you page access authorization
        session()->put('placed_order_id', $order->id);
        session()->put('placed_order_number', $order->order_number);

        return redirect()->route('order.thank-you', ['order_number' => $order->order_number]);
    }

    /**
     * Display the order confirmation page.
     * Allowed only for the same session or via signed URL.
     */
    public function thankYou(Request $request, string $order_number): Response|RedirectResponse
    {
        $isAuthorizedSession = session('placed_order_number') === $order_number;
        $hasValidSignature = $request->hasValidSignature();

        if (!$isAuthorizedSession && !$hasValidSignature) {
            return redirect()->route('home')->with('error', 'Unauthorized access to order confirmation.');
        }

        $order = Order::where('order_number', $order_number)
            ->with(['items.product.media', 'items.productVariant'])
            ->firstOrFail();

        return Inertia::render('Order/ThankYou', [
            'order' => $order,
            'seo' => [
                'title' => "Order Confirmed #{$order->order_number} | Soni Perfume",
                'description' => "Thank you for your order with Soni Perfume. We will contact you shortly to confirm delivery in Addis Ababa.",
            ],
        ]);
    }
}
