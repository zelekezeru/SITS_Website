<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

defineProps({
  module: { type: Object, required: true },
});
</script>

<template>
  <Head :title="`${module.label} — SITS Store`" />

  <div class="space-y-8 max-w-5xl">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-40%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-5">
        <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
          <Icon :name="module.icon || 'Package'" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · {{ module.section }}</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
        </div>
      </div>
    </section>

    <!-- Planned capabilities -->
    <section v-if="module.features && module.features.length">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Capabilities</h3>
      <div class="grid sm:grid-cols-2 gap-3">
        <div v-for="feature in module.features" :key="feature"
             class="flex items-center gap-3 p-4 rounded-xl border border-slate-900 bg-slate-900/20">
          <Icon name="Dot" :size="16" class="text-amber-400 shrink-0" />
          <span class="text-sm text-slate-300">{{ feature }}</span>
        </div>
      </div>
    </section>

    <!-- Honest status: you have access; the screen itself is still being built. -->
    <section class="rounded-2xl border border-slate-800 bg-slate-900/30 p-6 flex items-start gap-4">
      <span class="w-10 h-10 rounded-xl bg-slate-800/70 border border-slate-700 flex items-center justify-center text-slate-400 shrink-0">
        <Icon name="Info" :size="18" />
      </span>
      <div class="min-w-0 space-y-3">
        <div>
          <p class="text-sm font-bold text-slate-200">Access granted — screen in build</p>
          <p class="text-xs text-slate-500 mt-1 leading-relaxed">
            You hold the <span class="font-mono text-slate-400">{{ module.permission }}</span> permission, so this
            route is open to you. The working screen lands with its delivery phase; the data model, workflow
            and controls are specified in
            <span class="font-mono text-slate-400">docs/inventory-management-design.md</span>.
          </p>
        </div>
        <Link href="/store" class="inline-flex items-center gap-2 text-xs font-bold text-amber-400 hover:text-amber-300">
          <Icon name="ArrowLeft" :size="14" /> Back to the store dashboard
        </Link>
      </div>
    </section>
  </div>
</template>
