<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
      <div class="mb-6">
        <Link href="/admin/terminations" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back</Link>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">Termination: {{ termination.employee.user.name }}</h1>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-6">
              <div>
                <h2 class="text-2xl font-bold">{{ termination.employee.user.name }}</h2>
                <p class="text-gray-600">{{ termination.employee.department.name_en }}</p>
              </div>
              <span :class="statusBadgeClass(termination.status)" class="px-4 py-2 rounded-full text-sm font-medium">
                {{ formatEnum(termination.status) }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <p class="text-sm text-gray-500 uppercase">Reason</p>
                <p class="text-lg font-semibold">{{ formatEnum(termination.reason) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 uppercase">Effective Date</p>
                <p class="text-lg font-semibold">{{ formatDate(termination.effective_date) }}</p>
              </div>
            </div>

            <div v-if="termination.notes" class="border-t border-gray-200 pt-6">
              <h3 class="font-semibold mb-2">Notes</h3>
              <p class="text-gray-700 whitespace-pre-wrap">{{ termination.notes }}</p>
            </div>

            <!-- Finalization Panel -->
            <div v-if="canFinalize && termination.status === 'pending'" class="border-t border-gray-200 pt-6 mt-6">
              <h3 class="text-lg font-semibold mb-4">Finalize Termination</h3>
              <form @submit.prevent="submitFinalize" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium mb-2">Severance Amount (ETB)</label>
                  <input v-model.number="finalizeData.severance_amount" type="number" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-md" />
                </div>

                <div class="space-y-2">
                  <label class="flex items-center">
                    <input v-model="finalizeData.handover_tasks" type="checkbox" class="mr-2" />
                    <span class="text-sm">Tasks handed over</span>
                  </label>
                  <label class="flex items-center">
                    <input v-model="finalizeData.handover_equipment" type="checkbox" class="mr-2" />
                    <span class="text-sm">Equipment returned</span>
                  </label>
                  <label class="flex items-center">
                    <input v-model="finalizeData.handover_documents" type="checkbox" class="mr-2" />
                    <span class="text-sm">Documents transferred</span>
                  </label>
                </div>

                <button type="submit" :disabled="submitting" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400">
                  {{ submitting ? 'Processing...' : 'Finalize Termination' }}
                </button>
              </form>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-4">Information</h3>
            <div class="space-y-4 text-sm">
              <div>
                <p class="text-gray-500 uppercase">Initiated By</p>
                <p>{{ termination.initiated_by?.name || 'N/A' }}</p>
              </div>
              <div v-if="termination.severance_amount">
                <p class="text-gray-500 uppercase">Severance</p>
                <p class="font-semibold">{{ termination.severance_amount }} ETB</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  termination: Object,
  canFinalize: Boolean,
});

const finalizeData = ref({
  severance_amount: props.termination.severance_amount || '',
  handover_tasks: false,
  handover_equipment: false,
  handover_documents: false,
});

const submitting = ref(false);

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatEnum = (value) => {
  return value.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
};

const statusBadgeClass = (status) => {
  const classes = { 'pending': 'bg-yellow-100 text-yellow-800', 'finalized': 'bg-green-100 text-green-800' };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const submitFinalize = () => {
  submitting.value = true;
  router.post(`/admin/terminations/${props.termination.id}/finalize`, finalizeData.value, {
    onFinish: () => { submitting.value = false; },
  });
};
</script>
