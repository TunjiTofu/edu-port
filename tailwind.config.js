import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/filament/**/*.blade.php', // Add Filament views
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php', // Add Filament PHP files
        './resources/**/*.blade.php', // Make sure all blade files are included
        './resources/**/*.js',
        './resources/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                success: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0',
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#22c55e',
                    600: '#16a34a',
                    700: '#15803d',
                    800: '#166534',
                    900: '#14532d',
                },
                info: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
                warning: {
                    50: '#fefce8',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
                danger: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    200: '#fecaca',
                    300: '#fca5a5',
                    400: '#f87171',
                    500: '#ef4444',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: '#991b1b',
                    900: '#7f1d1d',
                },
            }
        },
    },

    safelist: [
        // Force include dynamic color classes that might not be detected
        'text-success-600', 'text-success-400', 'bg-success-500', 'bg-success-50', 'bg-success-500/10',
        'border-success-200', 'border-success-700',
        'text-info-600', 'text-info-400', 'bg-info-500', 'bg-info-50', 'bg-info-500/10',
        'border-info-200', 'border-info-700',
        'text-warning-600', 'text-warning-400', 'bg-warning-500', 'bg-warning-50', 'bg-warning-500/10',
        'border-warning-200', 'border-warning-700',
        'text-danger-600', 'text-danger-400', 'bg-danger-500', 'bg-danger-50', 'bg-danger-500/10',
        'border-danger-200', 'border-danger-700',
    ],

    plugins: [forms],
};
