<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Drawer from './Drawer.vue';
import Button from './Button.vue';
import { useToast } from '../Composables/useToast';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const { success, error } = useToast();

const miniCart = computed(() => page.props.miniCart || { items: [], count: 0, subtotal: 0, free_delivery: {} });
const items = computed(() => miniCart.value.items || []);
const count = computed(() => miniCart.value.count || 0);
const subtotal = computed(() => miniCart.value.subtotal || 0);
const freeDelivery = computed(() => miniCart.value.free_delivery || {});

const updateQuantity = (variantId, newQuantity) => {
    router.patch(`/cart/${variantId}`, { quantity: newQuantity }, {
        preserveScroll: true,
        onError: () => error('Could not update quantity.'),
    });
};

const removeItem = (variantId) => {
    router.delete(`/cart/${variantId}`, {
        preserveScroll: true,
        onSuccess: () => success('Item removed from bag.'),
        onError: () => error('Could not remove item.'),
    });
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Drawer
        :show="show"
        :title="`Shopping Bag (${count})`"
        position="right"
        max-width="md"
        @close="handleClose"
    >
        <div class="flex flex-col h-full">
            <!-- Free Delivery Progress Bar -->
            <div
                v-if="items.length > 0"
                class="bg-brand-surface-warm p-4 border-b border-brand-border/60 shrink-0"
            >
                <div class="flex items-center justify-between text-xs mb-1.5 font-sans">
                    <span
                        v-if="freeDelivery.is_eligible"
                        class="text-emerald-800 font-semibold flex items-center gap-1.5"
                    >
                        <span>✨</span>
                        <span>You unlocked <strong>FREE delivery</strong> across Addis Ababa!</span>
                    </span>
                    <span
                        v-else
                        class="text-brand-charcoal/80"
                    >
                        Add <strong class="text-brand-charcoal">{{ freeDelivery.formatted_amount_needed }}</strong> more for <strong class="text-brand-gold">FREE delivery</strong>
                    </span>
                    <span class="text-[11px] font-bold text-brand-charcoal">{{ freeDelivery.progress }}%</span>
                </div>
                <div class="w-full h-1.5 bg-brand-border/60 rounded-full overflow-hidden">
                    <div
                        class="h-full transition-all duration-300"
                        :class="freeDelivery.is_eligible ? 'bg-emerald-600' : 'bg-brand-gold'"
                        :style="{ width: `${freeDelivery.progress}%` }"
                    />
                </div>
            </div>

            <!-- Cart Items List (Scrollable) -->
            <div
                v-if="items.length > 0"
                class="flex-1 overflow-y-auto p-4 space-y-4"
            >
                <div
                    v-for="item in items"
                    :key="item.variant_id"
                    class="flex items-start gap-3.5 pb-4 border-b border-brand-border/40 last:border-0"
                >
                    <!-- Product Image -->
                    <Link
                        :href="`/product/${item.product_slug}`"
                        class="w-16 h-20 bg-white rounded-sm shrink-0 border border-brand-border/60 overflow-hidden p-1 flex items-center justify-center hover:opacity-90 transition-opacity"
                        @click="handleClose"
                    >
                        <img
                            :src="item.image || '/images/placeholder.svg'"
                            :alt="item.product_name"
                            class="max-w-full max-h-full object-contain"
                            loading="lazy"
                        >
                    </Link>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] text-brand-gold font-medium uppercase tracking-wider truncate">
                            {{ item.brand_name }}
                        </p>
                        <h4 class="font-serif text-sm font-medium text-brand-charcoal leading-snug truncate">
                            <Link
                                :href="`/product/${item.product_slug}`"
                                class="hover:text-brand-gold transition-colors"
                                @click="handleClose"
                            >
                                {{ item.product_name }}
                            </Link>
                        </h4>
                        <p class="text-[11px] text-brand-muted mt-0.5">
                            Size: {{ item.label }}
                        </p>

                        <!-- Quantity & Price Row -->
                        <div class="flex items-center justify-between mt-2.5">
                            <!-- Mini Stepper -->
                            <div class="inline-flex items-center border border-brand-border rounded-sm bg-brand-surface">
                                <button
                                    type="button"
                                    class="w-6 h-6 flex items-center justify-center text-xs text-brand-charcoal hover:bg-brand-bg transition-colors disabled:opacity-30"
                                    :disabled="item.quantity <= 1"
                                    aria-label="Decrease quantity"
                                    @click="updateQuantity(item.variant_id, item.quantity - 1)"
                                >
                                    –
                                </button>
                                <span class="w-7 text-center text-xs font-semibold text-brand-charcoal font-mono">
                                    {{ item.quantity }}
                                </span>
                                <button
                                    type="button"
                                    class="w-6 h-6 flex items-center justify-center text-xs text-brand-charcoal hover:bg-brand-bg transition-colors disabled:opacity-30"
                                    :disabled="item.quantity >= item.stock"
                                    aria-label="Increase quantity"
                                    @click="updateQuantity(item.variant_id, item.quantity + 1)"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Line Price -->
                            <div class="text-right">
                                <span class="font-serif font-bold text-sm text-brand-charcoal">
                                    {{ item.formatted_line_total }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Remove Item -->
                    <button
                        type="button"
                        class="text-brand-muted hover:text-red-600 transition-colors p-1"
                        aria-label="Remove item"
                        @click="removeItem(item.variant_id)"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex-1 flex flex-col items-center justify-center p-8 text-center"
            >
                <div class="w-14 h-14 rounded-full bg-brand-surface-warm flex items-center justify-center text-brand-gold mb-3">
                    <svg
                        class="w-7 h-7"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.3"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                        />
                    </svg>
                </div>
                <h3 class="font-serif text-lg font-medium text-brand-charcoal mb-1">
                    Your bag is empty
                </h3>
                <p class="text-xs text-brand-muted max-w-xs mb-5">
                    Explore our curated collection of authentic luxury fragrances.
                </p>
                <Button
                    href="/shop"
                    variant="primary"
                    size="sm"
                    @click="handleClose"
                >
                    Start Shopping
                </Button>
            </div>

            <!-- Bottom Checkout Actions -->
            <div
                v-if="items.length > 0"
                class="p-4 bg-brand-surface border-t border-brand-border/80 shrink-0 space-y-3 shadow-lg"
            >
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between text-brand-charcoal/80">
                        <span>Subtotal:</span>
                        <span class="font-serif font-bold text-sm text-brand-charcoal">{{ miniCart.formatted_subtotal }}</span>
                    </div>
                    <div class="flex justify-between text-brand-muted">
                        <span>Addis Delivery:</span>
                        <span :class="miniCart.delivery_fee === 0 ? 'text-emerald-700 font-semibold' : ''">
                            {{ miniCart.formatted_delivery_fee }}
                        </span>
                    </div>
                    <div class="pt-2 border-t border-brand-border/60 flex justify-between text-sm font-semibold text-brand-charcoal">
                        <span>Estimated Total:</span>
                        <span class="font-serif text-base text-brand-gold font-bold">{{ miniCart.formatted_total }}</span>
                    </div>
                </div>

                <div class="space-y-2 pt-1">
                    <Link
                        href="/checkout"
                        class="w-full py-3 px-4 rounded-sm bg-brand-charcoal text-white hover:bg-brand-charcoal/90 text-xs font-semibold uppercase tracking-widest text-center block transition-colors"
                        @click="handleClose"
                    >
                        Checkout • Pay on Delivery
                    </Link>

                    <Link
                        href="/cart"
                        class="w-full py-2 px-4 rounded-sm border border-brand-border bg-brand-surface hover:border-brand-gold text-xs font-medium uppercase tracking-wider text-brand-charcoal text-center block transition-colors"
                        @click="handleClose"
                    >
                        View Full Bag
                    </Link>
                </div>

                <p class="text-[10px] text-center text-brand-muted">
                    No advance payment required. Cash / Telebirr upon arrival.
                </p>
            </div>
        </div>
    </Drawer>
</template>
