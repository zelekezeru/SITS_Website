<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  deliverables: { type: Array, default: () => [] },
  fortnights: { type: Array, default: () => [] },
  people: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({ manage: false }) },
});

const summary = computed(() => ({
  total: props.deliverables.length,
  done: props.deliverables.filter((d) => d.is_completed).length,
  open: props.deliverables.filter((d) => !d.is_completed).length,
}));

const modalOpen = ref(false);
const editing = ref(null);
const form = useForm({ fortnight_id: '', user_id: '', name: '', deadline: '' });

const openCreate = () => {
  editing.value = null;
  form.reset();
  if (props.fortnights.length) form.fortnight_id = props.fortnights[0].id;
  if (props.people.length) form.user_id = props.people[0].id;
  form.clearErrors();
  modalOpen.value = true;
};

const openEdit = (d) => {
  editing.value = d;
  form.fortnight_id = d.fortnight_id;
  form.user_id = d.user_id;
  form.name = d.name;
  form.deadline = d.deadline ? d.deadline.substring(0, 10) : '';
  form.clearErrors();
  modalOpen.value = true;
};

const submit = () => {
  if (editing.value) {
    form.put(`/department/deliverables/${editing.value.id}`, { onSuccess: () => (modalOpen.value = false) });
  } else {
    form.post('/department/deliverables', { onSuccess: () => (modalOpen.value = false) });
  }
};

const toggle = (d) => router.post(`/department/deliverables/${d.id}/toggle`, {}, { preserveScroll: true });

const remove = async (d) => {
  if (await confirm({ title: 'Delete Deliverable', message: `Delete “${d.name}”?` })) {
    router.delete(`/department/deliverables/${d.id}`);
  }
};

const fmtDate = (d) => (d ? new Date(d).toLocaleDateString() : '—');
const overdue = (d) => !d.is_completed && d.deadline && new Date(d.deadline) < new Date(new Date().toDateString());
</script>

<template>
  <Head title="Deliverables — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-violet-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400 shrink-0"><Icon name="Flag" :size="26" /></span>
          <div>
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Performance</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Deliverables</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">Per-fortnight commitments with deadlines and your sign-off.</p>
          </div>
        </div>
        <button v-if="can.manage" @click="openCreate"
                class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">
          + Add Deliverable
        </button>
      </div>
    </section>

    <div class="grid grid-cols-3 gap-5">
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Open</span><p class="text-2xl font-extrabold text-white mt-1">{{ summary.open }}</p></div>
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Signed Off</span><p class="text-2xl font-extrabold text-emerald-400 mt-1">{{ summary.done }}</p></div>
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total</span><p class="text-2xl font-extrabold text-white mt-1">{{ summary.total }}</p></div>
    </div>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Deliverable</th>
              <th class="pb-3">Owner</th>
              <th class="pb-3">Fortnight</th>
              <th class="pb-3">Deadline</th>
              <th class="pb-3">Status</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="d in deliverables" :key="d.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">{{ d.name }}</td>
              <td class="py-4 text-slate-400">{{ d.user?.name }}</td>
              <td class="py-4 text-slate-400">{{ d.fortnight?.name || '—' }}</td>
              <td class="py-4">
                <span :class="overdue(d) ? 'text-rose-400 font-semibold' : 'text-slate-400'">{{ fmtDate(d.deadline) }}</span>
                <span v-if="overdue(d)" class="ml-1 text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-rose-500/10 border border-rose-500/20 text-rose-400">Overdue</span>
              </td>
              <td class="py-4">
                <button @click="can.manage && toggle(d)" :disabled="!can.manage"
                        class="px-2 py-0.5 text-[10px] rounded-md font-bold uppercase tracking-wider border transition-colors"
                        :class="d.is_completed ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-slate-800/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                  {{ d.is_completed ? 'Done' : 'Pending' }}
                </button>
                <p v-if="d.is_completed && d.reviewed_by" class="text-[10px] text-slate-600 mt-1">by {{ d.reviewed_by.name }}</p>
              </td>
              <td class="py-4 text-right space-x-2 whitespace-nowrap">
                <button v-if="can.manage" @click="openEdit(d)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer">Edit</button>
                <button v-if="can.manage" @click="remove(d)" class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-800 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer">Delete</button>
              </td>
            </tr>
            <tr v-if="!deliverables.length"><td colspan="6" class="py-12 text-center text-slate-600 italic">No deliverables yet.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">{{ editing ? 'Edit Deliverable' : 'Add Deliverable' }}</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name</label>
            <input v-model="form.name" type="text" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none text-sm" />
            <p v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Owner</label>
            <select v-model="form.user_id" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none text-sm">
              <option value="" disabled>Select</option>
              <option v-for="p in people" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <p v-if="form.errors.user_id" class="text-xs text-rose-400 mt-1">{{ form.errors.user_id }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Fortnight</label>
              <select v-model="form.fortnight_id" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none text-sm">
                <option value="" disabled>Select</option>
                <option v-for="f in fortnights" :key="f.id" :value="f.id">{{ f.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Deadline</label>
              <input v-model="form.deadline" type="date" class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none text-sm" />
            </div>
          </div>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="modalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">Cancel</button>
            <button type="submit" :disabled="form.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">{{ form.processing ? 'Saving…' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
