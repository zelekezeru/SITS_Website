<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <Link href="/admin/conduct" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Conduct Issues</Link>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">{{ issue.id ? 'Edit Conduct Issue' : 'New Conduct Issue' }}</h1>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-6">
        <div class="space-y-6">
          <!-- Employee Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
            <select 
              v-model="form.employee_id" 
              :disabled="issue?.id"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              @change="form.errors.employee_id = ''"
            >
              <option value="">Select an employee...</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                {{ emp.name }} ({{ emp.department }})
              </option>
            </select>
            <p v-if="form.errors.employee_id" class="text-red-600 text-sm mt-1">{{ form.errors.employee_id }}</p>
          </div>

          <!-- Issue Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Issue Type *</label>
            <select 
              v-model="form.issue_type" 
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              @change="form.errors.issue_type = ''"
            >
              <option value="">Select a type...</option>
              <option v-for="type in issueTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
            <p v-if="form.errors.issue_type" class="text-red-600 text-sm mt-1">{{ form.errors.issue_type }}</p>
          </div>

          <!-- Severity -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Severity *</label>
            <select 
              v-model="form.severity" 
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              @change="form.errors.severity = ''"
            >
              <option value="">Select severity...</option>
              <option v-for="severity in severities" :key="severity.value" :value="severity.value">
                {{ severity.label }}
              </option>
            </select>
            <p v-if="form.errors.severity" class="text-red-600 text-sm mt-1">{{ form.errors.severity }}</p>
          </div>

          <!-- Incident Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Incident Date *</label>
            <input 
              v-model="form.incident_date" 
              type="date" 
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              @change="form.errors.incident_date = ''"
            />
            <p v-if="form.errors.incident_date" class="text-red-600 text-sm mt-1">{{ form.errors.incident_date }}</p>
          </div>

          <!-- Location -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
            <input 
              v-model="form.location" 
              type="text" 
              placeholder="Where did the incident occur?"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <!-- Description (English) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description (English) *</label>
            <textarea 
              v-model="form.description_en" 
              rows="6"
              placeholder="Describe the conduct issue in detail..."
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              @change="form.errors.description_en = ''"
            ></textarea>
            <p v-if="form.errors.description_en" class="text-red-600 text-sm mt-1">{{ form.errors.description_en }}</p>
          </div>

          <!-- Description (Amharic) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description (Amharic)</label>
            <textarea 
              v-model="form.description_am" 
              rows="6"
              placeholder="ሁኔታውን በአማርኛ ይገለጹ..."
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>

          <!-- Justification -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Justification</label>
            <textarea 
              v-model="form.justification" 
              rows="4"
              placeholder="Why are you flagging this issue? What is the impact?"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>

          <!-- Witnesses -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Witnesses (email addresses)</label>
            <input 
              v-model="witnessInput" 
              type="text" 
              placeholder="Add witness email and press Enter"
              @keydown.enter.prevent="addWitness"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
            <div v-if="form.witnesses && form.witnesses.length > 0" class="mt-3 flex flex-wrap gap-2">
              <span 
                v-for="(witness, idx) in form.witnesses" 
                :key="idx"
                class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center gap-2"
              >
                {{ witness }}
                <button @click.prevent="removeWitness(idx)" class="text-blue-600 hover:text-blue-900">×</button>
              </span>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex gap-3 pt-6 border-t border-gray-200">
            <button 
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 transition"
            >
              {{ form.processing ? 'Saving...' : (issue?.id ? 'Update Issue' : 'Create Issue') }}
            </button>
            <Link href="/admin/conduct" class="px-6 py-2 bg-gray-200 text-gray-900 rounded-md hover:bg-gray-300 transition">
              Cancel
            </Link>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  issue: Object,
  employees: Array,
  issueTypes: Array,
  severities: Array,
});

const witnessInput = ref('');

const form = useForm({
  employee_id: props.issue?.employee_id || '',
  issue_type: props.issue?.issue_type || '',
  severity: props.issue?.severity || '',
  description_en: props.issue?.description_en || '',
  description_am: props.issue?.description_am || '',
  justification: props.issue?.justification || '',
  incident_date: props.issue?.incident_date || '',
  location: props.issue?.location || '',
  witnesses: props.issue?.witnesses || [],
});

const addWitness = () => {
  const email = witnessInput.value.trim();
  if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    if (!form.witnesses.includes(email)) {
      form.witnesses.push(email);
    }
    witnessInput.value = '';
  }
};

const removeWitness = (idx) => {
  form.witnesses.splice(idx, 1);
};

const submit = () => {
  if (props.issue?.id) {
    form.put(`/admin/conduct/${props.issue.id}`);
  } else {
    form.post('/admin/conduct');
  }
};
</script>
