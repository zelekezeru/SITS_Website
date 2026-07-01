<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const open = ref(false);

const notifications = computed(() => page.props.notifications ?? { unread: 0, recent: [] });
const unread = computed(() => notifications.value.unread ?? 0);
const recent = computed(() => notifications.value.recent ?? []);

const iconPaths = {
    clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    alert: 'M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z',
    check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    cash: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z',
    truck: 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1',
    bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
};
const iconFor = (cat) => iconPaths[cat] ?? iconPaths.bell;

const colorFor = (cat) => ({
    loan_due_soon: 'text-amber-500 bg-amber-50 dark:bg-amber-950',
    loan_overdue:  'text-red-500 bg-red-50 dark:bg-red-950',
    hold_ready:    'text-green-500 bg-green-50 dark:bg-green-950',
    fine_issued:   'text-rose-500 bg-rose-50 dark:bg-rose-950',
    transfer_stale:'text-sky-500 bg-sky-50 dark:bg-sky-950',
}[cat] ?? 'text-indigo-500 bg-indigo-50 dark:bg-indigo-950');

function markAllRead() {
    router.post(route('library.notifications.read-all'), {}, { preserveScroll: true, preserveState: false });
}

function toggle() { open.value = !open.value; }
function close() { open.value = false; }

function onKey(e) { if (e.key === 'Escape') close(); }
onMounted(() => document.addEventListener('keydown', onKey));
onUnmounted(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="relative">
        <button
            @click="toggle"
            class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
            title="Notifications"
            aria-label="Notifications"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPaths.bell" />
            </svg>
            <span
                v-if="unread > 0"
                class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
            >
                {{ unread > 9 ? '9+' : unread }}
            </span>
        </button>

        <!-- click-away backdrop -->
        <div v-if="open" class="fixed inset-0 z-30" @click="close" />

        <!-- dropdown -->
        <div
            v-if="open"
            class="absolute right-0 z-40 mt-2 w-80 origin-top-right rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg"
        >
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-4 py-2.5">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</p>
                <button
                    v-if="unread > 0"
                    @click="markAllRead"
                    class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    Mark all read
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <p v-if="!recent.length" class="px-4 py-8 text-center text-sm text-gray-400">
                    You're all caught up.
                </p>

                <Link
                    v-for="n in recent"
                    :key="n.id"
                    :href="route('library.notifications.read', n.id)"
                    @click="close"
                    class="flex items-start gap-3 border-b border-gray-50 dark:border-gray-800/50 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-800/50"
                    :class="!n.read_at ? 'bg-indigo-50/40 dark:bg-indigo-950/20' : ''"
                >
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" :class="colorFor(n.data.category)">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="iconFor(n.data.category)" />
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ n.data.title }}</span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ n.data.message }}</span>
                        <span class="mt-0.5 block text-[11px] text-gray-400">{{ n.created_at }}</span>
                    </span>
                    <span v-if="!n.read_at" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-500" />
                </Link>
            </div>

            <Link
                :href="route('library.notifications.index')"
                @click="close"
                class="block border-t border-gray-100 dark:border-gray-800 px-4 py-2.5 text-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-800/50"
            >
                View all notifications
            </Link>
        </div>
    </div>
</template>
