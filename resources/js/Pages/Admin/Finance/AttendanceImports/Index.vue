<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
  module: { type: Object, default: () => ({ section: 'Finance', label: 'Attendance Imports', description: 'Upload device attendance for review before it posts to payroll.' }) },
  imports: { type: Array, default: () => [] },
  baseUrl: { type: String, default: '/admin/attendance-imports' },
});

const { confirm } = useConfirm();

const deleteImport = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Attendance Import',
    message: 'Are you sure you want to delete this attendance import session? This action cannot be undone.',
  });
  if (confirmed) {
    router.delete(`${props.baseUrl}/${id}`, {
      preserveScroll: true,
    });
  }
};

const STATUS_CLASSES = {
  pending_review: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  approved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rejected: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
};
const statusClass = (s) => STATUS_CLASSES[s] || 'bg-slate-800/60 border-slate-800 text-slate-500';
const statusLabel = (s) => (s || '').replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
</script>

<template>
  <Head title="Attendance Imports — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="UploadCloud" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>
        <Link
          :href="`${baseUrl}/create`"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer inline-flex items-center gap-2"
        >
          <Icon name="Plus" :size="16" /> Import Attendance
        </Link>
      </div>
    </section>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Period</th>
              <th class="pb-3">File</th>
              <th class="pb-3">Matched</th>
              <th class="pb-3">Ambiguous</th>
              <th class="pb-3">Unmatched</th>
              <th class="pb-3">Excluded</th>
              <th class="pb-3">Status</th>
              <th class="pb-3">Reviewed</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="imp in imports" :key="imp.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">{{ imp.payroll_period?.name }}</td>
              <td class="py-4 text-slate-400 truncate max-w-[220px]" :title="imp.original_filename">
                <div class="flex items-center gap-2">
                  <a
                    v-if="imp.file_path"
                    :href="`/attendance-imports/${imp.id}/file`"
                    target="_blank"
                    class="text-blue-400 hover:text-blue-300 inline-flex items-center gap-1 hover:underline shrink-0"
                    title="Download original file"
                  >
                    <Icon name="Download" :size="14" />
                  </a>
                  <span class="truncate">{{ imp.original_filename }}</span>
                </div>
              </td>
              <td class="py-4 font-mono text-emerald-400">{{ imp.matched_count }}</td>
              <td class="py-4 font-mono text-amber-400">{{ imp.ambiguous_count }}</td>
              <td class="py-4 font-mono text-rose-400">{{ imp.unmatched_count }}</td>
              <td class="py-4 font-mono text-slate-400">{{ imp.excluded_count }}</td>
              <td class="py-4">
                <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="statusClass(imp.status)">
                  {{ statusLabel(imp.status) }}
                </span>
              </td>
              <td class="py-4 text-slate-450 text-xs">
                <span v-if="imp.reviewed_by">{{ imp.reviewed_by?.name }} &middot; {{ imp.reviewed_at }}</span>
                <span v-else class="text-slate-600 italic">—</span>
              </td>
              <td class="py-4 text-right">
                <div class="flex justify-end items-center gap-2">
                  <Link
                    :href="`${baseUrl}/${imp.id}`"
                    class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                  >
                    Review
                  </Link>
                  <button
                    v-if="imp.status !== 'approved'"
                    @click="deleteImport(imp.id)"
                    class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-rose-900 hover:text-rose-450 bg-slate-900/50 text-slate-400 rounded-lg transition-colors cursor-pointer inline-flex items-center gap-1"
                  >
                    <Icon name="Trash2" :size="12" /> Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!imports.length">
              <td colspan="9" class="py-8 text-center text-slate-650 italic">
                No attendance imports yet — import a HikVision Excel report to get started.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
