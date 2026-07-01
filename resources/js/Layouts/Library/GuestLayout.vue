<script setup>
import ApplicationLogo from '@/Components/Library/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { useDarkMode } from '@/composables/useDarkMode';
import LanguageSwitcher from '@/Components/Library/LanguageSwitcher.vue';

const { isDark, toggle: toggleDarkMode } = useDarkMode();

defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex">

        <!-- Left panel — branding (desktop only) -->
        <div class="hidden lg:flex lg:w-1/2 flex-col justify-between bg-indigo-600 dark:bg-indigo-700 p-12 relative overflow-hidden">
            <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-indigo-500/40 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-violet-600/30 blur-3xl" aria-hidden="true"></div>

            <Link href="/" class="relative flex items-center gap-3">
                <ApplicationLogo class="h-10 w-10 fill-current text-white" />
                <span class="text-xl font-bold text-white tracking-tight">SITS Library</span>
            </Link>

            <div class="relative">
                <blockquote class="text-white">
                    <p class="text-2xl font-semibold leading-snug mb-6">
                        "A library is not a luxury but one of the necessities of life."
                    </p>
                    <footer class="text-indigo-200 text-sm">— Henry Ward Beecher</footer>
                </blockquote>

                <div class="mt-12 grid grid-cols-2 gap-4">
                    <div class="rounded-xl bg-white/10 backdrop-blur px-4 py-3">
                        <div class="text-white font-semibold text-sm">{{ __('Multi-Campus') }}</div>
                        <div class="text-indigo-200 text-xs mt-0.5">{{ __('Unified catalog across all sites') }}</div>
                    </div>
                    <div class="rounded-xl bg-white/10 backdrop-blur px-4 py-3">
                        <div class="text-white font-semibold text-sm">{{ __('Digital Archive') }}</div>
                        <div class="text-indigo-200 text-xs mt-0.5">{{ __('Secure documents & resources') }}</div>
                    </div>
                    <div class="rounded-xl bg-white/10 backdrop-blur px-4 py-3">
                        <div class="text-white font-semibold text-sm">{{ __('QR Tracking') }}</div>
                        <div class="text-indigo-200 text-xs mt-0.5">{{ __('Find any book on any shelf') }}</div>
                    </div>
                    <div class="rounded-xl bg-white/10 backdrop-blur px-4 py-3">
                        <div class="text-white font-semibold text-sm">{{ __('Self-Service') }}</div>
                        <div class="text-indigo-200 text-xs mt-0.5">{{ __('Loans, holds & renewals') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel — form -->
        <div class="flex flex-1 flex-col justify-center px-6 py-12 lg:px-16 xl:px-24 relative">

            <div class="absolute top-4 right-4 flex items-center gap-2">
                <LanguageSwitcher />
                <button
                    @click="toggleDarkMode"
                    class="rounded-lg p-2 text-gray-400 hover:text-gray-650 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    :title="isDark ? __('Switch to light mode') : __('Switch to dark mode')"
                >
                    <svg v-if="isDark" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M16.95 16.95l.707.707M7.05 7.05l.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
            </div>

            <!-- Mobile logo -->
            <div class="lg:hidden mb-10 flex justify-center">
                <Link href="/" class="flex items-center gap-3">
                    <ApplicationLogo class="h-9 w-9 fill-current text-indigo-600 dark:text-indigo-400" />
                    <span class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">SITS Library</span>
                </Link>
            </div>

            <div class="mx-auto w-full max-w-sm">
                <div v-if="title || description" class="mb-8">
                    <h1 v-if="title" class="text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h1>
                    <p v-if="description" class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ description }}</p>
                </div>

                <slot />
            </div>
        </div>

    </div>
</template>
