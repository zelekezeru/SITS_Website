<script setup>
import { ref, watch, onMounted, onUnmounted, computed, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const isOpen = ref(false);
const query = ref('');
const results = ref([]);
const selectedIndex = ref(0);
const inputRef = ref(null);
const loading = ref(false);

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

// Static quick actions
const quickActions = computed(() => {
    const all = [
        { type: 'action', label: 'Dashboard',       route: 'library.dashboard',         icon: '🏠' },
        { type: 'action', label: 'Browse Catalog',   route: 'library.catalog.index',     icon: '📚', permission: 'view_books' },
        { type: 'action', label: 'Checkout Desk',    route: 'library.circulation.desk',  icon: '✅', permission: 'checkout_book' },
        { type: 'action', label: 'Process Returns',  route: 'library.circulation.returns', icon: '↩️', permission: 'return_book' },
        { type: 'action', label: 'Add New Book',     route: 'library.books.create',      icon: '➕', permission: 'create_book' },
        { type: 'action', label: 'Scan & Place',     route: 'library.scan.place',        icon: '📱', permission: 'create_book' },
        { type: 'action', label: 'Transfers',        route: 'library.transfers.index',   icon: '🔄', permission: 'request_transfer' },
        { type: 'action', label: 'My Loans',         route: 'library.my.loans',          icon: '📋' },
        { type: 'action', label: 'My Holds',         route: 'library.my.holds',          icon: '🔔' },
        { type: 'action', label: 'My Fines',         route: 'library.my.fines',          icon: '💰' },
        { type: 'action', label: 'Digital Archive',   route: 'library.archive.index',    icon: '📄', permission: 'view_secure_pdf' },
        { type: 'action', label: 'External Resources', route: 'library.resources.index', icon: '🔗' },
        { type: 'action', label: 'Manage Users',     route: 'library.users.index',       icon: '👥', permission: 'manage_users' },
        { type: 'action', label: 'Campuses',         route: 'library.campuses.index',    icon: '🏛️', permission: 'manage_campus' },
        { type: 'action', label: 'Reports',          route: 'library.reports.index',     icon: '📊', permission: 'view_loans' },
        { type: 'action', label: 'Stocktake',        route: 'library.stocktakes.index',  icon: '📦', permission: 'manage_shelf_box' },
        { type: 'action', label: 'Audit Log',        route: 'library.admin.audit',       icon: '📝', permission: 'manage_campus' },
        { type: 'action', label: 'Fines Management', route: 'library.fines.index',       icon: '💳', permission: 'collect_fine' },
    ];
    return all.filter(a => !a.permission || can(a.permission));
});

const filteredItems = computed(() => {
    const q = query.value.toLowerCase().trim();
    if (!q) return quickActions.value.slice(0, 8);

    const matched = quickActions.value.filter(a =>
        a.label.toLowerCase().includes(q)
    );

    // Merge book search results
    return [...matched, ...results.value].slice(0, 12);
});

let debounceTimer = null;
watch(query, (val) => {
    selectedIndex.value = 0;
    clearTimeout(debounceTimer);

    if (val.trim().length >= 2) {
        loading.value = true;
        debounceTimer = setTimeout(async () => {
            try {
                const resp = await axios.get(route('library.catalog.index'), {
                    params: { search: val, per_page: 5 },
                    headers: { 'X-Inertia': false, Accept: 'application/json' },
                });
                // Try to extract books from the response
                const books = resp.data?.props?.books?.data ?? resp.data?.books?.data ?? [];
                results.value = books.map(b => ({
                    type: 'book',
                    label: b.title,
                    subtitle: b.authors?.map(a => a.name).join(', ') ?? b.publisher,
                    route: 'library.catalog.show',
                    params: { book: b.id },
                    icon: '📖',
                }));
            } catch {
                results.value = [];
            } finally {
                loading.value = false;
            }
        }, 300);
    } else {
        results.value = [];
        loading.value = false;
    }
});

function navigate(item) {
    isOpen.value = false;
    query.value = '';
    results.value = [];
    router.visit(route(item.route, item.params ?? {}));
}

function handleKeydown(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        isOpen.value = !isOpen.value;
    }
    if (e.key === 'Escape') {
        isOpen.value = false;
    }
}

function handleArrowKeys(e) {
    const len = filteredItems.value.length;
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value + 1) % len;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value - 1 + len) % len;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const item = filteredItems.value[selectedIndex.value];
        if (item) navigate(item);
    }
}

watch(isOpen, (val) => {
    if (val) {
        nextTick(() => inputRef.value?.focus());
    } else {
        query.value = '';
        results.value = [];
        selectedIndex.value = 0;
    }
});

onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => document.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <!-- Trigger button -->
    <button
        @click="isOpen = true"
        class="flex items-center justify-center sm:justify-start gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-9 w-9 sm:h-auto sm:w-48 p-0 sm:px-3 sm:py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:border-indigo-300 dark:hover:border-indigo-700 hover:text-gray-700 dark:hover:text-gray-200 transition"
    >
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span class="hidden sm:inline flex-1 text-left truncate">Search…</span>
        <kbd class="hidden sm:inline-flex items-center gap-0.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-1.5 py-0.5 text-[10px] font-medium text-gray-400 dark:text-gray-500">
            <span>Ctrl</span><span>K</span>
        </kbd>
    </button>

    <!-- Modal overlay -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            leave-active-class="transition-opacity duration-150 ease-in"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isOpen" class="fixed inset-0 z-[9998] flex items-start justify-center pt-[15vh]">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="isOpen = false" />

                <div class="relative w-full max-w-lg mx-4 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
                    <!-- Search input -->
                    <div class="flex items-center gap-3 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                        <svg class="h-5 w-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="inputRef"
                            v-model="query"
                            @keydown="handleArrowKeys"
                            placeholder="Search books, navigate to pages…"
                            class="flex-1 bg-transparent border-none outline-none text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0"
                        />
                        <div v-if="loading" class="h-4 w-4 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin" />
                        <kbd class="text-[10px] text-gray-400 dark:text-gray-500 border border-gray-300 dark:border-gray-600 rounded px-1.5 py-0.5">ESC</kbd>
                    </div>

                    <!-- Results -->
                    <div class="max-h-80 overflow-y-auto py-2">
                        <div v-if="filteredItems.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
                            No results found
                        </div>
                        <button
                            v-for="(item, i) in filteredItems"
                            :key="i"
                            @click="navigate(item)"
                            @mouseenter="selectedIndex = i"
                            class="flex items-center gap-3 w-full px-4 py-2.5 text-left transition-colors"
                            :class="selectedIndex === i
                                ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-100'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'"
                        >
                            <span class="text-lg w-6 text-center">{{ item.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ item.label }}</p>
                                <p v-if="item.subtitle" class="text-xs text-gray-400 truncate">{{ item.subtitle }}</p>
                            </div>
                            <span class="text-xs px-1.5 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-400 capitalize">
                                {{ item.type }}
                            </span>
                        </button>
                    </div>

                    <!-- Footer hint -->
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-2 flex items-center gap-4 text-[10px] text-gray-400">
                        <span class="flex items-center gap-1"><kbd class="border rounded px-1 border-gray-300 dark:border-gray-600">↑↓</kbd> Navigate</span>
                        <span class="flex items-center gap-1"><kbd class="border rounded px-1 border-gray-300 dark:border-gray-600">↵</kbd> Open</span>
                        <span class="flex items-center gap-1"><kbd class="border rounded px-1 border-gray-300 dark:border-gray-600">ESC</kbd> Close</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
