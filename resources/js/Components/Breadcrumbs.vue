<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        required: true,
        validator: (value) => value.every((item) => typeof item.label === 'string'),
    },
});
</script>

<template>
    <nav
        aria-label="Breadcrumb"
        class="py-3 text-xs font-sans tracking-wider uppercase text-brand-muted"
    >
        <ol class="flex items-center flex-wrap gap-2">
            <!-- Home link -->
            <li class="flex items-center gap-2">
                <Link
                    href="/"
                    class="hover:text-brand-gold transition-colors duration-150"
                >
                    Home
                </Link>
                <span
                    class="text-brand-muted/40 font-light"
                    aria-hidden="true"
                >/</span>
            </li>

            <!-- Iterated items -->
            <li
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-2"
            >
                <Link
                    v-if="item.href && index !== items.length - 1"
                    :href="item.href"
                    class="hover:text-brand-gold transition-colors duration-150"
                >
                    {{ item.label }}
                </Link>

                <span
                    v-else
                    class="text-brand-charcoal font-medium truncate max-w-[200px] sm:max-w-xs"
                    aria-current="page"
                >
                    {{ item.label }}
                </span>

                <span
                    v-if="index !== items.length - 1"
                    class="text-brand-muted/40 font-light"
                    aria-hidden="true"
                >/</span>
            </li>
        </ol>
    </nav>
</template>
