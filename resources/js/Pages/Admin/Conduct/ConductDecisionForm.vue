<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <Link :href="`/admin/conduct/${issue.id}`" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Issue</Link>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">Make Conduct Decision</h1>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-6">
        <div class="space-y-6">
          <!-- Issue Summary -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-2">Issue Summary</h3>
            <p class="text-gray-700">{{ issue.description_en }}</p>
          </div>

          <!-- Decision Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Decision *</label>
            <div class="space-y-3">
              <div v-for="decision in decisions" :key="decision.value" class="flex items-start">
                <input 
                  :id="`decision-${decision.value}`"
                  v-model="form.decision" 
                  :value="decision.value"
                  type="radio" 
                  class="mt-1"
                  @change="form.errors.decision = ''"
                />
                <label :for="`decision-${decision.value}`" class="ml-3 cursor-pointer">
                  <p class="font-medium text-gray-900">{{ decision.label }}</p>
                  <p class="text-sm text-gray-600">{{ getDecisionDescription(decision.value) }}</p>
                </label>
              </div>
            </div>
            <p v-if="form.errors.decision" class="text-red-600 text-sm mt-1">{{ form.errors.decision }}</p>
          </div>

          <!-- Decision Date -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Effective Date *</label>
              <input 
                v-model="form.effective_date" 
                type="date" 
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                @change="form.errors.effective_date = ''"
              />
              <p v-if="form.errors.effective_date" class="text-red-600 text-sm mt-1">{{ form.errors.effective_date }}</p>
            </div>

            <div v-if="requiresExpiry">
              <label class="block text-sm font-medium text-gray-700 mb-2">Expires At (for time-limited decisions)</label>
              <input 
                v-model="form.expires_at" 
                type="date" 
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              />
              <p class="text-sm text-gray-500 mt-1">Optional - leave blank for permanent decisions</p>
            </div>
          </div>

          <!-- Decision Notes (English) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Decision Notes (English)</label>
            <textarea 
              v-model="form.decision_notes_en" 
              rows="6"
              placeholder="Detailed explanation of the decision, actions required, etc..."
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>

          <!-- Decision Notes (Amharic) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Decision Notes (Amharic)</label>
            <textarea 
              v-model="form.decision_notes_am" 
              rows="6"
              placeholder="ውሳኔውን በአማርኛ ይገለጹ..."
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>

          <!-- Info Box -->
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h4 class="font-semibold text-yellow-900 mb-2">⚠️ Important</h4>
            <ul class="text-sm text-yellow-900 space-y-1">
              <li>• This decision will be recorded and audited.</li>
              <li>• The employee will be notified of this decision.</li>
              <li v-if="form.decision && ['suspension', 'termination', 'dismissal'].includes(form.decision)">
                • This decision will automatically update the employee's status.
              </li>
            </ul>
          </div>

          <!-- Form Actions -->
          <div class="flex gap-3 pt-6 border-t border-gray-200">
            <button 
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 transition"
            >
              {{ form.processing ? 'Recording Decision...' : 'Record Decision' }}
            </button>
            <Link :href="`/admin/conduct/${issue.id}`" class="px-6 py-2 bg-gray-200 text-gray-900 rounded-md hover:bg-gray-300 transition">
              Cancel
            </Link>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  issue: Object,
  decisions: Array,
});

const form = useForm({
  decision: '',
  decision_notes_en: '',
  decision_notes_am: '',
  effective_date: new Date().toISOString().split('T')[0],
  expires_at: '',
});

const requiresExpiry = computed(() => {
  return ['suspension'].includes(form.decision);
});

const getDecisionDescription = (decision) => {
  const descriptions = {
    'no_action': 'No formal action required, issue is closed',
    'warning': 'Formal written warning placed in personnel file',
    'suspension': 'Employee suspension for a specified period',
    'termination': 'Employee termination of employment',
    'dismissal': 'Employee dismissal for cause',
    'rehabilitation_program': 'Employee enrolled in rehabilitation/improvement program',
  };
  return descriptions[decision] || '';
};

const submit = () => {
  form.post(`/admin/conduct/${props.issue.id}/decision`);
};
</script>
