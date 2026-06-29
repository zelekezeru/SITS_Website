<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
  departments: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();

const uploadModalOpen = ref(false);
const activePreviewImage = ref(null);

const form = useForm({
  name: '',
  department_id: props.departments[0]?.id || '',
  file_path: '',
  file: null,
});

const onFileChange = (e) => {
  form.file = e.target.files[0];
};

const submit = () => {
  form.post('/department/documents', {
    onSuccess: () => {
      uploadModalOpen.value = false;
      form.reset();
      form.department_id = props.departments[0]?.id || '';
    },
  });
};

const deleteDoc = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Document',
    message: 'Are you sure you want to delete this document from the department archive?',
  });
  if (confirmed) {
    router.delete(`/department/documents/${id}`);
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

const fmt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
  <Head title="Department Archive — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-emerald-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start justify-between gap-4 flex-wrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0"><Icon name="FolderOpen" :size="26" /></span>
          <div>
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">People</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Department Archive</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">Documents, files and web links for the department(s) you head.</p>
          </div>
        </div>
        <button
          v-if="departments.length"
          @click="uploadModalOpen = true"
          class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-4 py-2.5 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          + Add Document
        </button>
      </div>
    </section>

    <div v-if="!departments.length" class="py-16 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
      You don't head any departments.
    </div>

    <div v-for="dept in departments" :key="dept.id" class="space-y-4">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">{{ dept.name_en }}</h3>

      <div v-if="!(dept.documents || []).length" class="py-10 text-center text-slate-600 italic text-xs border border-dashed border-slate-900 rounded-2xl">
        No documents archived for this department yet.
      </div>

      <div v-else class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-slate-900 bg-slate-950/40">
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Title</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Uploaded By</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in dept.documents" :key="doc.id" class="hover:bg-slate-900/30 transition-colors border-b border-slate-900/60 last:border-0">
                <td class="px-5 py-4">
                  <button @click="handleOpenDocument(doc.path)" class="text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors cursor-pointer flex items-center gap-2">
                    <Icon :name="isWebLink(doc.path) ? 'Link2' : (isImage(doc.path) ? 'Image' : 'FileText')" :size="14" />
                    {{ doc.title }}
                  </button>
                </td>
                <td class="px-5 py-4 text-sm text-slate-400">{{ doc.uploaded_by?.name || '—' }}</td>
                <td class="px-5 py-4 text-sm text-slate-400">{{ fmt(doc.created_at) }}</td>
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
    </div>

    <!-- Upload Modal -->
    <div v-if="uploadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="uploadModalOpen = false">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-slate-950 p-6 space-y-5">
        <h3 class="text-lg font-bold text-white">Add Document</h3>

        <div v-if="departments.length > 1">
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Department</label>
          <select v-model="form.department_id" class="w-full bg-slate-900/60 border border-slate-850 rounded-xl px-4 py-2.5 text-slate-100 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500/50">
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name_en }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Title</label>
          <input v-model="form.name" type="text" required placeholder="e.g. Department Policy 2026" class="w-full bg-slate-900/60 border border-slate-850 rounded-xl px-4 py-2.5 text-slate-100 placeholder-slate-600 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500/50" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">File Upload</label>
          <input type="file" @change="onFileChange" class="w-full text-sm text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-800 file:text-slate-200 file:text-xs" />
        </div>

        <div class="flex items-center gap-3 text-xs text-slate-600">
          <span class="flex-1 border-t border-slate-900"></span> OR <span class="flex-1 border-t border-slate-900"></span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Web Link</label>
          <input v-model="form.file_path" type="url" placeholder="https://..." class="w-full bg-slate-900/60 border border-slate-850 rounded-xl px-4 py-2.5 text-slate-100 placeholder-slate-600 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500/50" />
        </div>

        <div v-if="form.errors.file" class="text-xs text-rose-450 font-semibold">{{ form.errors.file }}</div>

        <div class="flex justify-end gap-3 pt-2">
          <button @click="uploadModalOpen = false" class="px-4 py-2 text-sm text-slate-400 hover:text-slate-200 transition-colors cursor-pointer">Cancel</button>
          <button @click="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-xl transition-all cursor-pointer">
            {{ form.processing ? 'Uploading...' : 'Add Document' }}
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
