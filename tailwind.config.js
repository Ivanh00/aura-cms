const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */

function withOpacityValue(variable) {
  return ({ opacityValue }) => {
    if (opacityValue === undefined) {
      return `rgb(var(${variable}))`
    }
    return `rgb(var(${variable}) / ${opacityValue})`
  }
}

module.exports = {
    // darkMode: 'class',
    darkMode: 'selector',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './src/*.php',
    ],

    theme: {
        extend: {
            backgroundImage: {
                'gradient-conic': 'conic-gradient(var(--tw-gradient-stops))',
            },
            keyframes: {
                disco: {
                '0%': { transform: 'translateY(-50%) rotate(0deg)' },
                '100%': { transform: 'translateY(-50%) rotate(360deg)' },
                },
                countdown: {
                '0%': { transform: 'scaleX(1)' },
                '100%': { transform: 'scaleX(0)' },
                },
            },
            animation: {
                disco: 'disco 1.5s linear infinite',
                countdown: 'countdown 3s linear 1',
            },
            colors: {
                white: withOpacityValue('--aura-color-white'),
                black: withOpacityValue('--aura-color-black'),
                aura: {
                    primary: withOpacityValue('--aura-color-primary'),
                    background: withOpacityValue('--aura-color-background'),
                    panel: withOpacityValue('--aura-color-panel'),
                    border: withOpacityValue('--aura-color-border'),
                    text: withOpacityValue('--aura-color-text'),
                    muted: withOpacityValue('--aura-color-muted'),
                    success: withOpacityValue('--aura-color-success'),
                    warning: withOpacityValue('--aura-color-warning'),
                    danger: withOpacityValue('--aura-color-danger'),
                    white: withOpacityValue('--aura-color-white'),
                    black: withOpacityValue('--aura-color-black'),
                },
                sidebar: {
                    'bg' : withOpacityValue('--sidebar-bg'),
                    'bg-hover' : withOpacityValue('--sidebar-bg-hover'),
                    'bg-dropdown' : withOpacityValue('--sidebar-bg-dropdown'),
                    'icon' : withOpacityValue('--sidebar-icon'),
                    'icon-hover' : withOpacityValue('--sidebar-icon-hover'),
                    'text' : withOpacityValue('--sidebar-text'),
                },
                primary: {
                    '25': withOpacityValue('--primary-25'),
                    '50':  withOpacityValue('--primary-50'),
                    '100': withOpacityValue('--primary-100'),
                    '200': withOpacityValue('--primary-200'),
                    '300': withOpacityValue('--primary-300'),
                    '400': withOpacityValue('--primary-400'),
                    '500': withOpacityValue('--primary-500'),
                    '600': withOpacityValue('--primary-600'),
                    '700': withOpacityValue('--primary-700'),
                    '800': withOpacityValue('--primary-800'),
                    '900': withOpacityValue('--primary-900'),
                    '950': withOpacityValue('--primary-950'),
                },

                gray: {
                    '25': withOpacityValue('--gray-25'),
                    '50':  withOpacityValue('--gray-50'),
                    '100': withOpacityValue('--gray-100'),
                    '200': withOpacityValue('--gray-200'),
                    '300': withOpacityValue('--gray-300'),
                    '400': withOpacityValue('--gray-400'),
                    '500': withOpacityValue('--gray-500'),
                    '600': withOpacityValue('--gray-600'),
                    '700': withOpacityValue('--gray-700'),
                    '800': withOpacityValue('--gray-800'),
                    '900': withOpacityValue('--gray-900'),
                    '950': withOpacityValue('--gray-950'),
                }
            },
            fontFamily: {
                sans: ['var(--aura-font-sans)', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                '2xs': '0.67rem',
            },
            boxShadow: {
                'xs': '0px 1px 2px rgba(0, 8, 24, 0.05)',
            },
            spacing: {
                '4.5': '1.125rem',
            }
        },
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography'), require('tailwind-scrollbar'), require('@tailwindcss/aspect-ratio'),],
};
