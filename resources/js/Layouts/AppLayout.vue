<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import Drawer from '../Components/Drawer.vue';
import Modal from '../Components/Modal.vue';
import Toast from '../Components/Toast.vue';
import Button from '../Components/Button.vue';
import MiniCartDrawer from '../Components/MiniCartDrawer.vue';
import { useToast } from '../Composables/useToast';
import { setLocale } from '../i18n';

const page = usePage();
const { success, error } = useToast();
const { t, locale: currentLocale } = useI18n();

const switchLocale = (lang) => {
    setLocale(lang);
};

const settings = computed(() => page.props.settings || {});
const cartCount = computed(() => page.props.cartCount || 0);
const categoriesNav = computed(() => page.props.categoriesNav || []);
const brandsNav = computed(() => page.props.brandsNav || []);

const showAnnouncement = ref(true);
const mobileMenuOpen = ref(false);
const searchOpen = ref(false);
const miniCartOpen = ref(false);
const searchQuery = ref('');
const isSearching = ref(false);
const searchSuggestions = ref({ products: [], brands: [], total: 0 });
const floatingChatOpen = ref(false);
let searchDebounceTimer = null;

const handleOpenMiniCart = () => {
    miniCartOpen.value = true;
};

onMounted(() => {
    window.addEventListener('open-mini-cart', handleOpenMiniCart);
});

onUnmounted(() => {
    window.removeEventListener('open-mini-cart', handleOpenMiniCart);
});

watch(searchQuery, (newVal) => {
    const q = (newVal || '').trim();
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);

    if (q.length < 2) {
        searchSuggestions.value = { products: [], brands: [], total: 0 };
        isSearching.value = false;
        return;
    }

    isSearching.value = true;
    searchDebounceTimer = setTimeout(async () => {
        try {
            const res = await axios.get('/search/suggestions', { params: { q } });
            searchSuggestions.value = res.data || { products: [], brands: [], total: 0 };
        } catch (e) {
            console.error('Failed fetching search suggestions', e);
        } finally {
            isSearching.value = false;
        }
    }, 250);
});

// Newsletter subscription form
const newsletterForm = useForm({
    email_or_phone: '',
    source: 'footer_newsletter',
});

const submitNewsletter = () => {
    if (!newsletterForm.email_or_phone.trim()) return;

    newsletterForm.post('/subscribers', {
        preserveScroll: true,
        onSuccess: () => {
            newsletterForm.reset();
            success('Thank you for subscribing to Soni Perfume!');
        },
        onError: () => {
            error('Please provide a valid email or phone number.');
        },
    });
};

const handleSearch = () => {
    if (!searchQuery.value.trim()) return;
    const q = searchQuery.value.trim();
    searchOpen.value = false;
    router.visit(`/search?q=${encodeURIComponent(q)}`);
};

const selectProduct = (slug) => {
    searchOpen.value = false;
    router.visit(`/product/${slug}`);
};

const selectBrand = (slug) => {
    searchOpen.value = false;
    router.visit(`/brand/${slug}`);
};

const whatsappUrl = computed(() => {
    const raw = settings.value.store_whatsapp || settings.value.store_phone || '+251911234567';
    let clean = raw.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '251' + clean.slice(1);
    }
    const msg = encodeURIComponent('Hello Soni Perfume! I would like to inquire about your fragrance collection.');
    return `https://wa.me/${clean}?text=${msg}`;
});

const telegramUrl = computed(() => {
    return settings.value.telegram_url || 'https://t.me/soniperfume_admin';
});

const phoneUrl = computed(() => {
    return `tel:${settings.value.store_phone || '+251911234567'}`;
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-brand-bg text-brand-charcoal selection:bg-brand-gold/20 selection:text-brand-charcoal">
        <!-- Toast notifications wrapper -->
        <Toast />

        <!-- 1. Top Announcement Bar -->
        <div
            v-if="showAnnouncement && (settings.welcome_discount_text || settings.free_delivery_threshold)"
            class="relative bg-brand-charcoal text-brand-bg py-2 px-4 text-xs font-sans tracking-wider uppercase border-b border-white/10 z-50 transition-all"
        >
            <div class="container mx-auto flex items-center justify-between">
                <div class="hidden md:flex items-center gap-4 text-[11px] text-brand-bg/70">
                    <a
                        :href="phoneUrl"
                        class="hover:text-brand-gold transition-colors flex items-center gap-1.5"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-brand-gold"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"
                            />
                        </svg>
                        <span>{{ settings.store_phone || '+251 91 123 4567' }}</span>
                    </a>
                    <span>•</span>
                    <span>{{ currentLocale === 'am' ? 'አዲስ አበባ፣ ኢትዮጵያ' : 'Addis Ababa, Ethiopia' }}</span>
                </div>

                <!-- Center Banner Text -->
                <div class="mx-auto text-center truncate max-w-2xl px-2 font-medium">
                    <span class="text-brand-gold mr-1.5 font-semibold">✨</span>
                    <span>{{ settings.welcome_discount_text || (currentLocale === 'am' ? `ከ ${Number(settings.free_delivery_threshold || 5000).toLocaleString()} ብር በላይ ለሆኑ ትዕዛዞች በመላው አዲስ አበባ ነፃ ማድረሻ!` : `Complimentary delivery across Addis Ababa on orders over ETB ${Number(settings.free_delivery_threshold || 5000).toLocaleString()}!`) }}</span>
                </div>

                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Language Choosing Button (Switch between Amharic and English) -->
                    <div class="inline-flex items-center gap-1.5 text-[11px]">
                        <svg
                            class="w-3.5 h-3.5 text-brand-gold shrink-0 hidden sm:block"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"
                            />
                        </svg>
                        <div
                            class="inline-flex items-center bg-white/10 rounded-xs p-0.5 border border-white/15"
                            role="group"
                            aria-label="Language selection"
                        >
                            <button
                                type="button"
                                class="px-2 py-0.5 rounded-[2px] text-[10px] tracking-wider transition-all duration-150 focus:outline-none uppercase font-medium cursor-pointer"
                                :class="currentLocale === 'en' ? 'bg-brand-gold text-brand-charcoal font-semibold shadow-xs' : 'text-brand-bg/75 hover:text-white'"
                                :aria-pressed="currentLocale === 'en'"
                                title="English"
                                @click="switchLocale('en')"
                            >
                                <span class="hidden md:inline">English</span>
                                <span class="md:hidden">EN</span>
                            </button>
                            <button
                                type="button"
                                class="px-2 py-0.5 rounded-[2px] text-[10px] tracking-wider transition-all duration-150 focus:outline-none font-sans font-medium cursor-pointer"
                                :class="currentLocale === 'am' ? 'bg-brand-gold text-brand-charcoal font-semibold shadow-xs' : 'text-brand-bg/75 hover:text-white'"
                                :aria-pressed="currentLocale === 'am'"
                                title="አማርኛ"
                                @click="switchLocale('am')"
                            >
                                <span class="hidden md:inline">አማርኛ</span>
                                <span class="md:hidden">አማ</span>
                            </button>
                        </div>
                    </div>

                    <!-- Close Announcement -->
                    <button
                        type="button"
                        class="text-brand-bg/60 hover:text-white transition-colors p-1"
                        aria-label="Close notification"
                        @click="showAnnouncement = false"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Main Sticky Header -->
        <header class="sticky top-0 z-40 bg-brand-bg/95 backdrop-blur-md border-b border-brand-border/80 transition-all duration-200">
            <div class="container mx-auto">
                <div class="flex items-center justify-between h-16 md:h-20 px-2 sm:px-0">
                    <!-- Mobile Hamburger Button (Left) -->
                    <div class="flex items-center lg:hidden">
                        <button
                            type="button"
                            class="p-2 -ml-2 text-brand-charcoal hover:text-brand-gold transition-colors focus:outline-none"
                            aria-label="Open mobile menu"
                            @click="mobileMenuOpen = true"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                                />
                            </svg>
                        </button>

                        <!-- Mobile Search Icon -->
                        <button
                            type="button"
                            class="p-2 text-brand-charcoal hover:text-brand-gold transition-colors focus:outline-none"
                            aria-label="Open search"
                            @click="searchOpen = true"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Desktop Left Nav Items -->
                    <nav
                        class="hidden lg:flex items-center gap-7 text-xs uppercase tracking-widest font-sans font-medium text-brand-charcoal"
                        aria-label="Desktop Navigation"
                    >
                        <Link
                            href="/shop"
                            class="hover:text-brand-gold transition-colors duration-150 py-2 relative group"
                        >
                            <span>{{ $t('nav.shop') }}</span>
                            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-brand-gold transition-all duration-200 group-hover:w-full" />
                        </Link>

                        <!-- Categories dropdown trigger -->
                        <div class="relative group py-2">
                            <Link
                                href="/shop"
                                class="flex items-center gap-1 hover:text-brand-gold transition-colors duration-150"
                            >
                                <span>{{ $t('nav.categories') }}</span>
                                <svg
                                    class="w-3 h-3 text-brand-muted transition-transform group-hover:rotate-180"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </Link>

                            <!-- Dropdown menu -->
                            <div class="absolute top-full left-0 w-56 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200 z-50">
                                <div class="bg-brand-surface border border-brand-border rounded-sm shadow-xl py-2">
                                    <Link
                                        v-for="cat in categoriesNav"
                                        :key="cat.id"
                                        :href="`/category/${cat.slug}`"
                                        class="block px-4 py-2 text-xs tracking-wider text-brand-charcoal hover:bg-brand-gold/10 hover:text-brand-gold transition-colors"
                                    >
                                        {{ cat.name }}
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <Link
                            href="/brands"
                            class="hover:text-brand-gold transition-colors duration-150 py-2 relative group"
                        >
                            <span>{{ $t('nav.brands') }}</span>
                            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-brand-gold transition-all duration-200 group-hover:w-full" />
                        </Link>

                        <Link
                            href="/gifts"
                            class="hover:text-brand-gold transition-colors duration-150 py-2 relative group"
                        >
                            <span>{{ $t('nav.gifts') }}</span>
                            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-brand-gold transition-all duration-200 group-hover:w-full" />
                        </Link>
                    </nav>

                    <!-- Center Brand Logo -->
                    <div class="flex-1 lg:flex-none text-center">
                        <Link
                            href="/"
                            class="inline-flex flex-col items-center group focus:outline-none"
                        >
                            <span class="font-serif text-2xl sm:text-3xl md:text-4xl font-normal tracking-widest text-brand-charcoal uppercase leading-none group-hover:text-brand-gold transition-colors duration-200">
                                {{ settings.store_name?.split(' ')?.[0] || 'SONI' }}
                            </span>
                            <span class="text-[9px] md:text-[10px] font-sans font-semibold tracking-extrawide text-brand-gold uppercase mt-0.5">
                                {{ settings.store_name?.split(' ')?.slice(1)?.join(' ') || 'PERFUME' }}
                            </span>
                        </Link>
                    </div>

                    <!-- Right Desktop Nav & Utilities -->
                    <div class="flex items-center gap-4 sm:gap-6">
                        <nav
                            class="hidden lg:flex items-center gap-6 text-xs uppercase tracking-widest font-sans font-medium text-brand-charcoal mr-1"
                            aria-label="Secondary Navigation"
                        >
                            <Link
                                href="/about"
                                class="hover:text-brand-gold transition-colors duration-150 py-2"
                            >
                                {{ $t('nav.about') }}
                            </Link>
                            <Link
                                href="/contact"
                                class="hover:text-brand-gold transition-colors duration-150 py-2"
                            >
                                {{ $t('nav.contact') }}
                            </Link>
                        </nav>

                        <!-- Desktop Header Language Toggle -->
                        <div class="hidden lg:flex items-center">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm border border-brand-border/70 hover:border-brand-gold text-[11px] font-sans font-semibold text-brand-charcoal hover:text-brand-gold transition-colors cursor-pointer"
                                :title="currentLocale === 'en' ? 'Switch to Amharic (አማርኛ)' : 'Switch to English'"
                                @click="switchLocale(currentLocale === 'en' ? 'am' : 'en')"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-gold" />
                                <span>{{ currentLocale === 'en' ? 'አማርኛ' : 'EN' }}</span>
                            </button>
                        </div>

                        <!-- Search trigger (desktop) -->
                        <button
                            type="button"
                            class="hidden lg:flex items-center gap-2 text-xs uppercase tracking-wider text-brand-muted hover:text-brand-charcoal transition-colors p-1.5 focus:outline-none"
                            aria-label="Search perfumes"
                            @click="searchOpen = true"
                        >
                            <svg
                                class="w-4 h-4 text-brand-charcoal"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                                />
                            </svg>
                            <span class="hidden xl:inline text-[11px]">{{ $t('nav.search') }}</span>
                        </button>

                        <!-- Live Cart / Bag Button -->
                        <button
                            type="button"
                            class="relative p-2 text-brand-charcoal hover:text-brand-gold transition-colors flex items-center gap-2 group focus:outline-none"
                            aria-label="View shopping bag"
                            @click="miniCartOpen = true"
                        >
                            <div class="relative">
                                <svg
                                    class="w-5 h-5 sm:w-6 sm:h-6 transition-transform group-hover:scale-105"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                                    />
                                </svg>

                                <!-- Live Badge Counter -->
                                <span
                                    v-if="cartCount > 0"
                                    class="absolute -top-1.5 -right-2 min-w-[18px] h-[18px] px-1 bg-brand-gold text-white text-[10px] font-sans font-bold rounded-full flex items-center justify-center leading-none animate-fadeIn shadow-xs"
                                >
                                    {{ cartCount }}
                                </span>
                            </div>

                            <span class="hidden sm:inline text-xs uppercase tracking-wider font-medium text-brand-charcoal">
                                {{ $t('nav.bag') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mini Cart Drawer -->
        <MiniCartDrawer
            :show="miniCartOpen"
            @close="miniCartOpen = false"
        />

        <!-- 3. Mobile Slide-In Navigation Drawer -->
        <Drawer
            :show="mobileMenuOpen"
            position="left"
            title="Soni Perfume"
            max-width="sm"
            @close="mobileMenuOpen = false"
        >
            <div class="flex flex-col gap-5 py-2">
                <!-- Mobile Language Selector -->
                <div class="p-2.5 bg-brand-surface-warm/80 rounded-sm border border-brand-border/60 flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider text-brand-charcoal flex items-center gap-1.5 uppercase">
                        <svg class="w-4 h-4 text-brand-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        <span>{{ currentLocale === 'am' ? 'ቋንቋ' : 'Language' }}</span>
                    </span>
                    <div class="inline-flex items-center bg-white rounded-xs p-0.5 border border-brand-border">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-[2px] text-xs transition-all font-medium cursor-pointer"
                            :class="currentLocale === 'en' ? 'bg-brand-charcoal text-brand-gold font-semibold shadow-xs' : 'text-brand-muted hover:text-brand-charcoal'"
                            @click="switchLocale('en')"
                        >
                            English
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-[2px] text-xs transition-all font-sans font-medium cursor-pointer"
                            :class="currentLocale === 'am' ? 'bg-brand-charcoal text-brand-gold font-semibold shadow-xs' : 'text-brand-muted hover:text-brand-charcoal'"
                            @click="switchLocale('am')"
                        >
                            አማርኛ
                        </button>
                    </div>
                </div>

                <!-- Navigation links list -->
                <nav
                    class="flex flex-col gap-1 text-sm uppercase tracking-widest font-sans font-medium text-brand-charcoal"
                    aria-label="Mobile Drawer Navigation"
                >
                    <Link
                        href="/"
                        class="py-3 px-2 border-b border-brand-border/60 hover:text-brand-gold transition-colors flex items-center justify-between"
                        @click="mobileMenuOpen = false"
                    >
                        <span>{{ $t('nav.home') }}</span>
                        <span>›</span>
                    </Link>

                    <Link
                        href="/shop"
                        class="py-3 px-2 border-b border-brand-border/60 hover:text-brand-gold transition-colors flex items-center justify-between"
                        @click="mobileMenuOpen = false"
                    >
                        <span>{{ $t('nav.all_fragrances') }}</span>
                        <span>›</span>
                    </Link>

                    <!-- Categories list -->
                    <div class="py-2 px-2 border-b border-brand-border/60">
                        <p class="text-[11px] font-semibold text-brand-gold tracking-extrawide mb-2">
                            {{ $t('nav.categories') }}
                        </p>
                        <div class="grid grid-cols-2 gap-2 text-xs normal-case tracking-normal pl-1">
                            <Link
                                v-for="cat in categoriesNav"
                                :key="cat.id"
                                :href="`/category/${cat.slug}`"
                                class="py-1 text-brand-muted hover:text-brand-charcoal transition-colors"
                                @click="mobileMenuOpen = false"
                            >
                                {{ cat.name }}
                            </Link>
                        </div>
                    </div>

                    <Link
                        href="/brands"
                        class="py-3 px-2 border-b border-brand-border/60 hover:text-brand-gold transition-colors flex items-center justify-between"
                        @click="mobileMenuOpen = false"
                    >
                        <span>{{ $t('nav.brands') }}</span>
                        <span>›</span>
                    </Link>

                    <Link
                        href="/gifts"
                        class="py-3 px-2 border-b border-brand-border/60 hover:text-brand-gold transition-colors flex items-center justify-between"
                        @click="mobileMenuOpen = false"
                    >
                        <span>{{ $t('nav.gifts') }}</span>
                        <span>›</span>
                    </Link>

                    <Link
                        href="/about"
                        class="py-3 px-2 border-b border-brand-border/60 hover:text-brand-gold transition-colors flex items-center justify-between"
                        @click="mobileMenuOpen = false"
                    >
                        <span>{{ $t('nav.about') }}</span>
                        <span>›</span>
                    </Link>

                    <Link
                        href="/contact"
                        class="py-3 px-2 border-b border-brand-border/60 hover:text-brand-gold transition-colors flex items-center justify-between"
                        @click="mobileMenuOpen = false"
                    >
                        <span>{{ $t('nav.contact') }}</span>
                        <span>›</span>
                    </Link>

                    <Link
                        href="/delivery-returns"
                        class="py-3 px-2 border-b border-brand-border/60 hover:text-brand-gold transition-colors flex items-center justify-between text-brand-muted"
                        @click="mobileMenuOpen = false"
                    >
                        <span>{{ $t('nav.delivery_returns') }}</span>
                        <span>›</span>
                    </Link>
                </nav>

                <!-- Direct Customer Support Contact -->
                <div class="bg-brand-surface p-4 rounded-sm border border-brand-border/80 space-y-3">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-brand-gold">
                        {{ $t('nav.customer_assistance') }}
                    </p>
                    <p class="text-xs text-brand-muted leading-relaxed">
                        {{ currentLocale === 'am' ? 'ፈጣን ማድረሻ በመላው አዲስ አበባ። ስለ ሽቶዎች ወይም ትዕዛዝ ጥያቄ አለዎት?' : 'Fast delivery across Addis Ababa. Questions about scents or orders?' }}
                    </p>
                    <div class="flex flex-col gap-2 pt-1">
                        <a
                            :href="phoneUrl"
                            class="inline-flex items-center gap-2 text-xs font-medium text-brand-charcoal hover:text-brand-gold"
                        >
                            <span>📞 {{ currentLocale === 'am' ? 'ይደውሉ:' : 'Call:' }}</span>
                            <span>{{ settings.store_phone || '+251 91 123 4567' }}</span>
                        </a>
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 text-xs font-medium text-emerald-800 hover:text-emerald-900"
                        >
                            <span>💬 {{ currentLocale === 'am' ? 'የዋትስአፕ ቀጥታ መልእክት' : 'WhatsApp Direct Chat' }}</span>
                        </a>
                        <a
                            :href="telegramUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 text-xs font-medium text-sky-700 hover:text-sky-800"
                        >
                            <span>✈️ {{ currentLocale === 'am' ? 'የቴሌግራም ቻናል' : 'Telegram Channel' }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </Drawer>

        <!-- 4. Quick Search Modal -->
        <Modal
            :show="searchOpen"
            title="Search Fragrances"
            max-width="2xl"
            @close="searchOpen = false"
        >
            <form
                class="space-y-4"
                @submit.prevent="handleSearch"
            >
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by perfume name, brand, or notes (e.g. Sauvage, Oud, Vanilla)..."
                        class="w-full pl-11 pr-10 py-3.5 bg-brand-surface border border-brand-border rounded-sm text-sm focus:border-brand-gold focus:ring-1 focus:ring-brand-gold text-brand-charcoal placeholder:text-brand-muted/70"
                        autofocus
                    >
                    <svg
                        class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-muted"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                        />
                    </svg>

                    <!-- Loading or Clear button -->
                    <div class="absolute right-3.5 top-1/2 -translate-y-1/2 flex items-center">
                        <svg
                            v-if="isSearching"
                            class="animate-spin h-4 w-4 text-brand-gold"
                            xmlns="http://www.w3.org/2000/svg"
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
                        <button
                            v-else-if="searchQuery"
                            type="button"
                            class="text-brand-muted hover:text-brand-charcoal p-1"
                            aria-label="Clear search"
                            @click="searchQuery = ''; searchSuggestions = { products: [], brands: [], total: 0 };"
                        >
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Live Results: Brands Suggestions -->
                <div
                    v-if="searchSuggestions.brands && searchSuggestions.brands.length > 0"
                    class="pt-1"
                >
                    <p class="text-[10px] uppercase tracking-widest font-semibold text-brand-muted mb-2">
                        Matching Brands:
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="b in searchSuggestions.brands"
                            :key="b.id"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-sm bg-brand-surface border border-brand-border/80 hover:border-brand-gold hover:text-brand-gold text-xs font-medium text-brand-charcoal transition-colors"
                            @click="selectBrand(b.slug)"
                        >
                            <span class="font-serif italic font-semibold">{{ b.name }}</span>
                            <span class="text-[10px] text-brand-muted font-sans">({{ b.products_count }})</span>
                        </button>
                    </div>
                </div>

                <!-- Live Results: Product Suggestions Grid -->
                <div
                    v-if="searchSuggestions.products && searchSuggestions.products.length > 0"
                    class="pt-1 space-y-2"
                >
                    <div class="flex items-center justify-between text-[10px] uppercase tracking-widest font-semibold text-brand-muted">
                        <span>Matching Fragrances:</span>
                        <span>{{ searchSuggestions.total }} found</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto pr-1">
                        <button
                            v-for="p in searchSuggestions.products"
                            :key="p.id"
                            type="button"
                            class="flex items-center gap-3 p-2 rounded-sm border border-brand-border/60 hover:border-brand-gold hover:bg-brand-gold/5 text-left transition-all group"
                            @click="selectProduct(p.slug)"
                        >
                            <div class="w-12 h-12 bg-white rounded-sm shrink-0 overflow-hidden border border-brand-border/50 flex items-center justify-center">
                                <img
                                    :src="p.thumbnail_url || '/images/placeholder.svg'"
                                    :alt="p.name"
                                    class="w-full h-full object-contain p-1 group-hover:scale-105 transition-transform"
                                    loading="lazy"
                                >
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] text-brand-gold font-medium uppercase tracking-wider truncate">
                                    {{ p.brand_name }}
                                </p>
                                <p class="text-xs font-serif font-semibold text-brand-charcoal truncate group-hover:text-brand-gold transition-colors">
                                    {{ p.name }}
                                </p>
                                <div class="flex items-center justify-between gap-1 text-[11px] text-brand-charcoal font-medium mt-0.5">
                                    <span class="text-brand-muted text-[10px] uppercase">{{ p.concentration }}</span>
                                    <span>{{ p.formatted_price_from }}</span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- View All Results CTA -->
                    <div class="pt-2">
                        <button
                            type="button"
                            class="w-full py-2.5 px-4 text-xs font-medium uppercase tracking-widest bg-brand-charcoal text-brand-bg hover:bg-brand-charcoal/90 rounded-sm flex items-center justify-center gap-2 transition-colors"
                            @click="handleSearch"
                        >
                            <span>View All {{ searchSuggestions.total }} Fragrance Results for "{{ searchQuery }}"</span>
                            <span>→</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State when searching -->
                <div
                    v-else-if="!isSearching && searchQuery.trim().length >= 2"
                    class="py-6 text-center text-sm text-brand-muted"
                >
                    <p class="font-serif text-base text-brand-charcoal mb-1">
                        No fragrances found matching "{{ searchQuery }}"
                    </p>
                    <p class="text-xs">
                        Try searching for keywords like "Oud", "Sauvage", "Vanilla", or "Chanel".
                    </p>
                </div>

                <!-- Popular search keywords tags (shown when input is short) -->
                <div
                    v-else
                    class="pt-2"
                >
                    <p class="text-[11px] uppercase tracking-widest font-semibold text-brand-muted mb-2">
                        Popular Searches:
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="tag in ['Oud', 'Dior', 'Sauvage', 'Vanilla', 'Amber', 'Citrus', 'Men EDP', 'Gift Sets']"
                            :key="tag"
                            type="button"
                            class="px-2.5 py-1 text-xs bg-brand-surface hover:bg-brand-gold/15 text-brand-charcoal border border-brand-border/80 rounded-sm transition-colors"
                            @click="searchQuery = tag;"
                        >
                            {{ tag }}
                        </button>
                    </div>

                    <div class="flex justify-end pt-4">
                        <Button
                            type="submit"
                            variant="primary"
                            size="md"
                        >
                            Search Fragrances
                        </Button>
                    </div>
                </div>
            </form>
        </Modal>

        <!-- Main Content Slot -->
        <main class="flex-1 pb-16 md:pb-0">
            <slot />
        </main>

        <!-- 5. Floating WhatsApp / Telegram Button -->
        <div class="fixed bottom-20 md:bottom-8 right-4 md:right-8 z-40 flex flex-col items-end gap-2.5">
            <!-- Expandable chat menu -->
            <Transition
                enter-active-class="transform transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-3 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transform transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-3 scale-95"
            >
                <div
                    v-if="floatingChatOpen"
                    class="bg-brand-surface border border-brand-border rounded-sm shadow-2xl p-3 flex flex-col gap-2 min-w-[210px]"
                >
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-brand-muted px-1">
                        Chat with Soni Perfume
                    </p>

                    <!-- WhatsApp option -->
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-2.5 p-2 rounded-sm hover:bg-emerald-50 text-emerald-800 transition-colors text-xs font-medium"
                    >
                        <div class="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                            <svg
                                class="w-4 h-4 fill-current"
                                viewBox="0 0 24 24"
                            >
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.861.174.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.203c.043.071.043.418-.101.823z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-brand-charcoal">WhatsApp</div>
                            <div class="text-[10px] text-brand-muted">Instant customer chat</div>
                        </div>
                    </a>

                    <!-- Telegram option -->
                    <a
                        :href="telegramUrl"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-2.5 p-2 rounded-sm hover:bg-sky-50 text-sky-800 transition-colors text-xs font-medium"
                    >
                        <div class="w-7 h-7 rounded-full bg-sky-500 text-white flex items-center justify-center shrink-0">
                            <svg
                                class="w-4 h-4 fill-current"
                                viewBox="0 0 24 24"
                            >
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.52 2.77-1.16 3.35-1.36 3.73-1.37.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06-.01.19-.03.35z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-brand-charcoal">Telegram</div>
                            <div class="text-[10px] text-brand-muted">Admin bot & inquiries</div>
                        </div>
                    </a>
                </div>
            </Transition>

            <!-- Main Toggle Button -->
            <button
                type="button"
                class="w-13 h-13 md:w-14 md:h-14 rounded-full bg-brand-charcoal text-brand-gold hover:bg-black border-2 border-brand-gold/60 shadow-xl flex items-center justify-center transition-all duration-300 hover:scale-105 focus:outline-none"
                aria-label="Toggle chat options"
                @click="floatingChatOpen = !floatingChatOpen"
            >
                <!-- Open icon (Message Bubble) -->
                <svg
                    v-if="!floatingChatOpen"
                    class="w-6 h-6 md:w-7 md:h-7"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-.84-.962 4.484 4.484 0 00.354-1.222C3.766 17.202 3 14.73 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"
                    />
                </svg>

                <!-- Close icon (X) -->
                <svg
                    v-else
                    class="w-6 h-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <!-- 6. Mobile Bottom Navigation Bar (< md) -->
        <nav
            class="md:hidden fixed bottom-0 inset-x-0 bg-brand-bg/95 backdrop-blur-md border-t border-brand-border/80 z-30 py-1.5 px-4 flex justify-around items-center"
            aria-label="Mobile Bottom Navigation"
        >
            <Link
                href="/"
                class="flex flex-col items-center py-1 px-3 text-[10px] uppercase tracking-wider font-sans font-medium text-brand-charcoal hover:text-brand-gold transition-colors"
            >
                <svg
                    class="w-5 h-5 mb-0.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.6"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"
                    />
                </svg>
                <span>{{ $t('nav.home') }}</span>
            </Link>

            <Link
                href="/shop"
                class="flex flex-col items-center py-1 px-3 text-[10px] uppercase tracking-wider font-sans font-medium text-brand-charcoal hover:text-brand-gold transition-colors"
            >
                <svg
                    class="w-5 h-5 mb-0.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.6"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"
                    />
                </svg>
                <span>{{ $t('nav.shop') }}</span>
            </Link>

            <button
                type="button"
                class="flex flex-col items-center py-1 px-3 text-[10px] uppercase tracking-wider font-sans font-medium text-brand-charcoal hover:text-brand-gold transition-colors focus:outline-none"
                @click="searchOpen = true"
            >
                <svg
                    class="w-5 h-5 mb-0.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.6"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                    />
                </svg>
                <span>{{ $t('nav.search') }}</span>
            </button>

            <button
                type="button"
                class="flex flex-col items-center py-1 px-3 text-[10px] uppercase tracking-wider font-sans font-medium text-brand-charcoal hover:text-brand-gold transition-colors relative focus:outline-none"
                @click="miniCartOpen = true"
            >
                <div class="relative">
                    <svg
                        class="w-5 h-5 mb-0.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                        />
                    </svg>

                    <span
                        v-if="cartCount > 0"
                        class="absolute -top-1 -right-2 w-4 h-4 bg-brand-gold text-white text-[9px] font-bold rounded-full flex items-center justify-center leading-none"
                    >
                        {{ cartCount }}
                    </span>
                </div>
                <span>{{ $t('nav.bag') }}</span>
            </button>
        </nav>

        <!-- 7. Comprehensive Luxury Store Footer -->
        <footer class="bg-brand-dark text-brand-bg/90 pt-16 pb-24 md:pb-12 border-t border-white/10">
            <div class="container mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 lg:gap-8 pb-12 border-b border-white/10">
                    <!-- Col 1 & 2: Brand & Identity -->
                    <div class="lg:col-span-2 space-y-4">
                        <Link
                            href="/"
                            class="inline-flex flex-col"
                        >
                            <span class="font-serif text-3xl font-light tracking-widest text-brand-bg uppercase">
                                {{ settings.store_name?.split(' ')?.[0] || 'SONI' }}
                            </span>
                            <span class="text-[10px] font-sans font-semibold tracking-extrawide text-brand-gold uppercase">
                                {{ settings.store_name?.split(' ')?.slice(1)?.join(' ') || 'PERFUME' }}
                            </span>
                        </Link>

                        <p class="text-xs text-brand-bg/70 leading-relaxed max-w-sm font-sans font-light">
                            {{ settings.about_text || 'Addis Ababa’s curated destination for authentic designer, niche, and oriental fragrances. 100% genuine guaranteed with same-day express delivery across Addis Ababa.' }}
                        </p>

                        <div class="space-y-1.5 text-xs text-brand-bg/80 font-sans pt-2">
                            <p class="flex items-center gap-2">
                                <span class="text-brand-gold font-semibold">📍</span>
                                <span>{{ settings.store_address || 'Bole, Addis Ababa, Ethiopia' }}</span>
                            </p>
                            <p class="flex items-center gap-2">
                                <span class="text-brand-gold font-semibold">📞</span>
                                <a
                                    :href="phoneUrl"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ settings.store_phone || '+251 91 123 4567' }}
                                </a>
                            </p>
                            <p class="flex items-center gap-2">
                                <span class="text-brand-gold font-semibold">🕒</span>
                                <span>{{ settings.opening_hours || 'Mon–Sat: 9am–8pm | Sun: 10am–6pm' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Col 3: Quick Links -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                            {{ currentLocale === 'am' ? 'የሽቶ ስብስቦች' : 'Collections' }}
                        </h4>
                        <ul class="space-y-2 text-xs font-sans text-brand-bg/70 font-light">
                            <li>
                                <Link
                                    href="/shop"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ $t('nav.all_fragrances') }}
                                </Link>
                            </li>
                            <li
                                v-for="cat in categoriesNav.slice(0, 5)"
                                :key="cat.id"
                            >
                                <Link
                                    :href="`/category/${cat.slug}`"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ cat.name }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/brands"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ currentLocale === 'am' ? 'ዲዛይነር ብራንዶች' : 'Designer Brands' }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Col 4: Store & Service -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                            {{ currentLocale === 'am' ? 'መረጃዎች' : 'Information' }}
                        </h4>
                        <ul class="space-y-2 text-xs font-sans text-brand-bg/70 font-light">
                            <li>
                                <Link
                                    href="/about"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ $t('nav.about') }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/contact"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ currentLocale === 'am' ? 'የሱቅ አድራሻ እና ስልክ' : 'Store Location & Contact' }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/gifts"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ $t('nav.gifts') }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/delivery-returns"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ $t('nav.delivery_returns') }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/privacy"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ currentLocale === 'am' ? 'የግላዊነት መመሪያ' : 'Privacy Policy' }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/terms"
                                    class="hover:text-brand-gold transition-colors"
                                >
                                    {{ currentLocale === 'am' ? 'ውሎች እና ሁኔታዎች' : 'Terms & Conditions' }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Col 5: Newsletter Signup -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold uppercase tracking-widest text-brand-gold font-sans">
                            {{ currentLocale === 'am' ? 'ክበቡን ይቀላቀሉ' : 'Join The Circle' }}
                        </h4>
                        <p class="text-xs text-brand-bg/70 font-sans font-light leading-relaxed">
                            {{ currentLocale === 'am' ? 'ለአዳዲስ ሽቶዎች፣ ቅናሾች እና መረጃዎች ይመዝገቡ።' : 'Subscribe for exclusive private arrivals and VIP perfume announcements.' }}
                        </p>

                        <form
                            class="space-y-2"
                            @submit.prevent="submitNewsletter"
                        >
                            <div class="relative">
                                <input
                                    v-model="newsletterForm.email_or_phone"
                                    type="text"
                                    :placeholder="currentLocale === 'am' ? 'ስልክ ወይም ኢሜይል ያስገቡ' : 'Phone or email address'"
                                    required
                                    class="w-full px-3 py-2.5 bg-white/5 border border-white/15 rounded-sm text-xs text-white placeholder:text-white/40 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                                >
                            </div>

                            <Button
                                type="submit"
                                variant="primary"
                                size="sm"
                                block
                                :loading="newsletterForm.processing"
                            >
                                {{ currentLocale === 'am' ? 'ይመዝገቡ' : 'Subscribe' }}
                            </Button>
                        </form>
                    </div>
                </div>

                <!-- Footer Bottom Sub-Bar -->
                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-brand-bg/50 font-sans">
                    <p>© {{ new Date().getFullYear() }} {{ settings.store_name || 'Soni Perfume' }}. {{ currentLocale === 'am' ? 'መብቱ በህግ የተጠበቀ ነው። አዲስ አበባ፣ ኢትዮጵያ።' : 'All rights reserved. Addis Ababa, Ethiopia.' }}</p>

                    <!-- Social Channels -->
                    <div class="flex items-center gap-4 text-brand-bg/70">
                        <a
                            v-if="settings.instagram_url"
                            :href="settings.instagram_url"
                            target="_blank"
                            rel="noopener"
                            class="hover:text-brand-gold transition-colors"
                            aria-label="Instagram"
                        >
                            Instagram
                        </a>
                        <a
                            v-if="settings.tiktok_url"
                            :href="settings.tiktok_url"
                            target="_blank"
                            rel="noopener"
                            class="hover:text-brand-gold transition-colors"
                            aria-label="TikTok"
                        >
                            TikTok
                        </a>
                        <a
                            :href="telegramUrl"
                            target="_blank"
                            rel="noopener"
                            class="hover:text-brand-gold transition-colors"
                            aria-label="Telegram"
                        >
                            Telegram
                        </a>
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            class="hover:text-brand-gold transition-colors"
                            aria-label="WhatsApp"
                        >
                            WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
