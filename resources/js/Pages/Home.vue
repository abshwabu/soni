<script setup>
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <Head title="Home" />
    <main class="min-h-screen flex items-center justify-center bg-stone-50 text-stone-900 font-sans px-4">
        <div class="text-center max-w-lg">
            <h1 class="font-serif text-4xl md:text-5xl font-normal tracking-wide text-stone-900">
                {{ t('store.name') }}
            </h1>
            <p class="mt-3 text-stone-600 text-sm md:text-base tracking-widest uppercase">
                {{ t('store.tagline') }}
            </p>
        </div>
    </main>
</template>
