<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  panel: { type: Object, required: true },
});

const page = usePage();
const nav = computed(() => page.props.nav ?? []);

// All top-level modules except the dashboard itself, for the quick-access grid.
const modules = computed(() =>
  nav.value.flatMap((s) => s.items)
    .filter((i) => i.name !== 'admin.dashboard'));
</script>

<template>
  <Head title="Executive Command Center — SITS ERP" />

  <div class="space-y-8">

    <!-- Hero -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8 md:p-10">
      <div class="absolute top-[-30%] right-[-5%] w-96 h-96 rounded-full bg-blue-600/10 blur-[120px] pointer-events-none"></div>
      <div class="relative z-10 max-w-2xl space-y-3">
        <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-full px-3 py-1">
          <Icon name="ShieldCheck" :size="14" /> Full System Access
        </span>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight">
          Welcome, <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">{{ panel.account.name }}</span>
        </h2>
        <p class="text-slate-400 text-sm md:text-base">{{ panel.subtitle }}</p>
      </div>
    </section>

    <!-- Stat cards -->
    <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <div v-for="stat in panel.stats" :key="stat.label" class="p-6 rounded-2xl border border-slate-900 bg-slate-900/35 hover:border-slate-800 transition-colors">
        <span class="text-xs text-slate-500 block font-semibold uppercase tracking-wider mb-2">{{ stat.label }}</span>
        <span class="text-3xl font-extrabold text-white">{{ stat.value }}</span>
        <span class="text-xs text-slate-400 block mt-2 font-medium">{{ stat.hint }}</span>
      </div>
    </section>

    <!-- Module quick access -->
    <section>
      <h3 class="text-lg font-bold mb-4 flex items-center gap-2"><Icon name="LayoutDashboard" :size="18" class="text-blue-400" /> Modules</h3>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <Link v-for="m in modules" :key="m.name" :href="m.path"
              class="group p-5 rounded-2xl border border-slate-900 bg-slate-900/20 hover:bg-slate-900/50 hover:border-blue-500/30 transition-all">
          <div class="flex items-start justify-between">
            <span class="w-11 h-11 rounded-xl bg-slate-800/70 border border-slate-700 flex items-center justify-center text-blue-400 group-hover:scale-105 transition-transform">
              <Icon :name="m.icon" :size="20" />
            </span>
            <Icon name="ArrowRight" :size="16" class="text-slate-600 group-hover:text-blue-400 group-hover:translate-x-0.5 transition-all" />
          </div>
          <p class="mt-4 font-semibold text-white">{{ m.label }}</p>
          <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ m.description }}</p>
        </Link>
      </div>
    </section>
  </div>
</template>
