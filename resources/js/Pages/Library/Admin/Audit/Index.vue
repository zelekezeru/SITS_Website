<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'
import Pagination from '@/Components/Library/Pagination.vue'
import { ref, watch } from 'vue'
import debounce from 'lodash/debounce'

const props = defineProps({
    activities: Object,
    filters: Object
})

const user_id = ref(props.filters.user_id)
const subject_type = ref(props.filters.subject_type)
const from = ref(props.filters.from)
const to = ref(props.filters.to)

const filter = debounce(() => {
    router.get(route('library.admin.audit'), {
        user_id: user_id.value,
        subject_type: subject_type.value,
        from: from.value,
        to: to.value
    }, { preserveState: true, replace: true })
}, 500)

watch([user_id, subject_type, from, to], filter)

const formatDate = (date) => new Date(date).toLocaleString()
</script>

<template>
    <Head title="Audit Logs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                System Audit Logs
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6 border border-transparent dark:border-slate-800">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-400">User ID</label>
                            <input v-model="user_id" type="text" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 rounded-md shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-400">Model (e.g. Book)</label>
                            <input v-model="subject_type" type="text" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 rounded-md shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-400">From</label>
                            <input v-model="from" type="date" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 rounded-md shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-400">To</label>
                            <input v-model="to" type="date" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 rounded-md shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg border border-transparent dark:border-slate-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Timestamp</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Action</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Target</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Changes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800">
                                <tr v-for="log in activities.data" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100 font-medium">
                                        {{ log.causer?.name || 'System' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold uppercase" :class="{
                                        'text-green-600 dark:text-green-400': log.description === 'created',
                                        'text-blue-600 dark:text-blue-400': log.description === 'updated',
                                        'text-red-600 dark:text-red-400': log.description === 'deleted'
                                    }">
                                        {{ log.description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                        {{ log.subject_type.split('\\').pop() }} #{{ log.subject_id }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                        <div v-if="log.description === 'updated'" class="space-y-1">
                                            <template v-for="(val, key) in log.properties.attributes" :key="key">
                                                <div v-if="['title', 'subtitle', 'isbn'].includes(key)" class="text-[10px] leading-tight">
                                                    <span class="font-bold text-slate-700 dark:text-slate-300 uppercase">{{ key }}:</span>
                                                    <span class="line-through text-red-500 mx-1">{{ log.properties.old?.[key] }}</span>
                                                    <span class="text-green-600 dark:text-green-400 font-medium">{{ val }}</span>
                                                </div>
                                            </template>
                                        </div>
                                        <div v-else-if="log.properties.attributes" class="space-y-1">
                                            <template v-for="(val, key) in log.properties.attributes" :key="key">
                                                <div v-if="['title', 'subtitle', 'isbn'].includes(key)" class="text-[10px] leading-tight">
                                                    <span class="font-bold text-slate-700 dark:text-slate-300 uppercase">{{ key }}:</span>
                                                    <span class="text-slate-600 dark:text-slate-400 ml-1">{{ val }}</span>
                                                </div>
                                            </template>
                                        </div>
                                        <div v-else class="text-xs italic text-slate-400">No attribute details</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-6 border-t border-slate-200 dark:border-slate-800">
                        <Pagination :links="activities.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
