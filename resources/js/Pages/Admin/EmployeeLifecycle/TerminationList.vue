<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Terminations</h1>
          <p class="mt-1 text-sm text-gray-600">Manage employee terminations and separations</p>
        </div>
        <Link href="/admin/terminations/create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700">
          <PlusIcon class="h-5 w-5 mr-2" />
          New Termination
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form @submit.prevent="search" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select v-model="filters.status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="finalized">Finalized</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
            <select v-model="filters.reason" class="w-full px-3 py-2 border border-gray-300 rounded-md">
              <option value="">All Reasons</option>
              <option v-for="reason in reasons" :key="reason.value" :value="reason.value">
                {{ reason.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Employee</label>
            <input 
              v-model="filters.search" 
              type="text" 
              placeholder="Name..." 
              class="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
          <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
              Search
            </button>
          </div>
        </form>
      </div>

      <!-- Terminations Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Effective Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="term in terminations.data" :key="term.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ term.employee.user.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatEnum(term.reason) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(term.effective_date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="statusClass(term.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                  {{ formatEnum(term.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <Link :href="`/admin/terminations/${term.id}`" class="text-blue-600 hover:text-blue-900">View</Link>
              </td>
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
import { Plus as PlusIcon } from 'lucide-vue-next';

defineProps({
  terminations: Object,
  reasons: Array,
  filters: Object,
});

const filters = ref({ status: '', reason: '', search: '' });

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatEnum = (value) => {
  return value.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
};

const statusClass = (status) => {
  const classes = { 'pending': 'bg-yellow-100 text-yellow-800', 'finalized': 'bg-green-100 text-green-800', 'archived': 'bg-gray-100 text-gray-800' };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const search = () => { /* Inertia handles filtering */ };
</script>
