<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import SeoHead from '../Components/SeoHead.vue';
import SectionHeading from '../Components/SectionHeading.vue';
import Button from '../Components/Button.vue';
import { useToast } from '../Composables/useToast';
import { buildWhatsAppUrl, buildTelegramUrl } from '../Utils/contact';

const props = defineProps({
    storeInfo: {
        type: Object,
        default: () => ({}),
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const { success, error } = useToast();

const form = useForm({
    name: '',
    phone: '',
    email: '',
    subject: '',
    message: '',
});

const submit = () => {
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            success('Your message has been sent to our Telegram concierge team!');
        },
        onError: () => {
            error('Please check the form fields and try again.');
        },
    });
};

const whatsappLink = computed(() => {
    return buildWhatsAppUrl(
        props.storeInfo.whatsapp_url || props.storeInfo.whatsapp || props.storeInfo.phone,
        'Hello Soni Perfume concierge, I would like to inquire about:'
    );
});

const telegramLink = computed(() => {
    return buildTelegramUrl(props.storeInfo.telegram_url || props.storeInfo.telegram);
});

const googleMapEmbedUrl = computed(() => {
    return 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.569420955938!2d38.78659551478635!3d8.997814293542318!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x164b8504f4ec8f17%3A0x6b77ecb476eb4bca!2sMedhanialem%20Cathedral%2C%20Bole%2C%20Addis%20Ababa!5e0!3m2!1sen!2set!4v1620000000000!5m2!1sen!2set';
});
</script>

<template>
    <AppLayout>
        <SeoHead
            :title="seo.title || 'Contact Us & Boutique Location'"
            :description="seo.description || 'Visit our Addis Ababa boutique in Bole Medhanialem or contact our fragrance concierge via phone, WhatsApp, or Telegram.'"
            :url="seo.url"
        />

        <!-- Header -->
        <section class="bg-brand-dark text-brand-bg py-16 md:py-24">
            <div class="container mx-auto px-4 text-center max-w-3xl">
                <p class="text-xs uppercase tracking-extrawide text-brand-gold font-sans font-semibold mb-2">
                    Concierge & Boutique Location
                </p>
                <h1 class="font-serif text-4xl sm:text-5xl font-light tracking-wide text-brand-bg">
                    Get in Touch
                </h1>
                <p class="mt-4 text-sm md:text-base text-brand-bg/80 font-sans font-light leading-relaxed">
                    We welcome inquiries regarding fragrance recommendations, order status, or boutique visits.
                </p>
            </div>
        </section>

        <!-- Main Content Grid -->
        <section class="py-16 md:py-20">
            <div class="container mx-auto px-4 max-w-6xl">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                    <!-- Left: Contact Form -->
                    <div class="lg:col-span-7 bg-brand-surface p-6 sm:p-10 rounded-sm border border-brand-border shadow-sm">
                        <SectionHeading
                            badge="Message Us"
                            title="Direct Concierge Inquiry"
                            subtitle="Forwarded instantly to our store Telegram admin"
                            alignment="left"
                            class="mb-6"
                        />

                        <form class="space-y-5" @submit.prevent="submit">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-brand-charcoal mb-1.5">
                                        Your Name <span class="text-brand-gold">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="e.g. Abebe Bikila"
                                        class="w-full px-3.5 py-2.5 bg-brand-bg border border-brand-border rounded-sm text-sm text-brand-charcoal focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                    >
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-brand-charcoal mb-1.5">
                                        Phone Number <span class="text-brand-gold">*</span>
                                    </label>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        placeholder="09... or +251 9..."
                                        class="w-full px-3.5 py-2.5 bg-brand-bg border border-brand-border rounded-sm text-sm text-brand-charcoal focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                    >
                                    <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-600">
                                        {{ form.errors.phone }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-brand-charcoal mb-1.5">
                                        Email (Optional)
                                    </label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="name@example.com"
                                        class="w-full px-3.5 py-2.5 bg-brand-bg border border-brand-border rounded-sm text-sm text-brand-charcoal focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                    >
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-brand-charcoal mb-1.5">
                                        Subject
                                    </label>
                                    <input
                                        v-model="form.subject"
                                        type="text"
                                        placeholder="e.g. Scent Recommendation, Order Help"
                                        class="w-full px-3.5 py-2.5 bg-brand-bg border border-brand-border rounded-sm text-sm text-brand-charcoal focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                    >
                                    <p v-if="form.errors.subject" class="mt-1 text-xs text-rose-600">
                                        {{ form.errors.subject }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-charcoal mb-1.5">
                                    Message <span class="text-brand-gold">*</span>
                                </label>
                                <textarea
                                    v-model="form.message"
                                    rows="5"
                                    required
                                    placeholder="Tell us what fragrance you are seeking, or provide your order number..."
                                    class="w-full px-3.5 py-2.5 bg-brand-bg border border-brand-border rounded-sm text-sm text-brand-charcoal focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                />
                                <p v-if="form.errors.message" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.message }}
                                </p>
                            </div>

                            <Button
                                type="submit"
                                variant="primary"
                                size="md"
                                :loading="form.processing"
                                class="w-full sm:w-auto"
                            >
                                Send Message to Telegram Concierge
                            </Button>
                        </form>
                    </div>

                    <!-- Right: Boutique Contact Details & Instant Chat -->
                    <div class="lg:col-span-5 space-y-8">
                        <!-- Fast Chat Channels Card -->
                        <div class="bg-brand-surface p-6 rounded-sm border border-brand-border shadow-sm space-y-4">
                            <h2 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                                Direct Chat Channels
                            </h2>
                            <p class="text-xs text-brand-muted leading-relaxed">
                                For quickest reply on perfume stock, pricing, and orders, tap directly below:
                            </p>

                            <div class="flex flex-col gap-3 pt-2">
                                <a
                                    :href="whatsappLink"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex items-center gap-3 p-3 rounded-sm bg-emerald-50 border border-emerald-200 text-emerald-900 hover:bg-emerald-100 transition-colors"
                                >
                                    <span class="text-2xl">💬</span>
                                    <div>
                                        <div class="text-xs font-semibold">WhatsApp Chat</div>
                                        <div class="text-[11px] text-emerald-700">Instant messaging with concierge</div>
                                    </div>
                                </a>

                                <a
                                    :href="telegramLink"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex items-center gap-3 p-3 rounded-sm bg-sky-50 border border-sky-200 text-sky-900 hover:bg-sky-100 transition-colors"
                                >
                                    <span class="text-2xl">✈️</span>
                                    <div>
                                        <div class="text-xs font-semibold">Telegram Channel & Bot</div>
                                        <div class="text-[11px] text-sky-700">Official channel & automated assistance</div>
                                    </div>
                                </a>

                                <a
                                    :href="`tel:${storeInfo.phone}`"
                                    class="flex items-center gap-3 p-3 rounded-sm bg-brand-bg border border-brand-border text-brand-charcoal hover:bg-brand-gold/10 transition-colors"
                                >
                                    <span class="text-2xl">📞</span>
                                    <div>
                                        <div class="text-xs font-semibold">Phone Call</div>
                                        <div class="text-[11px] text-brand-muted">{{ storeInfo.phone }}</div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Boutique Address & Hours -->
                        <div class="bg-brand-surface p-6 rounded-sm border border-brand-border shadow-sm space-y-4">
                            <h2 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                                Visit Our Addis Ababa Boutique
                            </h2>

                            <div class="space-y-3 text-xs text-brand-charcoal">
                                <div class="flex items-start gap-3">
                                    <span class="text-brand-gold text-base">📍</span>
                                    <div>
                                        <strong class="block text-brand-charcoal font-medium">Boutique Address:</strong>
                                        <p class="text-brand-muted mt-0.5 leading-relaxed">
                                            {{ storeInfo.address }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <span class="text-brand-gold text-base">🕒</span>
                                    <div>
                                        <strong class="block text-brand-charcoal font-medium">Opening Hours:</strong>
                                        <p class="text-brand-muted mt-0.5 leading-relaxed">
                                            {{ storeInfo.hours }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <span class="text-brand-gold text-base">🚚</span>
                                    <div>
                                        <strong class="block text-brand-charcoal font-medium">Addis Ababa Delivery:</strong>
                                        <p class="text-brand-muted mt-0.5 leading-relaxed">
                                            Same-day door-to-door courier service across all sub-cities in Addis Ababa.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Embedded Google Map -->
                <div class="mt-16 rounded-sm overflow-hidden border border-brand-border shadow-md">
                    <div class="bg-brand-surface p-4 border-b border-brand-border flex items-center justify-between">
                        <div>
                            <h3 class="font-serif text-base font-semibold text-brand-charcoal">
                                Location Map
                            </h3>
                            <p class="text-xs text-brand-muted">
                                Bole Medhanialem Cathedral Area • Addis Ababa, Ethiopia
                            </p>
                        </div>
                        <a
                            href="https://maps.google.com/?q=Bole+Medhanialem+Addis+Ababa"
                            target="_blank"
                            rel="noopener"
                            class="text-xs font-semibold uppercase tracking-wider text-brand-gold hover:underline"
                        >
                            Open in Google Maps →
                        </a>
                    </div>
                    <div class="w-full h-80 bg-stone-100">
                        <iframe
                            :src="googleMapEmbedUrl"
                            width="100%"
                            height="100%"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Soni Perfume Boutique Location Map"
                        />
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
