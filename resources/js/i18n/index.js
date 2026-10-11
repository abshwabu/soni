import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import am from './locales/am.json';

const getInitialLocale = () => {
    if (typeof window !== 'undefined' && window.localStorage) {
        const saved = localStorage.getItem('soni_locale');
        if (saved && (saved === 'en' || saved === 'am')) {
            return saved;
        }
    }
    return 'en';
};

const initialLocale = getInitialLocale();

if (typeof document !== 'undefined') {
    document.documentElement.lang = initialLocale;
}

const i18n = createI18n({
    legacy: false,
    locale: initialLocale,
    fallbackLocale: 'en',
    messages: {
        en,
        am,
    },
});

export function setLocale(lang) {
    if (lang !== 'en' && lang !== 'am') return;
    i18n.global.locale.value = lang;
    if (typeof window !== 'undefined' && window.localStorage) {
        localStorage.setItem('soni_locale', lang);
    }
    if (typeof document !== 'undefined') {
        document.documentElement.lang = lang;
    }
}

export function getActiveLocale() {
    return i18n.global.locale.value;
}

export default i18n;
