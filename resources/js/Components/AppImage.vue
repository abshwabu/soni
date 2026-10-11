<script setup>
import { ref } from 'vue';

const props = defineProps({
    src: {
        type: String,
        default: '',
    },
    webpSrc: {
        type: String,
        default: '',
    },
    alt: {
        type: String,
        default: 'Soni Perfume',
    },
    aspectRatio: {
        type: String,
        default: 'aspect-[3/4]',
    },
    fit: {
        type: String,
        default: 'object-cover',
    },
    width: {
        type: [Number, String],
        default: null,
    },
    height: {
        type: [Number, String],
        default: null,
    },
    lazy: {
        type: Boolean,
        default: true,
    },
});

const isLoaded = ref(false);
const hasError = ref(false);

const handleLoad = () => {
    isLoaded.value = true;
};

const handleError = () => {
    hasError.value = true;
    isLoaded.value = true;
};
</script>

<template>
    <div
        class="relative overflow-hidden bg-brand-surface-warm/80"
        :class="[aspectRatio]"
    >
        <!-- Placeholder / Blur shimmer -->
        <div
            v-if="!isLoaded"
            class="absolute inset-0 bg-gradient-to-tr from-brand-blush/30 via-brand-surface-warm to-brand-border/40 animate-pulseSubtle"
            aria-hidden="true"
        />

        <!-- Fallback if error or missing src -->
        <div
            v-if="hasError || !src"
            class="absolute inset-0 flex flex-col items-center justify-center p-4 text-brand-muted bg-brand-surface-warm"
        >
            <svg
                class="w-10 h-10 text-brand-gold/50 mb-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                />
            </svg>
            <span class="text-xs uppercase tracking-widest text-brand-muted/70 font-sans">Soni Perfume</span>
        </div>

        <!-- Picture tag with WebP source support -->
        <picture v-else>
            <source
                v-if="webpSrc"
                :srcset="webpSrc"
                type="image/webp"
            >
            <img
                :src="src"
                :alt="alt"
                :width="width"
                :height="height"
                :loading="lazy ? 'lazy' : 'eager'"
                decoding="async"
                class="w-full h-full transition-all duration-700 ease-out"
                :class="[
                    fit,
                    isLoaded ? 'opacity-100 scale-100' : 'opacity-0 scale-105',
                ]"
                @load="handleLoad"
                @error="handleError"
            >
        </picture>
    </div>
</template>
