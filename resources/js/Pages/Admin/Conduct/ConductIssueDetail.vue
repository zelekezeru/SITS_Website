<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <Link href="/admin/conduct" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Conduct Issues</Link>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">Conduct Issue Details</h1>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Issue Card -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ issue.employee.user.name }}</h2>
                <p class="text-gray-600">{{ issue.employee.department.name_en }}</p>
              </div>
              <span :class="statusBadgeClass(issue.status)" class="px-4 py-2 rounded-full text-sm font-medium">
                {{ getStatusLabel(issue.status) }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <p class="text-sm text-gray-500 uppercase">Issue Type</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatEnum(issue.issue_type) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 uppercase">Severity</p>
                <span :class="severityBadgeClass(issue.severity)" class="px-3 py-1 rounded-full text-sm font-medium inline-block">
                  {{ formatEnum(issue.severity) }}
                </span>
              </div>
              <div>
                <p class="text-sm text-gray-500 uppercase">Incident Date</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatDate(issue.incident_date) }}</p>
              </div>
              <div v-if="issue.location">
                <p class="text-sm text-gray-500 uppercase">Location</p>
                <p class="text-lg font-semibold text-gray-900">{{ issue.location }}</p>
              </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Description</h3>
              <p class="text-gray-700 whitespace-pre-wrap">{{ issue.description_en }}</p>
              <div v-if="issue.description_am" class="mt-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Amharic Description</h4>
                <p class="text-gray-700 whitespace-pre-wrap">{{ issue.description_am }}</p>
              </div>
            </div>

            <div v-if="issue.justification" class="border-t border-gray-200 pt-6 mt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Justification</h3>
              <p class="text-gray-700 whitespace-pre-wrap">{{ issue.justification }}</p>
            </div>

            <div v-if="issue.witnesses && issue.witnesses.length > 0" class="border-t border-gray-200 pt-6 mt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Witnesses</h3>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="witness in issue.witnesses" 
                  :key="witness"
                  class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm"
                >
                  {{ witness }}
                </span>
              </div>
            </div>
          </div>

          <!-- Approval/Rejection Panel -->
          <div v-if="issue.status === 'submitted' || issue.status === 'under_review'" class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Review & Decision</h3>

            <div v-if="canApprove" class="space-y-4">
              <button 
                @click="showApproveForm = !showApproveForm"
                class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition"
              >
                Approve Issue
              </button>

              <div v-if="showApproveForm" class="border-t border-gray-200 pt-4">
                <textarea 
                  v-model="approvalNotes" 
                  rows="4"
                  placeholder="Optional approval notes..."
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                ></textarea>
                <button 
                  @click="submitApproval"
                  :disabled="submitting"
                  class="mt-3 w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:bg-gray-400 transition"
                >
                  {{ submitting ? 'Submitting...' : 'Confirm Approval' }}
                </button>
              </div>
            </div>

            <div v-if="canReject" class="space-y-4 border-t border-gray-200 pt-4">
              <button 
                @click="showRejectForm = !showRejectForm"
                class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition"
              >
                Reject Issue
              </button>

              <div v-if="showRejectForm" class="pt-4">
                <textarea 
                  v-model="rejectionReason" 
                  rows="4"
                  placeholder="Reason for rejection (required)..."
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
                ></textarea>
                <button 
                  @click="submitRejection"
                  :disabled="submitting || !rejectionReason.trim()"
                  class="mt-3 w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:bg-gray-400 transition"
                >
                  {{ submitting ? 'Submitting...' : 'Confirm Rejection' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Make Decision Panel -->
          <div v-if="issue.status === 'approved' && !issue.decision && canDecide" class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Make a Conduct Decision</h3>
            <p class="text-gray-600 mb-4">This approved issue requires a formal conduct decision.</p>
            <Link :href="`/admin/conduct/${issue.id}/decision/create`" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition inline-block">
              Make Decision
            </Link>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Meta Information -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Information</h3>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-500 uppercase">Reported By</p>
                <p class="text-gray-900">{{ issue.created_by.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 uppercase">Reported Date</p>
                <p class="text-gray-900">{{ formatDate(issue.created_at) }}</p>
              </div>
              <div v-if="issue.approved_by">
                <p class="text-sm text-gray-500 uppercase">Approved By</p>
                <p class="text-gray-900">{{ issue.approved_by.name }}</p>
              </div>
              <div v-if="issue.approved_at">
                <p class="text-sm text-gray-500 uppercase">Approved Date</p>
                <p class="text-gray-900">{{ formatDate(issue.approved_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Conduct Decision -->
          <div v-if="issue.decision" class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Decision</h3>
            <p class="text-sm text-gray-600 mb-2">Decision Status:</p>
            <p class="text-lg font-semibold text-purple-900">{{ formatEnum(issue.decision.decision) }}</p>
            <div class="mt-4">
              <Link :href="`/admin/conduct-decisions/${issue.decision.id}`" class="text-blue-600 hover:text-blue-900 text-sm">
                View Decision →
              </Link>
            </div>
          </div>

          <!-- Actions -->
          <div v-if="issue.status === 'draft'" class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
            <div class="space-y-2">
              <Link :href="`/admin/conduct/${issue.id}/edit`" class="block w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-center">
                Edit Issue
              </Link>
              <button 
                @click="submitForReview"
                class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition"
              >
                Submit for Review
              </button>
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
  issue: Object,
  canApprove: Boolean,
  canReject: Boolean,
  canDecide: Boolean,
});

const showApproveForm = ref(false);
const showRejectForm = ref(false);
const approvalNotes = ref('');
const rejectionReason = ref('');
const submitting = ref(false);

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatEnum = (value) => {
  return value
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};

const getStatusLabel = (status) => {
  const map = {
    'draft': 'Draft',
    'submitted': 'Submitted',
    'under_review': 'Under Review',
    'approved': 'Approved',
    'rejected': 'Rejected',
    'resolved': 'Resolved',
    'archived': 'Archived',
  };
  return map[status] || status;
};

const statusBadgeClass = (status) => {
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

const severityBadgeClass = (severity) => {
  const classes = {
    'minor': 'bg-yellow-100 text-yellow-800',
    'moderate': 'bg-orange-100 text-orange-800',
    'major': 'bg-red-100 text-red-800',
    'critical': 'bg-red-900 text-red-100',
  };
  return classes[severity] || 'bg-gray-100 text-gray-800';
};

const submitApproval = () => {
  submitting.value = true;
  router.post(`/admin/conduct/${props.issue.id}/approve`, {
    approval_notes: approvalNotes.value,
  }, {
    onFinish: () => {
      submitting.value = false;
    },
  });
};

const submitRejection = () => {
  if (!rejectionReason.value.trim()) return;
  submitting.value = true;
  router.post(`/admin/conduct/${props.issue.id}/reject`, {
    rejection_reason: rejectionReason.value,
  }, {
    onFinish: () => {
      submitting.value = false;
    },
  });
};

const submitForReview = () => {
  router.post(`/admin/conduct/${props.issue.id}/submit`);
};
</script>
