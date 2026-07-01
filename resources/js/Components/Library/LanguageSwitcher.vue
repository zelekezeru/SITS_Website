<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Library/Dropdown.vue';

const page = usePage();
const currentLocale = computed(() => page.props.locale || 'en');

const locales = {
    en: { name: 'English', flag: '🇬🇧' },
    am: { name: 'አማርኛ', flag: '🇪🇹' }
};

const changeLocale = (locale) => {
    router.post(route('library.language.switch'), { locale }, {
        preserveScroll: true
    });
};
</script>

<template>
    <div class="relative">
        <Dropdown align="right" width="32">
            <template #trigger>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-xs font-semibold text-gray-505 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition active:scale-95 duration-200"
                >
                    <span class="text-sm leading-none">{{ locales[currentLocale]?.flag }}</span>
                    <span class="text-xs leading-none font-bold">{{ locales[currentLocale]?.name }}</span>
                    <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </template>

            <template #content>
                <div class="py-1">
                    <button
                        v-for="(info, code) in locales"
                        :key="code"
                        @click="changeLocale(code)"
                        class="w-full text-left flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                        :class="{ 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-bold': code === currentLocale }"
                    >
                        <span class="text-sm leading-none">{{ info.flag }}</span>
                        <span class="text-xs leading-none">{{ info.name }}</span>
                    </button>
                </div>
            </template>
        </Dropdown>
    </div>
</template>
