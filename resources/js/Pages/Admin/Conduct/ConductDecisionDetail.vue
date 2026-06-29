<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <Link :href="`/admin/conduct/${decision.conduct_issue_id}`" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Issue</Link>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">Conduct Decision</h1>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Decision Card -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-6">
              <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ decision.conduct_issue.employee.user.name }}</h2>
                <p class="text-gray-600">{{ decision.conduct_issue.employee.department.name_en }}</p>
              </div>
              <span :class="statusBadgeClass(decision.status)" class="px-4 py-2 rounded-full text-sm font-medium">
                {{ formatStatus(decision.status) }}
              </span>
            </div>

            <!-- Decision Highlights -->
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg p-6 mb-6 border border-purple-200">
              <p class="text-sm text-gray-600 uppercase mb-2">Decision Outcome</p>
              <p class="text-3xl font-bold text-purple-900">{{ formatEnum(decision.decision) }}</p>
              <p class="text-sm text-gray-600 mt-2">Decided on {{ formatDate(decision.decided_at) }} by {{ decision.decided_by.name }}</p>
            </div>

            <!-- Key Dates -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500 uppercase mb-1">Effective Date</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatDate(decision.effective_date) }}</p>
              </div>
              <div v-if="decision.expires_at" class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500 uppercase mb-1">Expires</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatDate(decision.expires_at) }}</p>
              </div>
            </div>

            <!-- Decision Notes -->
            <div class="border-t border-gray-200 pt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Decision Notes</h3>
              <p class="text-gray-700 whitespace-pre-wrap mb-4">{{ decision.decision_notes_en }}</p>
              <div v-if="decision.decision_notes_am" class="mt-4 pt-4 border-t border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Amharic Notes</h4>
                <p class="text-gray-700 whitespace-pre-wrap">{{ decision.decision_notes_am }}</p>
              </div>
            </div>

            <!-- Appeal Section -->
            <div v-if="decision.status === 'active' && canAppeal" class="border-t border-gray-200 pt-6 mt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Appeal This Decision</h3>
              <button 
                @click="showAppealForm = !showAppealForm"
                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition"
              >
                Submit an Appeal
              </button>

              <div v-if="showAppealForm" class="mt-4 border-t border-gray-200 pt-4">
                <textarea 
                  v-model="appealNotes" 
                  rows="6"
                  placeholder="Explain why you believe this decision should be reconsidered..."
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-orange-500 focus:border-orange-500"
                ></textarea>
                <button 
                  @click="submitAppeal"
                  :disabled="submitting"
                  class="mt-3 px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 disabled:bg-gray-400 transition"
                >
                  {{ submitting ? 'Submitting Appeal...' : 'Submit Appeal' }}
                </button>
              </div>
            </div>

            <!-- Overturn Section -->
            <div v-if="canOverturn" class="border-t border-gray-200 pt-6 mt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Overturn Decision</h3>
              <button 
                @click="showOverturnForm = !showOverturnForm"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition"
              >
                Overturn This Decision
              </button>

              <div v-if="showOverturnForm" class="mt-4 border-t border-gray-200 pt-4">
                <textarea 
                  v-model="overturnReason" 
                  rows="6"
                  placeholder="Reason for overturning this decision..."
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
                ></textarea>
                <button 
                  @click="submitOverturn"
                  :disabled="submitting || !overturnReason.trim()"
                  class="mt-3 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:bg-gray-400 transition"
                >
                  {{ submitting ? 'Overturning...' : 'Confirm Overturn' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Decision Status -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Status</h3>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-500 uppercase mb-1">Decision Status</p>
                <p :class="statusBadgeClass(decision.status)" class="px-3 py-1 rounded-full text-sm font-medium inline-block">
                  {{ formatStatus(decision.status) }}
                </p>
              </div>

              <div v-if="decision.appeal_by">
                <p class="text-sm text-gray-500 uppercase mb-1">Appeal Status</p>
                <p class="text-gray-900">Appealed by {{ decision.appeal_by.name }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(decision.appeal_date) }}</p>
              </div>

              <div v-if="decision.overturned_by">
                <p class="text-sm text-gray-500 uppercase mb-1">Overturned By</p>
                <p class="text-gray-900">{{ decision.overturned_by.name }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(decision.overturned_at) }}</p>
              </div>

              <div v-if="decision.expires_at && !isExpired">
                <p class="text-sm text-gray-500 uppercase mb-1">Time Remaining</p>
                <p class="text-gray-900">{{ daysUntilExpiry }} days</p>
              </div>
            </div>
          </div>

          <!-- Original Issue -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Original Issue</h3>
            <div class="space-y-3">
              <div>
                <p class="text-sm text-gray-500 uppercase">Type</p>
                <p class="text-gray-900">{{ formatEnum(decision.conduct_issue.issue_type) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 uppercase">Severity</p>
                <span :class="severityBadgeClass(decision.conduct_issue.severity)" class="px-3 py-1 rounded-full text-sm font-medium inline-block">
                  {{ formatEnum(decision.conduct_issue.severity) }}
                </span>
              </div>
              <Link :href="`/admin/conduct/${decision.conduct_issue_id}`" class="text-blue-600 hover:text-blue-900 text-sm inline-block mt-2">
                View Issue →
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  decision: Object,
  canAppeal: Boolean,
  canOverturn: Boolean,
});

const showAppealForm = ref(false);
const showOverturnForm = ref(false);
const appealNotes = ref('');
const overturnReason = ref('');
const submitting = ref(false);

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
  });
};

const formatEnum = (value) => {
  return value
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};

const formatStatus = (status) => {
  const map = {
    'active': 'Active',
    'appealed': 'Appealed',
    'overturned': 'Overturned',
    'expired': 'Expired',
  };
  return map[status] || status;
};

const statusBadgeClass = (status) => {
  const classes = {
    'active': 'bg-green-100 text-green-800',
    'appealed': 'bg-yellow-100 text-yellow-800',
    'overturned': 'bg-red-100 text-red-800',
    'expired': 'bg-gray-100 text-gray-800',
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

const isExpired = computed(() => {
  if (!props.decision.expires_at) return false;
  return new Date(props.decision.expires_at) < new Date();
});

const daysUntilExpiry = computed(() => {
  if (!props.decision.expires_at) return 0;
  const expiryDate = new Date(props.decision.expires_at);
  const today = new Date();
  const diff = expiryDate - today;
  return Math.ceil(diff / (1000 * 60 * 60 * 24));
});

const submitAppeal = () => {
  submitting.value = true;
  router.post(`/admin/conduct-decisions/${props.decision.id}/appeal`, {
    appeal_notes: appealNotes.value,
  }, {
    onFinish: () => {
      submitting.value = false;
    },
  });
};

const submitOverturn = () => {
  if (!overturnReason.value.trim()) return;
  submitting.value = true;
  router.post(`/admin/conduct-decisions/${props.decision.id}/overturn`, {
    overturn_reason: overturnReason.value,
  }, {
    onFinish: () => {
      submitting.value = false;
    },
  });
};
</script>
