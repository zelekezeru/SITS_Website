<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Library/EmptyState.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    stocktakes: Object,
    campuses: Array,
});

const showNewForm = ref(false);
const form = useForm({
    campus_id: '',
    notes: '',
});

function startStocktake() {
    form.post(route('library.stocktakes.store'), {
        onSuccess: () => {
            showNewForm.value = false;
            form.reset();
        },
    });
}

const statusColors = {
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    completed: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
};
</script>

<template>
    <Head title="Stocktake" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">Inventory Stocktake</h1>
                <button
                    @click="showNewForm = !showNewForm"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    New Stocktake
                </button>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- New stocktake form -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                leave-active-class="transition-all duration-150 ease-in"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showNewForm" class="rounded-xl border border-indigo-200 dark:border-indigo-800 bg-white dark:bg-gray-900 p-5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Start New Inventory Audit</h3>
                    <form @submit.prevent="startStocktake" class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Campus</label>
                            <select v-model="form.campus_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select campus…</option>
                                <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }} ({{ c.code }})</option>
                            </select>
                            <p v-if="form.errors.campus_id" class="text-xs text-red-500 mt-1">{{ form.errors.campus_id }}</p>
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Notes (optional)</label>
                            <input v-model="form.notes" type="text" placeholder="e.g., End-of-semester audit" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div class="flex items-end">
                            <button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition">
                                Start Audit
                            </button>
                        </div>
                    </form>
                </div>
            </Transition>

            <!-- Stocktake list -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                <table v-if="stocktakes.data?.length" class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Campus</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Started By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Scans</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="st in stocktakes.data" :key="st.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ st.campus?.name }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ st.starter?.name }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ new Date(st.started_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300 font-medium">{{ st.scans_count }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusColors[st.status]">
                                    {{ st.status.replace(/_/g, ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('library.stocktakes.show', st.id)" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <EmptyState v-else title="No stocktakes yet" description="Start a new inventory audit to verify your physical collection against system records." />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
