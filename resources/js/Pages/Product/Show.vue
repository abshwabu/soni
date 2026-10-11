<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Breadcrumbs from '../../Components/Breadcrumbs.vue';
import ProductCard from '../../Components/ProductCard.vue';
import QuantityStepper from '../../Components/QuantityStepper.vue';
import Rating from '../../Components/Rating.vue';
import Badge from '../../Components/Badge.vue';
import Button from '../../Components/Button.vue';
import { useToast } from '../../Composables/useToast';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    images: {
        type: Array,
        default: () => [],
    },
    relatedProducts: {
        type: Array,
        default: () => [],
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const { success, error } = useToast();

const settings = computed(() => page.props.settings || {});

// Gallery state
const activeImageIndex = ref(0);
const isHoverZooming = ref(false);
const zoomCoords = ref({ x: 50, y: 50 });

// Active variant selection
const variants = computed(() => props.product.variants || []);
const selectedVariantId = ref(
    variants.value.find((v) => v.is_active && v.stock > 0)?.id || variants.value[0]?.id || null
);

const selectedVariant = computed(() => {
    return variants.value.find((v) => v.id === selectedVariantId.value) || variants.value[0] || null;
});

// Quantity state
const quantity = ref(1);
const isAddingToCart = ref(false);

// Active Tab state
const activeTab = ref('description'); // 'description', 'notes', 'delivery'

// Mobile touch swipe for gallery
let touchStartX = 0;
let touchEndX = 0;

const handleTouchStart = (e) => {
    touchStartX = e.changedTouches[0].screenX;
};

const handleTouchEnd = (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
};

const handleSwipe = () => {
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > 40) {
        if (diff > 0 && activeImageIndex.value < props.images.length - 1) {
            activeImageIndex.value++;
        } else if (diff < 0 && activeImageIndex.value > 0) {
            activeImageIndex.value--;
        }
    }
};

// Hover Zoom on main image
const handleMouseMove = (e) => {
    const rect = e.currentTarget.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    zoomCoords.value = { x, y };
};

// Stock status computation
const stock = computed(() => selectedVariant.value?.stock ?? 0);
const isOutOfStock = computed(() => !selectedVariant.value || !selectedVariant.value.is_active || stock.value <= 0);
const isLowStock = computed(() => !isOutOfStock.value && stock.value < 5);

// Pricing computations in whole ETB
const currentPrice = computed(() => selectedVariant.value?.price ?? props.product.price_from ?? 0);
const comparePrice = computed(() => selectedVariant.value?.compare_at_price ?? null);
const hasDiscount = computed(() => comparePrice.value && comparePrice.value > currentPrice.value);
const discountPercent = computed(() => {
    if (!hasDiscount.value) return 0;
    return Math.round(((comparePrice.value - currentPrice.value) / comparePrice.value) * 100);
});

// Add to Cart
const addToCart = () => {
    if (!selectedVariant.value || isOutOfStock.value || isAddingToCart.value) return;

    isAddingToCart.value = true;
    router.post('/cart', {
        variant_id: selectedVariant.value.id,
        quantity: quantity.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isAddingToCart.value = false;
            success(`Added ${quantity.value} × ${props.product.name} (${selectedVariant.value.size_ml}ml) to cart!`);
        },
        onError: () => {
            isAddingToCart.value = false;
            error('Could not add product to cart. Please try again.');
        },
    });
};

// WhatsApp Order URL with prefilled details
const whatsappOrderUrl = computed(() => {
    const raw = settings.value.store_whatsapp || settings.value.store_phone || '+251911234567';
    let clean = raw.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '251' + clean.slice(1);
    }
    const size = selectedVariant.value ? `${selectedVariant.value.size_ml}ml` : '';
    const priceText = `ETB ${Number(currentPrice.value).toLocaleString()}`;
    const url = typeof window !== 'undefined' ? window.location.href : '';

    const text = `Hello Soni Perfume! I would like to order:
• Fragrance: ${props.product.name}
• Size: ${size}
• Price: ${priceText}
• Quantity: ${quantity.value}
• Link: ${url}`;

    return `https://wa.me/${clean}?text=${encodeURIComponent(text)}`;
});

// Telegram Order URL
const telegramOrderUrl = computed(() => {
    const base = settings.value.telegram_url || 'https://t.me/soniperfume_admin';
    const size = selectedVariant.value ? `${selectedVariant.value.size_ml}ml` : '';
    const text = `Hello! I would like to order ${props.product.name} (${size}) for ETB ${Number(currentPrice.value).toLocaleString()}`;
    return `${base}?text=${encodeURIComponent(text)}`;
});

// Breadcrumbs
const breadcrumbs = computed(() => [
    { label: 'Home', href: '/' },
    { label: 'Shop', href: '/shop' },
    ...(props.product.category ? [{ label: props.product.category.name, href: `/category/${props.product.category.slug}` }] : []),
    { label: props.product.name, href: null },
]);

// Scent Pyramid Notes Arrays
const topNotesList = computed(() => {
    if (!props.product.top_notes) return [];
    return props.product.top_notes.split(',').map((n) => n.trim()).filter(Boolean);
});
const heartNotesList = computed(() => {
    if (!props.product.heart_notes) return [];
    return props.product.heart_notes.split(',').map((n) => n.trim()).filter(Boolean);
});
const baseNotesList = computed(() => {
    if (!props.product.base_notes) return [];
    return props.product.base_notes.split(',').map((n) => n.trim()).filter(Boolean);
});

// Sticky mobile bar visibility via IntersectionObserver
const mainBuySectionRef = ref(null);
const showStickyMobileBar = ref(false);

// Recently Viewed Fragrances in localStorage
const recentlyViewed = ref([]);

onMounted(() => {
    // 1. Observe main buy section
    if (mainBuySectionRef.value && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(([entry]) => {
            showStickyMobileBar.value = !entry.isIntersecting;
        }, { threshold: 0.1 });
        observer.observe(mainBuySectionRef.value);
    }

    // 2. Manage Recently Viewed in localStorage
    try {
        const stored = JSON.parse(localStorage.getItem('soni_recently_viewed') || '[]');
        recentlyViewed.value = stored.filter((item) => item.id !== props.product.id).slice(0, 4);

        const currentEntry = {
            id: props.product.id,
            name: props.product.name,
            slug: props.product.slug,
            brand_name: props.product.brand?.name,
            concentration: props.product.concentration,
            formatted_price_from: props.product.formatted_price_from,
            price_from: props.product.price_from,
            thumbnail_url: props.product.thumbnail_url || props.images[0]?.thumb_url,
            primary_image_url: props.product.primary_image_url || props.images[0]?.webp_url,
        };

        const updated = [currentEntry, ...stored.filter((item) => item.id !== props.product.id)].slice(0, 8);
        localStorage.setItem('soni_recently_viewed', JSON.stringify(updated));
    } catch (e) {
        console.error('Error handling recently viewed', e);
    }
});

// JSON-LD Structured Schema
const jsonLdSchema = computed(() => {
    const offers = variants.value.map((v) => ({
        '@type': 'Offer',
        price: v.price,
        priceCurrency: 'ETB',
        priceValidUntil: '2027-12-31',
        availability: v.stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        itemCondition: 'https://schema.org/NewCondition',
        url: typeof window !== 'undefined' ? window.location.href : '',
        seller: {
            '@type': 'Organization',
            name: 'Soni Perfume',
        },
    }));

    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: props.product.name,
        image: props.images.map((img) => img.webp_url || img.original_url),
        description: props.product.short_description || props.product.description,
        brand: {
            '@type': 'Brand',
            name: props.product.brand?.name || 'Soni Perfume',
        },
        sku: selectedVariant.value?.sku || `SP-${props.product.id}`,
        offers: offers.length > 0 ? offers : {
            '@type': 'Offer',
            price: currentPrice.value,
            priceCurrency: 'ETB',
            availability: 'https://schema.org/InStock',
        },
    });
});
</script>

<template>
    <AppLayout>
        <Head>
            <title>{{ seo.title || `${product.name} | Soni Perfume` }}</title>
            <meta
                name="description"
                :content="seo.description"
            >
            <meta
                property="og:title"
                :content="seo.title"
            >
            <meta
                property="og:description"
                :content="seo.description"
            >
            <meta
                property="og:image"
                :content="seo.image"
            >
            <meta
                property="og:type"
                :content="'product'"
            >
            <link
                rel="canonical"
                :href="seo.url"
            >
        </Head>

        <!-- JSON-LD Structured Data -->
        <component
            :is="'script'"
            type="application/ld+json"
            v-html="jsonLdSchema"
        />

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
            <!-- Breadcrumbs -->
            <Breadcrumbs
                :items="breadcrumbs"
                class="mb-8"
            />

            <!-- Main Product Grid: Gallery + Details -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
                <!-- Gallery Column (7 cols on LG) -->
                <div class="lg:col-span-7 flex flex-col-reverse md:flex-row gap-4">
                    <!-- Thumbnail Selector (desktop vertical / mobile horizontal) -->
                    <div
                        v-if="images.length > 1"
                        class="flex md:flex-col gap-2.5 overflow-x-auto md:overflow-y-auto max-h-[500px] shrink-0 pb-2 md:pb-0 scrollbar-none"
                    >
                        <button
                            v-for="(img, idx) in images"
                            :key="img.id || idx"
                            type="button"
                            class="w-16 h-16 sm:w-20 sm:h-20 rounded-sm overflow-hidden border-2 transition-all p-1 bg-white shrink-0"
                            :class="activeImageIndex === idx ? 'border-brand-gold ring-1 ring-brand-gold' : 'border-brand-border/70 hover:border-brand-gold/60'"
                            @click="activeImageIndex = idx"
                        >
                            <img
                                :src="img.thumb_url || img.original_url"
                                :alt="`${product.name} thumbnail ${idx + 1}`"
                                class="w-full h-full object-contain"
                            >
                        </button>
                    </div>

                    <!-- Main Showcase Image with Zoom & Swipe -->
                    <div class="relative flex-1 bg-brand-surface-warm rounded-sm border border-brand-border/70 overflow-hidden select-none">
                        <!-- Badges -->
                        <div class="absolute top-4 left-4 z-10 flex flex-col gap-1.5 pointer-events-none">
                            <Badge
                                v-if="product.is_bestseller"
                                variant="gold"
                            >
                                Bestseller
                            </Badge>
                            <Badge
                                v-if="product.is_new"
                                variant="dark"
                            >
                                New
                            </Badge>
                            <Badge
                                v-if="hasDiscount"
                                variant="blush"
                            >
                                -{{ discountPercent }}% OFF
                            </Badge>
                        </div>

                        <!-- Main Image Area -->
                        <div
                            class="relative aspect-square w-full flex items-center justify-center p-6 md:p-12 cursor-crosshair overflow-hidden"
                            @mouseenter="isHoverZooming = true"
                            @mouseleave="isHoverZooming = false"
                            @mousemove="handleMouseMove"
                            @touchstart="handleTouchStart"
                            @touchend="handleTouchEnd"
                        >
                            <img
                                :src="images[activeImageIndex]?.webp_url || images[activeImageIndex]?.original_url || '/images/placeholder.svg'"
                                :alt="product.name"
                                class="max-w-full max-h-full object-contain transition-transform duration-200"
                                :class="{ 'scale-150': isHoverZooming }"
                                :style="isHoverZooming ? { transformOrigin: `${zoomCoords.x}% ${zoomCoords.y}%` } : {}"
                            >
                        </div>

                        <!-- Image Counter on Mobile -->
                        <div
                            v-if="images.length > 1"
                            class="absolute bottom-4 right-4 bg-brand-charcoal/80 text-white text-[11px] px-2.5 py-1 rounded-sm tracking-wider font-mono"
                        >
                            {{ activeImageIndex + 1 }} / {{ images.length }}
                        </div>
                    </div>
                </div>

                <!-- Product Details Column (5 cols on LG) -->
                <div class="lg:col-span-5 flex flex-col space-y-6">
                    <!-- Brand & Title -->
                    <div>
                        <Link
                            v-if="product.brand"
                            :href="`/brand/${product.brand.slug}`"
                            class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium hover:text-brand-charcoal transition-colors inline-block mb-1.5"
                        >
                            {{ product.brand.name }}
                        </Link>
                        <h1 class="font-serif text-3xl sm:text-4xl text-brand-charcoal font-medium leading-tight mb-2">
                            {{ product.name }}
                        </h1>

                        <!-- Concentration & Gender tags + Rating -->
                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <span
                                v-if="product.concentration"
                                class="text-xs px-2.5 py-0.5 rounded-sm bg-brand-surface border border-brand-border text-brand-charcoal font-medium uppercase tracking-wider"
                            >
                                {{ product.concentration }}
                            </span>
                            <span
                                v-if="product.gender"
                                class="text-xs px-2.5 py-0.5 rounded-sm bg-brand-surface border border-brand-border text-brand-muted uppercase tracking-wider"
                            >
                                {{ product.gender }}
                            </span>
                            <div class="flex items-center gap-1.5 text-xs text-brand-muted">
                                <Rating
                                    :rating="5"
                                    size="sm"
                                />
                                <span>(18 verified reviews)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Price Display (ETB whole integers) -->
                    <div class="pt-2 border-t border-brand-border/60">
                        <div class="flex items-baseline gap-3">
                            <span class="font-serif text-3xl sm:text-4xl font-bold text-brand-charcoal">
                                ETB {{ Number(currentPrice).toLocaleString() }}
                            </span>
                            <span
                                v-if="hasDiscount"
                                class="text-base sm:text-lg text-brand-muted line-through"
                            >
                                ETB {{ Number(comparePrice).toLocaleString() }}
                            </span>
                        </div>
                        <p class="text-xs text-brand-muted mt-1">
                            All taxes included. Authentic original fragrance. Cash/Telebirr on delivery.
                        </p>
                    </div>

                    <!-- Size / Variant Selector -->
                    <div
                        v-if="variants.length > 0"
                        class="space-y-2.5 pt-2"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold uppercase tracking-wider text-brand-charcoal">
                                Select Bottle Size:
                            </span>
                            <span
                                v-if="selectedVariant"
                                class="text-brand-gold font-medium"
                            >
                                {{ selectedVariant.size_ml }}ml (ETB {{ Number(selectedVariant.price).toLocaleString() }})
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-2.5">
                            <button
                                v-for="v in variants"
                                :key="v.id"
                                type="button"
                                class="px-4 py-2.5 rounded-sm text-xs font-medium border transition-all flex items-center gap-2"
                                :class="selectedVariantId === v.id
                                    ? 'bg-brand-charcoal text-white border-brand-charcoal shadow-sm'
                                    : (v.stock > 0
                                        ? 'bg-brand-surface text-brand-charcoal border-brand-border hover:border-brand-gold'
                                        : 'bg-brand-surface text-brand-muted border-brand-border/50 opacity-60 line-through')"
                                @click="selectedVariantId = v.id"
                            >
                                <span>{{ v.size_ml }}ml</span>
                                <span
                                    v-if="v.stock <= 0"
                                    class="text-[10px] text-red-500 font-normal no-underline"
                                >
                                    (Sold out)
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Stock Status Indicator -->
                    <div class="pt-1">
                        <div
                            v-if="isOutOfStock"
                            class="flex items-center gap-2 text-xs text-red-600 font-medium"
                        >
                            <span class="w-2 h-2 rounded-full bg-red-500" />
                            <span>Currently Out of Stock. Contact us for upcoming restock.</span>
                        </div>
                        <div
                            v-else-if="isLowStock"
                            class="flex items-center gap-2 text-xs text-amber-700 font-semibold"
                        >
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse" />
                            <span>Only {{ stock }} left in stock – order soon!</span>
                        </div>
                        <div
                            v-else
                            class="flex items-center gap-2 text-xs text-emerald-700 font-medium"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-500" />
                            <span>In Stock • Ready for Same-Day Addis Ababa Delivery</span>
                        </div>
                    </div>

                    <!-- Main Add to Cart & Buy Box -->
                    <div
                        ref="mainBuySectionRef"
                        class="space-y-3 pt-3"
                    >
                        <div class="flex items-center gap-3">
                            <!-- Quantity stepper -->
                            <QuantityStepper
                                v-model="quantity"
                                :min="1"
                                :max="stock > 0 ? stock : 10"
                                :disabled="isOutOfStock"
                            />

                            <!-- Add to Cart Primary Button -->
                            <Button
                                variant="primary"
                                size="lg"
                                class="flex-1 text-xs uppercase tracking-widest py-3.5"
                                :disabled="isOutOfStock"
                                :loading="isAddingToCart"
                                @click="addToCart"
                            >
                                <span v-if="!isOutOfStock">Add to Shopping Bag</span>
                                <span v-else>Out of Stock</span>
                            </Button>
                        </div>

                        <!-- Direct WhatsApp / Telegram Order Buttons -->
                        <div class="grid grid-cols-2 gap-2.5 pt-1">
                            <a
                                :href="whatsappOrderUrl"
                                target="_blank"
                                rel="noopener"
                                class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-sm border border-emerald-600 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-medium transition-colors"
                            >
                                <svg
                                    class="w-4 h-4 fill-emerald-600 shrink-0"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.861.174.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.203c.043.071.043.418-.101.823z" />
                                </svg>
                                <span>Order via WhatsApp</span>
                            </a>

                            <a
                                :href="telegramOrderUrl"
                                target="_blank"
                                rel="noopener"
                                class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-sm border border-sky-600 bg-sky-50 hover:bg-sky-100 text-sky-800 text-xs font-medium transition-colors"
                            >
                                <svg
                                    class="w-4 h-4 fill-sky-600 shrink-0"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.37.74-.56 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z" />
                                </svg>
                                <span>Order on Telegram</span>
                            </a>
                        </div>
                    </div>

                    <!-- Trust Strip -->
                    <div class="pt-4 border-t border-brand-border/60 grid grid-cols-2 gap-3 text-xs text-brand-charcoal/80">
                        <div class="flex items-center gap-2">
                            <span class="text-brand-gold text-base">✓</span>
                            <span>100% Authentic Guaranteed</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-brand-gold text-base">⚡</span>
                            <span>Same-Day Addis Delivery</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-brand-gold text-base">💵</span>
                            <span>Pay on Delivery (Cash/CBE)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-brand-gold text-base">🎁</span>
                            <span>Luxury Gift Wrap Available</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visual Scent Pyramid Section -->
            <section
                v-if="topNotesList.length > 0 || heartNotesList.length > 0 || baseNotesList.length > 0"
                class="mt-16 pt-12 border-t border-brand-border/60"
            >
                <div class="max-w-4xl mx-auto text-center mb-8">
                    <span class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium mb-2 block">
                        Olfactory Architecture
                    </span>
                    <h2 class="font-serif text-2xl sm:text-3xl text-brand-charcoal font-normal mb-2">
                        The Fragrance Pyramid
                    </h2>
                    <p class="text-xs sm:text-sm text-brand-muted">
                        How the notes unfold on your skin over time
                    </p>
                </div>

                <div class="max-w-3xl mx-auto space-y-4">
                    <!-- Top Notes (Airy / Initial 15-30 mins) -->
                    <div
                        v-if="topNotesList.length > 0"
                        class="p-5 rounded-sm bg-brand-surface border border-brand-border/80 text-center"
                    >
                        <div class="flex items-center justify-center gap-2 text-xs uppercase tracking-widest font-semibold text-brand-gold mb-1">
                            <span>✨ Head / Top Notes</span>
                            <span class="text-[10px] text-brand-muted font-normal">(Initial 15–30 mins)</span>
                        </div>
                        <div class="flex flex-wrap items-center justify-center gap-2 mt-3">
                            <span
                                v-for="note in topNotesList"
                                :key="note"
                                class="px-3 py-1 rounded-sm bg-brand-gold/10 border border-brand-gold/20 text-xs text-brand-charcoal font-medium"
                            >
                                {{ note }}
                            </span>
                        </div>
                    </div>

                    <!-- Heart Notes (Body / 2-4 hours) -->
                    <div
                        v-if="heartNotesList.length > 0"
                        class="p-5 rounded-sm bg-brand-surface border border-brand-border/80 text-center"
                    >
                        <div class="flex items-center justify-center gap-2 text-xs uppercase tracking-widest font-semibold text-brand-charcoal mb-1">
                            <span>🌹 Heart / Middle Notes</span>
                            <span class="text-[10px] text-brand-muted font-normal">(Core scent • 2–4 hours)</span>
                        </div>
                        <div class="flex flex-wrap items-center justify-center gap-2 mt-3">
                            <span
                                v-for="note in heartNotesList"
                                :key="note"
                                class="px-3 py-1 rounded-sm bg-brand-blush/30 border border-brand-blush text-xs text-brand-charcoal font-medium"
                            >
                                {{ note }}
                            </span>
                        </div>
                    </div>

                    <!-- Base Notes (Drydown / 6-12+ hours) -->
                    <div
                        v-if="baseNotesList.length > 0"
                        class="p-5 rounded-sm bg-brand-surface border border-brand-border/80 text-center"
                    >
                        <div class="flex items-center justify-center gap-2 text-xs uppercase tracking-widest font-semibold text-brand-charcoal mb-1">
                            <span>🪵 Base / Drydown Notes</span>
                            <span class="text-[10px] text-brand-muted font-normal">(Lingering foundation • 6–12+ hours)</span>
                        </div>
                        <div class="flex flex-wrap items-center justify-center gap-2 mt-3">
                            <span
                                v-for="note in baseNotesList"
                                :key="note"
                                class="px-3 py-1 rounded-sm bg-stone-100 border border-stone-300 text-xs text-brand-charcoal font-medium"
                            >
                                {{ note }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Product Information Tabs -->
            <section class="mt-16 pt-10 border-t border-brand-border/60">
                <div class="max-w-4xl mx-auto">
                    <!-- Tab buttons -->
                    <div class="flex items-center justify-center gap-8 border-b border-brand-border/80 pb-4">
                        <button
                            type="button"
                            class="text-xs uppercase tracking-widest font-medium transition-colors relative pb-2"
                            :class="activeTab === 'description' ? 'text-brand-charcoal font-bold' : 'text-brand-muted hover:text-brand-charcoal'"
                            @click="activeTab = 'description'"
                        >
                            <span>Description</span>
                            <span
                                v-if="activeTab === 'description'"
                                class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-gold"
                            />
                        </button>

                        <button
                            type="button"
                            class="text-xs uppercase tracking-widest font-medium transition-colors relative pb-2"
                            :class="activeTab === 'notes' ? 'text-brand-charcoal font-bold' : 'text-brand-muted hover:text-brand-charcoal'"
                            @click="activeTab = 'notes'"
                        >
                            <span>Fragrance Notes</span>
                            <span
                                v-if="activeTab === 'notes'"
                                class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-gold"
                            />
                        </button>

                        <button
                            type="button"
                            class="text-xs uppercase tracking-widest font-medium transition-colors relative pb-2"
                            :class="activeTab === 'delivery' ? 'text-brand-charcoal font-bold' : 'text-brand-muted hover:text-brand-charcoal'"
                            @click="activeTab = 'delivery'"
                        >
                            <span>Delivery & Returns</span>
                            <span
                                v-if="activeTab === 'delivery'"
                                class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-gold"
                            />
                        </button>
                    </div>

                    <!-- Tab 1: Description -->
                    <div
                        v-if="activeTab === 'description'"
                        class="py-8 prose prose-stone max-w-none text-sm text-brand-charcoal/85 leading-relaxed"
                    >
                        <p
                            v-if="product.short_description"
                            class="text-base font-serif italic text-brand-charcoal font-normal mb-4"
                        >
                            "{{ product.short_description }}"
                        </p>
                        <div
                            class="space-y-4"
                            v-html="product.description || 'No detailed description provided.'"
                        />
                    </div>

                    <!-- Tab 2: Notes & Scent Profile -->
                    <div
                        v-else-if="activeTab === 'notes'"
                        class="py-8 space-y-6 text-sm text-brand-charcoal"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-5 bg-brand-surface rounded-sm border border-brand-border/60">
                                <h3 class="font-serif text-lg font-medium mb-3">
                                    Fragrance Characteristics
                                </h3>
                                <dl class="space-y-2 text-xs">
                                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                                        <dt class="text-brand-muted">
                                            Scent Family:
                                        </dt>
                                        <dd class="font-semibold capitalize">
                                            {{ product.scent_family || 'Woody / Oriental' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                                        <dt class="text-brand-muted">
                                            Concentration:
                                        </dt>
                                        <dd class="font-semibold">
                                            {{ product.concentration || 'Eau de Parfum' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                                        <dt class="text-brand-muted">
                                            Gender:
                                        </dt>
                                        <dd class="font-semibold capitalize">
                                            {{ product.gender || 'Unisex' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                                        <dt class="text-brand-muted">
                                            Recommended For:
                                        </dt>
                                        <dd class="font-semibold">
                                            Evening, Special Occasions, Signature Scent
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="p-5 bg-brand-surface rounded-sm border border-brand-border/60">
                                <h3 class="font-serif text-lg font-medium mb-3">
                                    Longevity & Sillage
                                </h3>
                                <p class="text-xs text-brand-charcoal/80 leading-relaxed mb-4">
                                    This formulation boasts extraordinary concentration with high sillage and longevity exceeding 8 to 12 hours on pulse points and fabric.
                                </p>
                                <div class="space-y-2 text-xs">
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>Longevity (8–12+ hrs)</span>
                                            <span class="font-semibold">9.5 / 10</span>
                                        </div>
                                        <div class="w-full bg-brand-border/60 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-brand-gold h-full w-[95%]" />
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>Sillage / Projection</span>
                                            <span class="font-semibold">8.5 / 10</span>
                                        </div>
                                        <div class="w-full bg-brand-border/60 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-brand-gold h-full w-[85%]" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Delivery & Returns Policy -->
                    <div
                        v-else-if="activeTab === 'delivery'"
                        class="py-8 space-y-6 text-sm text-brand-charcoal"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="p-5 bg-brand-surface rounded-sm border border-brand-border/60 space-y-2">
                                <span class="text-xl">⚡</span>
                                <h4 class="font-serif font-medium text-base">
                                    Fast Addis Delivery
                                </h4>
                                <p class="text-xs text-brand-charcoal/70 leading-relaxed">
                                    Orders placed before 2:00 PM are delivered the very same day anywhere across Addis Ababa. Evening orders arrive the following morning.
                                </p>
                            </div>

                            <div class="p-5 bg-brand-surface rounded-sm border border-brand-border/60 space-y-2">
                                <span class="text-xl">💵</span>
                                <h4 class="font-serif font-medium text-base">
                                    Cash & Mobile Pay
                                </h4>
                                <p class="text-xs text-brand-charcoal/70 leading-relaxed">
                                    Pay only when your perfume arrives at your door. We accept Cash, Telebirr, and direct CBE mobile banking transfers.
                                </p>
                            </div>

                            <div class="p-5 bg-brand-surface rounded-sm border border-brand-border/60 space-y-2">
                                <span class="text-xl">🛡️</span>
                                <h4 class="font-serif font-medium text-base">
                                    Authenticity & Returns
                                </h4>
                                <p class="text-xs text-brand-charcoal/70 leading-relaxed">
                                    Every bottle is 100% authentic original import. Unopened, factory-sealed boxes may be exchanged or returned within 7 calendar days.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Related Fragrances Section -->
            <section
                v-if="relatedProducts.length > 0"
                class="mt-20 pt-12 border-t border-brand-border/60"
            >
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <span class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium mb-1 block">
                            You May Also Adore
                        </span>
                        <h2 class="font-serif text-2xl sm:text-3xl text-brand-charcoal font-normal">
                            Related Fragrances
                        </h2>
                    </div>
                    <Link
                        href="/shop"
                        class="text-xs uppercase tracking-widest text-brand-gold hover:text-brand-charcoal font-semibold transition-colors hidden sm:block"
                    >
                        View Catalog →
                    </Link>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                    <ProductCard
                        v-for="rel in relatedProducts"
                        :key="rel.id"
                        :product="rel"
                    />
                </div>
            </section>

            <!-- Recently Viewed Section (localStorage) -->
            <section
                v-if="recentlyViewed.length > 0"
                class="mt-16 pt-12 border-t border-brand-border/60"
            >
                <div class="mb-8">
                    <span class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium mb-1 block">
                        Your History
                    </span>
                    <h2 class="font-serif text-2xl sm:text-3xl text-brand-charcoal font-normal">
                        Recently Viewed Fragrances
                    </h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                    <div
                        v-for="rv in recentlyViewed"
                        :key="rv.id"
                        class="group p-3 bg-brand-surface border border-brand-border/70 rounded-sm hover:border-brand-gold transition-all"
                    >
                        <Link
                            :href="`/product/${rv.slug}`"
                            class="block"
                        >
                            <div class="aspect-square bg-white rounded-sm overflow-hidden mb-3 p-3 flex items-center justify-center">
                                <img
                                    :src="rv.primary_image_url || rv.thumbnail_url || '/images/placeholder.svg'"
                                    :alt="rv.name"
                                    class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                >
                            </div>
                            <p class="text-[10px] text-brand-gold font-medium uppercase tracking-wider truncate">
                                {{ rv.brand_name }}
                            </p>
                            <p class="font-serif text-sm font-medium text-brand-charcoal truncate group-hover:text-brand-gold transition-colors">
                                {{ rv.name }}
                            </p>
                            <p class="text-xs font-semibold text-brand-charcoal mt-1">
                                {{ rv.formatted_price_from }}
                            </p>
                        </Link>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sticky Mobile Add-to-Cart Bar -->
        <Transition
            enter-active-class="transform transition ease-out duration-200"
            enter-from-class="translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transform transition ease-in duration-150"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0"
        >
            <div
                v-if="showStickyMobileBar"
                class="md:hidden fixed bottom-14 left-0 right-0 z-30 bg-brand-surface border-t border-brand-border/80 p-3 shadow-2xl flex items-center justify-between gap-3"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-10 h-10 rounded-sm bg-white border border-brand-border/60 shrink-0 p-1 flex items-center justify-center">
                        <img
                            :src="images[0]?.thumb_url || '/images/placeholder.svg'"
                            :alt="product.name"
                            class="max-w-full max-h-full object-contain"
                        >
                    </div>
                    <div class="min-w-0">
                        <p class="font-serif text-xs font-semibold text-brand-charcoal truncate">
                            {{ product.name }}
                        </p>
                        <p class="text-xs text-brand-gold font-bold">
                            ETB {{ Number(currentPrice).toLocaleString() }}
                            <span
                                v-if="selectedVariant"
                                class="text-[10px] text-brand-muted font-normal"
                            >({{ selectedVariant.size_ml }}ml)</span>
                        </p>
                    </div>
                </div>

                <Button
                    variant="primary"
                    size="sm"
                    class="shrink-0 text-[11px] uppercase tracking-wider px-4"
                    :disabled="isOutOfStock"
                    :loading="isAddingToCart"
                    @click="addToCart"
                >
                    <span v-if="!isOutOfStock">Add to Bag</span>
                    <span v-else>Out of Stock</span>
                </Button>
            </div>
        </Transition>
    </AppLayout>
</template>
