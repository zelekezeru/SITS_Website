<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <Link href="/admin/leave-requests" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Leave Requests</Link>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">{{ leave.id ? 'Leave Request Details' : 'New Leave Request' }}</h1>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-6">
        <div class="space-y-6">
          <!-- Employee Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
            <select 
              v-model="form.employee_id" 
              :disabled="leave?.id"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Select an employee...</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                {{ emp.name }} ({{ emp.department }})
              </option>
            </select>
            <p v-if="form.errors.employee_id" class="text-red-600 text-sm mt-1">{{ form.errors.employee_id }}</p>
          </div>

          <!-- Leave Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type *</label>
            <select 
              v-model="form.leave_type" 
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Select type...</option>
              <option v-for="type in leaveTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
            <p v-if="form.errors.leave_type" class="text-red-600 text-sm mt-1">{{ form.errors.leave_type }}</p>
          </div>

          <!-- Dates -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Start Date *</label>
              <input 
                v-model="form.start_date" 
                type="date" 
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                @change="calculateDays"
              />
              <p v-if="form.errors.start_date" class="text-red-600 text-sm mt-1">{{ form.errors.start_date }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">End Date *</label>
              <input 
                v-model="form.end_date" 
                type="date" 
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                @change="calculateDays"
              />
              <p v-if="form.errors.end_date" class="text-red-600 text-sm mt-1">{{ form.errors.end_date }}</p>
            </div>
          </div>

          <!-- Days Display -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm font-medium text-blue-900">Total Days: {{ daysCalculated }}</p>
          </div>

          <!-- Reason -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
            <textarea 
              v-model="form.reason" 
              rows="4"
              placeholder="Reason for leave..."
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
            <p v-if="form.errors.reason" class="text-red-600 text-sm mt-1">{{ form.errors.reason }}</p>
          </div>

          <!-- Form Actions -->
          <div class="flex gap-3 pt-6 border-t border-gray-200">
            <button 
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 transition"
            >
              {{ form.processing ? 'Saving...' : (leave?.id ? 'Update' : 'Create') }}
            </button>
            <Link href="/admin/leave-requests" class="px-6 py-2 bg-gray-200 text-gray-900 rounded-md hover:bg-gray-300 transition">
              Cancel
            </Link>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  leave: Object,
  employees: Array,
  leaveTypes: Array,
});

const form = useForm({
  employee_id: props.leave?.employee_id || '',
  leave_type: props.leave?.leave_type || '',
  start_date: props.leave?.start_date || '',
  end_date: props.leave?.end_date || '',
  reason: props.leave?.reason || '',
});

const daysCalculated = computed(() => {
  if (!form.start_date || !form.end_date) return 0;
  const start = new Date(form.start_date);
  const end = new Date(form.end_date);
  return Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
});

const calculateDays = () => {
  // Auto-calculated via computed property
};

const submit = () => {
  if (props.leave?.id) {
    form.put(`/admin/leave-requests/${props.leave.id}`);
  } else {
    form.post('/admin/leave-requests');
  }
};
</script>
