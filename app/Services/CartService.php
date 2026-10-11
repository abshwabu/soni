<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartService
{
    protected const SESSION_KEY = 'cart';

    /**
     * Get the raw cart array from session.
     * Keyed by variant id.
     *
     * @return array<int, array>
     */
    public function getRaw(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Get detailed cart items with models and formatted attributes.
     */
    public function items(): array
    {
        $raw = $this->getRaw();
        if (empty($raw)) {
            return [];
        }

        $variantIds = array_keys($raw);
        $variants = ProductVariant::with(['product.media', 'product.brand'])
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        $items = [];
        $hasChanges = false;

        foreach ($raw as $variantId => $itemData) {
            $variant = $variants->get($variantId);

            if (!$variant || !$variant->is_active || !$variant->product?->is_active) {
                // Item no longer available
                unset($raw[$variantId]);
                $hasChanges = true;
                continue;
            }

            $requestedQty = (int) ($itemData['quantity'] ?? 1);
            $qty = max(1, $requestedQty);
            $isOutOfStock = $variant->stock < $qty;

            $lineTotal = $variant->price * $qty;

            $items[] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'product_slug' => $variant->product->slug,
                'brand_name' => $variant->product->brand?->name,
                'size_ml' => $variant->size_ml,
                'label' => $variant->label,
                'unit_price' => $variant->price,
                'formatted_unit_price' => Money::format($variant->price),
                'quantity' => $qty,
                'line_total' => $lineTotal,
                'formatted_line_total' => Money::format($lineTotal),
                'image' => $variant->product->thumbnail_url ?: '/images/placeholder.svg',
                'stock' => $variant->stock,
                'is_out_of_stock' => $variant->stock <= 0,
            ];
        }

        if ($hasChanges) {
            Session::put(self::SESSION_KEY, $raw);
        }

        return $items;
    }

    /**
     * Add a variant to the cart.
     */
    public function add(int $variantId, int $quantity = 1): array
    {
        $variant = ProductVariant::with(['product.brand', 'product.media'])->findOrFail($variantId);

        if (!$variant->is_active || !$variant->product?->is_active) {
            throw ValidationException::withMessages([
                'variant_id' => 'This fragrance is currently inactive or unavailable.',
            ]);
        }

        if ($variant->stock <= 0) {
            throw ValidationException::withMessages([
                'variant_id' => "Sorry, {$variant->product->name} ({$variant->label}) is currently out of stock.",
            ]);
        }

        $raw = $this->getRaw();
        $currentQty = isset($raw[$variantId]) ? (int) $raw[$variantId]['quantity'] : 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $variant->stock) {
            $availableToAdd = max(0, $variant->stock - $currentQty);
            if ($availableToAdd <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => "You already have all available units ({$variant->stock}) of this fragrance in your bag.",
                ]);
            }
            $newQty = $variant->stock;
        }

        $raw[$variantId] = [
            'variant_id' => $variant->id,
            'quantity' => $newQty,
        ];

        Session::put(self::SESSION_KEY, $raw);

        return $this->toArray();
    }

    /**
     * Update quantity of a variant in the cart.
     */
    public function update(int $variantId, int $quantity): array
    {
        if ($quantity <= 0) {
            return $this->remove($variantId);
        }

        $variant = ProductVariant::with('product')->findOrFail($variantId);

        if ($quantity > $variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$variant->stock} bottles of {$variant->product->name} ({$variant->label}) are available.",
            ]);
        }

        $raw = $this->getRaw();
        $raw[$variantId] = [
            'variant_id' => $variant->id,
            'quantity' => $quantity,
        ];

        Session::put(self::SESSION_KEY, $raw);

        return $this->toArray();
    }

    /**
     * Remove an item from the cart.
     */
    public function remove(int $variantId): array
    {
        $raw = $this->getRaw();
        if (isset($raw[$variantId])) {
            unset($raw[$variantId]);
            Session::put(self::SESSION_KEY, $raw);
        }

        return $this->toArray();
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Get total quantity of all items in cart.
     */
    public function count(): int
    {
        $raw = $this->getRaw();
        return (int) collect($raw)->sum('quantity');
    }

    /**
     * Calculate cart subtotal in whole integer ETB.
     */
    public function subtotal(): int
    {
        $items = $this->items();
        return (int) collect($items)->sum('line_total');
    }

    /**
     * Free delivery threshold in ETB.
     */
    public function freeDeliveryThreshold(): int
    {
        $val = Setting::where('key', 'free_delivery_threshold')->value('value');
        return $val !== null ? (int) $val : 3000;
    }

    /**
     * Base delivery fee for Addis Ababa in ETB.
     */
    public function deliveryFeeAddis(): int
    {
        $val = Setting::where('key', 'delivery_fee_addis')->value('value');
        return $val !== null ? (int) $val : 250;
    }

    /**
     * Delivery fee based on method and subtotal.
     */
    public function deliveryFee(?string $deliveryMethod = 'delivery'): int
    {
        if ($deliveryMethod === 'pickup') {
            return 0;
        }

        if ($this->subtotal() >= $this->freeDeliveryThreshold()) {
            return 0;
        }

        return $this->deliveryFeeAddis();
    }

    /**
     * Total amount in whole ETB.
     */
    public function total(?string $deliveryMethod = 'delivery'): int
    {
        return $this->subtotal() + $this->deliveryFee($deliveryMethod);
    }

    /**
     * Amount remaining until free delivery is unlocked.
     */
    public function amountNeededForFreeDelivery(): int
    {
        return max(0, $this->freeDeliveryThreshold() - $this->subtotal());
    }

    /**
     * Free delivery progress percentage (0 - 100).
     */
    public function freeDeliveryProgress(): int
    {
        $threshold = $this->freeDeliveryThreshold();
        if ($threshold <= 0) {
            return 100;
        }

        return (int) min(100, round(($this->subtotal() / $threshold) * 100));
    }

    /**
     * Validate all items in cart against current stock.
     * Returns list of any errors or out of stock warnings.
     */
    public function validateStock(): array
    {
        $items = $this->items();
        $errors = [];

        foreach ($items as $item) {
            if ($item['is_out_of_stock'] || $item['quantity'] <= 0) {
                $errors[] = "{$item['product_name']} ({$item['label']}) is currently out of stock.";
            } elseif ($item['quantity'] > $item['stock']) {
                $errors[] = "Only {$item['stock']} bottles of {$item['product_name']} ({$item['label']}) available.";
            }
        }

        return $errors;
    }

    /**
     * Return comprehensive cart data array for Vue / JSON serialization.
     */
    public function toArray(?string $deliveryMethod = 'delivery'): array
    {
        $items = $this->items();
        $subtotal = $this->subtotal();
        $deliveryFee = $this->deliveryFee($deliveryMethod);
        $total = $subtotal + $deliveryFee;
        $threshold = $this->freeDeliveryThreshold();

        return [
            'items' => $items,
            'count' => (int) collect($items)->sum('quantity'),
            'subtotal' => $subtotal,
            'formatted_subtotal' => Money::format($subtotal),
            'delivery_fee' => $deliveryFee,
            'formatted_delivery_fee' => $deliveryFee === 0 ? 'FREE' : Money::format($deliveryFee),
            'total' => $total,
            'formatted_total' => Money::format($total),
            'free_delivery' => [
                'threshold' => $threshold,
                'formatted_threshold' => Money::format($threshold),
                'amount_needed' => $this->amountNeededForFreeDelivery(),
                'formatted_amount_needed' => Money::format($this->amountNeededForFreeDelivery()),
                'progress' => $this->freeDeliveryProgress(),
                'is_eligible' => $subtotal >= $threshold,
            ],
        ];
    }
}
