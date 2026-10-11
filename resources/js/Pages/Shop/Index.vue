<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProductCard from '../../Components/ProductCard.vue';
import Breadcrumbs from '../../Components/Breadcrumbs.vue';
import Drawer from '../../Components/Drawer.vue';
import Button from '../../Components/Button.vue';
import Badge from '../../Components/Badge.vue';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterOptions: {
        type: Object,
        default: () => ({}),
    },
    currentCategory: {
        type: Object,
        default: null,
    },
    currentBrand: {
        type: Object,
        default: null,
    },
    searchQuery: {
        type: String,
        default: '',
    },
    pageTitle: {
        type: String,
        default: 'Shop Fragrances',
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

// Mobile filter drawer state
const mobileFiltersOpen = ref(false);
const isLoadingMore = ref(false);

// Accumulated products list for "Load more" feature
const allProducts = ref([...props.products.data]);

// Sync products when props.products change (e.g. on new filter or search)
watch(() => props.products, (newProducts) => {
    if (newProducts.current_page === 1) {
        allProducts.value = [...newProducts.data];
    } else {
        // Append unique products
        const existingIds = new Set(allProducts.value.map((p) => p.id));
        const newItems = newProducts.data.filter((p) => !existingIds.has(p.id));
        allProducts.value = [...allProducts.value, ...newItems];
    }
}, { deep: true });

// Local reactive filter state
const localFilters = ref({
    category: props.filters.category || '',
    brand: props.filters.brand || '',
    gender: props.filters.gender || '',
    scent_family: props.filters.scent_family || '',
    concentration: props.filters.concentration || '',
    price_min: props.filters.price_min || '',
    price_max: props.filters.price_max || '',
    size: props.filters.size || '',
    in_stock: Boolean(props.filters.in_stock),
    sort: props.filters.sort || 'newest',
});

// Update URL and reload data
const applyFilters = () => {
    const params = {};

    if (props.searchQuery) params.q = props.searchQuery;

    if (localFilters.value.category && !props.currentCategory) params.category = localFilters.value.category;
    if (localFilters.value.brand && !props.currentBrand) params.brand = localFilters.value.brand;
    if (localFilters.value.gender) params.gender = localFilters.value.gender;
    if (localFilters.value.scent_family) params.scent_family = localFilters.value.scent_family;
    if (localFilters.value.concentration) params.concentration = localFilters.value.concentration;
    if (localFilters.value.price_min) params.price_min = localFilters.value.price_min;
    if (localFilters.value.price_max) params.price_max = localFilters.value.price_max;
    if (localFilters.value.size) params.size = localFilters.value.size;
    if (localFilters.value.in_stock) params.in_stock = 1;
    if (localFilters.value.sort && localFilters.value.sort !== 'newest') params.sort = localFilters.value.sort;

    let targetUrl = '/shop';
    if (props.currentCategory) targetUrl = `/category/${props.currentCategory.slug}`;
    else if (props.currentBrand) targetUrl = `/brand/${props.currentBrand.slug}`;
    else if (props.searchQuery) targetUrl = '/search';

    router.get(targetUrl, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleSortChange = (newSort) => {
    localFilters.value.sort = newSort;
    applyFilters();
};

const removeFilter = (key) => {
    if (key === 'price') {
        localFilters.value.price_min = '';
        localFilters.value.price_max = '';
    } else if (key === 'in_stock') {
        localFilters.value.in_stock = false;
    } else {
        localFilters.value[key] = '';
    }
    applyFilters();
};

const clearAllFilters = () => {
    localFilters.value = {
        category: '',
        brand: '',
        gender: '',
        scent_family: '',
        concentration: '',
        price_min: '',
        price_max: '',
        size: '',
        in_stock: false,
        sort: localFilters.value.sort,
    };
    applyFilters();
};

// Compute active filter chips
const activeChips = computed(() => {
    const chips = [];

    if (localFilters.value.category && !props.currentCategory) {
        const cat = props.filterOptions.categories?.find((c) => c.slug === localFilters.value.category);
        chips.push({ key: 'category', label: `Category: ${cat?.name || localFilters.value.category}` });
    }

    if (localFilters.value.brand && !props.currentBrand) {
        const b = props.filterOptions.brands?.find((item) => item.slug === localFilters.value.brand);
        chips.push({ key: 'brand', label: `Brand: ${b?.name || localFilters.value.brand}` });
    }

    if (localFilters.value.gender) {
        chips.push({ key: 'gender', label: `Gender: ${localFilters.value.gender}` });
    }

    if (localFilters.value.scent_family) {
        chips.push({ key: 'scent_family', label: `Family: ${localFilters.value.scent_family}` });
    }

    if (localFilters.value.concentration) {
        chips.push({ key: 'concentration', label: `Type: ${localFilters.value.concentration}` });
    }

    if (localFilters.value.size) {
        chips.push({ key: 'size', label: `Size: ${localFilters.value.size}ml` });
    }

    if (localFilters.value.price_min || localFilters.value.price_max) {
        const minStr = localFilters.value.price_min ? `ETB ${Number(localFilters.value.price_min).toLocaleString()}` : '0';
        const maxStr = localFilters.value.price_max ? `ETB ${Number(localFilters.value.price_max).toLocaleString()}` : '+';
        chips.push({ key: 'price', label: `Price: ${minStr} - ${maxStr}` });
    }

    if (localFilters.value.in_stock) {
        chips.push({ key: 'in_stock', label: 'In Stock Only' });
    }

    return chips;
});

const activeFiltersCount = computed(() => activeChips.value.length);

// "Load More" action using Inertia partial reload
const loadMore = () => {
    if (!props.products.next_page_url || isLoadingMore.value) return;

    isLoadingMore.value = true;
    router.get(props.products.next_page_url, {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['products'],
        onFinish: () => {
            isLoadingMore.value = false;
        },
    });
};

// Breadcrumbs
const breadcrumbs = computed(() => {
    const list = [{ label: 'Home', href: '/' }];

    if (props.currentCategory) {
        list.push({ label: 'Shop', href: '/shop' });
        list.push({ label: props.currentCategory.name, href: null });
    } else if (props.currentBrand) {
        list.push({ label: 'Brands', href: '/brands' });
        list.push({ label: props.currentBrand.name, href: null });
    } else if (props.searchQuery) {
        list.push({ label: 'Search', href: null });
    } else {
        list.push({ label: 'Shop', href: null });
    }

    return list;
});
</script>

<template>
    <AppLayout>
        <Head>
            <title>{{ seo.title || `${pageTitle} | Soni Perfume` }}</title>
            <meta
                name="description"
                :content="seo.description || 'Explore authentic luxury, designer, and niche fragrances in Addis Ababa.'"
            >
            <meta
                property="og:title"
                :content="seo.title || pageTitle"
            >
            <meta
                property="og:description"
                :content="seo.description"
            >
            <link
                rel="canonical"
                :href="seo.url || '/shop'"
            >
        </Head>

        <!-- Contextual Header Banner -->
        <section class="bg-brand-surface border-b border-brand-border/60 py-10 md:py-14">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumbs
                    :items="breadcrumbs"
                    class="mb-4"
                />

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="max-w-3xl">
                        <!-- Eyebrow -->
                        <span
                            v-if="currentCategory"
                            class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium mb-2 block"
                        >
                            Category Collection
                        </span>
                        <span
                            v-else-if="currentBrand"
                            class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium mb-2 block"
                        >
                            Designer House
                        </span>
                        <span
                            v-else-if="searchQuery"
                            class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium mb-2 block"
                        >
                            Search Query
                        </span>
                        <span
                            v-else
                            class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium mb-2 block"
                        >
                            The Perfume Library
                        </span>

                        <h1 class="font-serif text-3xl sm:text-4xl md:text-5xl font-normal text-brand-charcoal mb-3">
                            {{ pageTitle }}
                        </h1>

                        <p
                            v-if="currentCategory?.description"
                            class="text-sm sm:text-base text-brand-charcoal/70 leading-relaxed font-sans"
                        >
                            {{ currentCategory.description }}
                        </p>
                        <p
                            v-else-if="currentBrand?.description"
                            class="text-sm sm:text-base text-brand-charcoal/70 leading-relaxed font-sans"
                        >
                            {{ currentBrand.description }}
                        </p>
                        <p
                            v-else-if="searchQuery"
                            class="text-sm sm:text-base text-brand-charcoal/70 font-sans"
                        >
                            Found {{ products.total }} fragrance {{ products.total === 1 ? 'match' : 'matches' }} for your query.
                        </p>
                        <p
                            v-else
                            class="text-sm sm:text-base text-brand-charcoal/70 leading-relaxed font-sans"
                        >
                            Discover our handpicked curation of 100% authentic eau de parfums, extrait de parfums, and pure oils. Free delivery in Addis Ababa over ETB 3,000.
                        </p>
                    </div>

                    <!-- Brand Logo badge if current brand -->
                    <div
                        v-if="currentBrand?.logo_url"
                        class="hidden md:flex w-24 h-24 rounded-full bg-white p-3 border border-brand-border/60 items-center justify-center shrink-0 shadow-sm"
                    >
                        <img
                            :src="currentBrand.logo_url"
                            :alt="currentBrand.name"
                            class="max-w-full max-h-full object-contain"
                        >
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Catalog Area -->
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
            <!-- Filter & Sort Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-brand-border/60">
                <!-- Left: Mobile Filter Button & Results Count -->
                <div class="flex items-center gap-3">
                    <!-- Mobile Filter Trigger -->
                    <button
                        type="button"
                        class="lg:hidden inline-flex items-center gap-2 px-3.5 py-2 rounded-sm border border-brand-charcoal bg-brand-surface text-brand-charcoal text-xs font-medium uppercase tracking-wider hover:border-brand-gold hover:text-brand-gold transition-colors"
                        @click="mobileFiltersOpen = true"
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
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                            />
                        </svg>
                        <span>Filters</span>
                        <span
                            v-if="activeFiltersCount > 0"
                            class="w-4 h-4 rounded-full bg-brand-gold text-white text-[10px] flex items-center justify-center font-bold"
                        >
                            {{ activeFiltersCount }}
                        </span>
                    </button>

                    <p class="text-xs sm:text-sm text-brand-charcoal/70">
                        Showing <span class="font-semibold text-brand-charcoal">{{ allProducts.length }}</span> of <span class="font-semibold text-brand-charcoal">{{ products.total }}</span> fragrances
                    </p>
                </div>

                <!-- Right: Sort Dropdown -->
                <div class="flex items-center gap-2 self-end sm:self-auto">
                    <label
                        for="sort-select"
                        class="text-xs uppercase tracking-wider font-semibold text-brand-muted shrink-0"
                    >
                        Sort By:
                    </label>
                    <select
                        id="sort-select"
                        :value="localFilters.sort"
                        class="text-xs bg-brand-surface border border-brand-border rounded-sm py-2 pl-3 pr-8 text-brand-charcoal focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                        @change="handleSortChange($event.target.value)"
                    >
                        <option value="newest">
                            Newest Arrivals
                        </option>
                        <option value="bestsellers">
                            Bestsellers
                        </option>
                        <option value="price_asc">
                            Price: Low to High
                        </option>
                        <option value="price_desc">
                            Price: High to Low
                        </option>
                    </select>
                </div>
            </div>

            <!-- Active Filter Chips -->
            <div
                v-if="activeChips.length > 0"
                class="flex flex-wrap items-center gap-2 pt-4 pb-2"
            >
                <span class="text-xs text-brand-muted font-medium">Active:</span>
                <span
                    v-for="chip in activeChips"
                    :key="chip.key"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm bg-brand-gold/10 border border-brand-gold/30 text-xs text-brand-charcoal"
                >
                    <span>{{ chip.label }}</span>
                    <button
                        type="button"
                        class="hover:text-red-600 transition-colors"
                        :aria-label="`Remove filter ${chip.label}`"
                        @click="removeFilter(chip.key)"
                    >
                        ✕
                    </button>
                </span>

                <button
                    type="button"
                    class="text-xs text-brand-gold hover:text-brand-charcoal font-medium underline underline-offset-2 ml-2 transition-colors"
                    @click="clearAllFilters"
                >
                    Clear All
                </button>
            </div>

            <!-- Grid & Sidebar Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 pt-6">
                <!-- Desktop Sidebar Filters -->
                <aside class="hidden lg:block lg:col-span-1 space-y-6">
                    <div class="bg-brand-surface p-5 rounded-sm border border-brand-border/70 space-y-6 sticky top-24">
                        <div class="flex items-center justify-between pb-3 border-b border-brand-border/60">
                            <h2 class="font-serif text-lg font-medium text-brand-charcoal">
                                Filter Fragrances
                            </h2>
                            <button
                                v-if="activeFiltersCount > 0"
                                type="button"
                                class="text-xs text-brand-gold hover:underline"
                                @click="clearAllFilters"
                            >
                                Reset
                            </button>
                        </div>

                        <!-- In Stock Only Toggle -->
                        <div class="flex items-center justify-between pt-1">
                            <label
                                for="desktop-in-stock"
                                class="text-xs font-medium uppercase tracking-wider text-brand-charcoal cursor-pointer"
                            >
                                In Stock Only
                            </label>
                            <input
                                id="desktop-in-stock"
                                v-model="localFilters.in_stock"
                                type="checkbox"
                                class="w-4 h-4 rounded border-brand-border text-brand-gold focus:ring-brand-gold cursor-pointer"
                                @change="applyFilters"
                            >
                        </div>

                        <!-- Categories (if not on a category page) -->
                        <div
                            v-if="!currentCategory && filterOptions.categories?.length > 0"
                            class="pt-3 border-t border-brand-border/40"
                        >
                            <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-3">
                                Category
                            </h3>
                            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                <label
                                    class="flex items-center justify-between text-xs cursor-pointer py-1 text-brand-charcoal/80 hover:text-brand-gold transition-colors"
                                >
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="localFilters.category"
                                            type="radio"
                                            value=""
                                            name="desktop_category"
                                            class="text-brand-gold focus:ring-brand-gold"
                                            @change="applyFilters"
                                        >
                                        <span>All Categories</span>
                                    </div>
                                </label>
                                <label
                                    v-for="cat in filterOptions.categories"
                                    :key="cat.id"
                                    class="flex items-center justify-between text-xs cursor-pointer py-1 text-brand-charcoal/80 hover:text-brand-gold transition-colors"
                                >
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="localFilters.category"
                                            type="radio"
                                            :value="cat.slug"
                                            name="desktop_category"
                                            class="text-brand-gold focus:ring-brand-gold"
                                            @change="applyFilters"
                                        >
                                        <span>{{ cat.name }}</span>
                                    </div>
                                    <span class="text-brand-muted text-[10px]">({{ cat.products_count }})</span>
                                </label>
                            </div>
                        </div>

                        <!-- Brand (if not on brand page) -->
                        <div
                            v-if="!currentBrand && filterOptions.brands?.length > 0"
                            class="pt-3 border-t border-brand-border/40"
                        >
                            <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-3">
                                Brand
                            </h3>
                            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                <label
                                    class="flex items-center justify-between text-xs cursor-pointer py-1 text-brand-charcoal/80 hover:text-brand-gold transition-colors"
                                >
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="localFilters.brand"
                                            type="radio"
                                            value=""
                                            name="desktop_brand"
                                            class="text-brand-gold focus:ring-brand-gold"
                                            @change="applyFilters"
                                        >
                                        <span>All Brands</span>
                                    </div>
                                </label>
                                <label
                                    v-for="b in filterOptions.brands"
                                    :key="b.id"
                                    class="flex items-center justify-between text-xs cursor-pointer py-1 text-brand-charcoal/80 hover:text-brand-gold transition-colors"
                                >
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="localFilters.brand"
                                            type="radio"
                                            :value="b.slug"
                                            name="desktop_brand"
                                            class="text-brand-gold focus:ring-brand-gold"
                                            @change="applyFilters"
                                        >
                                        <span>{{ b.name }}</span>
                                    </div>
                                    <span class="text-brand-muted text-[10px]">({{ b.products_count }})</span>
                                </label>
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="pt-3 border-t border-brand-border/40">
                            <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-3">
                                Gender
                            </h3>
                            <div class="grid grid-cols-3 gap-1.5">
                                <button
                                    v-for="g in ['men', 'women', 'unisex']"
                                    :key="g"
                                    type="button"
                                    class="py-1.5 px-2 text-xs uppercase tracking-wider rounded-sm border transition-all text-center"
                                    :class="localFilters.gender === g
                                        ? 'bg-brand-charcoal text-white border-brand-charcoal font-semibold'
                                        : 'bg-brand-bg text-brand-charcoal border-brand-border hover:border-brand-gold'"
                                    @click="localFilters.gender = localFilters.gender === g ? '' : g; applyFilters();"
                                >
                                    {{ g }}
                                </button>
                            </div>
                        </div>

                        <!-- Scent Family -->
                        <div
                            v-if="filterOptions.scentFamilies?.length > 0"
                            class="pt-3 border-t border-brand-border/40"
                        >
                            <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-3">
                                Scent Family
                            </h3>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="sf in filterOptions.scentFamilies"
                                    :key="sf.value"
                                    type="button"
                                    class="py-1 px-2.5 text-xs rounded-sm border transition-all"
                                    :class="localFilters.scent_family === sf.value
                                        ? 'bg-brand-gold text-white border-brand-gold font-medium'
                                        : 'bg-brand-bg text-brand-charcoal border-brand-border hover:border-brand-gold'"
                                    @click="localFilters.scent_family = localFilters.scent_family === sf.value ? '' : sf.value; applyFilters();"
                                >
                                    {{ sf.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Concentration -->
                        <div
                            v-if="filterOptions.concentrations?.length > 0"
                            class="pt-3 border-t border-brand-border/40"
                        >
                            <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-3">
                                Concentration
                            </h3>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="c in filterOptions.concentrations"
                                    :key="c.value"
                                    type="button"
                                    class="py-1 px-2 text-xs rounded-sm border transition-all"
                                    :class="localFilters.concentration === c.value
                                        ? 'bg-brand-charcoal text-white border-brand-charcoal font-medium'
                                        : 'bg-brand-bg text-brand-charcoal border-brand-border hover:border-brand-gold'"
                                    @click="localFilters.concentration = localFilters.concentration === c.value ? '' : c.value; applyFilters();"
                                >
                                    {{ c.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Price Range in whole ETB -->
                        <div class="pt-3 border-t border-brand-border/40">
                            <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-3">
                                Price Range (ETB)
                            </h3>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] text-brand-muted block mb-1">Min ETB</label>
                                    <input
                                        v-model="localFilters.price_min"
                                        type="number"
                                        step="500"
                                        min="0"
                                        placeholder="Min"
                                        class="w-full text-xs p-2 bg-brand-bg border border-brand-border rounded-sm"
                                        @change="applyFilters"
                                    >
                                </div>
                                <div>
                                    <label class="text-[10px] text-brand-muted block mb-1">Max ETB</label>
                                    <input
                                        v-model="localFilters.price_max"
                                        type="number"
                                        step="500"
                                        min="0"
                                        placeholder="Max"
                                        class="w-full text-xs p-2 bg-brand-bg border border-brand-border rounded-sm"
                                        @change="applyFilters"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Size (ml) -->
                        <div
                            v-if="filterOptions.sizes?.length > 0"
                            class="pt-3 border-t border-brand-border/40"
                        >
                            <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-3">
                                Bottle Size
                            </h3>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="s in filterOptions.sizes"
                                    :key="s"
                                    type="button"
                                    class="py-1 px-2.5 text-xs rounded-sm border transition-all"
                                    :class="Number(localFilters.size) === Number(s)
                                        ? 'bg-brand-charcoal text-white border-brand-charcoal font-medium'
                                        : 'bg-brand-bg text-brand-charcoal border-brand-border hover:border-brand-gold'"
                                    @click="localFilters.size = Number(localFilters.size) === Number(s) ? '' : s; applyFilters();"
                                >
                                    {{ s }}ml
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Products Grid & Load More -->
                <main class="lg:col-span-3">
                    <!-- Products Grid -->
                    <div
                        v-if="allProducts.length > 0"
                        class="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6"
                    >
                        <ProductCard
                            v-for="product in allProducts"
                            :key="product.id"
                            :product="product"
                        />
                    </div>

                    <!-- Empty State -->
                    <div
                        v-else
                        class="bg-brand-surface border border-brand-border/70 rounded-sm p-12 text-center my-8"
                    >
                        <div class="w-16 h-16 mx-auto mb-4 text-brand-gold flex items-center justify-center rounded-full bg-brand-gold/10">
                            <svg
                                class="w-8 h-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <h2 class="font-serif text-2xl font-medium text-brand-charcoal mb-2">
                            No Fragrances Found
                        </h2>
                        <p class="text-sm text-brand-charcoal/70 max-w-md mx-auto mb-6">
                            We couldn't find any fragrances matching your current combination of filters. Try broadening your price range, scent family, or resetting filters.
                        </p>
                        <Button
                            variant="primary"
                            size="md"
                            @click="clearAllFilters"
                        >
                            Clear All Filters
                        </Button>
                    </div>

                    <!-- "Load More" Pagination via Inertia Partial Reload -->
                    <div
                        v-if="products.next_page_url"
                        class="mt-12 text-center space-y-4"
                    >
                        <p class="text-xs text-brand-muted">
                            Showing {{ allProducts.length }} of {{ products.total }} fragrances
                        </p>

                        <!-- Visual progress bar -->
                        <div class="w-48 h-1 bg-brand-border/70 rounded-full mx-auto overflow-hidden">
                            <div
                                class="h-full bg-brand-gold transition-all duration-300"
                                :style="{ width: `${Math.min(100, (allProducts.length / products.total) * 100)}%` }"
                            />
                        </div>

                        <div>
                            <Button
                                variant="outline"
                                size="lg"
                                :loading="isLoadingMore"
                                class="px-8"
                                @click="loadMore"
                            >
                                <span v-if="!isLoadingMore">Load More Fragrances</span>
                                <span v-else>Loading...</span>
                            </Button>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <!-- Mobile Filter Drawer -->
        <Drawer
            :show="mobileFiltersOpen"
            title="Filter Fragrances"
            position="right"
            max-width="md"
            @close="mobileFiltersOpen = false"
        >
            <div class="p-4 space-y-6">
                <!-- In Stock Toggle -->
                <div class="flex items-center justify-between pb-3 border-b border-brand-border/60">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-charcoal">
                        In Stock Only
                    </span>
                    <input
                        v-model="localFilters.in_stock"
                        type="checkbox"
                        class="w-4 h-4 rounded border-brand-border text-brand-gold focus:ring-brand-gold cursor-pointer"
                    >
                </div>

                <!-- Gender -->
                <div>
                    <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-2.5">
                        Gender
                    </h3>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="g in ['men', 'women', 'unisex']"
                            :key="g"
                            type="button"
                            class="py-2 text-xs uppercase tracking-wider rounded-sm border text-center transition-colors"
                            :class="localFilters.gender === g
                                ? 'bg-brand-charcoal text-white border-brand-charcoal font-semibold'
                                : 'bg-brand-surface text-brand-charcoal border-brand-border'"
                            @click="localFilters.gender = localFilters.gender === g ? '' : g"
                        >
                            {{ g }}
                        </button>
                    </div>
                </div>

                <!-- Categories -->
                <div v-if="!currentCategory && filterOptions.categories?.length > 0">
                    <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-2.5">
                        Category
                    </h3>
                    <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                        <label class="flex items-center justify-between text-xs cursor-pointer py-1">
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="localFilters.category"
                                    type="radio"
                                    value=""
                                    name="mobile_cat"
                                    class="text-brand-gold"
                                >
                                <span>All Categories</span>
                            </div>
                        </label>
                        <label
                            v-for="cat in filterOptions.categories"
                            :key="cat.id"
                            class="flex items-center justify-between text-xs cursor-pointer py-1"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="localFilters.category"
                                    type="radio"
                                    :value="cat.slug"
                                    name="mobile_cat"
                                    class="text-brand-gold"
                                >
                                <span>{{ cat.name }}</span>
                            </div>
                            <span class="text-brand-muted text-[10px]">({{ cat.products_count }})</span>
                        </label>
                    </div>
                </div>

                <!-- Brands -->
                <div v-if="!currentBrand && filterOptions.brands?.length > 0">
                    <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-2.5">
                        Brand
                    </h3>
                    <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                        <label class="flex items-center justify-between text-xs cursor-pointer py-1">
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="localFilters.brand"
                                    type="radio"
                                    value=""
                                    name="mobile_brand"
                                    class="text-brand-gold"
                                >
                                <span>All Brands</span>
                            </div>
                        </label>
                        <label
                            v-for="b in filterOptions.brands"
                            :key="b.id"
                            class="flex items-center justify-between text-xs cursor-pointer py-1"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="localFilters.brand"
                                    type="radio"
                                    :value="b.slug"
                                    name="mobile_brand"
                                    class="text-brand-gold"
                                >
                                <span>{{ b.name }}</span>
                            </div>
                            <span class="text-brand-muted text-[10px]">({{ b.products_count }})</span>
                        </label>
                    </div>
                </div>

                <!-- Scent Family -->
                <div v-if="filterOptions.scentFamilies?.length > 0">
                    <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-2.5">
                        Scent Family
                    </h3>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="sf in filterOptions.scentFamilies"
                            :key="sf.value"
                            type="button"
                            class="py-1 px-2.5 text-xs rounded-sm border transition-colors"
                            :class="localFilters.scent_family === sf.value
                                ? 'bg-brand-gold text-white border-brand-gold'
                                : 'bg-brand-surface text-brand-charcoal border-brand-border'"
                            @click="localFilters.scent_family = localFilters.scent_family === sf.value ? '' : sf.value"
                        >
                            {{ sf.label }}
                        </button>
                    </div>
                </div>

                <!-- Price Range -->
                <div>
                    <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-2.5">
                        Price (ETB)
                    </h3>
                    <div class="grid grid-cols-2 gap-2">
                        <input
                            v-model="localFilters.price_min"
                            type="number"
                            step="500"
                            placeholder="Min ETB"
                            class="text-xs p-2 bg-brand-surface border border-brand-border rounded-sm"
                        >
                        <input
                            v-model="localFilters.price_max"
                            type="number"
                            step="500"
                            placeholder="Max ETB"
                            class="text-xs p-2 bg-brand-surface border border-brand-border rounded-sm"
                        >
                    </div>
                </div>

                <!-- Size (ml) -->
                <div v-if="filterOptions.sizes?.length > 0">
                    <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-2.5">
                        Bottle Size
                    </h3>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="s in filterOptions.sizes"
                            :key="s"
                            type="button"
                            class="py-1 px-2.5 text-xs rounded-sm border"
                            :class="Number(localFilters.size) === Number(s)
                                ? 'bg-brand-charcoal text-white border-brand-charcoal'
                                : 'bg-brand-surface text-brand-charcoal border-brand-border'"
                            @click="localFilters.size = Number(localFilters.size) === Number(s) ? '' : s"
                        >
                            {{ s }}ml
                        </button>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="pt-4 border-t border-brand-border flex gap-3">
                    <Button
                        variant="outline"
                        size="md"
                        class="w-1/2"
                        @click="clearAllFilters(); mobileFiltersOpen = false;"
                    >
                        Reset All
                    </Button>
                    <Button
                        variant="primary"
                        size="md"
                        class="w-1/2"
                        @click="applyFilters(); mobileFiltersOpen = false;"
                    >
                        Apply Filters
                    </Button>
                </div>
            </div>
        </Drawer>
    </AppLayout>
</template>
