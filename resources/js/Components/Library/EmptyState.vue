<script setup>
defineProps({
    title: { type: String, default: 'No data yet' },
    description: { type: String, default: '' },
    icon: { type: String, default: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4' },
    actionLabel: { type: String, default: '' },
    actionRoute: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-4">
            <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-sm">
            {{ description }}
        </p>
        <slot />
        <a
            v-if="actionLabel && actionRoute"
            :href="route(actionRoute)"
            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            {{ actionLabel }}
        </a>
    </div>
</template>
