<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';
import Button from '../Components/Button.vue';
import { useToast } from '../Composables/useToast';

const props = defineProps({
    cart: {
        type: Object,
        required: true,
    },
    idempotencyToken: {
        type: String,
        default: '',
    },
    deliveryFeeAddis: {
        type: Number,
        default: 250,
    },
    freeDeliveryThreshold: {
        type: Number,
        default: 3000,
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const { error } = useToast();

const form = useForm({
    customer_name: '',
    phone: '',
    alt_phone: '',
    city: 'Addis Ababa',
    delivery_method: 'delivery', // 'delivery' or 'pickup'
    address: '',
    notes: '',
    website: '', // Honeypot field
    idempotency_token: props.idempotencyToken,
});

// Dynamic totals based on selected delivery method
const subtotal = computed(() => props.cart.subtotal || 0);

const isFreeDeliveryEligible = computed(() => subtotal.value >= props.freeDeliveryThreshold);

const computedDeliveryFee = computed(() => {
    if (form.delivery_method === 'pickup') {
        return 0;
    }
    if (isFreeDeliveryEligible.value) {
        return 0;
    }
    return props.deliveryFeeAddis;
});

const computedTotal = computed(() => subtotal.value + computedDeliveryFee.value);

// LocalStorage key for remembering customer information
const STORAGE_KEY = 'soni_customer_checkout';

onMounted(() => {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        if (saved.customer_name) form.customer_name = saved.customer_name;
        if (saved.phone) form.phone = saved.phone;
        if (saved.alt_phone) form.alt_phone = saved.alt_phone;
        if (saved.city) form.city = saved.city;
        if (saved.address) form.address = saved.address;
    } catch (e) {
        console.error('Failed restoring customer details from localStorage', e);
    }
});

// Auto-save form inputs to localStorage
const saveCustomerDetails = () => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            customer_name: form.customer_name,
            phone: form.phone,
            alt_phone: form.alt_phone,
            city: form.city,
            address: form.address,
        }));
    } catch (e) {
        // Ignore quota errors
    }
};

const submitOrder = () => {
    saveCustomerDetails();

    form.post('/checkout', {
        preserveScroll: true,
        onError: (errors) => {
            const firstErr = Object.values(errors)[0];
            if (firstErr) {
                error(firstErr);
            }
        },
    });
};

const breadcrumbs = [
    { label: 'Home', href: '/' },
    { label: 'Shopping Bag', href: '/cart' },
    { label: 'Guest Checkout', href: null },
];
</script>

<template>
    <AppLayout>
        <Head>
            <title>{{ seo.title || 'Guest Checkout | Soni Perfume' }}</title>
            <meta
                name="description"
                :content="seo.description || 'Order perfume in Addis Ababa. Cash/Telebirr on delivery.'"
            >
        </Head>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
            <Breadcrumbs
                :items="breadcrumbs"
                class="mb-6"
            />

            <div class="max-w-6xl mx-auto">
                <div class="mb-8">
                    <span class="text-xs uppercase tracking-[0.25em] text-brand-gold font-medium mb-1 block">
                        Fast & Direct
                    </span>
                    <h1 class="font-serif text-3xl sm:text-4xl md:text-5xl font-normal text-brand-charcoal">
                        Guest Checkout
                    </h1>
                    <p class="text-xs sm:text-sm text-brand-muted mt-1">
                        No online payment or account creation required. Pay upon delivery in Addis Ababa.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
                    <!-- Form Column (7 cols) -->
                    <div class="lg:col-span-7 space-y-8">
                        <form
                            class="space-y-6"
                            @submit.prevent="submitOrder"
                        >
                            <!-- Honeypot Field (Invisible to human users) -->
                            <div
                                style="position: absolute; left: -9999px; top: -9999px;"
                                aria-hidden="true"
                            >
                                <label for="website-hp">Website</label>
                                <input
                                    id="website-hp"
                                    v-model="form.website"
                                    type="text"
                                    tabindex="-1"
                                    autocomplete="off"
                                >
                            </div>

                            <!-- 1. Customer Details Section -->
                            <div class="bg-brand-surface p-6 rounded-sm border border-brand-border/70 space-y-4 shadow-xs">
                                <div class="flex items-center gap-2 border-b border-brand-border/60 pb-3">
                                    <span class="w-6 h-6 rounded-full bg-brand-charcoal text-white text-xs flex items-center justify-center font-bold">1</span>
                                    <h2 class="font-serif text-xl font-normal text-brand-charcoal">
                                        Your Contact Information
                                    </h2>
                                </div>

                                <!-- Full Name -->
                                <div>
                                    <label
                                        for="customer_name"
                                        class="block text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-1"
                                    >
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="customer_name"
                                        v-model="form.customer_name"
                                        type="text"
                                        required
                                        placeholder="e.g. Abebe Bikila"
                                        class="w-full text-sm bg-brand-bg border border-brand-border rounded-sm p-3 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                        :class="{ 'border-red-500': form.errors.customer_name }"
                                        @input="saveCustomerDetails"
                                    >
                                    <p
                                        v-if="form.errors.customer_name"
                                        class="text-xs text-red-600 mt-1"
                                    >
                                        {{ form.errors.customer_name }}
                                    </p>
                                </div>

                                <!-- Phone & Alt Phone -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            for="phone"
                                            class="block text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-1"
                                        >
                                            Mobile Phone <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="phone"
                                            v-model="form.phone"
                                            type="tel"
                                            required
                                            placeholder="0911 234567 or 0711..."
                                            class="w-full text-sm bg-brand-bg border border-brand-border rounded-sm p-3 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold font-mono"
                                            :class="{ 'border-red-500': form.errors.phone }"
                                            @input="saveCustomerDetails"
                                        >
                                        <p class="text-[10px] text-brand-muted mt-1">
                                            We will call this number to confirm delivery.
                                        </p>
                                        <p
                                            v-if="form.errors.phone"
                                            class="text-xs text-red-600 mt-1"
                                        >
                                            {{ form.errors.phone }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            for="alt_phone"
                                            class="block text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-1"
                                        >
                                            Alternative Phone <span class="text-brand-muted font-normal">(Optional)</span>
                                        </label>
                                        <input
                                            id="alt_phone"
                                            v-model="form.alt_phone"
                                            type="tel"
                                            placeholder="0912 345678"
                                            class="w-full text-sm bg-brand-bg border border-brand-border rounded-sm p-3 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold font-mono"
                                            :class="{ 'border-red-500': form.errors.alt_phone }"
                                            @input="saveCustomerDetails"
                                        >
                                        <p
                                            v-if="form.errors.alt_phone"
                                            class="text-xs text-red-600 mt-1"
                                        >
                                            {{ form.errors.alt_phone }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Delivery Method & Address Section -->
                            <div class="bg-brand-surface p-6 rounded-sm border border-brand-border/70 space-y-5 shadow-xs">
                                <div class="flex items-center gap-2 border-b border-brand-border/60 pb-3">
                                    <span class="w-6 h-6 rounded-full bg-brand-charcoal text-white text-xs flex items-center justify-center font-bold">2</span>
                                    <h2 class="font-serif text-xl font-normal text-brand-charcoal">
                                        Delivery Options
                                    </h2>
                                </div>

                                <!-- Method Selector (Delivery vs Store Pickup) -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label
                                        class="p-4 rounded-sm border cursor-pointer transition-all flex items-start gap-3"
                                        :class="form.delivery_method === 'delivery'
                                            ? 'border-brand-gold bg-brand-gold/5 ring-1 ring-brand-gold'
                                            : 'border-brand-border hover:border-brand-gold/50 bg-brand-bg'"
                                    >
                                        <input
                                            v-model="form.delivery_method"
                                            type="radio"
                                            value="delivery"
                                            name="delivery_method"
                                            class="text-brand-gold focus:ring-brand-gold mt-0.5"
                                        >
                                        <div>
                                            <span class="block text-xs font-semibold uppercase tracking-wider text-brand-charcoal">
                                                Addis Ababa Delivery
                                            </span>
                                            <span class="text-[11px] text-brand-muted block mt-0.5">
                                                {{ isFreeDeliveryEligible ? 'Free delivery unlocked' : `Standard fee: ETB ${deliveryFeeAddis}` }}
                                            </span>
                                        </div>
                                    </label>

                                    <label
                                        class="p-4 rounded-sm border cursor-pointer transition-all flex items-start gap-3"
                                        :class="form.delivery_method === 'pickup'
                                            ? 'border-brand-gold bg-brand-gold/5 ring-1 ring-brand-gold'
                                            : 'border-brand-border hover:border-brand-gold/50 bg-brand-bg'"
                                    >
                                        <input
                                            v-model="form.delivery_method"
                                            type="radio"
                                            value="pickup"
                                            name="delivery_method"
                                            class="text-brand-gold focus:ring-brand-gold mt-0.5"
                                        >
                                        <div>
                                            <span class="block text-xs font-semibold uppercase tracking-wider text-brand-charcoal">
                                                Store Pickup (Free)
                                            </span>
                                            <span class="text-[11px] text-brand-muted block mt-0.5">
                                                Bole, Addis Ababa store
                                            </span>
                                        </div>
                                    </label>
                                </div>

                                <!-- City & Address (Shown when delivery is chosen) -->
                                <div
                                    v-if="form.delivery_method === 'delivery'"
                                    class="space-y-4 pt-2"
                                >
                                    <div>
                                        <label
                                            for="city"
                                            class="block text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-1"
                                        >
                                            City
                                        </label>
                                        <input
                                            id="city"
                                            v-model="form.city"
                                            type="text"
                                            required
                                            class="w-full text-sm bg-brand-bg border border-brand-border rounded-sm p-3 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                            @input="saveCustomerDetails"
                                        >
                                    </div>

                                    <div>
                                        <label
                                            for="address"
                                            class="block text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-1"
                                        >
                                            Delivery Address & Landmark <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            id="address"
                                            v-model="form.address"
                                            rows="3"
                                            required
                                            placeholder="Sub-city, neighborhood, building name, or famous landmark (e.g. Bole Medhanialem, behind Edna Mall, Morning Star Mall 2nd floor)..."
                                            class="w-full text-sm bg-brand-bg border border-brand-border rounded-sm p-3 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                            :class="{ 'border-red-500': form.errors.address }"
                                            @input="saveCustomerDetails"
                                        />
                                        <p
                                            v-if="form.errors.address"
                                            class="text-xs text-red-600 mt-1"
                                        >
                                            {{ form.errors.address }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="p-4 rounded-sm bg-brand-bg border border-brand-border/60 text-xs text-brand-charcoal space-y-1"
                                >
                                    <p class="font-semibold text-brand-charcoal">
                                        📍 Store Pickup Location:
                                    </p>
                                    <p class="text-brand-charcoal/80">
                                        Soni Perfume Boutique, Bole Sub-City, Addis Ababa, Ethiopia.
                                    </p>
                                    <p class="text-brand-muted text-[11px] pt-1">
                                        Your perfume will be carefully packaged and reserved for you. Please bring your order confirmation number.
                                    </p>
                                </div>

                                <!-- Special Delivery Notes -->
                                <div>
                                    <label
                                        for="notes"
                                        class="block text-xs uppercase tracking-wider font-semibold text-brand-charcoal mb-1"
                                    >
                                        Order Notes / Timing Preference <span class="text-brand-muted font-normal">(Optional)</span>
                                    </label>
                                    <textarea
                                        id="notes"
                                        v-model="form.notes"
                                        rows="2"
                                        placeholder="e.g. Please deliver after 3:00 PM, or gift wrap the package."
                                        class="w-full text-sm bg-brand-bg border border-brand-border rounded-sm p-3 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                    />
                                </div>
                            </div>

                            <!-- 3. Payment Method (Pay on Delivery) -->
                            <div class="bg-brand-surface p-6 rounded-sm border border-brand-border/70 space-y-4 shadow-xs">
                                <div class="flex items-center gap-2 border-b border-brand-border/60 pb-3">
                                    <span class="w-6 h-6 rounded-full bg-brand-charcoal text-white text-xs flex items-center justify-center font-bold">3</span>
                                    <h2 class="font-serif text-xl font-normal text-brand-charcoal">
                                        Payment Method
                                    </h2>
                                </div>

                                <div class="p-4 rounded-sm bg-emerald-50 border border-emerald-200 flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-serif font-medium text-base text-emerald-900">
                                            Pay on Delivery / at Pickup
                                        </h3>
                                        <p class="text-xs text-emerald-800 mt-1 leading-relaxed">
                                            No advance credit card or online payment is required. You only pay once you receive and inspect your fragrance.
                                        </p>
                                        <div class="flex flex-wrap gap-2 mt-3">
                                            <span class="px-2.5 py-1 bg-white rounded-sm text-[11px] font-semibold text-brand-charcoal border border-emerald-200">
                                                💵 Cash on Delivery
                                            </span>
                                            <span class="px-2.5 py-1 bg-white rounded-sm text-[11px] font-semibold text-brand-charcoal border border-emerald-200">
                                                📱 Telebirr Transfer
                                            </span>
                                            <span class="px-2.5 py-1 bg-white rounded-sm text-[11px] font-semibold text-brand-charcoal border border-emerald-200">
                                                🏦 CBE Mobile Banking
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Place Order Button (Mobile & Desktop) -->
                            <div class="pt-2">
                                <Button
                                    type="submit"
                                    variant="primary"
                                    size="lg"
                                    block
                                    :loading="form.processing"
                                    :disabled="form.processing"
                                    class="py-4 text-xs uppercase tracking-widest font-semibold"
                                >
                                    <span v-if="!form.processing">Place Order • ETB {{ Number(computedTotal).toLocaleString() }}</span>
                                    <span v-else>Placing Your Order...</span>
                                </Button>
                                <p class="text-[11px] text-center text-brand-muted mt-2">
                                    By placing this order, you confirm your order details for telephone verification.
                                </p>
                            </div>
                        </form>
                    </div>

                    <!-- Order Summary Sidebar (5 cols) -->
                    <div class="lg:col-span-5">
                        <div class="bg-brand-surface border border-brand-border rounded-sm p-6 space-y-6 sticky top-28 shadow-xs">
                            <div class="flex items-center justify-between border-b border-brand-border pb-3">
                                <h2 class="font-serif text-xl font-normal text-brand-charcoal">
                                    Order Summary
                                </h2>
                                <Link
                                    href="/cart"
                                    class="text-xs text-brand-gold hover:underline"
                                >
                                    Edit Bag
                                </Link>
                            </div>

                            <!-- Items List -->
                            <div class="space-y-3.5 max-h-72 overflow-y-auto pr-1">
                                <div
                                    v-for="item in cart.items"
                                    :key="item.variant_id"
                                    class="flex items-center gap-3"
                                >
                                    <div class="w-12 h-14 bg-white rounded-sm border border-brand-border/60 shrink-0 p-1 flex items-center justify-center overflow-hidden">
                                        <img
                                            :src="item.image || '/images/placeholder.svg'"
                                            :alt="item.product_name"
                                            class="max-w-full max-h-full object-contain"
                                        >
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="font-serif text-xs font-semibold text-brand-charcoal truncate">
                                            {{ item.product_name }}
                                        </p>
                                        <p class="text-[11px] text-brand-muted">
                                            {{ item.label }} × {{ item.quantity }}
                                        </p>
                                    </div>

                                    <div class="text-right shrink-0">
                                        <span class="font-serif text-xs font-bold text-brand-charcoal">
                                            {{ item.formatted_line_total }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Cost Totals -->
                            <div class="space-y-2.5 pt-4 border-t border-brand-border/60 text-xs font-sans">
                                <div class="flex justify-between text-brand-charcoal/80">
                                    <span>Subtotal ({{ cart.count }} items):</span>
                                    <span class="font-serif font-bold text-sm text-brand-charcoal">{{ cart.formatted_subtotal }}</span>
                                </div>

                                <div class="flex justify-between text-brand-muted">
                                    <span>Delivery Fee:</span>
                                    <span
                                        class="font-medium"
                                        :class="computedDeliveryFee === 0 ? 'text-emerald-700 font-bold' : 'text-brand-charcoal'"
                                    >
                                        {{ computedDeliveryFee === 0 ? 'FREE' : `ETB ${Number(computedDeliveryFee).toLocaleString()}` }}
                                    </span>
                                </div>

                                <div class="pt-3 border-t border-brand-border flex justify-between text-sm font-semibold text-brand-charcoal">
                                    <span>Total Payable on Delivery:</span>
                                    <span class="font-serif text-xl text-brand-gold font-bold">
                                        ETB {{ Number(computedTotal).toLocaleString() }}
                                    </span>
                                </div>
                            </div>

                            <!-- Value badges -->
                            <div class="pt-4 border-t border-brand-border/60 space-y-2 text-[11px] text-brand-muted">
                                <p class="flex items-center gap-2">
                                    <span class="text-brand-gold">✓</span>
                                    <span>100% Genuine, Sealed Perfumes</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <span class="text-brand-gold">✓</span>
                                    <span>Same-Day Addis Ababa Express Dispatch</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <span class="text-brand-gold">✓</span>
                                    <span>Telephone Confirmation with Store Specialist</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
