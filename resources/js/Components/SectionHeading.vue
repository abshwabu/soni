<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    actionText: {
        type: String,
        default: '',
    },
    actionHref: {
        type: String,
        default: '',
    },
    align: {
        type: String,
        default: 'left',
        validator: (value) => ['left', 'center'].includes(value),
    },
});

const isCentered = computed(() => props.align === 'center');
</script>

<template>
    <div
        class="mb-8 md:mb-12"
        :class="[
            isCentered ? 'text-center' : 'flex flex-col md:flex-row md:items-end md:justify-between gap-4',
        ]"
    >
        <div :class="[isCentered ? 'max-w-2xl mx-auto' : 'max-w-xl']">
            <!-- Eyebrow -->
            <p
                v-if="eyebrow"
                class="text-xs uppercase tracking-extrawide text-brand-gold-800 font-sans font-bold mb-2"
            >
                {{ eyebrow }}
            </p>

            <!-- Title -->
            <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-normal text-brand-charcoal tracking-tight leading-tight">
                <slot name="title">
                    {{ title }}
                </slot>
            </h2>

            <!-- Divider line if centered -->
            <div
                v-if="isCentered"
                class="w-12 h-px bg-brand-gold mx-auto my-3"
            />

            <!-- Description -->
            <p
                v-if="description"
                class="text-brand-muted text-sm sm:text-base font-sans font-light mt-2 leading-relaxed"
            >
                {{ description }}
            </p>
        </div>

        <!-- Optional Action link -->
        <div
            v-if="actionText && actionHref"
            :class="[isCentered ? 'mt-4' : 'shrink-0']"
        >
            <Link
                :href="actionHref"
                class="group inline-flex items-center gap-1.5 text-xs uppercase tracking-widest font-medium text-brand-charcoal hover:text-brand-gold transition-colors duration-200"
            >
                <span>{{ actionText }}</span>
                <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
            </Link>
        </div>
    </div>
</template>
