<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
  department: { type: Object, required: true },
});

const activeTab = ref('employees');
const fmt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
const fmtCurrency = (n) => `ETB ${Number(n || 0).toLocaleString('en-GB', { minimumFractionDigits: 2 })}`;

// ─── Archive tab ──────────────────────────────────────────────────────────────
const { confirm } = useConfirm();
const uploadModalOpen = ref(false);
const activePreviewImage = ref(null);

const documentForm = useForm({
  name: '',
  documentable_type: 'App\\Models\\Department',
  documentable_id: props.department.id,
  file_path: '',
  file: null,
});

const onDocFileChange = (e) => {
  documentForm.file = e.target.files[0];
};

const submitDocument = () => {
  documentForm.post('/admin/documents', {
    onSuccess: () => {
      uploadModalOpen.value = false;
      documentForm.reset();
    },
  });
};

const deleteDoc = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Document',
    message: 'Are you sure you want to delete this document?',
  });
  if (confirmed) {
    router.delete(`/admin/documents/${id}`);
  }
};

const isImage = (path) => {
  if (!path) return false;
  const ext = path.split('.').pop().toLowerCase();
  return ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'bmp'].includes(ext);
};

const isWebLink = (path) => path?.startsWith('http://') || path?.startsWith('https://');

const handleOpenDocument = (path) => {
  if (isWebLink(path)) {
    window.open(path, '_blank');
  } else if (isImage(path)) {
    activePreviewImage.value = '/' + path;
  } else {
    const link = document.createElement('a');
    link.href = '/' + path;
    link.download = path.split('/').pop() || 'document';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
};

const statusClass = (status) => {
  const map = {
    active: 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400',
    on_leave: 'bg-amber-500/10 border-amber-500/25 text-amber-400',
    suspended: 'bg-rose-500/10 border-rose-500/25 text-rose-455',
    terminated: 'bg-red-500/10 border-red-500/25 text-red-400',
    inactive: 'bg-slate-800/60 border-slate-800 text-slate-500',
  };
  return map[status] || 'bg-slate-800/60 border-slate-800 text-slate-500';
};
</script>

<template>
  <Head :title="`Department Details — ${department.name_en}`" />

  <div class="space-y-8">
    <!-- Back Navigation -->
    <div class="flex items-center gap-3">
      <Link
        href="/admin/organization/departments"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-white bg-slate-900/60 hover:bg-slate-900 border border-slate-900 hover:border-slate-700 px-4 py-2 rounded-xl transition-all"
      >
        <Icon name="ArrowLeft" :size="15" />
        Back to Departments
      </Link>
      <div class="flex items-center gap-2 text-xs text-slate-600 font-semibold">
        <Icon name="ChevronRight" :size="13" />
        <span class="text-slate-400">{{ department.name_en }}</span>
      </div>
    </div>

    <!-- Header Section -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start justify-between gap-6 flex-wrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Building2" :size="26" />
          </span>
          <div>
            <div class="flex items-center gap-3 flex-wrap">
              <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight">{{ department.name_en }}</h2>
              <span
                class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border"
                :class="department.is_active
                  ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                  : 'bg-slate-800/60 border-slate-800 text-slate-500'"
              >
                {{ department.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <p v-if="department.name_am" class="text-slate-400 mt-1 font-medium">{{ department.name_am }}</p>
            <div class="text-slate-400 text-sm mt-3 flex items-center gap-4 flex-wrap">
              <span class="flex items-center gap-1.5">
                <Icon name="MapPin" :size="14" class="text-slate-600" />
                Campus: <span class="text-slate-200 font-semibold">{{ department.campus?.name_en || 'Remote / None' }}</span>
              </span>
              <span class="flex items-center gap-1.5" v-if="department.parent">
                <Icon name="GitMerge" :size="14" class="text-slate-600" />
                Parent: 
                <Link :href="`/admin/organization/departments/${department.parent.id}`" class="text-blue-400 hover:underline font-semibold">
                  {{ department.parent.name_en }}
                </Link>
              </span>
            </div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="flex gap-4 flex-wrap shrink-0">
          <div class="text-center px-5 py-3 rounded-2xl border border-slate-900 bg-slate-950/50">
            <div class="text-xl font-black text-blue-400">{{ department.employees?.length || 0 }}</div>
            <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Staff Members</div>
          </div>
          <div class="text-center px-5 py-3 rounded-2xl border border-slate-900 bg-slate-950/50">
            <div class="text-xl font-black text-purple-400">{{ department.targets?.length || 0 }}</div>
            <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Strategic Targets</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Key Roles & Org Details -->
    <div class="grid md:grid-cols-2 gap-6">
      <!-- Head & Leadership -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-350 flex items-center gap-2">
          <Icon name="UserCheck" :size="15" class="text-blue-400" /> Leadership & Management
        </h3>
        <div class="space-y-3 text-sm">
          <div v-if="department.head" class="flex items-center gap-3 py-2">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-500/30 to-purple-500/30 border border-slate-800 flex items-center justify-center text-sm font-bold text-white shrink-0">
              {{ department.head.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="text-slate-200 font-semibold">{{ department.head.name }}</p>
              <p class="text-xs text-slate-505">{{ department.head.email }}</p>
            </div>
            <span class="ml-auto text-[10px] bg-blue-500/10 border border-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-bold uppercase">
              Department Head
            </span>
          </div>
          <div v-else class="py-4 text-center text-slate-600 italic text-xs border border-dashed border-slate-900 rounded-xl">
            No Department Head currently assigned.
          </div>
        </div>
      </div>

      <!-- Sub-departments / Child departments -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-350 flex items-center gap-2">
          <Icon name="GitPullRequest" :size="15" class="text-purple-400" /> Sub-Departments ({{ department.children?.length || 0 }})
        </h3>
        <div class="space-y-2">
          <div v-for="child in department.children" :key="child.id" class="flex items-center justify-between p-3 rounded-xl border border-slate-900 bg-slate-950/40 hover:bg-slate-905 transition-colors">
            <div>
              <Link :href="`/admin/organization/departments/${child.id}`" class="text-slate-200 hover:text-blue-400 font-semibold text-sm transition-colors">
                {{ child.name_en }}
              </Link>
              <p class="text-[11px] text-slate-500 mt-0.5" v-if="child.head">Head: {{ child.head.name }}</p>
              <p class="text-[11px] text-slate-650 italic mt-0.5" v-else>Head: Unassigned</p>
            </div>
            <span
              class="px-2 py-0.5 text-[10px] rounded-full font-bold border"
              :class="child.is_active
                ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                : 'bg-slate-800/60 border-slate-800 text-slate-500'"
            >
              {{ child.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <div v-if="!department.children?.length" class="py-4 text-center text-slate-600 italic text-xs border border-dashed border-slate-900 rounded-xl">
            No sub-departments registered under this department.
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex border-b border-slate-900 gap-1">
      <button
        @click="activeTab = 'employees'"
        class="flex items-center gap-2 pb-3 px-4 text-sm font-bold tracking-wide transition-colors cursor-pointer"
        :class="activeTab === 'employees' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-300'"
      >
        <Icon name="Users" :size="15" />
        Employees ({{ department.employees?.length || 0 }})
      </button>
      <button
        @click="activeTab = 'targets'"
        class="flex items-center gap-2 pb-3 px-4 text-sm font-bold tracking-wide transition-colors cursor-pointer"
        :class="activeTab === 'targets' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-300'"
      >
        <Icon name="Target" :size="15" />
        Strategic Targets ({{ department.targets?.length || 0 }})
      </button>
      <button
        @click="activeTab = 'archive'"
        class="flex items-center gap-2 pb-3 px-4 text-sm font-bold tracking-wide transition-colors cursor-pointer"
        :class="activeTab === 'archive' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-300'"
      >
        <Icon name="FolderOpen" :size="15" />
        Archive ({{ department.documents?.length || 0 }})
      </button>
    </div>

    <!-- Tab contents: Employees -->
    <div v-if="activeTab === 'employees'" class="space-y-4">
      <div v-if="!department.employees || !department.employees.length" class="py-16 text-center text-slate-550 border border-dashed border-slate-900 rounded-3xl">
        No employees currently registered under this department.
      </div>
      
      <div v-else class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden shadow-md">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 bg-slate-950/40 text-xs font-semibold text-slate-500 uppercase">
              <th class="px-5 py-4">Staff ID</th>
              <th class="px-5 py-4">Name</th>
              <th class="px-5 py-4">Position</th>
              <th class="px-5 py-4">Employment Type</th>
              <th class="px-5 py-4">Status</th>
              <th class="px-5 py-4 text-right">Base Salary</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="emp in department.employees" :key="emp.id" class="hover:bg-slate-900/30 transition-colors">
              <td class="px-5 py-4 font-mono text-xs text-blue-400 font-semibold">{{ emp.staff_no }}</td>
              <td class="px-5 py-4">
                <Link :href="`/admin/employees/${emp.id}`" class="font-semibold text-slate-200 hover:text-blue-400 transition-colors">
                  {{ emp.full_name_en }}
                </Link>
                <div class="text-[11px] text-slate-500 mt-0.5" v-if="emp.full_name_am">{{ emp.full_name_am }}</div>
              </td>
              <td class="px-5 py-4 text-slate-350">
                {{ emp.position?.title_en || '—' }}
              </td>
              <td class="px-5 py-4 text-slate-400 capitalize">
                {{ emp.employment_type?.replace('_', ' ') }}
              </td>
              <td class="px-5 py-4">
                <span
                  class="px-2 py-0.5 text-[10px] rounded-full font-bold border capitalize"
                  :class="statusClass(emp.status)"
                >
                  {{ emp.status || (emp.is_active ? 'active' : 'inactive') }}
                </span>
              </td>
              <td class="px-5 py-4 text-right font-mono font-medium text-slate-300">
                {{ fmtCurrency(emp.base_salary) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab contents: Strategic Targets -->
    <div v-if="activeTab === 'targets'" class="space-y-4">
      <div v-if="!department.targets || !department.targets.length" class="py-16 text-center text-slate-550 border border-dashed border-slate-900 rounded-3xl">
        No strategic targets currently assigned to this department.
      </div>

      <div v-else class="grid sm:grid-cols-2 gap-5">
        <div v-for="target in department.targets" :key="target.id" class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 flex flex-col justify-between hover:border-slate-800 transition-all">
          <div class="space-y-3">
            <div class="flex justify-between items-start gap-3">
              <span class="text-[10px] bg-purple-500/10 border border-purple-500/20 text-purple-400 px-2 py-0.5 rounded font-bold uppercase font-mono">
                Target #{{ target.id }}
              </span>
              <span class="text-xs font-mono font-bold text-slate-500" v-if="target.budget">
                Budget: {{ fmtCurrency(target.budget) }}
              </span>
            </div>

            <h4 class="font-bold text-white leading-snug">{{ target.name }}</h4>

            <div class="space-y-2 pt-2 border-t border-slate-900 text-xs text-slate-450">
              <div class="flex justify-between">
                <span>Value Goal:</span>
                <span class="text-slate-300 font-medium">{{ target.value }} {{ target.unit || '' }}</span>
              </div>
            </div>

            <!-- KPIs mapping -->
            <div class="pt-3 border-t border-slate-900/60" v-if="target.kpis?.length">
              <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-2">Assigned KPIs:</span>
              <div class="space-y-1.5">
                <div v-for="kpi in target.kpis" :key="kpi.id" class="flex items-center justify-between text-xs p-2 rounded bg-slate-950/40 border border-slate-900">
                  <span class="text-slate-300 font-semibold truncate max-w-[200px]" :title="kpi.title_en">
                    {{ kpi.title_en }}
                  </span>
                  <span class="text-[10px] font-mono text-slate-500 shrink-0">Weight: {{ kpi.weight }}</span>
                </div>
              </div>
            </div>
            <div class="pt-3 border-t border-slate-900/60 text-[10px] text-slate-650 italic" v-else>
              No KPIs defined for this strategic target.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab contents: Archive -->
    <div v-if="activeTab === 'archive'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Department Documents</h3>
        <button
          @click="uploadModalOpen = true"
          class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-3.5 py-2 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          + Add Document
        </button>
      </div>

      <div v-if="!department.documents || !department.documents.length" class="py-16 text-center text-slate-550 border border-dashed border-slate-900 rounded-3xl">
        No documents archived for this department yet.
      </div>

      <div v-else class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden shadow-md">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 bg-slate-950/40 text-xs font-semibold text-slate-500 uppercase">
              <th class="px-5 py-4">Title</th>
              <th class="px-5 py-4">Uploaded By</th>
              <th class="px-5 py-4">Date</th>
              <th class="px-5 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="doc in department.documents" :key="doc.id" class="hover:bg-slate-900/30 transition-colors">
              <td class="px-5 py-4">
                <button @click="handleOpenDocument(doc.path)" class="font-semibold text-blue-400 hover:text-blue-300 transition-colors cursor-pointer flex items-center gap-2">
                  <Icon :name="isWebLink(doc.path) ? 'Link2' : (isImage(doc.path) ? 'Image' : 'FileText')" :size="14" />
                  {{ doc.title }}
                </button>
              </td>
              <td class="px-5 py-4 text-slate-400">{{ doc.uploaded_by?.name || '—' }}</td>
              <td class="px-5 py-4 text-slate-400">{{ fmt(doc.created_at) }}</td>
              <td class="px-5 py-4 text-right">
                <button @click="deleteDoc(doc.id)" class="text-rose-400 hover:text-rose-300 transition-colors cursor-pointer">
                  <Icon name="X" :size="15" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Upload Document Modal -->
    <div v-if="uploadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="uploadModalOpen = false">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-slate-950 p-6 space-y-5">
        <h3 class="text-lg font-bold text-white">Add Document</h3>

        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Title</label>
          <input v-model="documentForm.name" type="text" required placeholder="e.g. Department Policy 2026" class="w-full bg-slate-900/60 border border-slate-850 rounded-xl px-4 py-2.5 text-slate-100 placeholder-slate-600 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500/50" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">File Upload</label>
          <input type="file" @change="onDocFileChange" class="w-full text-sm text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-800 file:text-slate-200 file:text-xs" />
        </div>

        <div class="flex items-center gap-3 text-xs text-slate-600">
          <span class="flex-1 border-t border-slate-900"></span> OR <span class="flex-1 border-t border-slate-900"></span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Web Link</label>
          <input v-model="documentForm.file_path" type="url" placeholder="https://..." class="w-full bg-slate-900/60 border border-slate-850 rounded-xl px-4 py-2.5 text-slate-100 placeholder-slate-600 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500/50" />
        </div>

        <div v-if="documentForm.errors.file" class="text-xs text-rose-450 font-semibold">{{ documentForm.errors.file }}</div>

        <div class="flex justify-end gap-3 pt-2">
          <button @click="uploadModalOpen = false" class="px-4 py-2 text-sm text-slate-400 hover:text-slate-200 transition-colors cursor-pointer">Cancel</button>
          <button @click="submitDocument" :disabled="documentForm.processing" class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-xl transition-all cursor-pointer">
            {{ documentForm.processing ? 'Uploading...' : 'Add Document' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Image preview -->
    <div v-if="activePreviewImage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-6" @click="activePreviewImage = null">
      <img :src="activePreviewImage" class="max-h-[90vh] max-w-full rounded-2xl shadow-2xl" />
    </div>

  </div>
</template>
