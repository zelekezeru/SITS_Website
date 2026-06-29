<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <Link href="/admin/leave-requests" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Leave Requests</Link>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">Leave Request Details</h1>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Leave Card -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ leave.employee.user.name }}</h2>
                <p class="text-gray-600">{{ leave.employee.department.name_en }}</p>
              </div>
              <span :class="statusBadgeClass(leave.status)" class="px-4 py-2 rounded-full text-sm font-medium">
                {{ getStatusLabel(leave.status) }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <p class="text-sm text-gray-500 uppercase">Leave Type</p>
                <p class="text-lg font-semibold text-gray-900">{{ leave.leave_type }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 uppercase">Duration</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 uppercase">Days Requested</p>
                <p class="text-lg font-semibold text-gray-900">{{ leave.days_requested }}</p>
              </div>
              <div v-if="leave.days_approved">
                <p class="text-sm text-gray-500 uppercase">Days Approved</p>
                <p class="text-lg font-semibold text-green-600">{{ leave.days_approved }}</p>
              </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Reason</h3>
              <p class="text-gray-700">{{ leave.reason }}</p>
            </div>

            <!-- Approval Panel -->
            <div v-if="leave.status === 'submitted' && canApprove" class="border-t border-gray-200 pt-6 mt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Approve Leave Request</h3>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Days to Approve *</label>
                  <input 
                    v-model.number="approveDays" 
                    type="number" 
                    :max="leave.days_requested"
                    min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                  <textarea 
                    v-model="approvalNotes" 
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  ></textarea>
                </div>
                <button 
                  @click="submitApproval"
                  :disabled="submitting"
                  class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:bg-gray-400 transition"
                >
                  {{ submitting ? 'Processing...' : 'Approve Leave' }}
                </button>
              </div>
            </div>

            <!-- Rejection Panel -->
            <div v-if="leave.status === 'submitted' && canReject" class="border-t border-gray-200 pt-6 mt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Leave Request</h3>
              <textarea 
                v-model="rejectionReason" 
                rows="4"
                placeholder="Reason for rejection..."
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
              ></textarea>
              <button 
                @click="submitRejection"
                :disabled="submitting || !rejectionReason.trim()"
                class="mt-3 w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:bg-gray-400 transition"
              >
                {{ submitting ? 'Processing...' : 'Reject Leave' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Request Info -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Info</h3>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-500 uppercase">Requested By</p>
                <p class="text-gray-900">{{ leave.created_by.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 uppercase">Request Date</p>
                <p class="text-gray-900">{{ formatDate(leave.created_at) }}</p>
              </div>
              <div v-if="leave.approved_by">
                <p class="text-sm text-gray-500 uppercase">Approved By</p>
                <p class="text-gray-900">{{ leave.approved_by.name }}</p>
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
  leave: Object,
  canApprove: Boolean,
  canReject: Boolean,
  canCancel: Boolean,
});

const approveDays = ref(props.leave.days_requested);
const approvalNotes = ref('');
const rejectionReason = ref('');
const submitting = ref(false);

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getStatusLabel = (status) => {
  const map = { 'draft': 'Draft', 'submitted': 'Submitted', 'approved': 'Approved', 'rejected': 'Rejected', 'cancelled': 'Cancelled' };
  return map[status] || status;
};

const statusBadgeClass = (status) => {
  const classes = {
    'draft': 'bg-gray-100 text-gray-800',
    'submitted': 'bg-blue-100 text-blue-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'cancelled': 'bg-gray-100 text-gray-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const submitApproval = () => {
  submitting.value = true;
  router.post(`/admin/leave-requests/${props.leave.id}/approve`, {
    days_approved: approveDays.value,
    approval_notes: approvalNotes.value,
  }, {
    onFinish: () => { submitting.value = false; },
  });
};

const submitRejection = () => {
  submitting.value = true;
  router.post(`/admin/leave-requests/${props.leave.id}/reject`, {
    rejection_reason: rejectionReason.value,
  }, {
    onFinish: () => { submitting.value = false; },
  });
};
</script>
