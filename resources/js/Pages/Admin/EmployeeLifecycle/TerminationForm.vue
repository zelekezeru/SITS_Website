<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <Link href="/admin/terminations" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back</Link>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">{{ termination.id ? 'Termination Details' : 'New Termination' }}</h1>
      </div>

      <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-6 space-y-6">
        <!-- Employee Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
          <select v-model="form.employee_id" :disabled="termination?.id" class="w-full px-4 py-2 border border-gray-300 rounded-md">
            <option value="">Select employee...</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
              {{ emp.name }} ({{ emp.department }})
            </option>
          </select>
        </div>

        <!-- Termination Reason -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
          <select v-model="form.reason" class="w-full px-4 py-2 border border-gray-300 rounded-md">
            <option value="">Select reason...</option>
            <option v-for="reason in reasons" :key="reason.value" :value="reason.value">
              {{ reason.label }}
            </option>
          </select>
        </div>

        <!-- Effective Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Effective Date *</label>
          <input v-model="form.effective_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-md" />
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
          <textarea v-model="form.notes" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md"></textarea>
        </div>

        <!-- Severance -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Severance Amount (ETB)</label>
          <input v-model.number="form.severance_amount" type="number" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-md" />
        </div>

        <div class="flex gap-3 pt-6 border-t border-gray-200">
          <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:bg-gray-400">
            {{ form.processing ? 'Saving...' : (termination?.id ? 'Update' : 'Create') }}
          </button>
          <Link href="/admin/terminations" class="px-6 py-2 bg-gray-200 text-gray-900 rounded-md hover:bg-gray-300">
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  termination: Object,
  employees: Array,
  reasons: Array,
});

const form = useForm({
  employee_id: props.termination?.employee_id || '',
  reason: props.termination?.reason || '',
  effective_date: props.termination?.effective_date || '',
  notes: props.termination?.notes || '',
  severance_amount: props.termination?.severance_amount || '',
});

const submit = () => {
  if (props.termination?.id) {
    form.put(`/admin/terminations/${props.termination.id}`);
  } else {
    form.post('/admin/terminations');
  }
};
</script>
