<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import SeoHead from '../Components/SeoHead.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';

const props = defineProps({
    page: {
        type: Object,
        required: true,
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const breadcrumbs = computed(() => [
    { label: 'Home', url: '/' },
    { label: props.page.title, url: null },
]);

const formattedDate = computed(() => {
    if (!props.page.updated_at) return '';
    const d = new Date(props.page.updated_at);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
});
</script>

<template>
    <AppLayout>
        <SeoHead
            :title="seo.title || page.title"
            :description="seo.description || page.meta_description"
            :url="seo.url"
        />

        <!-- Top Breadcrumbs Bar -->
        <div class="border-b border-brand-border/60 bg-brand-surface py-3">
            <div class="container mx-auto px-4">
                <Breadcrumbs :items="breadcrumbs" />
            </div>
        </div>

        <!-- Header -->
        <header class="bg-brand-surface border-b border-brand-border py-12 md:py-16">
            <div class="container mx-auto px-4 text-center max-w-3xl">
                <p class="text-xs uppercase tracking-widest text-brand-gold font-sans font-semibold mb-2">
                    Soni Perfume Policy & Information
                </p>
                <h1 class="font-serif text-3xl sm:text-4xl md:text-5xl font-light text-brand-charcoal tracking-wide">
                    {{ page.title }}
                </h1>
                <p v-if="formattedDate" class="text-xs text-brand-muted mt-3">
                    Effective & Last Updated: {{ formattedDate }}
                </p>
            </div>
        </header>

        <!-- Page Body Content -->
        <main class="py-12 md:py-16">
            <div class="container mx-auto px-4 max-w-3xl">
                <article
                    class="prose prose-stone prose-sm md:prose-base font-sans font-light leading-relaxed text-brand-charcoal/90 max-w-none
                           prose-headings:font-serif prose-headings:font-normal prose-headings:text-brand-charcoal
                           prose-h2:text-2xl prose-h2:border-b prose-h2:border-brand-border prose-h2:pb-2 prose-h2:mt-8
                           prose-h3:text-xl prose-h3:mt-6
                           prose-a:text-brand-gold prose-a:underline hover:prose-a:text-brand-charcoal
                           prose-strong:font-semibold prose-strong:text-brand-charcoal"
                    v-html="page.content"
                />

                <!-- Support Footer Callout -->
                <div class="mt-16 pt-8 border-t border-brand-border flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-brand-muted">
                    <p>Have questions about this policy? Contact our Addis Ababa concierge.</p>
                    <Link
                        href="/contact"
                        class="text-xs uppercase tracking-widest font-semibold text-brand-gold hover:underline"
                    >
                        Contact Store Support →
                    </Link>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
