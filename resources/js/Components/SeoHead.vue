<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: 'Soni Perfume is Addis Ababa’s premier boutique for 100% authentic international designer perfumes, niche attars, and luxury scents with fast same-day delivery.',
    },
    image: {
        type: String,
        default: '/images/og-soni.jpg',
    },
    url: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'website',
    },
    jsonLd: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const storeName = computed(() => page.props.settings?.store_name || 'Soni Perfume');

const fullTitle = computed(() => {
    if (!props.title) return storeName.value;
    return props.title.includes(storeName.value) ? props.title : `${props.title} | ${storeName.value}`;
});

const canonicalUrl = computed(() => {
    if (props.url) return props.url;
    if (typeof window !== 'undefined') return window.location.href;
    return '';
});

const resolvedImage = computed(() => {
    if (props.image && props.image.startsWith('http')) return props.image;
    const base = typeof window !== 'undefined' ? window.location.origin : '';
    return `${base}${props.image || '/images/placeholder.svg'}`;
});
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description">

        <!-- Canonical Link -->
        <link v-if="canonicalUrl" rel="canonical" :href="canonicalUrl">

        <!-- OpenGraph (Facebook / WhatsApp / Telegram) -->
        <meta property="og:site_name" :content="storeName">
        <meta property="og:type" :content="type">
        <meta property="og:title" :content="fullTitle">
        <meta property="og:description" :content="description">
        <meta property="og:image" :content="resolvedImage">
        <meta v-if="canonicalUrl" property="og:url" :content="canonicalUrl">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" :content="fullTitle">
        <meta name="twitter:description" :content="description">
        <meta name="twitter:image" :content="resolvedImage">

        <!-- Structured Data (JSON-LD) -->
        <component
            :is="'script'"
            v-if="jsonLd"
            type="application/ld+json"
        >
            {{ JSON.stringify(jsonLd) }}
        </component>
    </Head>
</template>
