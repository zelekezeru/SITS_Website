<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
      <div class="mb-6">
        <Link href="/admin/status-changes" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Status Changes</Link>
        <h1 class="mt-2 text-3xl font-bold text-gray-900">Status Change</h1>
      </div>

      <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <!-- Employee + transition -->
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Employee</p>
            <p class="text-lg font-medium text-gray-900">{{ change.employee?.user?.name ?? '—' }}</p>
          </div>
          <div class="text-right">
            <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700">{{ formatEnum(change.from_status) }}</span>
            <span class="mx-1 text-gray-400">&rarr;</span>
            <span :class="statusClass(change.to_status)" class="px-2 py-0.5 rounded font-medium">{{ formatEnum(change.to_status) }}</span>
          </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 border-t border-gray-100 pt-6">
          <div>
            <dt class="text-sm text-gray-500">Reason</dt>
            <dd class="text-sm font-medium text-gray-900">{{ formatEnum(change.reason) }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">Changed By</dt>
            <dd class="text-sm font-medium text-gray-900">{{ change.changed_by?.name ?? 'System' }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">Effective Date</dt>
            <dd class="text-sm font-medium text-gray-900">{{ formatDate(change.effective_date) }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">Recorded At</dt>
            <dd class="text-sm font-medium text-gray-900">{{ formatDate(change.changed_at) }}</dd>
          </div>
          <div v-if="change.reference_type">
            <dt class="text-sm text-gray-500">Source</dt>
            <dd class="text-sm font-medium text-gray-900">{{ formatEnum(change.reference_type) }} #{{ change.reference_id }}</dd>
          </div>
        </dl>

        <div v-if="change.notes" class="border-t border-gray-100 pt-6">
          <dt class="text-sm text-gray-500 mb-1">Notes</dt>
          <dd class="text-sm text-gray-700 whitespace-pre-line">{{ change.notes }}</dd>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  change: Object,
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
</script>
