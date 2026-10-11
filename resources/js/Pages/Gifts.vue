<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import SeoHead from '../Components/SeoHead.vue';
import ProductCard from '../Components/ProductCard.vue';
import SectionHeading from '../Components/SectionHeading.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';

const props = defineProps({
    forHim: {
        type: Array,
        default: () => [],
    },
    forHer: {
        type: Array,
        default: () => [],
    },
    under3000: {
        type: Array,
        default: () => [],
    },
    luxury: {
        type: Array,
        default: () => [],
    },
    giftSets: {
        type: Array,
        default: () => [],
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const activeSection = ref('all');

const scrollToSection = (id) => {
    activeSection.value = id;
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const breadcrumbs = [
    { label: 'Home', url: '/' },
    { label: 'Gift Guide', url: null },
];
</script>

<template>
    <AppLayout>
        <SeoHead
            :title="seo.title || 'Fragrance Gift Guide'"
            :description="seo.description || 'Curated luxury perfume gifts in Addis Ababa: for him, for her, under ETB 3,000, and luxury statement scents.'"
            :url="seo.url"
        />

        <!-- Breadcrumbs -->
        <div class="border-b border-brand-border/60 bg-brand-surface py-3">
            <div class="container mx-auto px-4">
                <Breadcrumbs :items="breadcrumbs" />
            </div>
        </div>

        <!-- Hero Header -->
        <header class="relative bg-brand-dark text-brand-bg py-16 md:py-24 text-center">
            <div class="container mx-auto px-4 max-w-3xl">
                <p class="text-xs uppercase tracking-extrawide text-brand-gold font-sans font-semibold mb-3">
                    Curated Presents • Addis Ababa
                </p>
                <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl font-light tracking-wide text-brand-bg leading-tight">
                    The Fragrance Gift Guide
                </h1>
                <p class="mt-4 text-sm md:text-base text-brand-bg/80 font-sans font-light leading-relaxed">
                    Giving perfume is giving memory. Discover our handpicked edits of exquisite perfumes guaranteed to delight.
                </p>

                <!-- Gift Guarantee Callout -->
                <div class="mt-8 inline-flex items-center gap-3 bg-white/10 backdrop-blur-sm px-5 py-2.5 rounded-full border border-white/15 text-xs text-brand-bg">
                    <span>🎁</span>
                    <span>Complimentary gift box packaging & custom handwritten card available on all orders</span>
                </div>
            </div>
        </header>

        <!-- Sticky Section Navigation Filter Bar -->
        <nav
            class="sticky top-16 md:top-20 z-20 bg-brand-bg/95 backdrop-blur-md border-b border-brand-border py-3 shadow-xs"
            aria-label="Gift Guide Categories"
        >
            <div class="container mx-auto px-4 flex items-center justify-center gap-2 sm:gap-4 overflow-x-auto no-scrollbar">
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-full text-xs uppercase tracking-wider font-medium transition-colors shrink-0"
                    :class="activeSection === 'him' ? 'bg-brand-gold text-white' : 'bg-brand-surface text-brand-charcoal hover:bg-brand-gold/15 border border-brand-border'"
                    @click="scrollToSection('him')"
                >
                    For Him
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-full text-xs uppercase tracking-wider font-medium transition-colors shrink-0"
                    :class="activeSection === 'her' ? 'bg-brand-gold text-white' : 'bg-brand-surface text-brand-charcoal hover:bg-brand-gold/15 border border-brand-border'"
                    @click="scrollToSection('her')"
                >
                    For Her
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-full text-xs uppercase tracking-wider font-medium transition-colors shrink-0"
                    :class="activeSection === 'budget' ? 'bg-brand-gold text-white' : 'bg-brand-surface text-brand-charcoal hover:bg-brand-gold/15 border border-brand-border'"
                    @click="scrollToSection('budget')"
                >
                    Under ETB 3,000
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-full text-xs uppercase tracking-wider font-medium transition-colors shrink-0"
                    :class="activeSection === 'luxury' ? 'bg-brand-gold text-white' : 'bg-brand-surface text-brand-charcoal hover:bg-brand-gold/15 border border-brand-border'"
                    @click="scrollToSection('luxury')"
                >
                    Luxury & Niche
                </button>
                <button
                    v-if="giftSets && giftSets.length > 0"
                    type="button"
                    class="px-3.5 py-1.5 rounded-full text-xs uppercase tracking-wider font-medium transition-colors shrink-0"
                    :class="activeSection === 'sets' ? 'bg-brand-gold text-white' : 'bg-brand-surface text-brand-charcoal hover:bg-brand-gold/15 border border-brand-border'"
                    @click="scrollToSection('sets')"
                >
                    Gift Sets
                </button>
            </div>
        </nav>

        <!-- Main Curated Grids -->
        <main class="py-12 md:py-20 space-y-24">
            <div class="container mx-auto px-4">
                <!-- Section 1: For Him -->
                <section id="him" class="scroll-mt-36">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                        <SectionHeading
                            badge="Curated Collection"
                            title="Signature Scents For Him"
                            subtitle="Woody, aromatic, and charismatic fragrances tailored for modern gentlemen"
                            alignment="left"
                            class="mb-0"
                        />
                        <Link
                            href="/shop?gender=men"
                            class="text-xs uppercase tracking-wider font-semibold text-brand-gold hover:underline whitespace-nowrap"
                        >
                            View All Men's Fragrances →
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                        <ProductCard
                            v-for="p in forHim"
                            :key="p.id"
                            :product="p"
                        />
                    </div>
                </section>

                <!-- Section 2: For Her -->
                <section id="her" class="scroll-mt-36 pt-16 border-t border-brand-border">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                        <SectionHeading
                            badge="Curated Collection"
                            title="Enchanting Fragrances For Her"
                            subtitle="Floral, amber, and gourmand scents exuding warmth, poise, and sophistication"
                            alignment="left"
                            class="mb-0"
                        />
                        <Link
                            href="/shop?gender=women"
                            class="text-xs uppercase tracking-wider font-semibold text-brand-gold hover:underline whitespace-nowrap"
                        >
                            View All Women's Fragrances →
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                        <ProductCard
                            v-for="p in forHer"
                            :key="p.id"
                            :product="p"
                        />
                    </div>
                </section>

                <!-- Section 3: Under ETB 3,000 -->
                <section id="budget" class="scroll-mt-36 pt-16 border-t border-brand-border">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                        <SectionHeading
                            badge="Everyday Luxury"
                            title="Gifts Under ETB 3,000"
                            subtitle="Exceptional fragrances and travel sizes offering remarkable value without compromise"
                            alignment="left"
                            class="mb-0"
                        />
                        <Link
                            href="/shop?price_max=3000"
                            class="text-xs uppercase tracking-wider font-semibold text-brand-gold hover:underline whitespace-nowrap"
                        >
                            View All Budget Edits →
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                        <ProductCard
                            v-for="p in under3000"
                            :key="p.id"
                            :product="p"
                        />
                    </div>
                </section>

                <!-- Section 4: Luxury & Niche -->
                <section id="luxury" class="scroll-mt-36 pt-16 border-t border-brand-border">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                        <SectionHeading
                            badge="High Sillage & Extrait"
                            title="Luxury & Statement Bottles"
                            subtitle="Grand Parfums, pure attars, and royal oud creations for unforgettable milestones"
                            alignment="left"
                            class="mb-0"
                        />
                        <Link
                            href="/shop?price_min=7000"
                            class="text-xs uppercase tracking-wider font-semibold text-brand-gold hover:underline whitespace-nowrap"
                        >
                            View Luxury Collection →
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                        <ProductCard
                            v-for="p in luxury"
                            :key="p.id"
                            :product="p"
                        />
                    </div>
                </section>

                <!-- Section 5: Gift Sets (if available) -->
                <section v-if="giftSets && giftSets.length > 0" id="sets" class="scroll-mt-36 pt-16 border-t border-brand-border">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                        <SectionHeading
                            badge="Boxed Edits"
                            title="Curated Gift Sets"
                            subtitle="Perfume paired with matching body lotions, shower gels, and miniature vials"
                            alignment="left"
                            class="mb-0"
                        />
                        <Link
                            href="/category/gift-sets"
                            class="text-xs uppercase tracking-wider font-semibold text-brand-gold hover:underline whitespace-nowrap"
                        >
                            View All Gift Sets →
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                        <ProductCard
                            v-for="p in giftSets"
                            :key="p.id"
                            :product="p"
                        />
                    </div>
                </section>
            </div>
        </main>
    </AppLayout>
</template>
