<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';
import Button from '../Components/Button.vue';
import QuantityStepper from '../Components/QuantityStepper.vue';
import { useToast } from '../Composables/useToast';

const props = defineProps({
    cart: {
        type: Object,
        default: () => ({
            items: [],
            count: 0,
            subtotal: 0,
            formatted_subtotal: 'ETB 0',
            delivery_fee: 0,
            formatted_delivery_fee: 'FREE',
            total: 0,
            formatted_total: 'ETB 0',
            free_delivery: {
                threshold: 3000,
                formatted_threshold: 'ETB 3,000',
                amount_needed: 0,
                formatted_amount_needed: 'ETB 0',
                progress: 0,
                is_eligible: false,
            },
        }),
    },
});

const { success, error } = useToast();

const items = computed(() => props.cart.items || []);
const count = computed(() => props.cart.count || 0);
const subtotal = computed(() => props.cart.subtotal || 0);
const deliveryFee = computed(() => props.cart.delivery_fee || 0);
const total = computed(() => props.cart.total || 0);
const freeDelivery = computed(() => props.cart.free_delivery || {});
const isEmpty = computed(() => items.value.length === 0);

const updateQuantity = (variantId, quantity) => {
    router.patch(
        `/cart/${variantId}`,
        { quantity },
        {
            preserveScroll: true,
            onError: () => error('Could not update quantity.'),
        },
    );
};

const removeItem = (variantId) => {
    router.delete(
        `/cart/${variantId}`,
        {
            preserveScroll: true,
            onSuccess: () => success('Item removed from bag.'),
            onError: () => error('Could not remove item.'),
        },
    );
};

const clearCart = () => {
    router.delete('/cart', {
        preserveScroll: true,
        onSuccess: () => success('Shopping bag cleared.'),
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Shopping Bag | Soni Perfume" />

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
            <Breadcrumbs :items="[{ label: 'Home', href: '/' }, { label: 'Shopping Bag', href: null }]" />

            <div class="mt-4 mb-8 flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
                <div>
                    <h1 class="font-serif text-3xl sm:text-4xl md:text-5xl font-normal text-brand-charcoal">
                        Your Fragrance Bag
                    </h1>
                    <p class="text-xs sm:text-sm text-brand-muted font-sans font-light mt-1">
                        {{ isEmpty ? 'Your bag is currently empty' : `${count} item(s) in your bag` }}
                    </p>
                </div>

                <button
                    v-if="!isEmpty"
                    type="button"
                    class="text-xs text-brand-muted hover:text-red-600 transition-colors underline self-start sm:self-auto"
                    @click="clearCart"
                >
                    Clear All Items
                </button>
            </div>

            <!-- Empty State -->
            <div
                v-if="isEmpty"
                class="bg-brand-surface border border-brand-border rounded-sm p-12 text-center max-w-lg mx-auto my-8 space-y-5 shadow-xs"
            >
                <div class="w-16 h-16 rounded-full bg-brand-surface-warm mx-auto flex items-center justify-center text-brand-gold">
                    <svg
                        class="w-8 h-8"
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

                <div class="space-y-1">
                    <h3 class="font-serif text-2xl font-normal text-brand-charcoal">
                        Your bag awaits your signature scent
                    </h3>
                    <p class="text-xs text-brand-muted font-sans font-light">
                        Discover luxury oriental and designer fragrances curated for Addis Ababa.
                    </p>
                </div>

                <Button
                    href="/shop"
                    variant="primary"
                    size="md"
                >
                    Start Shopping
                </Button>
            </div>

            <!-- Filled Cart State -->
            <div
                v-else
                class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12"
            >
                <!-- Items Column (8 cols) -->
                <div class="lg:col-span-8 space-y-4">
                    <!-- Free Delivery Progress Bar Callout -->
                    <div class="bg-brand-surface-warm border border-brand-border/70 rounded-sm p-4">
                        <div class="flex items-center justify-between text-xs sm:text-sm font-sans mb-2">
                            <span
                                v-if="freeDelivery.is_eligible"
                                class="text-emerald-800 font-semibold flex items-center gap-1.5"
                            >
                                <span>🎉</span>
                                <span>Congratulations! You've unlocked <strong>FREE delivery</strong> in Addis Ababa.</span>
                            </span>
                            <span
                                v-else
                                class="text-brand-charcoal/85"
                            >
                                Add <strong class="text-brand-charcoal">{{ freeDelivery.formatted_amount_needed }}</strong> more to unlock <strong class="text-brand-gold">FREE delivery</strong>
                            </span>
                            <span class="text-xs font-bold text-brand-charcoal">{{ freeDelivery.progress }}%</span>
                        </div>

                        <div class="w-full h-2 bg-brand-border/60 rounded-full overflow-hidden">
                            <div
                                class="h-full transition-all duration-300"
                                :class="freeDelivery.is_eligible ? 'bg-emerald-600' : 'bg-brand-gold'"
                                :style="{ width: `${freeDelivery.progress}%` }"
                            />
                        </div>
                    </div>

                    <!-- Items List -->
                    <div
                        v-for="item in items"
                        :key="item.variant_id"
                        class="bg-brand-surface border border-brand-border/70 p-4 sm:p-5 rounded-sm flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 shadow-xs"
                    >
                        <!-- Thumbnail -->
                        <div class="w-20 h-24 sm:w-24 sm:h-28 bg-white shrink-0 rounded-sm overflow-hidden border border-brand-border/60 p-1 flex items-center justify-center">
                            <img
                                :src="item.image || '/images/placeholder.svg'"
                                :alt="item.product_name"
                                class="max-w-full max-h-full object-contain"
                                loading="lazy"
                            >
                        </div>

                        <!-- Item details -->
                        <div class="flex-1 min-w-0 space-y-1">
                            <p
                                v-if="item.brand_name"
                                class="text-[11px] uppercase tracking-widest font-semibold text-brand-gold"
                            >
                                {{ item.brand_name }}
                            </p>
                            <h3 class="font-serif text-lg sm:text-xl font-normal text-brand-charcoal leading-snug truncate">
                                <Link
                                    :href="`/product/${item.product_slug}`"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ item.product_name }}
                                </Link>
                            </h3>
                            <p class="text-xs text-brand-muted font-sans">
                                Bottle Size: <strong>{{ item.label }}</strong>
                            </p>
                            <p class="text-xs text-brand-charcoal/80 font-sans">
                                Unit: <strong>{{ item.formatted_unit_price }}</strong>
                            </p>
                            <p class="text-xs font-semibold font-sans text-brand-charcoal sm:hidden pt-1">
                                Total: {{ item.formatted_line_total }}
                            </p>
                        </div>

                        <!-- Stepper & Controls -->
                        <div class="flex items-center justify-between w-full sm:w-auto gap-4 pt-2 sm:pt-0 border-t sm:border-0 border-brand-border/60">
                            <QuantityStepper
                                :model-value="item.quantity"
                                :min="1"
                                :max="item.stock || 20"
                                size="sm"
                                @change="updateQuantity(item.variant_id, $event)"
                            />

                            <!-- Line total (desktop) -->
                            <div class="hidden sm:block text-right min-w-[110px]">
                                <span class="font-serif font-bold text-base text-brand-charcoal">
                                    {{ item.formatted_line_total }}
                                </span>
                            </div>

                            <!-- Remove button -->
                            <button
                                type="button"
                                class="p-1.5 text-brand-muted hover:text-red-600 transition-colors"
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
                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Column (4 cols) -->
                <div class="lg:col-span-4">
                    <div class="bg-brand-surface border border-brand-border rounded-sm p-6 space-y-6 sticky top-28 shadow-xs">
                        <h2 class="font-serif text-2xl font-normal text-brand-charcoal border-b border-brand-border pb-3">
                            Bag Summary
                        </h2>

                        <div class="space-y-3 text-xs sm:text-sm font-sans">
                            <div class="flex justify-between text-brand-muted">
                                <span>Subtotal</span>
                                <span class="font-serif font-bold text-base text-brand-charcoal">{{ props.cart.formatted_subtotal }}</span>
                            </div>

                            <div class="flex justify-between text-brand-muted">
                                <span>Addis Ababa Delivery</span>
                                <span
                                    class="font-medium"
                                    :class="deliveryFee === 0 ? 'text-emerald-700 font-bold' : 'text-brand-charcoal'"
                                >
                                    {{ props.cart.formatted_delivery_fee }}
                                </span>
                            </div>

                            <div class="pt-3 border-t border-brand-border flex justify-between font-semibold text-base text-brand-charcoal">
                                <span>Estimated Total</span>
                                <span class="font-serif text-xl text-brand-gold font-bold">{{ props.cart.formatted_total }}</span>
                            </div>
                        </div>

                        <div class="space-y-2.5 pt-2">
                            <Button
                                href="/checkout"
                                variant="primary"
                                size="lg"
                                block
                            >
                                Proceed To Checkout
                            </Button>

                            <Button
                                href="/shop"
                                variant="ghost"
                                size="sm"
                                block
                            >
                                Continue Browsing
                            </Button>
                        </div>

                        <!-- Value guarantee notes -->
                        <div class="text-[11px] text-brand-muted font-sans space-y-2 pt-2 border-t border-brand-border/60">
                            <p class="flex items-center gap-1.5">
                                <span class="text-brand-gold">✓</span>
                                <span>No advance card needed — payment upon arrival.</span>
                            </p>
                            <p class="flex items-center gap-1.5">
                                <span class="text-brand-gold">✓</span>
                                <span>We accept Cash, Telebirr, and CBE Mobile transfer.</span>
                            </p>
                            <p class="flex items-center gap-1.5">
                                <span class="text-brand-gold">✓</span>
                                <span>Orders processed and confirmed via telephone.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
