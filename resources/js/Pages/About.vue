<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import SeoHead from '../Components/SeoHead.vue';
import SectionHeading from '../Components/SectionHeading.vue';
import Button from '../Components/Button.vue';

const props = defineProps({
    page: {
        type: Object,
        default: null,
    },
    storyImages: {
        type: Array,
        default: () => [],
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const defaultContent = `
<h2>The Soni Perfume Legacy</h2>
<p>Founded in the vibrant heart of Addis Ababa, <strong>Soni Perfume</strong> is Ethiopia’s premier boutique destination for genuine international designer fragrances, bespoke Arabian ouds, and niche artisanal extraits.</p>

<h3>Our Philosophy: Authenticity Without Compromise</h3>
<p>In a fragrance landscape frequently clouded by replicas and diluted oils, Soni Perfume was born out of an uncompromising commitment: <strong>every single bottle on our shelves is 100% original, sealed, and traceable directly to authorized European and Gulf distributors</strong>.</p>

<h3>The Addis Ababa Experience</h3>
<p>From our flagship showroom located in Bole Medhanialem to our direct-to-door express delivery service across Addis Ababa, we treat fragrance shopping as a deeply personal sensory journey. Whether you are searching for a signature daily scent, a regal oriental oud for grand celebrations, or a treasured gift for someone special, our dedicated perfume consultants guide you through top notes, sillage, and scent chemistry.</p>
`;

const renderedHtml = computed(() => props.page?.content || defaultContent);
</script>

<template>
    <AppLayout>
        <SeoHead
            :title="seo.title || 'About Our Fragrance Boutique'"
            :description="seo.description || 'Addis Ababa’s premier boutique destination for authentic luxury designer perfumes and rare oriental scents.'"
            :image="seo.image || storyImages[0]?.url"
            :url="seo.url"
        />

        <!-- Hero Header -->
        <section class="relative bg-brand-dark text-brand-bg py-20 md:py-28 overflow-hidden">
            <div class="absolute inset-0 opacity-15">
                <img
                    :src="storyImages[0]?.url || 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=1600&q=80'"
                    alt="Soni Perfume Boutique background"
                    class="w-full h-full object-cover"
                >
            </div>
            <div class="relative container mx-auto px-4 text-center max-w-3xl">
                <p class="text-xs uppercase tracking-extrawide text-brand-gold font-sans font-semibold mb-3">
                    Addis Ababa • Ethiopia
                </p>
                <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl font-light tracking-wide text-brand-bg leading-tight">
                    The Art of Pure Scent
                </h1>
                <p class="mt-6 text-sm md:text-base text-brand-bg/80 font-sans font-light leading-relaxed">
                    Dedicated exclusively to authentic luxury designer perfumes, rare oriental ouds, and artisanal French extraits.
                </p>
            </div>
        </section>

        <!-- Values Strip -->
        <section class="border-b border-brand-border bg-brand-surface py-12">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center p-6 space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-full bg-brand-gold/10 text-brand-gold flex items-center justify-center text-xl">
                            ✨
                        </div>
                        <h2 class="font-serif text-xl text-brand-charcoal font-medium">
                            100% Genuine Guaranteed
                        </h2>
                        <p class="text-xs text-brand-muted leading-relaxed">
                            Zero counterfeit tolerance. Factory sealed bottles directly imported from authorized houses in France, Italy, and the UAE.
                        </p>
                    </div>

                    <div class="text-center p-6 space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-full bg-brand-gold/10 text-brand-gold flex items-center justify-center text-xl">
                            🚚
                        </div>
                        <h2 class="font-serif text-xl text-brand-charcoal font-medium">
                            Express Addis Delivery
                        </h2>
                        <p class="text-xs text-brand-muted leading-relaxed">
                            Same-day courier dispatch across Addis Ababa with pay-on-delivery inspection before payment.
                        </p>
                    </div>

                    <div class="text-center p-6 space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-full bg-brand-gold/10 text-brand-gold flex items-center justify-center text-xl">
                            👑
                        </div>
                        <h2 class="font-serif text-xl text-brand-charcoal font-medium">
                            Personal Fragrance Concierge
                        </h2>
                        <p class="text-xs text-brand-muted leading-relaxed">
                            Need help selecting a signature scent or luxury gift? Chat directly with our consultants via Telegram or WhatsApp.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Narrative & Imagery -->
        <section class="py-16 md:py-24">
            <div class="container mx-auto px-4 max-w-5xl">
                <!-- Split Story Block -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center mb-20">
                    <div class="lg:col-span-7 space-y-6">
                        <SectionHeading
                            badge="Our Story"
                            title="A Passion for Authentic Fragrance"
                            subtitle="Elevating fragrance shopping in Addis Ababa"
                            alignment="left"
                        />
                        <div
                            class="prose prose-stone prose-sm md:prose-base font-sans font-light leading-relaxed text-brand-charcoal/90"
                            v-html="renderedHtml"
                        />

                        <div class="pt-4 flex flex-wrap gap-4">
                            <Button
                                as="a"
                                href="/shop"
                                variant="primary"
                                size="md"
                            >
                                Explore Fragrance Collection
                            </Button>
                            <Button
                                as="a"
                                href="/contact"
                                variant="secondary"
                                size="md"
                            >
                                Visit Boutique in Bole
                            </Button>
                        </div>
                    </div>

                    <div class="lg:col-span-5 space-y-4">
                        <div class="relative rounded-sm overflow-hidden shadow-2xl border border-brand-border aspect-[3/4]">
                            <img
                                :src="storyImages[0]?.url || 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=800&q=80'"
                                :alt="storyImages[0]?.alt || 'Boutique fragrance showroom'"
                                class="w-full h-full object-cover hover:scale-105 transition-transform duration-700"
                                loading="lazy"
                            >
                        </div>
                        <p class="text-[11px] font-sans text-brand-muted text-center italic">
                            {{ storyImages[0]?.caption || 'Soni Perfume Showroom • Bole Medhanialem' }}
                        </p>
                    </div>
                </div>

                <!-- Boutique Visual Gallery -->
                <div class="border-t border-brand-border pt-16">
                    <div class="text-center mb-10">
                        <h2 class="font-serif text-3xl font-light text-brand-charcoal">
                            Inside The Boutique
                        </h2>
                        <p class="text-xs uppercase tracking-widest text-brand-gold mt-1 font-sans">
                            Curated Fragrance Displays
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            v-for="(img, idx) in storyImages"
                            :key="idx"
                            class="group relative overflow-hidden rounded-sm border border-brand-border bg-brand-surface shadow-sm"
                        >
                            <div class="aspect-[4/3] overflow-hidden">
                                <img
                                    :src="img.url"
                                    :alt="img.alt"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                >
                            </div>
                            <div class="p-3 text-center bg-brand-surface">
                                <p class="text-xs font-serif text-brand-charcoal font-medium">
                                    {{ img.caption }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
