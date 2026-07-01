<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap justify-center -mb-1">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null" 
                     class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded dark:border-gray-800" 
                     v-html="link.label" />
                <Link v-else 
                      class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500 transition-colors" 
                      :class="{ 'bg-indigo-600 text-white hover:bg-indigo-700': link.active, 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 dark:border-gray-800 dark:hover:bg-gray-800': !link.active }" 
                      :href="link.url" 
                      v-html="link.label" />
            </template>
        </div>
    </div>
</template>
