<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, required: true },
  campuses: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingCampus = ref(null);

const form = useForm({
  name_en: '',
  name_am: '',
  city: '',
  is_active: true,
});

const openCreateModal = () => {
  editingCampus.value = null;
  form.reset();
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (campus) => {
  editingCampus.value = campus;
  form.name_en = campus.name_en;
  form.name_am = campus.name_am || '';
  form.city = campus.city || '';
  form.is_active = !!campus.is_active;
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingCampus.value) {
    form.put(`/admin/campuses/${editingCampus.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/campuses', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteCampus = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Campus',
    message: 'Are you sure you want to delete this campus? This may affect departments located at this campus.',
  });
  if (confirmed) {
    router.delete(`/admin/campuses/${id}`);
  }
};
</script>

<template>
  <Head title="Campuses — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Building2" :size="26" />
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
          + Add Campus
        </button>
      </div>
    </section>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">English Name</th>
              <th class="pb-3">Amharic Name</th>
              <th class="pb-3">City</th>
              <th class="pb-3">Status</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr
              v-for="campus in campuses"
              :key="campus.id"
              class="hover:bg-slate-900/30 transition-colors cursor-pointer"
              @click="router.visit(`/admin/organization/campuses/${campus.id}`)"
            >
              <td class="py-4 font-semibold">
                <Link
                  :href="`/admin/organization/campuses/${campus.id}`"
                  class="text-blue-400 hover:text-blue-300 hover:underline"
                  @click.stop
                >
                  {{ campus.name_en }}
                </Link>
              </td>
              <td class="py-4 text-slate-400">{{ campus.name_am || '—' }}</td>
              <td class="py-4 text-slate-400">{{ campus.city || '—' }}</td>
              <td class="py-4">
                <span 
                  class="px-2.5 py-0.5 text-xs rounded-full font-bold border"
                  :class="campus.is_active 
                    ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400' 
                    : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                >
                  {{ campus.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="py-4 text-right space-x-2">
                <button 
                  @click.stop="openEditModal(campus)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  @click.stop="deleteCampus(campus.id)"
                  class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="!campuses.length">
              <td colspan="5" class="py-8 text-center text-slate-600 italic">
                No campuses defined.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingCampus ? 'Edit Campus' : 'Add Campus' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- English Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">English Name</label>
            <input 
              v-model="form.name_en" 
              type="text" 
              required
              placeholder="e.g. Main Campus" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.name_en ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.name_en" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.name_en }}</div>
          </div>

          <!-- Amharic Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Amharic Name</label>
            <input 
              v-model="form.name_am" 
              type="text" 
              placeholder="e.g. ዋናው ግቢ" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.name_am ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.name_am" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.name_am }}</div>
          </div>

          <!-- City -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">City</label>
            <input 
              v-model="form.city" 
              type="text" 
              placeholder="e.g. Addis Ababa" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.city ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.city" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.city }}</div>
          </div>

          <!-- Active Toggle -->
          <div class="flex items-center gap-3">
            <input 
              v-model="form.is_active" 
              type="checkbox" 
              id="is_active"
              class="rounded border-slate-850 bg-slate-950 text-blue-500 focus:ring-0"
            />
            <label for="is_active" class="text-sm font-semibold text-slate-300 cursor-pointer">Active</label>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="closeModal"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="form.processing"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
            >
              {{ form.processing ? 'Saving...' : 'Save Campus' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
