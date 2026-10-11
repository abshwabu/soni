<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import ProductCard from '../Components/ProductCard.vue';
import SectionHeading from '../Components/SectionHeading.vue';
import Button from '../Components/Button.vue';
import Badge from '../Components/Badge.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';
import Rating from '../Components/Rating.vue';
import QuantityStepper from '../Components/QuantityStepper.vue';
import Skeleton from '../Components/Skeleton.vue';

const props = defineProps({
    featuredProducts: {
        type: Array,
        default: () => [],
    },
    banners: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    testimonials: {
        type: Array,
        default: () => [],
    },
});

const demoStepper = ref(2);
const activeTab = ref('all');
</script>

<template>
    <AppLayout>
        <Head title="Curated Luxury Perfumes in Addis Ababa" />

        <!-- 1. Hero Banner Section -->
        <section class="relative bg-brand-surface-warm/60 border-b border-brand-border/60 overflow-hidden">
            <div class="container mx-auto py-16 sm:py-20 md:py-28 px-4 sm:px-6">
                <div class="max-w-3xl mx-auto text-center space-y-6">
                    <!-- Eyebrow -->
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-brand-surface border border-brand-border/80 rounded-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-gold" />
                        <span class="text-[11px] font-sans uppercase tracking-widest text-brand-charcoal font-semibold">
                            Authentic Luxury Fragrances • Addis Ababa
                        </span>
                    </div>

                    <!-- Main Heading in Cormorant Garamond -->
                    <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-normal text-brand-charcoal leading-[1.08] tracking-tight">
                        Curated Scents for the <br class="hidden sm:inline">
                        <span class="italic text-brand-gold font-normal">Discerning</span> Collector
                    </h1>

                    <!-- Description -->
                    <p class="text-sm sm:text-base md:text-lg text-brand-muted font-sans font-light max-w-xl mx-auto leading-relaxed">
                        Hand-selected designer and royal Arabian perfumes sourced directly from Paris, Italy, and Dubai. 100% authentic with same-day doorstep delivery across Addis Ababa.
                    </p>

                    <!-- CTAs -->
                    <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
                        <Button
                            href="/shop"
                            variant="primary"
                            size="lg"
                            class="w-full sm:w-auto"
                        >
                            Explore Collection
                        </Button>

                        <Button
                            href="/brands"
                            variant="outline"
                            size="lg"
                            class="w-full sm:w-auto"
                        >
                            Discover Brands
                        </Button>
                    </div>

                    <!-- Trust Points -->
                    <div class="pt-8 flex flex-wrap items-center justify-center gap-6 sm:gap-10 text-xs text-brand-muted font-sans font-medium">
                        <div class="flex items-center gap-2">
                            <span class="text-brand-gold">✦</span>
                            <span>100% Genuine Guaranteed</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-brand-gold">✦</span>
                            <span>Same-Day Addis Delivery</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-brand-gold">✦</span>
                            <span>Order via Telegram & WhatsApp</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Value Propositions Bar -->
        <section class="border-b border-brand-border/60 bg-brand-surface py-8">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-sm bg-brand-surface-warm border border-brand-border flex items-center justify-center shrink-0 text-brand-gold">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wider font-sans text-brand-charcoal">
                                100% Authentic
                            </h4>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                Verified direct factory batches
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-sm bg-brand-surface-warm border border-brand-border flex items-center justify-center shrink-0 text-brand-gold">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75m0 3.75h4.125c.621 0 1.125.504 1.125 1.125v4.5"
                                />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wider font-sans text-brand-charcoal">
                                Express Delivery
                            </h4>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                Same-day across Addis Ababa
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-sm bg-brand-surface-warm border border-brand-border flex items-center justify-center shrink-0 text-brand-gold">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m0 0v8.25m0 0h.75a.75.75 0 01.75.75v.75m0 0h16.5m-18 0v-8.25m18 8.25v-8.25"
                                />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wider font-sans text-brand-charcoal">
                                Zero Payment Hassle
                            </h4>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                Orders confirmed via Telegram
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-sm bg-brand-surface-warm border border-brand-border flex items-center justify-center shrink-0 text-brand-gold">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H4.5a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21"
                                />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wider font-sans text-brand-charcoal">
                                Luxury Gift Wrap
                            </h4>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                Complimentary bespoke box
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Featured Categories Showcase -->
        <section class="py-14 sm:py-20 container mx-auto px-4 sm:px-6">
            <SectionHeading
                eyebrow="Olfactory Families"
                title="Explore By Collection"
                description="Discover fragrances categorised by personality, occasion, and scent notes."
                action-text="All Categories"
                action-href="/shop"
            />

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                <Link
                    v-for="cat in categories"
                    :key="cat.id"
                    :href="`/category/${cat.slug}`"
                    class="group relative bg-brand-surface border border-brand-border p-4 text-center rounded-sm hover:border-brand-gold transition-all duration-300 hover:shadow-md flex flex-col items-center justify-center aspect-[4/5]"
                >
                    <div class="w-12 h-12 rounded-full bg-brand-surface-warm group-hover:bg-brand-gold/10 flex items-center justify-center mb-3 text-brand-gold transition-colors">
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.3"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"
                            />
                        </svg>
                    </div>

                    <h3 class="font-serif text-base sm:text-lg font-normal text-brand-charcoal group-hover:text-brand-gold transition-colors">
                        {{ cat.name }}
                    </h3>

                    <span class="text-[10px] uppercase tracking-wider text-brand-muted mt-1 font-sans">
                        Explore →
                    </span>
                </Link>
            </div>
        </section>

        <!-- 4. Featured Products Grid (Showcases ProductCard with Hover Swap & Quick Add) -->
        <section class="py-14 sm:py-20 bg-brand-surface/40 border-y border-brand-border/60">
            <div class="container mx-auto px-4 sm:px-6">
                <SectionHeading
                    eyebrow="Curated Selection"
                    title="Featured Masterpieces"
                    description="Signature fragrances that define elegance and personal aura."
                    action-text="View All Fragrances"
                    action-href="/shop"
                />

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
                    <ProductCard
                        v-for="product in featuredProducts"
                        :key="product.id"
                        :product="product"
                    />
                </div>
            </div>
        </section>

        <!-- 5. Design System & Component Library Showcase -->
        <section class="py-14 sm:py-20 container mx-auto px-4 sm:px-6">
            <SectionHeading
                align="center"
                eyebrow="Design System Showcase"
                title="Shared UI Components"
                description="Living style guide verifying tokens, button variants, badges, steppers, and ratings across breakpoints."
            />

            <div class="bg-brand-surface border border-brand-border rounded-sm p-6 sm:p-10 space-y-12 shadow-xs">
                <!-- Breadcrumbs demo -->
                <div class="space-y-2">
                    <h4 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                        Breadcrumbs
                    </h4>
                    <Breadcrumbs :items="[{ label: 'Fragrances', href: '/shop' }, { label: 'Oud & Arabian', href: '/category/oud-arabian' }, { label: 'MFK Oud Satin Mood' }]" />
                </div>

                <!-- Button Variants -->
                <div class="space-y-4">
                    <h4 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                        Button Component (Variants & Sizes)
                    </h4>
                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            variant="primary"
                            size="md"
                        >
                            Primary Gold
                        </Button>
                        <Button
                            variant="dark"
                            size="md"
                        >
                            Dark Charcoal
                        </Button>
                        <Button
                            variant="secondary"
                            size="md"
                        >
                            Secondary Blush
                        </Button>
                        <Button
                            variant="outline"
                            size="md"
                        >
                            Outline Dark
                        </Button>
                        <Button
                            variant="outline-gold"
                            size="md"
                        >
                            Outline Gold
                        </Button>
                        <Button
                            variant="ghost"
                            size="md"
                        >
                            Ghost Button
                        </Button>
                        <Button
                            variant="primary"
                            size="sm"
                            loading
                        >
                            Loading
                        </Button>
                    </div>
                </div>

                <!-- Badges -->
                <div class="space-y-4">
                    <h4 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                        Badge Component
                    </h4>
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            variant="gold"
                            size="md"
                        >
                            New Arrival
                        </Badge>
                        <Badge
                            variant="dark"
                            size="md"
                        >
                            Bestseller
                        </Badge>
                        <Badge
                            variant="blush"
                            size="md"
                        >
                            Special Offer
                        </Badge>
                        <Badge
                            variant="outline"
                            size="md"
                        >
                            Extrait de Parfum
                        </Badge>
                        <Badge
                            variant="success"
                            size="md"
                        >
                            In Stock
                        </Badge>
                        <Badge
                            variant="danger"
                            size="md"
                        >
                            Sold Out
                        </Badge>
                    </div>
                </div>

                <!-- Quantity Stepper & Rating -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 pt-4 border-t border-brand-border">
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                            Quantity Stepper (Live Bound)
                        </h4>
                        <div class="flex items-center gap-4">
                            <QuantityStepper
                                v-model="demoStepper"
                                :min="1"
                                :max="10"
                            />
                            <span class="text-xs font-sans text-brand-muted">Current Quantity: <strong>{{ demoStepper }}</strong></span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                            Rating Component
                        </h4>
                        <div class="flex items-center gap-4">
                            <Rating
                                :value="4.9"
                                :count="38"
                                size="md"
                                show-value
                            />
                        </div>
                    </div>
                </div>

                <!-- Skeleton preview -->
                <div class="space-y-3 pt-4 border-t border-brand-border">
                    <h4 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                        Skeleton Shimmer Loading States
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <Skeleton
                            type="card"
                            class="w-full"
                        />
                        <div class="space-y-3 col-span-1 sm:col-span-3">
                            <Skeleton
                                type="text"
                                :lines="4"
                            />
                            <div class="w-48">
                                <Skeleton type="button" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. Customer Testimonials -->
        <section
            v-if="testimonials.length > 0"
            class="py-14 sm:py-20 bg-brand-surface-warm/40 border-t border-brand-border/60"
        >
            <div class="container mx-auto px-4 sm:px-6">
                <SectionHeading
                    align="center"
                    eyebrow="Client Experiences"
                    title="Voices of Elegance"
                    description="What our distinguished customers in Addis Ababa share about Soni Perfume."
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="test in testimonials"
                        :key="test.id"
                        class="bg-brand-surface border border-brand-border p-6 rounded-sm space-y-4 shadow-2xs"
                    >
                        <Rating
                            :value="test.rating"
                            size="sm"
                        />
                        <p class="font-serif text-base sm:text-lg text-brand-charcoal italic leading-relaxed">
                            "{{ test.text }}"
                        </p>
                        <div class="pt-2 border-t border-brand-border/60 flex items-center justify-between">
                            <span class="text-xs font-semibold font-sans uppercase tracking-wider text-brand-charcoal">
                                {{ test.name }}
                            </span>
                            <span class="text-[10px] text-brand-muted font-sans">Verified Client • Addis Ababa</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
