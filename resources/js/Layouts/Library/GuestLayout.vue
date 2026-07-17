<script setup>
import ApplicationLogo from '@/Components/Library/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { useDarkMode } from '@/composables/useDarkMode';
import LanguageSwitcher from '@/Components/Library/LanguageSwitcher.vue';
import Icon from '@/Components/Icon.vue';

const { isDark, toggle: toggleDarkMode } = useDarkMode();

defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 flex">

        <!-- Left panel — branding (desktop only) -->
        <div class="hidden lg:flex lg:w-1/2 flex-col justify-between bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 p-12 relative overflow-hidden">
            <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-indigo-400/30 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-violet-500/30 blur-3xl" aria-hidden="true"></div>

            <Link href="/" class="relative flex items-center gap-3">
                <div class="h-11 w-11 rounded-xl bg-white/15 backdrop-blur flex items-center justify-center border border-white/20">
                    <Icon name="LibraryBig" :size="22" class="text-white" :stroke-width="2" />
                </div>
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
                    <div class="rounded-2xl bg-white/10 backdrop-blur px-4 py-3.5 border border-white/10">
                        <Icon name="Building2" :size="18" class="text-indigo-200 mb-2" />
                        <div class="text-white font-semibold text-sm">{{ __('Multi-Campus') }}</div>
                        <div class="text-indigo-200 text-xs mt-0.5">{{ __('Unified catalog across all sites') }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 backdrop-blur px-4 py-3.5 border border-white/10">
                        <Icon name="Archive" :size="18" class="text-indigo-200 mb-2" />
                        <div class="text-white font-semibold text-sm">{{ __('Digital Archive') }}</div>
                        <div class="text-indigo-200 text-xs mt-0.5">{{ __('Secure documents & resources') }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 backdrop-blur px-4 py-3.5 border border-white/10">
                        <Icon name="QrCode" :size="18" class="text-indigo-200 mb-2" />
                        <div class="text-white font-semibold text-sm">{{ __('QR Tracking') }}</div>
                        <div class="text-indigo-200 text-xs mt-0.5">{{ __('Find any book on any shelf') }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 backdrop-blur px-4 py-3.5 border border-white/10">
                        <Icon name="BookMarked" :size="18" class="text-indigo-200 mb-2" />
                        <div class="text-white font-semibold text-sm">{{ __('Self-Service') }}</div>
                        <div class="text-indigo-200 text-xs mt-0.5">{{ __('Loans, holds & renewals') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel â€” form -->
        <div class="flex flex-1 flex-col justify-center px-6 py-12 lg:px-16 xl:px-24 relative">

            <div class="absolute top-4 right-4 flex items-center gap-2">
                <LanguageSwitcher />
                <button
                    @click="toggleDarkMode"
                    class="rounded-lg p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    :title="isDark ? __('Switch to light mode') : __('Switch to dark mode')"
                >
                    <Icon :name="isDark ? 'Sun' : 'Moon'" :size="20" />
                </button>
            </div>

            <!-- Mobile logo -->
            <div class="lg:hidden mb-10 flex justify-center">
                <Link href="/" class="flex items-center gap-3">
                    <ApplicationLogo class="h-9 w-9 fill-current text-indigo-600 dark:text-indigo-400" />
                    <span class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">SITS Library</span>
                </Link>
            </div>

            <div class="mx-auto w-full max-w-sm">
                <div v-if="title || description" class="mb-8">
                    <h1 v-if="title" class="text-2xl font-bold text-slate-900 dark:text-white">{{ title }}</h1>
                    <p v-if="description" class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ description }}</p>
                </div>

                <slot />
            </div>
        </div>

    </div>
</template>
