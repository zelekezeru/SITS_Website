<script setup>
import { ref, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Icon from '@/Components/Icon.vue';

/**
 * Scanner desk. A handheld barcode/QR reader types into the input and presses
 * Enter, which is why the manual field is the primary control — it works with
 * every scanner on site without a camera permission prompt.
 */
const code = ref('');
const result = ref(null);
const error = ref(null);
const busy = ref(false);

async function lookup() {
    if (!code.value.trim()) return;

    busy.value = true;
    error.value = null;
    result.value = null;

    try {
        const response = await fetch(route('bookstore.scan.lookup'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ code: code.value }),
        });

        if (!response.ok) {
            error.value = 'That code does not match anything in the bookstore.';
            return;
        }

        result.value = await response.json();
    } catch (e) {
        error.value = 'Could not reach the server. Check the connection and try again.';
    } finally {
        busy.value = false;
        code.value = '';
    }
}

const open = () => result.value && router.visit(result.value.url);

const labels = {
    title: 'Book title', store: 'Store room', shelf: 'Shelf',
    section: 'Shelf section', waybill: 'Dispatch waybill',
};

onBeforeUnmount(() => { result.value = null; });
</script>

<template>
    <Head title="Scan" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">Scan</h1>
        </template>

        <div class="p-6 max-w-2xl mx-auto space-y-6">

            <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-300">
                    <Icon name="QrCode" :size="26" />
                </span>
                <h2 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">Scan any bookstore QR</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    A book title, a store room, a shelf, a shelf section or a dispatch waybill. A phone camera can open
                    the code directly — this desk is for handheld scanners.
                </p>

                <form @submit.prevent="lookup" class="mt-5 flex gap-2">
                    <input v-model="code" type="text" autofocus
                           placeholder="Scan here, or paste the code…"
                           class="flex-1 rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <button type="submit" :disabled="busy"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition">
                        Look up
                    </button>
                </form>
            </section>

            <div v-if="error" class="flex items-center gap-3 rounded-xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 px-4 py-3">
                <Icon name="AlertTriangle" :size="18" class="text-rose-600 dark:text-rose-400 shrink-0" />
                <p class="text-sm text-rose-900 dark:text-rose-200">{{ error }}</p>
            </div>

            <button v-if="result" type="button" @click="open"
                    class="w-full rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 p-5 text-left hover:bg-emerald-100 dark:hover:bg-emerald-950/50 transition">
                <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
                    {{ labels[result.type] }}
                </p>
                <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ result.caption.name }}</p>
                <p v-if="result.caption.sub" class="text-sm text-slate-600 dark:text-slate-400">{{ result.caption.sub }}</p>
                <p v-if="result.summary" class="mt-2 text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ result.summary }}</p>
                <p class="mt-3 flex items-center gap-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                    Open <Icon name="ArrowRight" :size="15" />
                </p>
            </button>
        </div>
    </AuthenticatedLayout>
</template>
