import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                navy: {
                    DEFAULT: '#1B3A6E',
                    dark: '#0D1F3C',
                    light: '#2A4F96',
                    50: '#EEF2FA',
                    100: '#D4DEF2',
                    200: '#A9BDE5',
                    300: '#7E9CD8',
                    400: '#537BCB',
                    500: '#2A5ABE',
                    600: '#1B3A6E',
                    700: '#142D55',
                    800: '#0D1F3C',
                    900: '#071223',
                },
                gold: {
                    DEFAULT: '#F5B914',
                    light: '#FDE68A',
                    dark: '#D4970A',
                    50: '#FFFBEB',
                    100: '#FEF3C7',
                    200: '#FDE68A',
                    300: '#FCD34D',
                    400: '#FBBF24',
                    500: '#F5B914',
                    600: '#D4970A',
                    700: '#B27708',
                    800: '#8F5A06',
                    900: '#6D4004',
                },
                cream: '#FDF8F0',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                script: ['Dancing Script', 'cursive'],
            },
            backgroundImage: {
                'navy-gradient': 'linear-gradient(135deg, #0D1F3C 0%, #1B3A6E 100%)',
                'gold-gradient': 'linear-gradient(135deg, #F5B914 0%, #FDE68A 100%)',
                'hero-pattern': 'radial-gradient(ellipse at top right, #2A4F96 0%, #0D1F3C 60%)',
            },
            animation: {
                'float': 'float 6s ease-in-out infinite',
                'float-delay': 'float 6s ease-in-out 2s infinite',
                'marquee': 'marquee 30s linear infinite',
                'counter': 'counter 2s ease-out forwards',
                'fade-up': 'fadeUp 0.6s ease-out forwards',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-20px)' },
                },
                marquee: {
                    '0%': { transform: 'translateX(0%)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                fadeUp: {
                    '0%': { opacity: '0', transform: 'translateY(30px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            clipPath: {
                'diagonal': 'polygon(0 0, 100% 0, 100% 85%, 0 100%)',
                'diagonal-reverse': 'polygon(0 0, 100% 15%, 100% 100%, 0 100%)',
            },
        },
    },

    plugins: [forms],
};
