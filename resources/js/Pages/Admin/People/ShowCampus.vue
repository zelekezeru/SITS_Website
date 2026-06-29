<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

defineProps({
  campus: { type: Object, required: true },
});

const fmt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
  <Head :title="`Campus Details — ${campus.name_en}`" />

  <div class="space-y-8">
    <!-- Back Navigation -->
    <div class="flex items-center gap-3">
      <Link
        href="/admin/organization/campuses"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-white bg-slate-900/60 hover:bg-slate-900 border border-slate-900 hover:border-slate-700 px-4 py-2 rounded-xl transition-all"
      >
        <Icon name="ArrowLeft" :size="15" />
        Back to Campuses
      </Link>
      <div class="flex items-center gap-2 text-xs text-slate-600 font-semibold">
        <Icon name="ChevronRight" :size="13" />
        <span class="text-slate-400">{{ campus.name_en }}</span>
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
              <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight">{{ campus.name_en }}</h2>
              <span
                class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border"
                :class="campus.is_active
                  ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                  : 'bg-slate-800/60 border-slate-800 text-slate-500'"
              >
                {{ campus.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <p v-if="campus.name_am" class="text-slate-400 mt-1 font-medium">{{ campus.name_am }}</p>
            <p class="text-slate-400 text-sm mt-3 flex items-center gap-1.5">
              <Icon name="MapPin" :size="14" class="text-slate-650" />
              Location / City: <span class="text-slate-200 font-semibold">{{ campus.city || '—' }}</span>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Departments List -->
    <div class="space-y-4">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Departments at this Campus</h3>

      <div v-if="!campus.departments || !campus.departments.length" class="py-16 text-center text-slate-505 border border-dashed border-slate-900 rounded-3xl">
        No departments registered under this campus yet.
      </div>

      <div v-else class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden shadow-md">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 bg-slate-950/40 text-xs font-semibold text-slate-500 uppercase">
              <th class="px-5 py-4">Department Name</th>
              <th class="px-5 py-4">Amharic Name</th>
              <th class="px-5 py-4">Department Head</th>
              <th class="px-5 py-4">Status</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="dept in campus.departments" :key="dept.id" class="hover:bg-slate-900/30 transition-colors">
              <td class="px-5 py-4">
                <Link :href="'/admin/organization/departments/' + dept.id" class="hover:underline text-blue-450 font-semibold">{{ dept.name_en }}</Link>
              </td>
              <td class="px-5 py-4 text-slate-400">{{ dept.name_am || '—' }}</td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-2" v-if="dept.head">
                  <div class="w-6 h-6 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-400 shrink-0">
                    {{ dept.head.name.charAt(0) }}
                  </div>
                  <span class="text-slate-300">{{ dept.head.name }}</span>
                </div>
                <span class="text-slate-600 italic text-xs" v-else>No head assigned</span>
              </td>
              <td class="px-5 py-4">
                <span
                  class="px-2.5 py-0.5 text-xs rounded-full font-bold border"
                  :class="dept.is_active
                    ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                    : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                >
                  {{ dept.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
