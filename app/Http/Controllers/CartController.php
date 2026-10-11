<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    /**
     * Display the cart page or return json cart data.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $cart = session()->get('cart', []);
        $items = array_values($cart);
        $subtotal = collect($items)->sum(fn ($item) => $item['price'] * $item['quantity']);
        $count = collect($items)->sum('quantity');

        if ($request->wantsJson()) {
            return response()->json([
                'items' => $items,
                'subtotal' => $subtotal,
                'count' => $count,
            ]);
        }

        return Inertia::render('Cart', [
            'cart' => [
                'items' => $items,
                'subtotal' => $subtotal,
                'count' => $count,
            ],
        ]);
    }

    /**
     * Add a product variant to the cart.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);
        $variant = ProductVariant::with(['product.media', 'product.brand'])->findOrFail($validated['variant_id']);

        if (!$variant->is_active || !$variant->product->is_active) {
            return back()->with('error', 'This item is currently unavailable.');
        }

        $cart = session()->get('cart', []);
        $key = (string) $variant->id;

        if (isset($cart[$key])) {
            $newQuantity = min($variant->stock, $cart[$key]['quantity'] + $quantity);
            $cart[$key]['quantity'] = $newQuantity;
        } else {
            $cart[$key] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'product_slug' => $variant->product->slug,
                'brand_name' => $variant->product->brand?->name,
                'size_ml' => $variant->size_ml,
                'price' => $variant->price,
                'formatted_price' => $variant->formatted_price,
                'quantity' => min($variant->stock, $quantity),
                'image' => $variant->product->thumbnail_url ?: '/images/placeholder.svg',
                'stock' => $variant->stock,
            ];
        }

        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Added {$variant->product->name} ({$variant->size_ml}ml) to your bag.",
                'cartCount' => collect($cart)->sum('quantity'),
                'cart' => array_values($cart),
            ]);
        }

        return back()->with('success', "Added {$variant->product->name} ({$variant->size_ml}ml) to your bag.");
    }

    /**
     * Update quantity of a variant in the cart.
     */
    public function update(Request $request, int $variantId): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $cart = session()->get('cart', []);
        $key = (string) $variantId;

        if (isset($cart[$key])) {
            $variant = ProductVariant::find($variantId);
            $maxStock = $variant ? $variant->stock : 10;
            $cart[$key]['quantity'] = min($maxStock, $validated['quantity']);
            session()->put('cart', $cart);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cartCount' => collect($cart)->sum('quantity'),
                'cart' => array_values($cart),
            ]);
        }

        return back()->with('success', 'Bag updated.');
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(Request $request, int $variantId): RedirectResponse|JsonResponse
    {
        $cart = session()->get('cart', []);
        $key = (string) $variantId;

        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('cart', $cart);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item removed from your bag.',
                'cartCount' => collect($cart)->sum('quantity'),
                'cart' => array_values($cart),
            ]);
        }

        return back()->with('success', 'Item removed from your bag.');
    }
}
