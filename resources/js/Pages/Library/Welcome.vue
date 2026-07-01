<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/Library/ApplicationLogo.vue';
import { useDarkMode } from '@/composables/useDarkMode';
import LanguageSwitcher from '@/Components/Library/LanguageSwitcher.vue';

const { isDark, toggle: toggleDarkMode } = useDarkMode();

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});

const features = [
    {
        icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        title: 'Smart Catalog',
        description: 'Browse thousands of books across all campuses. Search by title, author, ISBN, or category with real-time availability.',
    },
    {
        icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        title: 'Multi-Campus Circulation',
        description: 'Check out, renew, and return books at any campus. Inter-campus transfers handled seamlessly.',
    },
    {
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        title: 'Digital Archive',
        description: 'Secure access to digital documents, research papers, and e-resources with watermarking and audit trails.',
    },
    {
        icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        title: 'Shelf Tracking',
        description: 'QR-code driven shelf placement system. Find any copy\'s exact location: campus, floor, row, and shelf box.',
    },
    {
        icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        title: 'Holds & Reservations',
        description: 'Reserve books that are currently checked out. Get notified when your hold is ready for pickup.',
    },
    {
        icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        title: 'External Resources',
        description: 'Curated access to licensed databases, online journals, and research tools — all in one place.',
    },
];

const stats = [
    { value: 'Multi-Campus', label: 'Coverage' },
    { value: 'Real-Time', label: 'Availability' },
    { value: 'Secure', label: 'Digital Archive' },
    { value: 'QR-Enabled', label: 'Shelf Tracking' },
];

</script>

<template>
    <Head title="Welcome — SITS Library" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100">

        <!-- Nav -->
        <header class="fixed inset-x-0 top-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur border-b border-gray-200 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <ApplicationLogo class="h-8 w-8 text-indigo-600 dark:text-indigo-400 fill-current" />
                    <span class="text-lg font-bold tracking-tight">SITS Library</span>
                </div>

                <nav v-if="canLogin" class="flex items-center gap-2">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('library.dashboard')"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition"
                    >
                        {{ __('Go to Dashboard') }}
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            {{ __('Sign in') }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition"
                        >
                            {{ __('Register') }}
                        </Link>
                    </template>

                    <div class="h-6 w-px bg-gray-200 dark:bg-gray-800 mx-2 hidden sm:block"></div>

                    <LanguageSwitcher />

                    <button
                        @click="toggleDarkMode"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        :title="isDark ? __('Switch to light mode') : __('Switch to dark mode')"
                    >
                        <svg v-if="isDark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M16.95 16.95l.707.707M7.05 7.05l.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    </button>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative pt-32 pb-24 px-4 sm:px-6 lg:px-8 overflow-hidden">
            <!-- Background decoration -->
            <div class="absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                <div class="absolute -top-40 -right-32 h-[600px] w-[600px] rounded-full bg-indigo-100 dark:bg-indigo-950 opacity-60 blur-3xl"></div>
                <div class="absolute top-20 -left-32 h-[400px] w-[400px] rounded-full bg-violet-100 dark:bg-violet-950 opacity-50 blur-3xl"></div>
            </div>

            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950 px-4 py-1.5 text-sm text-indigo-700 dark:text-indigo-300 mb-8">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                    {{ __('SITS Integrated Library System') }}
                </div>

                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight leading-tight mb-6">
                    {{ __('Your campus library, everywhere.') }}
                </h1>

                <p class="text-xl text-gray-650 dark:text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                    {{ __('Search the catalog, manage loans, access digital resources, and track books across all campuses — from a single, unified platform.') }}
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('library.dashboard')"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg hover:bg-indigo-700 transition"
                    >
                        {{ __('Open Dashboard') }}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                    <template v-else>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg hover:bg-indigo-700 transition"
                        >
                            {{ __('Sign in to Library') }}
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-8 py-3.5 text-base font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                        >
                            {{ __('Create account') }}
                        </Link>
                    </template>
                </div>
            </div>
        </section>

        <!-- Stats strip -->
        <section class="border-y border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <dl class="grid grid-cols-2 gap-8 lg:grid-cols-4">
                    <div v-for="stat in stats" :key="stat.label" class="text-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __(stat.label) }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ __(stat.value) }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Features -->
        <section class="py-24 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight mb-4">{{ __('Everything a modern library needs') }}</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        {{ __('Built for institutions with multiple campuses, complex workflows, and a growing digital collection.') }}
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition"
                    >
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 mb-4 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="feature.icon" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold mb-2">{{ __(feature.title) }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ __(feature.description) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center rounded-3xl bg-indigo-600 dark:bg-indigo-700 px-8 py-16 shadow-xl">
                <h2 class="text-3xl font-bold text-white mb-4">{{ __('Ready to get started?') }}</h2>
                <p class="text-indigo-200 mb-8 text-lg">
                    {{ __('Sign in with your institutional account to access the catalog, your loans, and all library services.') }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        v-if="canLogin && !$page.props.auth.user"
                        :href="route('login')"
                        class="rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-indigo-700 hover:bg-indigo-50 transition shadow"
                    >
                        {{ __('Sign in') }}
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('library.dashboard')"
                        class="rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-indigo-700 hover:bg-indigo-50 transition shadow"
                    >
                        {{ __('Go to Dashboard') }}
                    </Link>
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="route('register')"
                        class="rounded-xl border border-indigo-400 px-8 py-3.5 text-base font-semibold text-white hover:bg-indigo-500 transition"
                    >
                        {{ __('Create account') }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-505 dark:text-gray-400">
                <div class="flex items-center gap-2">
                    <ApplicationLogo class="h-5 w-5 text-indigo-600 dark:text-indigo-400 fill-current" />
                    <span>{{ __('SITS Library Management System') }}</span>
                </div>
                <span>Laravel v{{ laravelVersion }} &middot; PHP v{{ phpVersion }}</span>
            </div>
        </footer>

    </div>
</template>
