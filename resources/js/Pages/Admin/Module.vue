<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module: { type: Object, required: true },
});
</script>

<template>
  <Head :title="`${module.label} — SITS ERP`" />

  <div class="space-y-8 max-w-5xl">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-40%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-5">
        <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
          <Icon :name="module.icon || 'Dot'" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
        </div>
      </div>
    </section>

    <!-- Sub-sections -->
    <section v-if="module.children && module.children.length">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Sections</h3>
      <div class="grid sm:grid-cols-2 gap-4">
        <Link v-for="child in module.children" :key="child.name" :href="child.path"
              class="group flex items-start gap-4 p-5 rounded-2xl border border-slate-900 bg-slate-900/20 hover:bg-slate-900/50 hover:border-blue-500/30 transition-all">
          <span class="w-10 h-10 rounded-xl bg-slate-800/70 border border-slate-700 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="ChevronRight" :size="18" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="font-semibold text-white">{{ child.label }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ child.description }}</p>
          </div>
          <Icon name="ArrowRight" :size="16" class="text-slate-600 group-hover:text-blue-400 group-hover:translate-x-0.5 transition-all mt-1" />
        </Link>
      </div>
    </section>

    <!-- Capabilities -->
    <section v-if="module.features && module.features.length">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">What this module covers</h3>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="feature in module.features" :key="feature"
             class="flex items-center gap-3 p-4 rounded-xl border border-slate-900 bg-slate-900/20">
          <span class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
            <Icon name="ShieldCheck" :size="16" />
          </span>
          <span class="text-sm text-slate-300 font-medium">{{ feature }}</span>
        </div>
      </div>
    </section>

    <!-- Build status -->
    <section class="rounded-2xl border border-blue-500/15 bg-blue-500/5 p-6 flex items-center gap-4">
      <span class="w-10 h-10 rounded-xl bg-blue-500/15 flex items-center justify-center text-blue-400 shrink-0">
        <Icon name="Sparkles" :size="20" />
      </span>
      <div>
        <p class="font-semibold text-white">Module scaffolded &amp; routed</p>
        <p class="text-sm text-slate-400 mt-0.5">Navigation, access control and the data model are in place. Interactive management screens are being built on the verified schema.</p>
      </div>
    </section>
  </div>
</template>
