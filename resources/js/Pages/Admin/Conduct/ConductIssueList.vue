<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Conduct Management</h1>
          <p class="mt-1 text-sm text-gray-600">Track and manage employee conduct issues</p>
        </div>
        <Link href="/admin/conduct/create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
          <PlusIcon class="h-5 w-5 mr-2" />
          New Issue
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
          <form @submit.prevent="search" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Search Employee</label>
              <input 
                v-model="filters.search" 
                type="text" 
                placeholder="Name, email..." 
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
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
              <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
              <select v-model="filters.severity" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Severities</option>
                <option v-for="severity in severities" :key="severity.value" :value="severity.value">
                  {{ severity.label }}
                </option>
              </select>
            </div>
            <div class="flex items-end">
              <button type="submit" class="w-full px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition">
                Search
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Issues Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reported</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="issue in issues.data" :key="issue.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ issue.employee.user.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ issue.issue_type }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="severityClass(issue.severity)" class="px-3 py-1 rounded-full text-sm font-medium">
                  {{ getSeverityLabel(issue.severity) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="statusClass(issue.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                  {{ getStatusLabel(issue.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ formatDate(issue.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                <Link :href="`/admin/conduct/${issue.id}`" class="text-blue-600 hover:text-blue-900">View</Link>
                <Link :href="`/admin/conduct/${issue.id}/edit`" class="text-green-600 hover:text-green-900">Edit</Link>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="issues.links" class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
          <div class="text-sm text-gray-600">
            Showing {{ issues.from }} to {{ issues.to }} of {{ issues.total }} issues
          </div>
          <div class="flex gap-2">
            <Link 
              v-for="link in issues.links" 
              :key="link.url"
              :href="link.url"
              :class="[link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300']"
              class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100"
            >
              {{ link.label }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Plus as PlusIcon } from 'lucide-vue-next';

defineProps({
  issues: Object,
  statuses: Array,
  severities: Array,
  filters: Object,
});

const filters = ref({
  search: '',
  status: '',
  severity: '',
});

const search = () => {
  // Inertia will handle the filtering
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  });
};

const getStatusLabel = (status) => {
  const statusMap = {
    'draft': 'Draft',
    'submitted': 'Submitted',
    'under_review': 'Under Review',
    'approved': 'Approved',
    'rejected': 'Rejected',
    'resolved': 'Resolved',
    'archived': 'Archived',
  };
  return statusMap[status] || status;
};

const getSeverityLabel = (severity) => {
  const severityMap = {
    'minor': 'Minor',
    'moderate': 'Moderate',
    'major': 'Major',
    'critical': 'Critical',
  };
  return severityMap[severity] || severity;
};

const statusClass = (status) => {
  const classes = {
    'draft': 'bg-gray-100 text-gray-800',
    'submitted': 'bg-blue-100 text-blue-800',
    'under_review': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'resolved': 'bg-purple-100 text-purple-800',
    'archived': 'bg-gray-100 text-gray-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const severityClass = (severity) => {
  const classes = {
    'minor': 'bg-yellow-100 text-yellow-800',
    'moderate': 'bg-orange-100 text-orange-800',
    'major': 'bg-red-100 text-red-800',
    'critical': 'bg-red-900 text-red-100',
  };
  return classes[severity] || 'bg-gray-100 text-gray-800';
};
</script>
