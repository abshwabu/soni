<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Display the cart page or return json cart data.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $cartData = $this->cartService->toArray();

        if ($request->wantsJson()) {
            return response()->json($cartData);
        }

        return Inertia::render('Cart', [
            'cart' => $cartData,
        ]);
    }

    /**
     * Add a product variant to the cart.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);

        try {
            $cartData = $this->cartService->add((int) $validated['variant_id'], $quantity);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fragrance added to your shopping bag.',
                    'cart' => $cartData,
                    'cartCount' => $cartData['count'],
                ]);
            }

            return back()->with('success', 'Fragrance added to your shopping bag.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }
    }

    /**
     * Update quantity of a variant in the cart.
     */
    public function update(Request $request, int $variantId): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        try {
            $cartData = $this->cartService->update($variantId, (int) $validated['quantity']);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bag updated.',
                    'cart' => $cartData,
                    'cartCount' => $cartData['count'],
                ]);
            }

            return back()->with('success', 'Bag updated.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(Request $request, int $variantId): RedirectResponse|JsonResponse
    {
        $cartData = $this->cartService->remove($variantId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item removed from bag.',
                'cart' => $cartData,
                'cartCount' => $cartData['count'],
            ]);
        }

        return back()->with('success', 'Item removed from bag.');
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request): RedirectResponse|JsonResponse
    {
        $this->cartService->clear();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Shopping bag cleared.',
                'cartCount' => 0,
            ]);
        }

        return back()->with('success', 'Shopping bag cleared.');
    }
}
