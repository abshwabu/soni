<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Breadcrumbs from '../../Components/Breadcrumbs.vue';
import SectionHeading from '../../Components/SectionHeading.vue';

const props = defineProps({
    featuredBrands: {
        type: Array,
        default: () => [],
    },
    groupedBrands: {
        type: Object,
        default: () => ({}),
    },
    totalBrands: {
        type: Number,
        default: 0,
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const letters = computed(() => Object.keys(props.groupedBrands).sort());

const breadcrumbs = [
    { label: 'Home', href: '/' },
    { label: 'Brands', href: null },
];
</script>

<template>
    <AppLayout>
        <Head>
            <title>{{ seo.title || 'Fragrance Brands & Designers | Soni Perfume' }}</title>
            <meta
                name="description"
                :content="seo.description || 'Explore authentic designer and niche perfume houses in Addis Ababa.'"
            >
            <meta
                property="og:title"
                :content="seo.title"
            >
            <meta
                property="og:description"
                :content="seo.description"
            >
        </Head>

        <!-- Header Banner -->
        <section class="bg-brand-surface border-b border-brand-border/60 py-12 md:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumbs
                    :items="breadcrumbs"
                    class="mb-6"
                />

                <div class="max-w-3xl">
                    <span class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium mb-3 block">
                        The Olfactory Roster
                    </span>
                    <h1 class="font-serif text-3xl sm:text-4xl md:text-5xl font-normal text-brand-charcoal mb-4">
                        Fragrance Brands & Designers
                    </h1>
                    <p class="text-sm sm:text-base text-brand-charcoal/70 leading-relaxed font-sans">
                        Discover 100% authentic designer icons, niche artisanal houses, and Arabian treasures. Each fragrance in our collection is sourced directly and stored in climate-controlled conditions in Addis Ababa.
                    </p>
                </div>

                <!-- Alphabetical Jump Links -->
                <div class="mt-8 pt-6 border-t border-brand-border/40 flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span class="text-xs uppercase tracking-wider font-semibold text-brand-muted mr-2">Jump to:</span>
                    <a
                        v-for="letter in letters"
                        :key="letter"
                        :href="`#brand-group-${letter}`"
                        class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-sm bg-brand-bg hover:bg-brand-gold hover:text-white text-xs font-serif font-bold text-brand-charcoal border border-brand-border/60 transition-colors"
                    >
                        {{ letter }}
                    </a>
                </div>
            </div>
        </section>

        <!-- Featured Brands Showcase -->
        <section
            v-if="featuredBrands.length > 0"
            class="py-12 md:py-16 border-b border-brand-border/40"
        >
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Curated Houses"
                    title="Featured Brands"
                    subtitle="Our most requested luxury and oriental fragrance artisans"
                    align="left"
                    class="mb-8"
                />

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
                    <Link
                        v-for="brand in featuredBrands"
                        :key="brand.id"
                        :href="`/brand/${brand.slug}`"
                        class="group p-6 bg-brand-surface rounded-sm border border-brand-border/70 hover:border-brand-gold transition-all duration-300 flex flex-col items-center text-center shadow-sm hover:shadow-md"
                    >
                        <div class="w-20 h-20 sm:w-24 sm:h-24 mb-4 rounded-full bg-white p-3 flex items-center justify-center border border-brand-border/50 group-hover:scale-105 transition-transform duration-300">
                            <img
                                :src="brand.logo_url || '/images/placeholder.svg'"
                                :alt="brand.name"
                                class="max-w-full max-h-full object-contain"
                                loading="lazy"
                            >
                        </div>
                        <h3 class="font-serif text-lg font-medium text-brand-charcoal group-hover:text-brand-gold transition-colors">
                            {{ brand.name }}
                        </h3>
                        <p class="text-xs text-brand-muted mt-1">
                            {{ brand.products_count }} {{ brand.products_count === 1 ? 'Fragrance' : 'Fragrances' }}
                        </p>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Alphabetical Directory -->
        <section class="py-12 md:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div
                    v-for="letter in letters"
                    :id="`brand-group-${letter}`"
                    :key="letter"
                    class="pt-6 border-t border-brand-border/40 scroll-mt-24"
                >
                    <div class="flex items-baseline gap-4 mb-6">
                        <span class="font-serif text-3xl sm:text-4xl font-bold text-brand-gold">
                            {{ letter }}
                        </span>
                        <div class="h-px bg-brand-border/60 flex-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <Link
                            v-for="brand in groupedBrands[letter]"
                            :key="brand.id"
                            :href="`/brand/${brand.slug}`"
                            class="p-4 rounded-sm bg-brand-surface border border-brand-border/50 hover:border-brand-gold hover:bg-brand-gold/5 transition-all flex items-center justify-between group"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-white shrink-0 p-1.5 border border-brand-border/40 flex items-center justify-center">
                                    <img
                                        :src="brand.logo_url || '/images/placeholder.svg'"
                                        :alt="brand.name"
                                        class="max-w-full max-h-full object-contain"
                                        loading="lazy"
                                    >
                                </div>
                                <span class="font-serif text-base text-brand-charcoal group-hover:text-brand-gold font-medium truncate transition-colors">
                                    {{ brand.name }}
                                </span>
                            </div>
                            <span class="text-xs text-brand-muted font-sans shrink-0 ml-2">
                                {{ brand.products_count }}
                            </span>
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
