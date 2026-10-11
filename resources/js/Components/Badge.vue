<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'gold',
        validator: (value) => [
            'gold',
            'dark',
            'blush',
            'outline',
            'success',
            'danger',
            'warning',
        ].includes(value),
    },
    size: {
        type: String,
        default: 'sm',
        validator: (value) => ['xs', 'sm', 'md'].includes(value),
    },
});

const sizeClasses = computed(() => {
    return {
        xs: 'px-1.5 py-0.5 text-[9px]',
        sm: 'px-2 py-0.5 text-[10px]',
        md: 'px-2.5 py-1 text-xs',
    }[props.size];
});

const variantClasses = computed(() => {
    return {
        gold: 'bg-brand-gold text-brand-dark font-semibold',
        dark: 'bg-brand-charcoal text-brand-bg',
        blush: 'bg-brand-blush text-brand-charcoal',
        outline: 'border border-brand-border text-brand-charcoal bg-brand-surface/90',
        success: 'bg-emerald-800 text-emerald-50',
        danger: 'bg-rose-800 text-rose-50',
        warning: 'bg-amber-700 text-amber-50',
    }[props.variant];
});
</script>

<template>
    <span
        class="inline-flex items-center justify-center font-sans font-medium uppercase tracking-widest rounded-sm shadow-none"
        :class="[sizeClasses, variantClasses]"
    >
        <slot />
    </span>
</template>
