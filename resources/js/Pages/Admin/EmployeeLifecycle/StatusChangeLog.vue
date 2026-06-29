<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Employee Status Changes</h1>
        <p class="mt-1 text-sm text-gray-600">Audit trail of every employment-status transition</p>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form @submit.prevent="search" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From Status</label>
            <select v-model="filters.from_status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
              <option value="">Any</option>
              <option v-for="s in statuses" :key="`from-${s.value}`" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To Status</label>
            <select v-model="filters.to_status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
              <option value="">Any</option>
              <option v-for="s in statuses" :key="`to-${s.value}`" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
              Filter
            </button>
          </div>
        </form>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transition</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Changed By</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Effective</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="change in changes.data" :key="change.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ change.employee?.user?.name ?? '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700">{{ formatEnum(change.from_status) }}</span>
                <span class="mx-1 text-gray-400">&rarr;</span>
                <span :class="statusClass(change.to_status)" class="px-2 py-0.5 rounded font-medium">{{ formatEnum(change.to_status) }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatEnum(change.reason) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ change.changed_by?.name ?? 'System' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(change.effective_date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <Link :href="`/admin/status-changes/${change.id}`" class="text-blue-600 hover:text-blue-900">View</Link>
              </td>
            </tr>
            <tr v-if="!changes.data.length">
              <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No status changes recorded yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  changes: Object,
  statuses: Array,
  filters: Object,
});

const filters = ref({
  from_status: props.filters?.from_status ?? '',
  to_status: props.filters?.to_status ?? '',
});

const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';

const formatEnum = (value) => value ? value.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') : '—';

const statusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    on_leave: 'bg-blue-100 text-blue-800',
    suspended: 'bg-yellow-100 text-yellow-800',
    terminated: 'bg-red-100 text-red-800',
    inactive: 'bg-gray-100 text-gray-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const search = () => { /* Inertia handles filtering */ };
</script>
