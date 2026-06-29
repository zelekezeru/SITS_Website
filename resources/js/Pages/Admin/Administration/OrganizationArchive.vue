<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
  module: { type: Object, required: true },
  organization: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();

const categoryIcon = (value) => ({
  image: 'Image',
  file: 'File',
  web_link: 'Link2',
  other: 'FolderOpen',
}[value] || 'FolderOpen');

const activeCategory = ref('all');

const documents = computed(() => props.organization.documents || []);
const filteredDocuments = computed(() =>
  activeCategory.value === 'all'
    ? documents.value
    : documents.value.filter((d) => d.category === activeCategory.value)
);

const modalOpen = ref(false);
const activePreviewImage = ref(null);

const form = useForm({
  name: '',
  category: props.categories[0]?.value || 'other',
  file_path: '',
  file: null,
});

const onFileChange = (e) => {
  form.file = e.target.files[0];
};

const submit = () => {
  form.post('/admin/organization/archive/documents', {
    onSuccess: () => {
      modalOpen.value = false;
      form.reset();
    },
  });
};

const deleteDoc = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Resource',
    message: 'Are you sure you want to remove this resource from the organization archive?',
  });
  if (confirmed) {
    router.delete(`/admin/organization/archive/documents/${id}`);
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
  <Head title="Organization Archive — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-purple-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 shrink-0">
            <Icon name="Archive" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>
        <button
          @click="modalOpen = true"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          + Add Resource
        </button>
      </div>
    </section>

    <!-- Category filter chips -->
    <div class="flex items-center gap-2 flex-wrap">
      <button
        @click="activeCategory = 'all'"
        class="text-xs font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-full border transition-all cursor-pointer"
        :class="activeCategory === 'all' ? 'bg-blue-500/10 border-blue-500/25 text-blue-400' : 'border-slate-900 text-slate-500 hover:text-slate-300'"
      >
        All ({{ documents.length }})
      </button>
      <button
        v-for="cat in categories"
        :key="cat.value"
        @click="activeCategory = cat.value"
        class="text-xs font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-full border transition-all cursor-pointer inline-flex items-center gap-1.5"
        :class="activeCategory === cat.value ? 'bg-blue-500/10 border-blue-500/25 text-blue-400' : 'border-slate-900 text-slate-500 hover:text-slate-300'"
      >
        <Icon :name="categoryIcon(cat.value)" :size="12" />
        {{ cat.label }} ({{ documents.filter(d => d.category === cat.value).length }})
      </button>
    </div>

    <div v-if="!filteredDocuments.length" class="py-16 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
      No resources in this category yet.
    </div>

    <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="doc in filteredDocuments"
        :key="doc.id"
        class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5 flex flex-col gap-3 hover:border-slate-800 transition-all"
      >
        <div class="flex items-start justify-between gap-2">
          <span class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 shrink-0">
            <Icon :name="categoryIcon(doc.category)" :size="18" />
          </span>
          <button @click="deleteDoc(doc.id)" class="text-slate-600 hover:text-rose-400 transition-colors cursor-pointer">
            <Icon name="X" :size="15" />
          </button>
        </div>
        <button @click="handleOpenDocument(doc.path)" class="text-left font-semibold text-slate-200 hover:text-blue-400 transition-colors cursor-pointer truncate">
          {{ doc.title }}
        </button>
        <div class="flex items-center justify-between text-[11px] text-slate-500 mt-auto pt-2 border-t border-slate-900/60">
          <span>{{ doc.uploaded_by?.name || '—' }}</span>
          <span>{{ fmt(doc.created_at) }}</span>
        </div>
      </div>
    </div>

    <!-- Add Resource Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">Add Resource</h3>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Title</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="e.g. Institutional Seal (PNG)"
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm border-slate-850"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Category</label>
            <select
              v-model="form.category"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            >
              <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">File Upload</label>
            <input
              type="file"
              @change="onFileChange"
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all border-slate-850"
            />
            <p class="text-[10px] text-slate-500 mt-1">Any file type, up to 50MB.</p>
          </div>

          <div class="flex items-center gap-3 text-xs text-slate-600">
            <span class="flex-1 border-t border-slate-900"></span> OR <span class="flex-1 border-t border-slate-900"></span>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Web Link</label>
            <input
              v-model="form.file_path"
              type="url"
              placeholder="https://..."
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all border-slate-850"
            />
          </div>

          <div v-if="form.errors.file" class="text-xs text-rose-450 font-semibold">{{ form.errors.file }}</div>

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
              {{ form.processing ? 'Adding...' : 'Add Resource' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Image Preview -->
    <div v-if="activePreviewImage" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/90 backdrop-blur-md" @click="activePreviewImage = null">
      <img :src="activePreviewImage" class="max-w-full max-h-[85vh] object-contain rounded-2xl border border-slate-800 shadow-2xl" alt="Preview" />
    </div>
  </div>
</template>
