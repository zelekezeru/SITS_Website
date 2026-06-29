<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
  module: { type: Object, required: true },
  documents: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();

// Modal state
const modalOpen = ref(false);

const form = useForm({
  name: '',
  documentable_type: 'App\\Models\\Employee',
  documentable_id: '',
  file_path: '',
  file: null,
  upload_type: 'file',
});

const openCreateModal = () => {
  form.reset();
  if (props.employees.length) {
    form.documentable_id = props.employees[0].id;
  }
  form.clearErrors();
  modalOpen.value = true;
};

const onFileChange = (e) => {
  form.file = e.target.files[0];
};

const submitForm = () => {
  form.post('/admin/documents', {
    onSuccess: () => {
      modalOpen.value = false;
    }
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

const activePreviewImage = ref(null);

const isImage = (path) => {
  if (!path) return false;
  const ext = path.split('.').pop().toLowerCase();
  return ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'bmp'].includes(ext);
};

const isWebLink = (path) => {
  if (!path) return false;
  return path.startsWith('http://') || path.startsWith('https://');
};

const handleOpenDocument = (path) => {
  if (isWebLink(path)) {
    window.open(path, '_blank');
  } else if (isImage(path)) {
    activePreviewImage.value = '/' + path;
  } else {
    // It's a document: download it
    const link = document.createElement('a');
    link.href = '/' + path;
    link.download = path.split('/').pop() || 'document';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
};
</script>

<template>
  <Head title="Secure Documents Vault — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="FolderOpen" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>
        <button 
          @click="openCreateModal"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          + Upload Document
        </button>
      </div>
    </section>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Name</th>
              <th class="pb-3">Linked Record Type</th>
              <th class="pb-3">Secure Storage Path</th>
              <th class="pb-3">Uploaded By</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="doc in documents" :key="doc.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">{{ doc.name }}</td>
              <td class="py-4 font-mono text-xs text-slate-450">{{ doc.documentable_type.split('\\').pop() }} (ID: {{ doc.documentable_id }})</td>
              <td class="py-4 font-mono text-xs text-blue-400">{{ doc.file_path }}</td>
              <td class="py-4 text-slate-400">{{ doc.uploader?.name || 'System' }}</td>
              <td class="py-4 text-right space-x-2">
                <button 
                  @click="handleOpenDocument(doc.file_path)"
                  class="text-[11px] font-bold px-3 py-1.5 bg-blue-600/10 hover:bg-blue-600/20 border border-blue-500/20 text-blue-400 rounded-lg transition-colors cursor-pointer"
                >
                  Open
                </button>
                <button 
                  @click="deleteDoc(doc.id)"
                  class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="!documents.length">
              <td colspan="5" class="py-8 text-center text-slate-600 italic">
                No documents uploaded.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">Upload Secure Document</h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Document Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Document Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              placeholder="e.g. Elfinesh Degree Certificate" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
          </div>

          <!-- Linked Type -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Link To Type</label>
            <select 
              v-model="form.documentable_type"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            >
              <option value="App\\Models\\Employee">Employee File</option>
            </select>
          </div>

          <!-- Employee Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Select Target Record</label>
            <select 
              v-model="form.documentable_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              required
            >
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.full_name_en }} ({{ emp.staff_no }})</option>
            </select>
          </div>

          <!-- Source Type Toggle -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Source Type</label>
            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                <input type="radio" v-model="form.upload_type" value="file" class="accent-blue-500" />
                <span>Upload File</span>
              </label>
              <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                <input type="radio" v-model="form.upload_type" value="link" class="accent-blue-500" />
                <span>Web Link</span>
              </label>
            </div>
          </div>

          <!-- File Input -->
          <div v-if="form.upload_type === 'file'">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Choose File</label>
            <input 
              type="file" 
              @change="onFileChange"
              required
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all"
              accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip"
            />
            <p class="text-[10px] text-slate-500 mt-1">Supports image, PDF, Word, Excel, ZIP (Max 50MB)</p>
          </div>

          <!-- Web Link Input -->
          <div v-else>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Web Link / URL</label>
            <input 
              v-model="form.file_path" 
              type="url" 
              required
              placeholder="https://example.com/document.pdf"
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all"
            />
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="modalOpen = false"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="form.processing"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
            >
              Upload
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Image Preview Modal / Lightbox -->
    <div v-if="activePreviewImage" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/90 backdrop-blur-md" @click="activePreviewImage = null">
      <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center justify-center bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-2xl" @click.stop>
        <button 
          @click="activePreviewImage = null"
          class="absolute top-4 right-4 text-slate-400 hover:text-white bg-slate-950/80 p-2 rounded-full border border-slate-800 hover:border-slate-700 transition-all cursor-pointer z-10"
        >
          <Icon name="X" :size="20" />
        </button>
        <img :src="activePreviewImage" class="max-w-full max-h-[75vh] object-contain rounded-2xl border border-slate-950/50 shadow-inner" alt="Document Preview" />
        <div class="mt-4 text-slate-300 font-semibold text-sm text-center">
          Document Preview
        </div>
      </div>
    </div>
  </div>
</template>
