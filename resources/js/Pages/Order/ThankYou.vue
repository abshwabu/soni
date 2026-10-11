<script setup>
import { computed, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Button from '../../Components/Button.vue';
import { useAnalytics } from '../../Composables/useAnalytics';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const { trackEvent } = useAnalytics();

onMounted(() => {
    trackEvent('order_placed', {
        order_number: props.order.order_number,
        total: props.order.total,
        items_count: props.order.items?.length || 0,
    });
});

const page = usePage();
const settings = computed(() => page.props.settings || {});

const whatsappUrl = computed(() => {
    const raw = settings.value.store_whatsapp || settings.value.store_phone || '+251911234567';
    let clean = raw.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '251' + clean.slice(1);
    }
    const msg = `Hello Soni Perfume, I just placed order #${props.order.order_number}. My name is ${props.order.customer_name}.`;
    return `https://wa.me/${clean}?text=${encodeURIComponent(msg)}`;
});

const phoneUrl = computed(() => {
    return `tel:${settings.value.store_phone || '+251911234567'}`;
});
</script>

<template>
    <AppLayout>
        <Head>
            <title>{{ seo.title || `Order Confirmed #${order.order_number} | Soni Perfume` }}</title>
            <meta
                name="description"
                :content="seo.description || 'Thank you for your order with Soni Perfume Addis Ababa.'"
            >
        </Head>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <div class="max-w-3xl mx-auto space-y-8">
                <!-- Success Header Banner -->
                <div class="text-center space-y-4 bg-brand-surface p-8 sm:p-10 rounded-sm border border-brand-border/70 shadow-sm">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-emerald-50 border-2 border-emerald-500 text-emerald-600 mx-auto flex items-center justify-center">
                        <svg
                            class="w-8 h-8 sm:w-10 sm:h-10"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4.5 12.75l6 6 9-13.5"
                            />
                        </svg>
                    </div>

                    <div class="space-y-2">
                        <span class="inline-block px-3 py-1 rounded-sm bg-brand-gold/15 text-brand-gold font-sans text-xs uppercase tracking-widest font-bold">
                            Order Received • Awaiting Telephone Confirmation
                        </span>

                        <h1 class="font-serif text-3xl sm:text-4xl text-brand-charcoal font-medium">
                            Thank You, {{ order.customer_name }}!
                        </h1>

                        <p class="text-xs sm:text-sm text-brand-muted font-sans font-light max-w-lg mx-auto">
                            Your order has been recorded successfully under reference:
                        </p>

                        <div class="inline-block px-4 py-2 bg-brand-surface-warm border border-brand-border rounded-sm font-mono text-sm sm:text-base font-bold text-brand-charcoal tracking-wider">
                            {{ order.order_number }}
                        </div>
                    </div>

                    <div class="p-4 rounded-sm bg-brand-bg border border-brand-border/60 text-xs text-brand-charcoal leading-relaxed max-w-md mx-auto text-left flex items-start gap-3">
                        <span class="text-lg">📞</span>
                        <div>
                            <p class="font-semibold text-brand-charcoal">
                                What happens next?
                            </p>
                            <p class="text-brand-charcoal/80 mt-0.5">
                                Our Addis Ababa concierge team will call you at <strong class="text-brand-charcoal font-mono">{{ order.phone }}</strong> within 30 minutes to confirm your delivery address and schedule delivery.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Order Details & Summary Card -->
                <div class="bg-brand-surface p-6 sm:p-8 rounded-sm border border-brand-border/70 space-y-6 shadow-sm">
                    <h2 class="font-serif text-2xl font-normal text-brand-charcoal border-b border-brand-border/60 pb-3">
                        Order Details
                    </h2>

                    <!-- Customer & Delivery Info Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs text-brand-charcoal">
                        <div>
                            <h3 class="uppercase tracking-wider font-semibold text-brand-muted mb-2">
                                Customer Contact
                            </h3>
                            <p class="font-medium text-sm text-brand-charcoal">
                                {{ order.customer_name }}
                            </p>
                            <p class="font-mono mt-0.5 text-brand-charcoal/80">
                                {{ order.phone }}
                            </p>
                            <p
                                v-if="order.alt_phone"
                                class="font-mono text-brand-muted"
                            >
                                Alt: {{ order.alt_phone }}
                            </p>
                        </div>

                        <div>
                            <h3 class="uppercase tracking-wider font-semibold text-brand-muted mb-2">
                                Delivery Information
                            </h3>
                            <p class="font-medium capitalize text-sm text-brand-charcoal">
                                {{ order.delivery_method === 'pickup' ? 'Store Pickup (Bole)' : 'Addis Ababa Delivery' }}
                            </p>
                            <p class="text-brand-charcoal/80 mt-0.5">
                                {{ order.address }}
                            </p>
                            <p
                                v-if="order.notes"
                                class="text-brand-muted italic mt-1"
                            >
                                Note: "{{ order.notes }}"
                            </p>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="pt-4 border-t border-brand-border/60 space-y-3">
                        <h3 class="text-xs uppercase tracking-wider font-semibold text-brand-muted">
                            Ordered Fragrances
                        </h3>

                        <div class="divide-y divide-brand-border/40">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="py-3 flex items-center justify-between gap-4"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="font-serif text-sm font-semibold text-brand-charcoal truncate">
                                        {{ item.product_name }}
                                    </p>
                                    <p class="text-[11px] text-brand-muted">
                                        {{ item.variant_label }} × {{ item.quantity }}
                                    </p>
                                </div>

                                <div class="text-right">
                                    <span class="font-serif font-bold text-sm text-brand-charcoal">
                                        ETB {{ Number(item.line_total).toLocaleString() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cost Totals -->
                    <div class="pt-4 border-t border-brand-border/60 space-y-2 text-xs font-sans">
                        <div class="flex justify-between text-brand-charcoal/80">
                            <span>Subtotal:</span>
                            <span class="font-serif font-bold text-sm text-brand-charcoal">
                                ETB {{ Number(order.subtotal).toLocaleString() }}
                            </span>
                        </div>

                        <div class="flex justify-between text-brand-muted">
                            <span>Delivery Fee:</span>
                            <span :class="order.delivery_fee === 0 ? 'text-emerald-700 font-bold' : 'text-brand-charcoal'">
                                {{ order.delivery_fee === 0 ? 'FREE' : `ETB ${Number(order.delivery_fee).toLocaleString()}` }}
                            </span>
                        </div>

                        <div class="pt-3 border-t border-brand-border flex justify-between text-base font-semibold text-brand-charcoal">
                            <span>Total Payable on Delivery:</span>
                            <span class="font-serif text-xl text-brand-gold font-bold">
                                ETB {{ Number(order.total).toLocaleString() }}
                            </span>
                        </div>

                        <p class="text-[11px] text-brand-muted pt-1">
                            Payment is due upon handover via Cash, Telebirr, or CBE Mobile Transfer.
                        </p>
                    </div>
                </div>

                <!-- Assistance / Contact Callout -->
                <div class="bg-brand-surface p-6 rounded-sm border border-brand-border/70 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="font-serif text-lg font-medium text-brand-charcoal">
                            Need immediate assistance?
                        </h4>
                        <p class="text-xs text-brand-muted">
                            Reach out to our customer care team anytime.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <a
                            :href="phoneUrl"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-sm border border-brand-border bg-brand-surface hover:border-brand-gold text-xs font-semibold text-brand-charcoal transition-colors"
                        >
                            <span>📞 Call Us</span>
                        </a>

                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-sm bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors"
                        >
                            <span>💬 WhatsApp Us</span>
                        </a>
                    </div>
                </div>

                <!-- Continue Browsing Button -->
                <div class="text-center pt-2">
                    <Button
                        href="/shop"
                        variant="primary"
                        size="lg"
                    >
                        Continue Exploring Fragrances
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
