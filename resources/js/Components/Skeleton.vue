<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'custom',
        validator: (value) => ['custom', 'text', 'image', 'button', 'card'].includes(value),
    },
    width: {
        type: String,
        default: 'w-full',
    },
    height: {
        type: String,
        default: 'h-4',
    },
    rounded: {
        type: String,
        default: 'rounded-sm',
    },
    lines: {
        type: Number,
        default: 3,
    },
});

const isCard = computed(() => props.type === 'card');
const isText = computed(() => props.type === 'text');
const isImage = computed(() => props.type === 'image');
const isButton = computed(() => props.type === 'button');
</script>

<template>
    <!-- Full Product Card Skeleton -->
    <div
        v-if="isCard"
        class="flex flex-col gap-3 w-full"
    >
        <div class="relative aspect-[3/4] w-full bg-brand-surface-warm/80 rounded-sm overflow-hidden animate-pulseSubtle">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full animate-shimmer" />
        </div>
        <div class="h-3 w-1/3 bg-brand-border/60 rounded-sm animate-pulseSubtle" />
        <div class="h-5 w-4/5 bg-brand-border/80 rounded-sm animate-pulseSubtle" />
        <div class="h-4 w-1/2 bg-brand-border/50 rounded-sm animate-pulseSubtle" />
    </div>

    <!-- Text Lines Skeleton -->
    <div
        v-else-if="isText"
        class="flex flex-col gap-2.5 w-full"
    >
        <div
            v-for="i in lines"
            :key="i"
            class="h-3.5 bg-brand-border/70 rounded-sm animate-pulseSubtle"
            :class="[i === lines ? 'w-2/3' : 'w-full']"
        />
    </div>

    <!-- Image Skeleton -->
    <div
        v-else-if="isImage"
        class="aspect-[3/4] w-full bg-brand-surface-warm/80 rounded-sm overflow-hidden relative animate-pulseSubtle"
    >
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full animate-shimmer" />
    </div>

    <!-- Button Skeleton -->
    <div
        v-else-if="isButton"
        class="h-11 w-full bg-brand-border/80 rounded-sm animate-pulseSubtle"
    />

    <!-- Custom element -->
    <div
        v-else
        class="bg-brand-border/70 animate-pulseSubtle relative overflow-hidden"
        :class="[width, height, rounded]"
    >
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full animate-shimmer" />
    </div>
</template>
