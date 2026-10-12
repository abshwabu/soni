<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '../Layouts/AppLayout.vue';
import ProductCard from '../Components/ProductCard.vue';
import SectionHeading from '../Components/SectionHeading.vue';
import Button from '../Components/Button.vue';
import Badge from '../Components/Badge.vue';
import Modal from '../Components/Modal.vue';
import Rating from '../Components/Rating.vue';
import AppImage from '../Components/AppImage.vue';
import { useToast } from '../Composables/useToast';
import { useAnalytics } from '../Composables/useAnalytics';

const { trackEvent } = useAnalytics();

const props = defineProps({
    banners: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    bestsellers: {
        type: Array,
        default: () => [],
    },
    newArrivals: {
        type: Array,
        default: () => [],
    },
    featuredBrands: {
        type: Array,
        default: () => [],
    },
    giftSets: {
        type: Array,
        default: () => [],
    },
    testimonials: {
        type: Array,
        default: () => [],
    },
    quizProducts: {
        type: Array,
        default: () => [],
    },
});

const { success, error } = useToast();

// ----------------------------------------------------
// 1. HERO SLIDER STATE
// ----------------------------------------------------
const currentSlide = ref(0);
let slideTimer = null;

const bannerSlides = computed(() => {
    const slides = (props.banners && props.banners.length > 0)
        ? props.banners
        : [
            {
                id: 1,
                title: 'Curated Scents for the Connoisseur',
                subtitle: 'Direct from Paris, Italy & Dubai to Addis Ababa.',
                button_text: 'Explore Collection',
                button_url: '/shop',
                image_url: '/images/placeholder.svg',
            },
        ];

    return slides.map((banner) => {
        let url = banner.button_url || '/shop';
        if (url.startsWith('/catalog')) {
            url = url.replace(/^\/catalog/, '/shop');
        }

        let imageUrl = banner.image_url;
        if (imageUrl && imageUrl.includes('/storage/')) {
            imageUrl = '/storage/' + imageUrl.split('/storage/')[1];
        }

        return {
            ...banner,
            button_url: url,
            image_url: imageUrl || '/images/placeholder.svg',
        };
    });
});

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % bannerSlides.value.length;
};

const prevSlide = () => {
    currentSlide.value = (currentSlide.value - 1 + bannerSlides.value.length) % bannerSlides.value.length;
};

const startSlideTimer = () => {
    stopSlideTimer();
    if (bannerSlides.value.length > 1) {
        slideTimer = setInterval(nextSlide, 6500);
    }
};

const stopSlideTimer = () => {
    if (slideTimer) clearInterval(slideTimer);
};

// ----------------------------------------------------
// 2. SCENT FINDER QUIZ STATE
// ----------------------------------------------------
const quizStep = ref(1);
const quizAnswers = ref({
    gender: 'all',
    occasion: 'daily',
    family: 'all',
    budget: 'any',
});
const quizCompleted = ref(false);
const quizResults = ref([]);

const selectQuizAnswer = (field, value) => {
    quizAnswers.value[field] = value;
    if (quizStep.value < 4) {
        quizStep.value++;
    } else {
        calculateQuizResults();
    }
};

const calculateQuizResults = () => {
    const pool = (props.quizProducts && props.quizProducts.length > 0)
        ? props.quizProducts
        : (props.bestsellers || []);

    if (!pool.length) {
        quizResults.value = [];
        quizCompleted.value = true;
        return;
    }

    const { gender, occasion, family, budget } = quizAnswers.value;

    // Score products based on user preferences with high fidelity
    const scored = pool.map((product) => {
        let score = 50;

        // Extract and normalize product attributes
        const productGender = (typeof product.gender === 'object' ? product.gender?.value : product.gender)?.toLowerCase() || '';
        const productFamily = (typeof product.scent_family === 'object' ? product.scent_family?.value : product.scent_family)?.toLowerCase() || '';
        const productConcentration = (typeof product.concentration === 'object' ? product.concentration?.value : product.concentration)?.toUpperCase() || '';
        const price = Number(product.price_from || product.variants?.[0]?.price || 0);

        // Combined notes & text blob for rich keyword matching
        const notesText = [
            product.name,
            product.scent_family,
            product.top_notes,
            product.heart_notes,
            product.base_notes,
            product.short_description,
            product.description,
        ].filter(Boolean).join(' ').toLowerCase();

        // -----------------------------------------------------------------
        // 1. GENDER COMPATIBILITY (Strict Priority)
        // -----------------------------------------------------------------
        if (gender === 'men') {
            if (productGender === 'men') {
                score += 50;
            } else if (productGender === 'unisex') {
                score += 35;
            } else if (productGender === 'women') {
                score -= 150; // Harsh penalty: never recommend women-only perfumes for him
            }
        } else if (gender === 'women') {
            if (productGender === 'women') {
                score += 50;
            } else if (productGender === 'unisex') {
                score += 35;
            } else if (productGender === 'men') {
                score -= 150; // Harsh penalty: never recommend men-only colognes for her
            }
        } else if (gender === 'unisex') {
            if (productGender === 'unisex') {
                score += 50;
            } else {
                score += 15;
            }
        }

        // -----------------------------------------------------------------
        // 2. SCENT PROFILE / OLFACTORY FAMILY MATCHING
        // -----------------------------------------------------------------
        if (family === 'oriental') {
            // "Royal Oud & Amber" — Oud, amber, incense, rich spices
            if (productFamily === 'oriental') score += 45;
            else if (productFamily === 'woody') score += 35;

            const orientalKeywords = ['oud', 'amber', 'incense', 'spice', 'spicy', 'saffron', 'smoke', 'cardamom', 'cinnamon', 'leather', 'myrrh', 'oriental'];
            const matchedNotes = orientalKeywords.filter((k) => notesText.includes(k)).length;
            score += Math.min(matchedNotes * 5, 20);
        } else if (family === 'fresh') {
            // "Fresh Citrus & Bergamot" — Crisp lemon, Italian bergamot, sea breeze
            if (productFamily === 'fresh' || productFamily === 'citrus') score += 45;

            const freshKeywords = ['bergamot', 'citrus', 'lemon', 'marine', 'sea', 'aquatic', 'grapefruit', 'mint', 'mandarin', 'lime', 'calabrian', 'ambroxan', 'fresh'];
            const matchedNotes = freshKeywords.filter((k) => notesText.includes(k)).length;
            score += Math.min(matchedNotes * 5, 20);
        } else if (family === 'floral') {
            // "Sensual Florals & Rose" — Bulgarian rose, jasmine, white musk
            if (productFamily === 'floral') score += 45;

            const floralKeywords = ['rose', 'jasmine', 'floral', 'tuberose', 'iris', 'peony', 'orange blossom', 'neroli', 'violet', 'ylang', 'gardenia', 'lily', 'musk'];
            const matchedNotes = floralKeywords.filter((k) => notesText.includes(k)).length;
            score += Math.min(matchedNotes * 5, 20);
        } else if (family === 'gourmand') {
            // "Sweet Vanilla & Gourmand" — Bourbon vanilla, tonka bean, caramel
            if (productFamily === 'gourmand') score += 45;

            const gourmandKeywords = ['vanilla', 'tonka', 'caramel', 'sweet', 'cherry', 'praline', 'chocolate', 'cacao', 'coffee', 'almond', 'marshmallow', 'sugar', 'gourmand'];
            const matchedNotes = gourmandKeywords.filter((k) => notesText.includes(k)).length;
            score += Math.min(matchedNotes * 5, 20);
        }

        // -----------------------------------------------------------------
        // 3. OCCASION MATCHING
        // -----------------------------------------------------------------
        if (occasion === 'daily') {
            // Daily Signature & Office: Clean, balanced, versatile
            if (productFamily === 'fresh' || productFamily === 'citrus') score += 25;
            else if (productFamily === 'woody') score += 15;
            else if (productFamily === 'floral') score += 10;

            const dailyKeywords = ['clean', 'balanced', 'versatile', 'office', 'signature', 'bergamot', 'lavender', 'vetiver', 'cedarwood', 'fresh'];
            if (dailyKeywords.some((k) => notesText.includes(k))) score += 10;
        } else if (occasion === 'evening') {
            // Evening & Romantic Nights: Warm, seductive, intimate, sillage
            if (productFamily === 'oriental' || productFamily === 'gourmand') score += 25;
            else if (productFamily === 'woody') score += 20;

            if (productConcentration.includes('PARFUM') || productConcentration.includes('EDP')) score += 10;

            const eveningKeywords = ['warm', 'seductive', 'intimate', 'night', 'sillage', 'vanilla', 'amber', 'tonka', 'leather', 'iris', 'spicy', 'cinnamon'];
            if (eveningKeywords.some((k) => notesText.includes(k))) score += 10;
        } else if (occasion === 'royal') {
            // Weddings & Royal Celebrations: Opulent, long-lasting oriental oud & French extrait
            if (productFamily === 'oriental' || productFamily === 'woody') score += 30;

            if (productConcentration.includes('PARFUM') || productConcentration.includes('EXTRAIT')) score += 15;

            const royalKeywords = ['oud', 'royal', 'opulent', 'saffron', 'amber', 'incense', 'rose', 'luxury', 'gold', 'prestige'];
            if (royalKeywords.some((k) => notesText.includes(k))) score += 15;
        } else if (occasion === 'casual') {
            // Weekend & Sunny Outdoor: Citrus, aquatic, fresh
            if (productFamily === 'fresh' || productFamily === 'citrus') score += 30;
            else if (productFamily === 'floral') score += 15;

            const casualKeywords = ['citrus', 'aquatic', 'sea', 'marine', 'lemon', 'fruit', 'sunny', 'outdoor', 'crisp', 'mint'];
            if (casualKeywords.some((k) => notesText.includes(k))) score += 10;
        }

        // -----------------------------------------------------------------
        // 4. BUDGET MATCHING
        // -----------------------------------------------------------------
        if (budget === 'under5k') {
            if (price > 0 && price < 5000) score += 40;
            else if (price >= 5000 && price <= 8000) score -= 25;
            else if (price > 8000) score -= 100; // Do not recommend luxury bottles far over budget
        } else if (budget === '5k-10k') {
            if (price >= 5000 && price <= 10000) score += 40;
            else if (price >= 3500 && price < 5000) score += 15;
            else if (price > 10000 && price <= 15000) score += 10;
            else if (price > 15000) score -= 60;
            else if (price < 3500) score -= 30;
        } else if (budget === 'above10k') {
            if (price > 10000) score += 40;
            else if (price >= 7000 && price <= 10000) score += 10;
            else if (price < 5000) score -= 80; // User explicitly wanted high-end luxury / niche
        } else if (budget === 'any') {
            score += 20;
        }

        // -----------------------------------------------------------------
        // 5. MERCHANDISING BONUSES
        // -----------------------------------------------------------------
        if (product.is_bestseller) score += 10;
        if (product.is_featured) score += 5;
        if (product.in_stock !== false) score += 15;

        return { product, score };
    });

    // Sort products by descending score
    scored.sort((a, b) => b.score - a.score);

    // Filter out items with heavily negative scores (gender/budget disqualification) unless pool is too small
    const positiveMatches = scored.filter((item) => item.score > 0);
    const source = positiveMatches.length >= 4 ? positiveMatches : scored;

    quizResults.value = source.slice(0, 4).map((item) => item.product);
    quizCompleted.value = true;
};

const resetQuiz = () => {
    quizStep.value = 1;
    quizAnswers.value = { gender: 'all', occasion: 'daily', family: 'all', budget: 'any' };
    quizCompleted.value = false;
    quizResults.value = [];
};

const scrollToQuiz = () => {
    const el = document.getElementById('scent-finder');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};

// ----------------------------------------------------
// 3. FIRST-VISIT PROMOTION MODAL STATE
// ----------------------------------------------------
const welcomeModalOpen = ref(false);
const welcomeEmailOrPhone = ref('');
const welcomeIsClaiming = ref(false);
const welcomeClaimed = ref(false);
const welcomePromoCode = ref('SONIWELCOME');
const promoCopied = ref(false);

const checkFirstVisit = () => {
    try {
        const stored = localStorage.getItem('soni_welcome_offer');
        if (stored) {
            const data = JSON.parse(stored);
            const now = new Date().getTime();
            // 30 days expiration in milliseconds
            if (data.expiry && now < data.expiry) {
                return; // Already seen within 30 days
            }
        }

        // Show modal after 5 seconds delay
        setTimeout(() => {
            if (!welcomeModalOpen.value && !welcomeClaimed.value) {
                welcomeModalOpen.value = true;
            }
        }, 5000);

        // Desktop exit-intent detection
        const handleMouseLeave = (e) => {
            if (e.clientY <= 5 && !welcomeModalOpen.value && !localStorage.getItem('soni_welcome_offer')) {
                welcomeModalOpen.value = true;
                document.removeEventListener('mouseleave', handleMouseLeave);
            }
        };

        if (window.innerWidth >= 1024) {
            document.addEventListener('mouseleave', handleMouseLeave);
        }
    } catch {
        // localStorage gracefully handled
    }
};

const closeWelcomeModal = () => {
    welcomeModalOpen.value = false;
    dismissOfferFor30Days();
};

const dismissOfferFor30Days = () => {
    try {
        const expiry = new Date().getTime() + 30 * 24 * 60 * 60 * 1000;
        localStorage.setItem('soni_welcome_offer', JSON.stringify({ dismissed: true, expiry }));
    } catch {
        // storage fallback
    }
};

const claimWelcomeOffer = async () => {
    if (!welcomeEmailOrPhone.value.trim()) {
        error('Please provide a phone number or email.');
        return;
    }

    welcomeIsClaiming.value = true;

    try {
        const response = await axios.post('/claim-welcome-offer', {
            email_or_phone: welcomeEmailOrPhone.value.trim(),
        });

        if (response.data.success) {
            welcomeClaimed.value = true;
            welcomePromoCode.value = response.data.promo_code || 'SONIWELCOME';
            dismissOfferFor30Days();
            success('Welcome gift claimed! Included with your first order.');
            trackEvent('welcome_modal_signup', {
                email_or_phone: welcomeEmailOrPhone.value.trim(),
            });
        }
    } catch {
        error('Could not claim offer. Please try again.');
    } finally {
        welcomeIsClaiming.value = false;
    }
};

const copyPromoCode = () => {
    navigator.clipboard.writeText(welcomePromoCode.value);
    promoCopied.value = true;
    setTimeout(() => {
        promoCopied.value = false;
    }, 2500);
};

// ----------------------------------------------------
// 4. TESTIMONIAL SLIDER STATE
// ----------------------------------------------------
const currentTestimonial = ref(0);

const nextTestimonial = () => {
    if (props.testimonials.length > 0) {
        currentTestimonial.value = (currentTestimonial.value + 1) % props.testimonials.length;
    }
};

const prevTestimonial = () => {
    if (props.testimonials.length > 0) {
        currentTestimonial.value = (currentTestimonial.value - 1 + props.testimonials.length) % props.testimonials.length;
    }
};

// ----------------------------------------------------
// 5. LIFECYCLE HOOKS
// ----------------------------------------------------
onMounted(() => {
    startSlideTimer();
    checkFirstVisit();
});

onUnmounted(() => {
    stopSlideTimer();
});

// JSON-LD Organization & Store Schema for SEO
const jsonLdSchema = computed(() => {
    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Store',
        name: 'Soni Perfume',
        image: bannerSlides.value[0]?.image_url || 'https://soniperfume.com/images/og-image.jpg',
        description: 'Addis Ababa’s curated destination for authentic designer, niche, and royal Arabian perfumes with same-day doorstep delivery.',
        telephone: '+251911234567',
        address: {
            '@type': 'PostalAddress',
            streetAddress: 'Bole Road, Atlas Area',
            addressLocality: 'Addis Ababa',
            addressCountry: 'ET',
        },
        priceRange: 'ETB 1,500 - ETB 45,000',
        openingHoursSpecification: [
            {
                '@type': 'OpeningHoursSpecification',
                dayOfWeek: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                opens: '09:00',
                closes: '20:00',
            },
            {
                '@type': 'OpeningHoursSpecification',
                dayOfWeek: ['Sunday'],
                opens: '10:00',
                closes: '18:00',
            },
        ],
    });
});
</script>

<template>
    <AppLayout>
        <!-- SEO Head with Preload, OG tags, and JSON-LD -->
        <Head>
            <title>Soni Perfume | Luxury & Arabian Fragrances in Addis Ababa</title>
            <meta
                name="description"
                content="Discover genuine designer perfumes, royal Arabian ouds, and niche fragrances in Addis Ababa. Sourced from Paris, Italy and Dubai with same-day express delivery."
            >
            <meta
                property="og:title"
                content="Soni Perfume | Luxury & Arabian Fragrances in Addis Ababa"
            >
            <meta
                property="og:description"
                content="Curated authentic perfumes in Addis Ababa with same-day doorstep delivery. No online card required — orders confirmed via Telegram & WhatsApp."
            >
            <meta
                property="og:type"
                content="website"
            >
            <meta
                property="og:image"
                :content="bannerSlides[0]?.image_url"
            >

            <!-- Preload hero banner image for instant mobile render -->
            <link
                v-if="bannerSlides[0]?.image_url"
                rel="preload"
                as="image"
                :href="bannerSlides[0]?.image_url"
                fetchpriority="high"
            >

            <!-- Structured Data Schema -->
            <component
                :is="'script'"
                type="application/ld+json"
            >
                {{ jsonLdSchema }}
            </component>
        </Head>

        <!-- ======================================================================= -->
        <!-- 1. HERO BANNER SLIDER (Full-bleed, DB-driven, 2 CTAs) -->
        <!-- ======================================================================= -->
        <section
            class="relative w-full overflow-hidden bg-brand-charcoal text-white"
            @mouseenter="stopSlideTimer"
            @mouseleave="startSlideTimer"
        >
            <div class="relative min-h-[520px] sm:min-h-[600px] md:min-h-[680px] lg:min-h-[740px] flex items-center">
                <!-- Slides Container -->
                <div
                    v-for="(banner, index) in bannerSlides"
                    v-show="currentSlide === index"
                    :key="banner.id"
                    class="absolute inset-0 transition-opacity duration-1000 ease-out"
                >
                    <!-- Background Image with High-End Editorial Dark Gradient -->
                    <div class="absolute inset-0">
                        <img
                            :src="banner.image_url"
                            :alt="banner.title"
                            class="w-full h-full object-cover object-center scale-100 transition-transform duration-10000 ease-out"
                            :loading="index === 0 ? 'eager' : 'lazy'"
                            :fetchpriority="index === 0 ? 'high' : 'auto'"
                            @error="(e) => e.target.src = '/images/placeholder.svg'"
                        >
                        <!-- Dual gradient overlay for readability -->
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-charcoal via-brand-charcoal/60 to-black/30" />
                        <div class="absolute inset-0 bg-black/25" />
                    </div>

                    <!-- Slide Text Content -->
                    <div class="relative container mx-auto h-full flex flex-col justify-center px-4 sm:px-6 lg:px-8 py-20 z-10">
                        <div class="max-w-2xl space-y-4 sm:space-y-6 animate-fadeIn">
                            <!-- Eyebrow Pill -->
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-md border border-white/20 rounded-sm">
                                <span class="w-2 h-2 rounded-full bg-brand-gold" />
                                <span class="text-[11px] uppercase tracking-widest text-brand-gold-300 font-sans font-semibold">
                                    Curated Haute Parfumerie
                                </span>
                            </div>

                            <!-- Big Serif Headline -->
                            <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-normal text-white tracking-tight leading-[1.08]">
                                {{ banner.title }}
                            </h1>

                            <!-- Subtitle -->
                            <p
                                v-if="banner.subtitle"
                                class="text-sm sm:text-base md:text-lg text-white/80 font-sans font-light max-w-lg leading-relaxed"
                            >
                                {{ banner.subtitle }}
                            </p>

                            <!-- 2 Action CTAs -->
                            <div class="pt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5">
                                <Button
                                    :href="banner.button_url || '/shop'"
                                    variant="primary"
                                    size="lg"
                                >
                                    {{ banner.button_text || 'Shop Now' }}
                                </Button>

                                <Button
                                    variant="outline-gold"
                                    size="lg"
                                    @click="scrollToQuiz"
                                >
                                    Find Your Scent Quiz
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slider Arrows (Desktop) -->
                <div
                    v-if="bannerSlides.length > 1"
                    class="absolute inset-y-0 inset-x-4 md:inset-x-8 flex items-center justify-between pointer-events-none z-20"
                >
                    <button
                        type="button"
                        class="pointer-events-auto p-3 rounded-full bg-black/30 hover:bg-black/60 text-white/80 hover:text-white border border-white/10 transition-colors focus:outline-none"
                        aria-label="Previous slide"
                        @click="prevSlide"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="pointer-events-auto p-3 rounded-full bg-black/30 hover:bg-black/60 text-white/80 hover:text-white border border-white/10 transition-colors focus:outline-none"
                        aria-label="Next slide"
                        @click="nextSlide"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Slider Dots & Slide Number Indicator -->
                <div
                    v-if="bannerSlides.length > 1"
                    class="absolute bottom-6 inset-x-0 flex items-center justify-center gap-3 z-20"
                >
                    <button
                        v-for="(_, idx) in bannerSlides"
                        :key="idx"
                        type="button"
                        class="p-3 inline-flex items-center justify-center focus:outline-none group"
                        :aria-label="`Go to slide ${idx + 1}`"
                        @click="currentSlide = idx"
                    >
                        <span
                            class="h-1.5 transition-all duration-300 rounded-full block"
                            :class="[currentSlide === idx ? 'w-8 bg-brand-gold' : 'w-2 bg-white/40 group-hover:bg-white/70']"
                        />
                    </button>
                </div>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 2. TRUST STRIP (4 Icons: Authentic, Fast Delivery, Pay on Delivery, Gift Wrap) -->
        <!-- ======================================================================= -->
        <section class="border-b border-brand-border/60 bg-brand-surface py-7 shadow-xs">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                    <!-- Prop 1: Authentic -->
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-sm bg-brand-surface-warm border border-brand-border flex items-center justify-center shrink-0 text-brand-gold">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider font-sans text-brand-charcoal">
                                {{ $i18n?.locale === 'am' ? '100% ኦሪጅናል' : '100% Authentic' }}
                            </p>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                {{ $i18n?.locale === 'am' ? 'ከፓሪስ፣ ጣሊያን እና ዱባይ' : 'Paris, Italy & Dubai batches' }}
                            </p>
                        </div>
                    </div>

                    <!-- Prop 2: Fast Delivery in Addis -->
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-sm bg-brand-surface-warm border border-brand-border flex items-center justify-center shrink-0 text-brand-gold">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75m0 3.75h4.125c.621 0 1.125.504 1.125 1.125v4.5"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider font-sans text-brand-charcoal">
                                {{ $i18n?.locale === 'am' ? 'በተመሳሳይ ቀን ማድረሻ' : 'Same-Day Delivery' }}
                            </p>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                {{ $i18n?.locale === 'am' ? 'በአዲስ አበባ እስከ ደጃፍዎ' : 'Doorstep arrival in Addis' }}
                            </p>
                        </div>
                    </div>

                    <!-- Prop 3: Pay on Delivery -->
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-sm bg-brand-surface-warm border border-brand-border flex items-center justify-center shrink-0 text-brand-gold">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m0 0v8.25m0 0h.75a.75.75 0 01.75.75v.75m0 0h16.5m-18 0v-8.25m18 8.25v-8.25"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider font-sans text-brand-charcoal">
                                {{ $i18n?.locale === 'am' ? 'ሲረከቡ ይክፈሉ' : 'Pay On Delivery' }}
                            </p>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                {{ $i18n?.locale === 'am' ? 'ሽቶውን አይተው ይክፈሉ' : 'Inspect bottle before payment' }}
                            </p>
                        </div>
                    </div>

                    <!-- Prop 4: Gift Wrapping -->
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-sm bg-brand-surface-warm border border-brand-border flex items-center justify-center shrink-0 text-brand-gold">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H4.5a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider font-sans text-brand-charcoal">
                                {{ $i18n?.locale === 'am' ? 'የቅንጦት ማሸጊያ' : 'Luxury Packaging' }}
                            </p>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                {{ $i18n?.locale === 'am' ? 'ልዩ የስጦታ ሳጥን' : 'Complimentary bespoke box' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 3. SHOP BY CATEGORY (Image Tiles) -->
        <!-- ======================================================================= -->
        <section class="py-16 sm:py-20 container mx-auto px-4 sm:px-6">
            <SectionHeading
                :eyebrow="$i18n?.locale === 'am' ? 'የሽቶ ዓለማት' : 'Curated Fragrance Worlds'"
                :title="$i18n?.locale === 'am' ? 'በምድብ ይምረጡ' : 'Shop By Category'"
                :description="$i18n?.locale === 'am' ? 'የሚወዱትን የሽቶ ዓይነት ከተመረጡ ስብስቦች ይምረጡ።' : 'Discover your olfactory identity across specialized collections.'"
                :action-text="$i18n?.locale === 'am' ? 'ሁሉም ምድቦች' : 'All Categories'"
                action-href="/shop"
            />

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-5">
                <Link
                    v-for="cat in categories"
                    :key="cat.id"
                    :href="`/category/${cat.slug}`"
                    class="group relative overflow-hidden aspect-[4/5] rounded-sm border border-brand-border bg-brand-charcoal block shadow-xs"
                >
                    <!-- Category Image -->
                    <img
                        :src="cat.image_url"
                        :alt="cat.name"
                        class="w-full h-full object-cover object-center transition-transform duration-700 ease-out group-hover:scale-108 opacity-85 group-hover:opacity-100"
                        loading="lazy"
                    >

                    <!-- Dark Gradient Vignette -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent" />

                    <!-- Text Overlay -->
                    <div class="absolute inset-x-3 bottom-3 sm:bottom-4 z-10 text-center">
                        <h3 class="font-serif text-lg sm:text-xl font-normal text-white group-hover:text-brand-gold transition-colors leading-tight">
                            {{ cat.name }}
                        </h3>
                        <p class="text-[10px] sm:text-[11px] text-white/70 uppercase tracking-widest font-sans mt-0.5">
                            {{ cat.products_count ? `${cat.products_count} Fragrances` : 'Explore' }}
                        </p>
                    </div>
                </Link>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 4. BESTSELLERS CAROUSEL & NEW ARRIVALS GRID -->
        <!-- ======================================================================= -->
        <!-- 4a. Bestsellers -->
        <section class="py-16 sm:py-20 bg-brand-surface-warm/40 border-y border-brand-border/60">
            <div class="container mx-auto px-4 sm:px-6">
                <SectionHeading
                    :eyebrow="$i18n?.locale === 'am' ? 'በአዲስ አበባ ተወዳጅ' : 'Addis Ababa Favorites'"
                    :title="$i18n?.locale === 'am' ? 'ተወዳጅ ሽቶዎች' : 'Most Loved Bestsellers'"
                    :description="$i18n?.locale === 'am' ? 'በአዲስ አበባ ከፍተኛ ተወዳጅነት ያተረፉ ኦሪጅናል ሽቶዎች።' : 'The most complimented and requested perfumes in our boutique.'"
                    :action-text="$i18n?.locale === 'am' ? 'ሁሉንም ተወዳጆች ይመልከቱ' : 'Shop All Bestsellers'"
                    action-href="/shop?sort=bestseller"
                />

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-6">
                    <ProductCard
                        v-for="product in bestsellers.slice(0, 8)"
                        :key="`best-${product.id}`"
                        :product="product"
                    />
                </div>
            </div>
        </section>

        <!-- 4b. New Arrivals -->
        <section class="py-16 sm:py-20 container mx-auto px-4 sm:px-6">
            <SectionHeading
                :eyebrow="$i18n?.locale === 'am' ? 'አዳዲስ ጭነቶች' : 'Fresh Shipments'"
                :title="$i18n?.locale === 'am' ? 'አዳዲስ የገቡ' : 'New Arrivals'"
                :description="$i18n?.locale === 'am' ? 'በቅርቡ የገቡ ኦሪጅናል ሽቶዎች እና አዳዲስ ምርቶች።' : 'Latest releases and recently imported bottles from international fragrance houses.'"
                :action-text="$i18n?.locale === 'am' ? 'ሁሉንም አዳዲስ ይመልከቱ' : 'Explore All New'"
                action-href="/shop?sort=newest"
            />

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-6">
                <ProductCard
                    v-for="product in newArrivals.slice(0, 8)"
                    :key="`new-${product.id}`"
                    :product="product"
                />
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 5. FEATURED BRANDS LOGO MARQUEE -->
        <!-- ======================================================================= -->
        <section class="py-14 sm:py-16 border-y border-brand-border/60 bg-brand-surface">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="text-center mb-8">
                    <p class="text-xs uppercase tracking-extrawide text-brand-gold-800 font-sans font-bold">
                        Prestige Perfumery
                    </p>
                    <h2 class="font-serif text-2xl sm:text-3xl font-normal text-brand-charcoal mt-1">
                        Curated International Houses
                    </h2>
                </div>

                <!-- Brand Logos Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4 sm:gap-6 items-center">
                    <Link
                        v-for="brand in featuredBrands"
                        :key="brand.id"
                        :href="`/shop?brand=${brand.id}`"
                        class="group p-4 bg-brand-surface-warm/40 border border-brand-border rounded-sm flex flex-col items-center justify-center hover:border-brand-gold/60 transition-all duration-300 aspect-square text-center"
                    >
                        <img
                            v-if="brand.logo_url"
                            :src="brand.logo_url"
                            :alt="brand.name"
                            class="w-14 h-14 object-contain filter grayscale group-hover:grayscale-0 transition-all duration-300 opacity-75 group-hover:opacity-100 mb-2"
                            loading="lazy"
                        >
                        <span class="font-serif text-sm font-medium text-brand-charcoal group-hover:text-brand-gold transition-colors">
                            {{ brand.name }}
                        </span>
                        <span class="text-[9px] uppercase tracking-wider text-brand-muted mt-0.5">
                            {{ brand.products_count ? `${brand.products_count} scents` : 'View' }}
                        </span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 6. SCENT FINDER QUIZ (4 Questions + Client-side Matching + Results CTA) -->
        <!-- ======================================================================= -->
        <section
            id="scent-finder"
            class="py-16 sm:py-24 bg-brand-charcoal text-white relative overflow-hidden"
        >
            <!-- Decorative background elements -->
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-brand-gold/10 blur-3xl pointer-events-none" />
            <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-brand-blush/10 blur-3xl pointer-events-none" />

            <div class="container mx-auto px-4 sm:px-6 relative z-10 max-w-4xl">
                <div class="text-center mb-10 sm:mb-14">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 text-brand-gold-300 rounded-sm text-xs uppercase tracking-widest font-semibold border border-brand-gold/30">
                        ✦ Bespoke Recommendation Engine
                    </span>
                    <h2 class="font-serif text-3xl sm:text-4xl md:text-5xl font-normal text-white mt-3">
                        The Soni Scent Finder
                    </h2>
                    <p class="text-xs sm:text-sm text-white/70 font-sans font-light mt-2 max-w-md mx-auto">
                        Answer 4 simple questions to discover your personalized fragrance matches.
                    </p>
                </div>

                <!-- Quiz Interactive Container -->
                <div class="bg-white/5 border border-white/15 backdrop-blur-md rounded-sm p-6 sm:p-10 shadow-2xl">
                    <!-- Progress Bar -->
                    <div
                        v-if="!quizCompleted"
                        class="mb-8"
                    >
                        <div class="flex items-center justify-between text-xs font-sans uppercase tracking-widest text-brand-gold-300 font-semibold mb-2">
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="quizStep > 1"
                                    type="button"
                                    class="text-white/60 hover:text-brand-gold text-[11px] normal-case tracking-normal transition-colors flex items-center gap-1 cursor-pointer"
                                    @click="quizStep--"
                                >
                                    ← Back
                                </button>
                                <span>Question {{ quizStep }} of 4</span>
                            </div>
                            <span>{{ quizStep * 25 }}%</span>
                        </div>
                        <div class="h-1 bg-white/10 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-brand-gold transition-all duration-300"
                                :style="{ width: `${quizStep * 25}%` }"
                            />
                        </div>
                    </div>

                    <!-- STEP 1: Who it is for -->
                    <div
                        v-if="quizStep === 1 && !quizCompleted"
                        class="space-y-6 animate-fadeIn"
                    >
                        <h3 class="font-serif text-2xl sm:text-3xl text-center text-white font-normal">
                            Who are you choosing this fragrance for?
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                            <button
                                type="button"
                                class="p-6 border rounded-sm text-center transition-all duration-200 hover:border-brand-gold group"
                                :class="[quizAnswers.gender === 'men' ? 'border-brand-gold bg-brand-gold/15' : 'border-white/15 hover:bg-white/5']"
                                @click="selectQuizAnswer('gender', 'men')"
                            >
                                <span class="text-2xl mb-2 block">🎩</span>
                                <h4 class="font-serif text-xl font-normal text-white group-hover:text-brand-gold">
                                    For Him
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans font-light">
                                    Masculine, bold, & sophisticated
                                </p>
                            </button>

                            <button
                                type="button"
                                class="p-6 border rounded-sm text-center transition-all duration-200 hover:border-brand-gold group"
                                :class="[quizAnswers.gender === 'women' ? 'border-brand-gold bg-brand-gold/15' : 'border-white/15 hover:bg-white/5']"
                                @click="selectQuizAnswer('gender', 'women')"
                            >
                                <span class="text-2xl mb-2 block">🌸</span>
                                <h4 class="font-serif text-xl font-normal text-white group-hover:text-brand-gold">
                                    For Her
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans font-light">
                                    Feminine, graceful, & enchanting
                                </p>
                            </button>

                            <button
                                type="button"
                                class="p-6 border rounded-sm text-center transition-all duration-200 hover:border-brand-gold group"
                                :class="[quizAnswers.gender === 'unisex' ? 'border-brand-gold bg-brand-gold/15' : 'border-white/15 hover:bg-white/5']"
                                @click="selectQuizAnswer('gender', 'unisex')"
                            >
                                <span class="text-2xl mb-2 block">✨</span>
                                <h4 class="font-serif text-xl font-normal text-white group-hover:text-brand-gold">
                                    Unisex / Shared
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans font-light">
                                    Versatile & boundary-free luxury
                                </p>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: Occasion -->
                    <div
                        v-else-if="quizStep === 2 && !quizCompleted"
                        class="space-y-6 animate-fadeIn"
                    >
                        <h3 class="font-serif text-2xl sm:text-3xl text-center text-white font-normal">
                            When will this fragrance be worn most?
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <button
                                type="button"
                                class="p-5 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5"
                                @click="selectQuizAnswer('occasion', 'daily')"
                            >
                                <h4 class="font-serif text-xl text-white">
                                    ☀️ Daily Signature & Office
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans font-light">
                                    Clean, balanced, and perfect for meetings & everyday wear
                                </p>
                            </button>

                            <button
                                type="button"
                                class="p-5 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5"
                                @click="selectQuizAnswer('occasion', 'evening')"
                            >
                                <h4 class="font-serif text-xl text-white">
                                    🌙 Evening & Romantic Nights
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans font-light">
                                    Warm, seductive, intimate, and leaving an unforgettable sillage
                                </p>
                            </button>

                            <button
                                type="button"
                                class="p-5 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5"
                                @click="selectQuizAnswer('occasion', 'royal')"
                            >
                                <h4 class="font-serif text-xl text-white">
                                    👑 Weddings & Royal Celebrations
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans font-light">
                                    Opulent, long-lasting oriental oud & French extrait
                                </p>
                            </button>

                            <button
                                type="button"
                                class="p-5 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5"
                                @click="selectQuizAnswer('occasion', 'casual')"
                            >
                                <h4 class="font-serif text-xl text-white">
                                    🌿 Weekend & Sunny Outdoor
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans font-light">
                                    Effortless citrus, aquatic, and revitalizing fresh notes
                                </p>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: Scent Family -->
                    <div
                        v-else-if="quizStep === 3 && !quizCompleted"
                        class="space-y-6 animate-fadeIn"
                    >
                        <h3 class="font-serif text-2xl sm:text-3xl text-center text-white font-normal">
                            Which olfactory profile sounds most appealing?
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2">
                            <button
                                type="button"
                                class="p-4 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5 flex items-center justify-between"
                                @click="selectQuizAnswer('family', 'oriental')"
                            >
                                <div>
                                    <h4 class="font-serif text-lg text-white">
                                        Royal Oud & Amber
                                    </h4>
                                    <p class="text-xs text-white/60 font-sans">
                                        Oud, amber, incense, rich spices
                                    </p>
                                </div>
                                <span class="text-xl">🪵</span>
                            </button>

                            <button
                                type="button"
                                class="p-4 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5 flex items-center justify-between"
                                @click="selectQuizAnswer('family', 'fresh')"
                            >
                                <div>
                                    <h4 class="font-serif text-lg text-white">
                                        Fresh Citrus & Bergamot
                                    </h4>
                                    <p class="text-xs text-white/60 font-sans">
                                        Crisp lemon, Italian bergamot, sea breeze
                                    </p>
                                </div>
                                <span class="text-xl">🍋</span>
                            </button>

                            <button
                                type="button"
                                class="p-4 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5 flex items-center justify-between"
                                @click="selectQuizAnswer('family', 'floral')"
                            >
                                <div>
                                    <h4 class="font-serif text-lg text-white">
                                        Sensual Florals & Rose
                                    </h4>
                                    <p class="text-xs text-white/60 font-sans">
                                        Bulgarian rose, jasmine, white musk
                                    </p>
                                </div>
                                <span class="text-xl">🌹</span>
                            </button>

                            <button
                                type="button"
                                class="p-4 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5 flex items-center justify-between"
                                @click="selectQuizAnswer('family', 'gourmand')"
                            >
                                <div>
                                    <h4 class="font-serif text-lg text-white">
                                        Sweet Vanilla & Gourmand
                                    </h4>
                                    <p class="text-xs text-white/60 font-sans">
                                        Bourbon vanilla, tonka bean, caramel
                                    </p>
                                </div>
                                <span class="text-xl">🍨</span>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 4: Budget -->
                    <div
                        v-else-if="quizStep === 4 && !quizCompleted"
                        class="space-y-6 animate-fadeIn"
                    >
                        <h3 class="font-serif text-2xl sm:text-3xl text-center text-white font-normal">
                            What is your desired investment range?
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <button
                                type="button"
                                class="p-5 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5"
                                @click="selectQuizAnswer('budget', 'under5k')"
                            >
                                <h4 class="font-serif text-xl text-white">
                                    Under ETB 5,000
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans">
                                    Everyday accessible luxury & body mists
                                </p>
                            </button>

                            <button
                                type="button"
                                class="p-5 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5"
                                @click="selectQuizAnswer('budget', '5k-10k')"
                            >
                                <h4 class="font-serif text-xl text-white">
                                    ETB 5,000 – ETB 10,000
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans">
                                    Renowned designer Eau de Parfum icons
                                </p>
                            </button>

                            <button
                                type="button"
                                class="p-5 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5"
                                @click="selectQuizAnswer('budget', 'above10k')"
                            >
                                <h4 class="font-serif text-xl text-white">
                                    Above ETB 10,000
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans">
                                    Prestige niche houses & rare extraits
                                </p>
                            </button>

                            <button
                                type="button"
                                class="p-5 border rounded-sm text-left transition-all duration-200 border-white/15 hover:border-brand-gold hover:bg-white/5"
                                @click="selectQuizAnswer('budget', 'any')"
                            >
                                <h4 class="font-serif text-xl text-white">
                                    Show All Matching Scents
                                </h4>
                                <p class="text-xs text-white/60 mt-1 font-sans">
                                    Recommend based purely on scent notes
                                </p>
                            </button>
                        </div>
                    </div>

                    <!-- RESULTS STATE -->
                    <div
                        v-else-if="quizCompleted"
                        class="space-y-8 animate-fadeIn"
                    >
                        <div class="text-center space-y-3">
                            <span class="text-brand-gold text-xs uppercase tracking-widest font-semibold">
                                ✓ Your Olfactory Profile Analysis Complete
                            </span>
                            <h3 class="font-serif text-3xl sm:text-4xl text-white">
                                Recommended Fragrances For You
                            </h3>
                            <p class="text-xs sm:text-sm text-white/70 font-sans font-light max-w-md mx-auto">
                                Handpicked according to your preferences in scent family, personality, and investment.
                            </p>
                            <!-- Selected Filters Summary Badges -->
                            <div class="flex flex-wrap justify-center gap-2 pt-1 text-[11px] font-sans">
                                <span class="px-3 py-1 rounded-full bg-white/10 text-white/90 border border-white/15">
                                    {{ quizAnswers.gender === 'men' ? '🎩 For Him' : quizAnswers.gender === 'women' ? '🌸 For Her' : '✨ Unisex / Shared' }}
                                </span>
                                <span class="px-3 py-1 rounded-full bg-white/10 text-white/90 border border-white/15">
                                    {{ quizAnswers.occasion === 'daily' ? '☀️ Daily Signature' : quizAnswers.occasion === 'evening' ? '🌙 Evening & Nights' : quizAnswers.occasion === 'royal' ? '👑 Royal Celebrations' : '🌿 Weekend Outdoor' }}
                                </span>
                                <span class="px-3 py-1 rounded-full bg-white/10 text-white/90 border border-white/15">
                                    {{ quizAnswers.family === 'oriental' ? '🪵 Oud & Amber' : quizAnswers.family === 'fresh' ? '🍋 Fresh Citrus' : quizAnswers.family === 'floral' ? '🌹 Sensual Florals' : '🍨 Sweet Gourmand' }}
                                </span>
                                <span class="px-3 py-1 rounded-full bg-white/10 text-white/90 border border-white/15">
                                    {{ quizAnswers.budget === 'under5k' ? '💰 Under ETB 5K' : quizAnswers.budget === '5k-10k' ? '💰 ETB 5K–10K' : quizAnswers.budget === 'above10k' ? '💎 Above ETB 10K' : '✨ Any Budget' }}
                                </span>
                            </div>
                        </div>

                        <!-- Results Products Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <ProductCard
                                v-for="product in quizResults"
                                :key="`quiz-${product.id}`"
                                :product="product"
                            />
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4 border-t border-white/10">
                            <Button
                                variant="ghost"
                                size="md"
                                class="text-white hover:text-brand-gold"
                                @click="resetQuiz"
                            >
                                ↺ Retake Quiz
                            </Button>

                            <Button
                                href="/shop"
                                variant="primary"
                                size="md"
                            >
                                Browse Full Catalog →
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 7. BRAND STORY (Split Layout with Image + Settings About Text) -->
        <!-- ======================================================================= -->
        <section class="py-16 sm:py-24 container mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-center">
                <!-- Left: Luxury Visual Column -->
                <div class="lg:col-span-6 relative">
                    <div class="relative aspect-[4/5] rounded-sm overflow-hidden border border-brand-border shadow-xl bg-brand-surface-warm">
                        <img
                            :src="categories[3]?.image_url || '/images/placeholder.svg'"
                            alt="Soni Perfume Boutique Ambience"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-charcoal/60 via-transparent to-transparent" />
                    </div>

                    <!-- Floating luxury quote box -->
                    <div class="absolute -bottom-6 -right-4 sm:-right-6 bg-brand-surface p-5 sm:p-6 rounded-sm border border-brand-border shadow-xl max-w-xs hidden sm:block">
                        <p class="font-serif text-base text-brand-charcoal italic leading-snug">
                            "Perfume is the art that makes memory speak."
                        </p>
                        <p class="text-[10px] uppercase tracking-widest text-brand-gold-800 font-sans font-semibold mt-2">
                            — Soni Perfume Philosophy
                        </p>
                    </div>
                </div>

                <!-- Right: Editorial Brand Story -->
                <div class="lg:col-span-6 space-y-6">
                    <div>
                        <p class="text-xs uppercase tracking-extrawide text-brand-gold-800 font-sans font-semibold mb-2">
                            Our Origins & Commitment
                        </p>
                        <h2 class="font-serif text-3xl sm:text-4xl md:text-5xl font-normal text-brand-charcoal leading-tight">
                            Crafted for Addis Ababa's Most Discerning
                        </h2>
                    </div>

                    <p class="text-brand-muted text-sm sm:text-base font-sans font-light leading-relaxed">
                        {{ $page.props.settings?.about_text || 'Soni Perfume is Addis Ababa’s destination for genuine designer and luxury oriental fragrances. Sourced directly from Paris, Italy, and Dubai, every bottle in our collection is 100% authentic with fast same-day delivery across Addis Ababa.' }}
                    </p>

                    <div class="grid grid-cols-2 gap-6 pt-2 border-t border-brand-border/60">
                        <div>
                            <span class="font-serif text-3xl font-normal text-brand-gold-800 block">100%</span>
                            <span class="text-xs uppercase tracking-wider text-brand-charcoal font-semibold">Direct Sourcing</span>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                Verified batch codes with zero replicas.
                            </p>
                        </div>

                        <div>
                            <span class="font-serif text-3xl font-normal text-brand-gold-800 block">Same-Day</span>
                            <span class="text-xs uppercase tracking-wider text-brand-charcoal font-semibold">Doorstep Express</span>
                            <p class="text-[11px] text-brand-muted mt-0.5">
                                Fast hand-delivery across Addis Ababa.
                            </p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <Button
                            href="/about"
                            variant="dark"
                            size="md"
                        >
                            Read Our Full Story →
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 8. GIFT SETS HIGHLIGHT -->
        <!-- ======================================================================= -->
        <section class="py-16 sm:py-20 bg-brand-surface-warm/40 border-y border-brand-border/60">
            <div class="container mx-auto px-4 sm:px-6">
                <SectionHeading
                    eyebrow="The Art of Giving"
                    title="Luxury Gift Sets & Coffrets"
                    description="Elegantly boxed duos, miniatures, and paired scent sets created for memorable gifting."
                    action-text="Shop All Gift Sets"
                    action-href="/category/gift-sets"
                />

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
                    <ProductCard
                        v-for="product in giftSets.slice(0, 4)"
                        :key="`gift-${product.id}`"
                        :product="product"
                    />
                </div>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 9. TESTIMONIALS SLIDER -->
        <!-- ======================================================================= -->
        <section
            v-if="testimonials.length > 0"
            class="py-16 sm:py-24 container mx-auto px-4 sm:px-6"
        >
            <SectionHeading
                align="center"
                eyebrow="Client Experiences"
                title="Voices of Elegance"
                description="Read why perfume lovers in Addis Ababa trust Soni Perfume for their signature fragrances."
            />

            <div class="max-w-3xl mx-auto relative bg-brand-surface border border-brand-border rounded-sm p-8 sm:p-12 text-center shadow-sm">
                <!-- Testimonial Item with Transition -->
                <div
                    v-for="(test, index) in testimonials"
                    v-show="currentTestimonial === index"
                    :key="test.id"
                    class="space-y-4 animate-fadeIn"
                >
                    <div class="flex justify-center mb-2">
                        <Rating
                            :value="test.rating"
                            size="md"
                        />
                    </div>

                    <p class="font-serif text-xl sm:text-2xl text-brand-charcoal italic leading-relaxed max-w-xl mx-auto">
                        "{{ test.text }}"
                    </p>

                    <div class="pt-4 border-t border-brand-border/60">
                        <p class="font-sans font-semibold text-xs uppercase tracking-widest text-brand-charcoal">
                            {{ test.name }}
                        </p>
                        <span class="text-[11px] text-brand-gold-800 font-sans">Verified Client • Addis Ababa</span>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="flex items-center justify-between absolute inset-y-0 inset-x-2 pointer-events-none">
                    <button
                        type="button"
                        class="pointer-events-auto p-2 text-brand-muted hover:text-brand-charcoal transition-colors focus:outline-none"
                        aria-label="Previous review"
                        @click="prevTestimonial"
                    >
                        ‹
                    </button>
                    <button
                        type="button"
                        class="pointer-events-auto p-2 text-brand-muted hover:text-brand-charcoal transition-colors focus:outline-none"
                        aria-label="Next review"
                        @click="nextTestimonial"
                    >
                        ›
                    </button>
                </div>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 10. VISIT US (Address, Hours, Map Embed, Directions, Phone/WhatsApp) -->
        <!-- ======================================================================= -->
        <section class="py-16 sm:py-24 bg-brand-surface border-t border-brand-border/60">
            <div class="container mx-auto px-4 sm:px-6">
                <SectionHeading
                    eyebrow="Our Physical Boutique"
                    title="Visit Soni Perfume In Addis Ababa"
                    description="Experience our complete collection in person with our private scent advisors."
                />

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                    <!-- Boutique Info (5 cols) -->
                    <div class="lg:col-span-5 bg-brand-surface-warm/50 border border-brand-border p-6 sm:p-8 rounded-sm space-y-6 flex flex-col justify-between">
                        <div class="space-y-5">
                            <div>
                                <span class="text-xs uppercase tracking-widest font-semibold text-brand-gold-800 font-sans">
                                    Flagship Location
                                </span>
                                <h3 class="font-serif text-2xl font-normal text-brand-charcoal mt-1">
                                    Bole Branch • Addis Ababa
                                </h3>
                                <p class="text-xs text-brand-muted font-sans mt-1">
                                    {{ $page.props.settings?.store_address || 'Bole, near Edna Mall, Addis Ababa, Ethiopia' }}
                                </p>
                            </div>

                            <div class="pt-4 border-t border-brand-border/60 space-y-3 text-xs font-sans">
                                <div>
                                    <span class="font-semibold uppercase tracking-wider text-brand-charcoal block">🕒 Opening Hours</span>
                                    <span class="text-brand-muted">{{ $page.props.settings?.opening_hours || 'Monday – Saturday: 9:00 AM – 8:00 PM | Sunday: 10:00 AM – 6:00 PM' }}</span>
                                </div>

                                <div>
                                    <span class="font-semibold uppercase tracking-wider text-brand-charcoal block">📞 Phone Reservations & Orders</span>
                                    <a
                                        :href="`tel:${$page.props.settings?.store_phone || '+251911234567'}`"
                                        class="text-brand-gold-800 hover:text-brand-gold-900 underline"
                                    >
                                        {{ $page.props.settings?.store_phone || '+251 91 123 4567' }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Direct buttons -->
                        <div class="pt-4 border-t border-brand-border/60 flex flex-col sm:flex-row gap-3">
                            <a
                                :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent($page.props.settings?.store_address || 'Bole, Addis Ababa')}`"
                                target="_blank"
                                rel="noopener"
                                class="flex-1 inline-flex items-center justify-center py-2.5 px-4 bg-brand-charcoal text-white text-xs uppercase tracking-widest font-medium rounded-sm hover:bg-black transition-colors"
                            >
                                Get Directions →
                            </a>

                            <a
                                :href="`https://wa.me/${($page.props.settings?.store_whatsapp || '+251911234567').replace(/[^0-9]/g, '')}`"
                                target="_blank"
                                rel="noopener"
                                class="flex-1 inline-flex items-center justify-center py-2.5 px-4 bg-emerald-700 text-white text-xs uppercase tracking-widest font-medium rounded-sm hover:bg-emerald-800 transition-colors"
                            >
                                WhatsApp Chat
                            </a>
                        </div>
                    </div>

                    <!-- Google Maps Embed (7 cols) -->
                    <div class="lg:col-span-7 bg-brand-surface-warm border border-brand-border rounded-sm overflow-hidden min-h-[350px]">
                        <iframe
                            v-if="$page.props.settings?.google_maps_embed"
                            :src="$page.props.settings?.google_maps_embed"
                            class="w-full h-full min-h-[350px] border-0"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Soni Perfume Boutique Location Map"
                        />
                        <!-- Fallback styled interactive map placeholder -->
                        <div
                            v-else
                            class="w-full h-full min-h-[350px] flex flex-col items-center justify-center p-8 text-center bg-brand-surface-warm"
                        >
                            <span class="text-4xl text-brand-gold mb-3">📍</span>
                            <p class="font-serif text-2xl font-normal text-brand-charcoal">
                                Soni Perfume Boutique
                            </p>
                            <p class="text-xs text-brand-muted max-w-sm mt-1">
                                {{ $page.props.settings?.store_address || 'Bole, Addis Ababa, Ethiopia' }}
                            </p>
                            <a
                                :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent($page.props.settings?.store_address || 'Bole, Addis Ababa')}`"
                                target="_blank"
                                rel="noopener"
                                class="mt-4 text-xs font-sans uppercase tracking-widest font-semibold text-brand-gold-800 hover:text-brand-gold-900 underline"
                            >
                                Open in Google Maps →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 11. INSTAGRAM / TIKTOK SOCIAL STRIP -->
        <!-- ======================================================================= -->
        <section class="py-14 bg-brand-bg border-t border-brand-border/60">
            <div class="container mx-auto px-4 sm:px-6 mb-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                <div>
                    <span class="text-xs uppercase tracking-widest text-brand-gold-800 font-sans font-semibold">
                        Social Feed & Aesthetics
                    </span>
                    <h3 class="font-serif text-2xl font-normal text-brand-charcoal">
                        Follow @soniperfume on Instagram & TikTok
                    </h3>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        :href="$page.props.settings?.instagram_url || 'https://instagram.com/soniperfume'"
                        target="_blank"
                        rel="noopener"
                        class="px-4 py-2 border border-brand-border bg-brand-surface text-brand-charcoal hover:border-brand-gold text-xs uppercase tracking-wider font-medium rounded-sm transition-colors"
                    >
                        Instagram
                    </a>
                    <a
                        :href="$page.props.settings?.tiktok_url || 'https://tiktok.com/@soniperfume'"
                        target="_blank"
                        rel="noopener"
                        class="px-4 py-2 border border-brand-border bg-brand-surface text-brand-charcoal hover:border-brand-gold text-xs uppercase tracking-wider font-medium rounded-sm transition-colors"
                    >
                        TikTok
                    </a>
                </div>
            </div>

            <!-- 6 Visual Gallery Tiles -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 px-2 sm:px-4">
                <a
                    v-for="(cat, idx) in categories.slice(0, 6)"
                    :key="`social-${idx}`"
                    :href="$page.props.settings?.instagram_url || 'https://instagram.com/soniperfume'"
                    target="_blank"
                    rel="noopener"
                    class="group relative aspect-square overflow-hidden rounded-sm bg-brand-surface-warm"
                >
                    <img
                        :src="cat.image_url"
                        :alt="`Soni Perfume Instagram aesthetic ${idx + 1}`"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-108"
                        loading="lazy"
                    >
                    <div class="absolute inset-0 bg-brand-charcoal/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center text-white text-xs uppercase tracking-widest font-sans font-semibold">
                        @soniperfume
                    </div>
                </a>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 12. VIP NEWSLETTER / OFFER BLOCK -->
        <!-- ======================================================================= -->
        <section class="py-16 sm:py-20 bg-brand-charcoal text-white border-t border-white/10">
            <div class="container mx-auto px-4 sm:px-6 max-w-3xl text-center space-y-6">
                <span class="inline-block text-xs uppercase tracking-extrawide text-brand-gold font-sans font-semibold">
                    Exclusive Privileges
                </span>
                <h2 class="font-serif text-3xl sm:text-4xl md:text-5xl font-normal text-white">
                    Join The Soni Perfume Circle
                </h2>
                <p class="text-xs sm:text-sm text-white/70 font-sans font-light max-w-lg mx-auto leading-relaxed">
                    Be the first in Addis Ababa to receive private notifications when rare European and Dubai fragrance batches arrive.
                </p>

                <!-- Signup inline form -->
                <form
                    class="flex flex-col sm:flex-row items-center justify-center gap-3 max-w-md mx-auto pt-2"
                    @submit.prevent="claimWelcomeOffer"
                >
                    <input
                        v-model="welcomeEmailOrPhone"
                        type="text"
                        placeholder="Enter phone or email address..."
                        required
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-sm text-xs text-white placeholder:text-white/40 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                    >
                    <Button
                        type="submit"
                        variant="primary"
                        size="md"
                        class="w-full sm:w-auto shrink-0"
                    >
                        Join Circle
                    </Button>
                </form>

                <p class="text-[10px] text-white/75 font-sans tracking-wide">
                    Zero spam. Unsubscribe anytime with a single message.
                </p>
            </div>
        </section>

        <!-- ======================================================================= -->
        <!-- 13. FIRST-VISIT PROMOTION WELCOME MODAL -->
        <!-- ======================================================================= -->
        <Modal
            :show="welcomeModalOpen"
            max-width="md"
            @close="closeWelcomeModal"
        >
            <div class="text-center p-2 sm:p-4 space-y-5">
                <!-- Icon Header -->
                <div class="w-16 h-16 rounded-full bg-brand-gold/15 text-brand-gold mx-auto flex items-center justify-center text-3xl">
                    🎁
                </div>

                <!-- Unclaimed Form State -->
                <div
                    v-if="!welcomeClaimed"
                    class="space-y-4"
                >
                    <div>
                        <span class="text-[11px] uppercase tracking-extrawide font-semibold text-brand-gold-800 font-sans">
                            Welcome Exclusive
                        </span>
                        <h3 class="font-serif text-2xl sm:text-3xl font-normal text-brand-charcoal mt-1">
                            A Welcome Gift For You
                        </h3>
                        <p class="text-xs text-brand-muted font-sans font-light mt-2 leading-relaxed">
                            {{ $page.props.settings?.welcome_discount_text || 'Enjoy a complimentary luxury sample vial with your first order in Addis Ababa!' }}
                        </p>
                    </div>

                    <form
                        class="space-y-3 pt-2 text-left"
                        @submit.prevent="claimWelcomeOffer"
                    >
                        <div>
                            <label class="block text-[11px] font-sans uppercase tracking-wider font-semibold text-brand-charcoal mb-1">
                                Your Phone or Email:
                            </label>
                            <input
                                v-model="welcomeEmailOrPhone"
                                type="text"
                                placeholder="e.g. 0911234567 or selam@gmail.com"
                                required
                                class="w-full px-3.5 py-2.5 bg-brand-surface border border-brand-border rounded-sm text-xs text-brand-charcoal focus:border-brand-gold focus:ring-1 focus:ring-brand-gold"
                            >
                        </div>

                        <Button
                            type="submit"
                            variant="primary"
                            size="md"
                            block
                            :loading="welcomeIsClaiming"
                        >
                            Claim Welcome Gift
                        </Button>
                    </form>

                    <button
                        type="button"
                        class="text-xs text-brand-muted hover:text-brand-charcoal font-sans transition-colors pt-1"
                        @click="closeWelcomeModal"
                    >
                        No thanks, continue browsing
                    </button>
                </div>

                <!-- Claimed Success State -->
                <div
                    v-else
                    class="space-y-4 animate-fadeIn"
                >
                    <div class="space-y-1">
                        <span class="text-xs text-emerald-800 uppercase tracking-widest font-semibold font-sans">
                            ✓ Offer Successfully Claimed
                        </span>
                        <h3 class="font-serif text-2xl font-normal text-brand-charcoal">
                            Your Gift Is Activated!
                        </h3>
                        <p class="text-xs text-brand-muted font-sans leading-relaxed">
                            A complimentary luxury sample vial will be included with your first order automatically.
                        </p>
                    </div>

                    <!-- Promo Code Box -->
                    <div class="p-3 bg-brand-surface-warm border border-brand-border rounded-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-brand-muted uppercase tracking-wider block">Your Welcome Code</span>
                            <span class="font-mono font-bold text-base text-brand-charcoal tracking-wider">{{ welcomePromoCode }}</span>
                        </div>
                        <button
                            type="button"
                            class="px-3 py-1.5 bg-brand-charcoal text-white text-xs uppercase tracking-wider font-medium rounded-sm hover:bg-black transition-colors"
                            @click="copyPromoCode"
                        >
                            {{ promoCopied ? 'Copied! ✓' : 'Copy Code' }}
                        </button>
                    </div>

                    <Button
                        variant="primary"
                        size="md"
                        block
                        @click="welcomeModalOpen = false"
                    >
                        Start Shopping Now →
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
