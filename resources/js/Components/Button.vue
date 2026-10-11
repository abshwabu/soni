<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        default: null,
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => [
            'primary',
            'dark',
            'secondary',
            'outline',
            'outline-gold',
            'ghost',
            'danger',
        ].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value),
    },
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    block: {
        type: Boolean,
        default: false,
    },
});

const baseClasses = computed(() => {
    return [
        'inline-flex items-center justify-center font-sans font-medium uppercase transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed select-none rounded-sm',
        props.block ? 'w-full' : '',
        props.disabled || props.loading ? 'opacity-60 pointer-events-none' : '',
    ];
});

const sizeClasses = computed(() => {
    return {
        xs: 'px-2.5 py-1 text-[10px] tracking-wider',
        sm: 'px-3.5 py-1.5 text-xs tracking-wider',
        md: 'px-5 py-2.5 text-xs md:text-sm tracking-widest',
        lg: 'px-7 py-3.5 text-sm tracking-widest',
    }[props.size];
});

const variantClasses = computed(() => {
    return {
        primary: 'bg-brand-gold text-white hover:bg-brand-gold-600 active:bg-brand-gold-700 focus:ring-brand-gold/50 shadow-sm',
        dark: 'bg-brand-charcoal text-brand-bg hover:bg-black active:bg-neutral-900 focus:ring-brand-charcoal/50 shadow-sm',
        secondary: 'bg-brand-blush/60 text-brand-charcoal hover:bg-brand-blush focus:ring-brand-blush',
        outline: 'border border-brand-charcoal text-brand-charcoal hover:bg-brand-charcoal hover:text-brand-bg focus:ring-brand-charcoal/30 bg-transparent',
        'outline-gold': 'border border-brand-gold text-brand-gold hover:bg-brand-gold hover:text-white focus:ring-brand-gold/30 bg-transparent',
        ghost: 'bg-transparent text-brand-charcoal hover:bg-brand-gold/10 hover:text-brand-gold focus:ring-brand-gold/20',
        danger: 'bg-rose-700 text-white hover:bg-rose-800 focus:ring-rose-500',
    }[props.variant];
});
</script>

<template>
    <Link
        v-if="href && !disabled && !loading"
        :href="href"
        :class="[baseClasses, sizeClasses, variantClasses]"
    >
        <span class="inline-flex items-center gap-2">
            <slot />
        </span>
    </Link>

    <button
        v-else
        :type="type"
        :disabled="disabled || loading"
        :class="[baseClasses, sizeClasses, variantClasses]"
    >
        <!-- Loading spinner -->
        <svg
            v-if="loading"
            class="animate-spin -ml-1 mr-2 h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>

        <span class="inline-flex items-center gap-2">
            <slot />
        </span>
    </button>
</template>
