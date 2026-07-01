<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);
let toastId = 0;

function addToast(message, type = 'success') {
    const id = ++toastId;
    toasts.value.push({ id, message, type, progress: 100 });

    // Auto-dismiss after 4s
    const interval = setInterval(() => {
        const toast = toasts.value.find(t => t.id === id);
        if (toast) {
            toast.progress -= 2;
            if (toast.progress <= 0) {
                clearInterval(interval);
                removeToast(id);
            }
        } else {
            clearInterval(interval);
        }
    }, 80);
}

function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

// Watch flash messages from Inertia
watch(() => page.props.flash, (flash) => {
    if (flash?.success) addToast(flash.success, 'success');
    if (flash?.error) addToast(flash.error, 'error');
}, { deep: true });

onMounted(() => {
    const flash = page.props.flash;
    if (flash?.success) addToast(flash.success, 'success');
    if (flash?.error) addToast(flash.error, 'error');
});

const iconPaths = {
    success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
};

const colorClasses = {
    success: {
        bg: 'bg-emerald-50 dark:bg-emerald-950/60',
        border: 'border-emerald-200 dark:border-emerald-800',
        icon: 'text-emerald-500 dark:text-emerald-400',
        text: 'text-emerald-800 dark:text-emerald-200',
        bar: 'bg-emerald-500',
    },
    error: {
        bg: 'bg-red-50 dark:bg-red-950/60',
        border: 'border-red-200 dark:border-red-800',
        icon: 'text-red-500 dark:text-red-400',
        text: 'text-red-800 dark:text-red-200',
        bar: 'bg-red-500',
    },
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-3 w-80 pointer-events-none">
            <TransitionGroup
                enter-active-class="transition-all duration-300 ease-out"
                leave-active-class="transition-all duration-200 ease-in"
                enter-from-class="opacity-0 translate-x-8 scale-95"
                enter-to-class="opacity-100 translate-x-0 scale-100"
                leave-from-class="opacity-100 translate-x-0 scale-100"
                leave-to-class="opacity-0 translate-x-8 scale-95"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto rounded-xl border shadow-lg backdrop-blur-sm overflow-hidden"
                    :class="[colorClasses[toast.type].bg, colorClasses[toast.type].border]"
                >
                    <div class="flex items-start gap-3 p-4">
                        <svg class="h-5 w-5 shrink-0 mt-0.5" :class="colorClasses[toast.type].icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="iconPaths[toast.type]" />
                        </svg>
                        <p class="text-sm font-medium flex-1" :class="colorClasses[toast.type].text">
                            {{ toast.message }}
                        </p>
                        <button
                            @click="removeToast(toast.id)"
                            class="shrink-0 rounded-lg p-0.5 hover:bg-black/5 dark:hover:bg-white/10 transition"
                            :class="colorClasses[toast.type].text"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <!-- Progress bar -->
                    <div class="h-0.5 w-full bg-black/5 dark:bg-white/5">
                        <div
                            class="h-full transition-all duration-75 ease-linear"
                            :class="colorClasses[toast.type].bar"
                            :style="{ width: toast.progress + '%' }"
                        />
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
