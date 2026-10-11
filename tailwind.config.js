import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        container: {
            center: true,
            padding: {
                DEFAULT: '1rem',
                sm: '1.5rem',
                lg: '2rem',
                xl: '2.5rem',
            },
            screens: {
                sm: '640px',
                md: '768px',
                lg: '1024px',
                xl: '1280px',
                '2xl': '1440px',
            },
        },
        extend: {
            colors: {
                brand: {
                    bg: '#FAF7F2',
                    surface: '#FFFFFF',
                    'surface-warm': '#F5EFE6',
                    charcoal: '#1C1917',
                    dark: '#121110',
                    gold: {
                        DEFAULT: '#B08D57',
                        50: '#FBF9F7',
                        100: '#F7F4EE',
                        200: '#EBD9D0',
                        300: '#DFD1BC',
                        400: '#C8AF89',
                        500: '#B08D57',
                        600: '#9E7F4E',
                        700: '#846A41',
                        800: '#6A5534',
                        900: '#56452B',
                    },
                    blush: {
                        light: '#F8F2EE',
                        DEFAULT: '#EBD9D0',
                        dark: '#D8BCB0',
                    },
                    muted: '#78716C',
                    subtle: '#A8A29E',
                    border: '#E7E0D8',
                    'border-light': '#F0EBE4',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['"Cormorant Garamond"', ...defaultTheme.fontFamily.serif],
                heading: ['"Cormorant Garamond"', ...defaultTheme.fontFamily.serif],
            },
            borderRadius: {
                none: '0px',
                sm: '2px',
                DEFAULT: '2px',
                md: '4px',
                lg: '6px',
            },
            letterSpacing: {
                widest: '.2em',
                extrawide: '.25em',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(16px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideDown: {
                    '0%': { opacity: '0', transform: 'translateY(-16px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideInRight: {
                    '0%': { transform: 'translateX(100%)' },
                    '100%': { transform: 'translateX(0)' },
                },
                slideInLeft: {
                    '0%': { transform: 'translateX(-100%)' },
                    '100%': { transform: 'translateX(0)' },
                },
                shimmer: {
                    '100%': { transform: 'translateX(100%)' },
                },
            },
            animation: {
                fadeIn: 'fadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                slideUp: 'slideUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                slideDown: 'slideDown 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                slideInRight: 'slideInRight 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                slideInLeft: 'slideInLeft 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                shimmer: 'shimmer 1.8s infinite',
            },
        },
    },

    plugins: [forms, typography],
};
