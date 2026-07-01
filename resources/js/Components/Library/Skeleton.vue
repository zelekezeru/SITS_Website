<script setup>
defineProps({
    width: { type: String, default: 'w-full' },
    height: { type: String, default: 'h-4' },
    rounded: { type: String, default: 'rounded-md' },
});
</script>

<template>
    <div
        :class="[width, height, rounded]"
        class="animate-pulse bg-gray-200 dark:bg-gray-700"
    />
</template>
