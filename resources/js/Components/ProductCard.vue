<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Badge from './Badge.vue';
import AppImage from './AppImage.vue';
import Modal from './Modal.vue';
import Button from './Button.vue';
import { useToast } from '../Composables/useToast';
import { useAnalytics } from '../Composables/useAnalytics';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const { success, error } = useToast();
const { trackEvent } = useAnalytics();
const isAdding = ref(false);
const showQuickSelect = ref(false);
const selectedVariantId = ref(null);

const primaryImage = computed(() => {
    return props.product.primary_image_url
        || props.product.image
        || props.product.media?.[0]?.original_url
        || props.product.thumbnail_url
        || '/images/placeholder.svg';
});

const secondaryImage = computed(() => {
    return props.product.secondary_image_url
        || props.product.media?.[1]?.original_url
        || null;
});

const primaryWebp = computed(() => {
    return props.product.primary_webp_url
        || props.product.media?.[0]?.conversions?.webp
        || null;
});

const secondaryWebp = computed(() => {
    return props.product.secondary_webp_url
        || props.product.media?.[1]?.conversions?.webp
        || null;
});

const variants = computed(() => props.product.variants || []);
const hasMultipleVariants = computed(() => variants.value.length > 1);

const defaultVariant = computed(() => {
    if (!variants.value.length) return null;
    return variants.value.find((v) => v.is_active && v.stock > 0) || variants.value[0];
});

const isOutOfStock = computed(() => {
    if (props.product.in_stock !== undefined) return !props.product.in_stock;
    if (variants.value.length > 0) {
        return !variants.value.some((v) => v.is_active && v.stock > 0);
    }
    return false;
});

const hasDiscount = computed(() => {
    return defaultVariant.value?.compare_at_price && defaultVariant.value.compare_at_price > defaultVariant.value.price;
});

const formattedComparePrice = computed(() => {
    if (!hasDiscount.value) return null;
    return `ETB ${Number(defaultVariant.value.compare_at_price).toLocaleString()}`;
});

const displayPrice = computed(() => {
    if (props.product.formatted_price_from) {
        return hasMultipleVariants.value ? `From ${props.product.formatted_price_from}` : props.product.formatted_price_from;
    }
    if (defaultVariant.value) {
        const p = `ETB ${Number(defaultVariant.value.price).toLocaleString()}`;
        return hasMultipleVariants.value ? `From ${p}` : p;
    }
    return 'Price on request';
});

const handleQuickAdd = () => {
    if (isOutOfStock.value) return;

    if (hasMultipleVariants.value) {
        selectedVariantId.value = defaultVariant.value?.id;
        showQuickSelect.value = true;
        return;
    }

    if (defaultVariant.value) {
        executeAddToCart(defaultVariant.value.id);
    }
};

const executeAddToCart = (variantId) => {
    if (!variantId) return;
    isAdding.value = true;

    router.post(
        '/cart',
        { variant_id: variantId, quantity: 1 },
        {
            preserveScroll: true,
            onSuccess: () => {
                isAdding.value = false;
                showQuickSelect.value = false;
                success(`Added ${props.product.name} to your bag.`);
                trackEvent('add_to_cart', {
                    product_id: props.product.id,
                    product_name: props.product.name,
                    variant_id: variantId,
                });
                if (typeof window !== 'undefined') {
                    window.dispatchEvent(new CustomEvent('open-mini-cart'));
                }
            },
            onError: () => {
                isAdding.value = false;
                error('Could not add item to bag.');
            },
        },
    );
};
</script>

<template>
    <div class="group relative flex flex-col h-full bg-brand-surface rounded-sm border border-brand-border/60 hover:border-brand-gold/60 transition-all duration-300 shadow-sm hover:shadow-md">
        <!-- Image Container with Aspect Ratio and Hover Swap -->
        <div class="relative overflow-hidden bg-brand-surface-warm aspect-[3/4] w-full">
            <Link
                :href="`/product/${product.slug}`"
                class="block w-full h-full"
            >
                <!-- Primary Image -->
                <AppImage
                    :src="primaryImage"
                    :webp-src="primaryWebp"
                    :alt="product.name"
                    aspect-ratio="aspect-full h-full w-full"
                    class="absolute inset-0 transition-opacity duration-500 ease-out"
                    :class="[
                        secondaryImage ? 'group-hover:opacity-0' : 'group-hover:scale-105 duration-700',
                    ]"
                />

                <!-- Secondary Image for Hover Swap -->
                <AppImage
                    v-if="secondaryImage"
                    :src="secondaryImage"
                    :webp-src="secondaryWebp"
                    :alt="`${product.name} alternate view`"
                    aspect-ratio="aspect-full h-full w-full"
                    class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 ease-out group-hover:scale-105"
                />
            </Link>

            <!-- Badges Floating in Corner -->
            <div class="absolute top-2.5 left-2.5 z-10 flex flex-col gap-1 pointer-events-none">
                <Badge
                    v-if="isOutOfStock"
                    variant="dark"
                    size="xs"
                >
                    Sold Out
                </Badge>
                <Badge
                    v-else-if="product.is_new"
                    variant="gold"
                    size="xs"
                >
                    New
                </Badge>
                <Badge
                    v-if="product.is_bestseller && !isOutOfStock"
                    variant="dark"
                    size="xs"
                >
                    Bestseller
                </Badge>
                <Badge
                    v-if="hasDiscount && !isOutOfStock"
                    variant="blush"
                    size="xs"
                >
                    Special Offer
                </Badge>
            </div>

            <!-- Concentration Tag (Top Right) -->
            <div
                v-if="product.concentration"
                class="absolute top-2.5 right-2.5 z-10 pointer-events-none"
            >
                <span class="text-[10px] tracking-wider font-sans font-semibold text-brand-charcoal/80 bg-brand-bg/85 backdrop-blur-xs px-1.5 py-0.5 rounded-sm">
                    {{ typeof product.concentration === 'object' ? product.concentration.value : product.concentration }}
                </span>
            </div>

            <!-- Quick Add Overlay on Desktop Hover -->
            <div class="absolute inset-x-2 bottom-2 z-20 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0 hidden sm:block">
                <button
                    type="button"
                    :disabled="isOutOfStock || isAdding"
                    class="w-full py-2.5 px-3 bg-brand-charcoal/90 hover:bg-black text-brand-bg text-xs uppercase tracking-widest font-sans font-medium rounded-sm backdrop-blur-sm transition-colors duration-200 shadow-md flex items-center justify-center gap-2 disabled:bg-neutral-400 disabled:cursor-not-allowed"
                    @click.prevent="handleQuickAdd"
                >
                    <svg
                        v-if="!isAdding"
                        class="w-3.5 h-3.5 text-brand-gold"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15"
                        />
                    </svg>
                    <span>{{ isOutOfStock ? ($i18n?.locale === 'am' ? 'አልቋል' : 'Sold Out') : hasMultipleVariants ? ($i18n?.locale === 'am' ? 'መጠን ይምረጡ' : 'Select Size') : ($i18n?.locale === 'am' ? 'ወደ ቦርሳ ጨምር' : 'Quick Add') }}</span>
                </button>
            </div>
        </div>

        <!-- Product Details -->
        <div class="p-3.5 sm:p-4 flex flex-col flex-1 justify-between gap-2.5">
            <div>
                <!-- Brand Name -->
                <p
                    v-if="product.brand"
                    class="text-[11px] uppercase tracking-widest font-sans font-semibold text-brand-gold mb-1 truncate"
                >
                    {{ product.brand.name }}
                </p>

                <!-- Product Title -->
                <h3 class="font-serif text-base sm:text-lg font-normal text-brand-charcoal leading-snug line-clamp-1 group-hover:text-brand-gold transition-colors duration-150">
                    <Link :href="`/product/${product.slug}`">
                        {{ product.name }}
                    </Link>
                </h3>

                <!-- Short Olfactory Notes Preview -->
                <p
                    v-if="product.scent_family"
                    class="text-[11px] text-brand-muted font-sans font-light mt-0.5 capitalize truncate"
                >
                    {{ typeof product.scent_family === 'object' ? product.scent_family.value : product.scent_family }} Family
                </p>
            </div>

            <!-- Price & Mobile Action -->
            <div class="pt-2 border-t border-brand-border/40 flex items-center justify-between gap-2">
                <div class="flex items-baseline gap-2 truncate">
                    <span class="text-xs sm:text-sm font-semibold font-sans text-brand-charcoal tracking-wide">
                        {{ displayPrice }}
                    </span>
                    <span
                        v-if="formattedComparePrice"
                        class="text-[11px] text-brand-muted line-through font-sans"
                    >
                        {{ formattedComparePrice }}
                    </span>
                </div>

                <!-- Mobile Quick Add Icon Button -->
                <button
                    type="button"
                    :disabled="isOutOfStock || isAdding"
                    class="sm:hidden p-2 text-brand-charcoal hover:text-brand-gold active:bg-brand-surface-warm rounded-sm transition-colors border border-brand-border disabled:opacity-40"
                    aria-label="Add to bag"
                    @click.prevent="handleQuickAdd"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Variant Selector Modal for Multiple Sizes -->
        <Modal
            :show="showQuickSelect"
            :title="`Select Size — ${product.name}`"
            max-width="md"
            @close="showQuickSelect = false"
        >
            <div class="space-y-4">
                <p class="text-xs text-brand-muted uppercase tracking-wider font-sans">
                    Choose bottle size:
                </p>

                <div class="grid grid-cols-1 gap-2.5">
                    <button
                        v-for="variant in variants"
                        :key="variant.id"
                        type="button"
                        :disabled="variant.stock <= 0"
                        class="p-3 border text-left flex items-center justify-between rounded-sm transition-all focus:outline-none"
                        :class="[
                            selectedVariantId === variant.id
                                ? 'border-brand-gold bg-brand-gold/5'
                                : 'border-brand-border hover:border-brand-gold/60',
                            variant.stock <= 0 ? 'opacity-50 cursor-not-allowed bg-stone-100' : 'cursor-pointer',
                        ]"
                        @click="selectedVariantId = variant.id"
                    >
                        <div>
                            <span class="font-serif text-lg font-medium text-brand-charcoal">{{ variant.size_ml }}ml</span>
                            <span
                                v-if="variant.stock <= 5 && variant.stock > 0"
                                class="ml-2 text-[10px] text-amber-700 uppercase font-sans tracking-wider"
                            >
                                Only {{ variant.stock }} left
                            </span>
                            <span
                                v-else-if="variant.stock <= 0"
                                class="ml-2 text-[10px] text-rose-600 uppercase font-sans tracking-wider"
                            >
                                Out of stock
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="font-sans font-semibold text-sm text-brand-charcoal">
                                ETB {{ Number(variant.price).toLocaleString() }}
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            <template #footer>
                <Button
                    variant="ghost"
                    size="sm"
                    @click="showQuickSelect = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="primary"
                    size="sm"
                    :loading="isAdding"
                    :disabled="!selectedVariantId"
                    @click="executeAddToCart(selectedVariantId)"
                >
                    Add To Bag
                </Button>
            </template>
        </Modal>
    </div>
</template>
