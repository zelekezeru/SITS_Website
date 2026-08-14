<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  can: { type: Object, default: () => ({}) },
  capabilities: { type: Array, default: () => [] },
  sections: { type: Array, default: () => [] },
  roadmap: { type: Array, default: () => [] },
  reorderAlerts: { type: Array, default: () => [] },
  reorderAlertsTotal: { type: Number, default: 0 },
});

const granted = computed(() => props.capabilities.filter((c) => c.granted));
const withheld = computed(() => props.capabilities.filter((c) => !c.granted));

const PHASE = {
  done: 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400',
  next: 'bg-amber-500/10 border-amber-500/25 text-amber-300',
  planned: 'bg-slate-800/40 border-slate-800 text-slate-500',
};
</script>

<template>
  <Head title="Store — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-5">
        <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
          <Icon name="Warehouse" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Inventory &amp; Assets</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Store Dashboard</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">
            Every material and asset the Seminary owns — consumable stock counted from an append-only
            movement ledger, and fixed assets tracked individually by tag, custody and condition.
          </p>
        </div>
      </div>
    </section>

    <!-- Access summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-amber-500/15 bg-amber-500/[0.04] p-5">
        <p class="text-[11px] text-amber-500/80 font-semibold uppercase tracking-wider">Your Store Permissions</p>
        <p class="text-2xl font-extrabold text-amber-300 mt-1">{{ granted.length }}<span class="text-sm text-slate-500 font-bold"> / {{ capabilities.length }}</span></p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Areas You Can Open</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ sections.reduce((n, s) => n + s.items.length, 0) }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Can Approve Requisitions</p>
        <p class="text-2xl font-extrabold mt-1" :class="can.APPROVE_REQUESTS ? 'text-emerald-400' : 'text-slate-600'">
          {{ can.APPROVE_REQUESTS ? 'Yes' : 'No' }}
        </p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Can Post Adjustments</p>
        <p class="text-2xl font-extrabold mt-1" :class="can.ADJUST ? 'text-emerald-400' : 'text-slate-600'">
          {{ can.ADJUST ? 'Yes' : 'No' }}
        </p>
      </div>
    </div>

    <!-- Segregation-of-duties note: only shown to the custodian who is held to it -->
    <div v-if="can.ISSUE && !can.APPROVE_REQUESTS" class="flex items-start gap-3 rounded-2xl border border-slate-800 bg-slate-900/30 px-5 py-4">
      <Icon name="ShieldCheck" :size="18" class="text-slate-400 shrink-0 mt-0.5" />
      <p class="text-xs text-slate-400 leading-relaxed">
        <span class="font-semibold text-slate-300">Separation of duties.</span>
        As custodian of the goods you receive, issue and count stock — but requisition approval,
        posting a stocktake variance and approving a disposal all require a second signature from
        Operations or the President. This is what makes the store auditable.
      </p>
    </div>

    <!-- Reorder alerts -->
    <div v-if="reorderAlertsTotal" class="rounded-2xl border border-amber-500/20 bg-amber-500/[0.03] p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-amber-400/90 flex items-center gap-2">
          <Icon name="AlertTriangle" :size="15" /> Reorder Alerts
        </h3>
        <Link href="/store/items" class="text-[11px] font-bold text-amber-400 hover:text-amber-300">
          {{ reorderAlertsTotal }} item{{ reorderAlertsTotal === 1 ? '' : 's' }} — view catalog
        </Link>
      </div>
      <ul class="space-y-2">
        <li v-for="a in reorderAlerts" :key="a.id" class="flex items-center justify-between text-xs">
          <div class="min-w-0">
            <span class="font-mono text-amber-400/80">{{ a.code }}</span>
            <span class="text-slate-300 ml-2">{{ a.name_en }}</span>
            <span v-if="a.category" class="text-slate-600 ml-1">· {{ a.category }}</span>
          </div>
          <span class="font-mono text-slate-400 shrink-0 ml-4">{{ a.on_hand }} / {{ a.reorder_level }}</span>
        </li>
      </ul>
    </div>

    <!-- Store areas -->
    <div v-for="section in sections" :key="section.label" class="space-y-4">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">{{ section.label }}</h3>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <Link v-for="item in section.items" :key="item.path" :href="item.path"
              class="group flex items-start gap-4 p-5 rounded-2xl border border-slate-900 bg-slate-900/20 hover:bg-slate-900/50 hover:border-amber-500/30 transition-all">
          <span class="w-10 h-10 rounded-xl bg-slate-800/70 border border-slate-700 flex items-center justify-center text-amber-400 shrink-0">
            <Icon :name="item.icon || 'Package'" :size="18" />
          </span>
          <div class="min-w-0">
            <p class="font-bold text-slate-200 group-hover:text-white text-sm">{{ item.label }}</p>
            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">{{ item.description }}</p>
          </div>
        </Link>
      </div>
    </div>

    <!-- Permission detail -->
    <div class="grid lg:grid-cols-2 gap-4">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Granted to you</h3>
        <ul class="space-y-2.5">
          <li v-for="c in granted" :key="c.key" class="flex items-start gap-3">
            <Icon name="CheckCircle2" :size="15" class="text-emerald-400 shrink-0 mt-0.5" />
            <div class="min-w-0">
              <p class="text-xs font-semibold text-slate-300">{{ c.description }}</p>
              <p class="text-[10px] font-mono text-slate-600">{{ c.name }}</p>
            </div>
          </li>
          <li v-if="!granted.length" class="text-xs text-slate-500 italic">No store permissions granted.</li>
        </ul>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Requires another role</h3>
        <ul class="space-y-2.5">
          <li v-for="c in withheld" :key="c.key" class="flex items-start gap-3">
            <Icon name="Lock" :size="15" class="text-slate-600 shrink-0 mt-0.5" />
            <div class="min-w-0">
              <p class="text-xs font-semibold text-slate-500">{{ c.description }}</p>
              <p class="text-[10px] font-mono text-slate-700">{{ c.name }}</p>
            </div>
          </li>
          <li v-if="!withheld.length" class="text-xs text-slate-500 italic">You hold every store permission.</li>
        </ul>
      </div>
    </div>

    <!-- Roadmap -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-1">Delivery phases</h3>
      <p class="text-[11px] text-slate-600 mb-5">Access control ships first so the module is governed before it holds data.</p>
      <ol class="space-y-3">
        <li v-for="p in roadmap" :key="p.phase" class="flex items-start gap-4">
          <span class="px-2.5 py-1 text-[10px] rounded-lg font-bold border shrink-0 w-20 text-center" :class="PHASE[p.status]">
            {{ p.phase }}
          </span>
          <div class="min-w-0">
            <p class="text-xs font-bold" :class="p.status === 'planned' ? 'text-slate-500' : 'text-slate-200'">{{ p.title }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">{{ p.detail }}</p>
          </div>
        </li>
      </ol>
    </div>
  </div>
</template>
