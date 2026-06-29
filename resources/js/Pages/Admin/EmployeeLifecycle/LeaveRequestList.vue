<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Leave Requests</h1>
          <p class="mt-1 text-sm text-gray-600">Manage employee leave requests</p>
        </div>
        <Link href="/admin/leave-requests/create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
          <PlusIcon class="h-5 w-5 mr-2" />
          New Leave Request
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form @submit.prevent="search" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select v-model="filters.status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
              <option value="">All Statuses</option>
              <option v-for="status in statuses" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
            <select v-model="filters.leave_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
              <option value="">All Types</option>
              <option v-for="type in leaveTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Employee</label>
            <input 
              v-model="filters.search" 
              type="text" 
              placeholder="Name, email..." 
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition">
              Search
            </button>
          </div>
        </form>
      </div>

      <!-- Leaves Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ leave.employee.user.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ leave.leave_type }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ leave.days_requested }}
                <span v-if="leave.days_approved" class="text-xs text-green-600">({{ leave.days_approved }} approved)</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="statusClass(leave.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                  {{ getStatusLabel(leave.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                <Link :href="`/admin/leave-requests/${leave.id}`" class="text-blue-600 hover:text-blue-900">View</Link>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="leaves.links" class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
          <div class="text-sm text-gray-600">
            Showing {{ leaves.from }} to {{ leaves.to }} of {{ leaves.total }} requests
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Plus as PlusIcon } from 'lucide-vue-next';

defineProps({
  leaves: Object,
  statuses: Array,
  leaveTypes: Array,
  filters: Object,
});

const filters = ref({
  status: '',
  leave_type: '',
  search: '',
});

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getStatusLabel = (status) => {
  const map = { 'draft': 'Draft', 'submitted': 'Submitted', 'approved': 'Approved', 'rejected': 'Rejected', 'cancelled': 'Cancelled' };
  return map[status] || status;
};

const statusClass = (status) => {
  const classes = {
    'draft': 'bg-gray-100 text-gray-800',
    'submitted': 'bg-blue-100 text-blue-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'cancelled': 'bg-gray-100 text-gray-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const search = () => { /* Inertia handles filtering */ };
</script>
