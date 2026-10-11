<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import Button from '../Components/Button.vue';

const props = defineProps({
    status: {
        type: Number,
        default: 404,
    },
    message: {
        type: String,
        default: '',
    },
});

const title = computed(() => {
    return {
        503: '503: Service Unavailable',
        500: '500: Server Error',
        404: '404: Fragrance Not Found',
        403: '403: Forbidden',
    }[props.status] || 'An Error Occurred';
});

const description = computed(() => {
    return {
        503: 'We are currently performing boutique maintenance. Please check back shortly.',
        500: 'Whoops, something went wrong on our servers. Our concierge team has been notified.',
        404: 'The fragrance, brand, or page you are looking for may have been retired or moved.',
        403: 'Sorry, you are forbidden from accessing this boutique area.',
    }[props.status] || 'An unexpected issue occurred.';
});
</script>

<template>
    <AppLayout>
        <Head :title="title" />

        <div class="min-h-[60vh] flex items-center justify-center py-20 px-4">
            <div class="max-w-md w-full text-center space-y-6">
                <!-- Status Code Watermark -->
                <div class="font-serif text-7xl md:text-8xl font-light text-brand-gold/60 select-none">
                    {{ status }}
                </div>

                <div class="space-y-3">
                    <h1 class="font-serif text-2xl sm:text-3xl font-normal text-brand-charcoal">
                        {{ title }}
                    </h1>
                    <p class="text-xs sm:text-sm text-brand-muted font-sans font-light leading-relaxed">
                        {{ description }}
                    </p>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Button
                        as="a"
                        href="/"
                        variant="primary"
                        size="md"
                    >
                        Return to Boutique
                    </Button>
                    <Button
                        as="a"
                        href="/shop"
                        variant="secondary"
                        size="md"
                    >
                        Explore Fragrances
                    </Button>
                </div>

                <p class="text-[11px] text-brand-muted/80 pt-6">
                    Need immediate assistance? Contact our Addis Ababa concierge via
                    <a href="https://t.me/soniperfume" target="_blank" rel="noopener" class="text-brand-gold underline">Telegram</a>
                    or
                    <Link href="/contact" class="text-brand-gold underline">Contact Page</Link>.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
