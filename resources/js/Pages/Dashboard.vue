<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const page = usePage();
const panel = computed(() => page.props.panel ?? {});
const data = computed(() => page.props.portalData ?? null);
const user = computed(() => page.props.auth?.user);

const scope = computed(() => panel.value.scope);
const isEmployee = computed(() => scope.value === 'self');
const isDepartment = computed(() => scope.value === 'department' && !!data.value);

// ---- Accent palette (driven by the role's panel.accent) ------------------
const ACCENTS = {
  blue: { text: 'text-blue-400', soft: 'bg-blue-500/10', ring: 'border-blue-500/20', bar: 'bg-blue-500', glow: 'bg-blue-600/10' },
  indigo: { text: 'text-indigo-400', soft: 'bg-indigo-500/10', ring: 'border-indigo-500/20', bar: 'bg-indigo-500', glow: 'bg-indigo-600/10' },
  violet: { text: 'text-violet-400', soft: 'bg-violet-500/10', ring: 'border-violet-500/20', bar: 'bg-violet-500', glow: 'bg-violet-600/10' },
  cyan: { text: 'text-cyan-400', soft: 'bg-cyan-500/10', ring: 'border-cyan-500/20', bar: 'bg-cyan-500', glow: 'bg-cyan-600/10' },
  emerald: { text: 'text-emerald-400', soft: 'bg-emerald-500/10', ring: 'border-emerald-500/20', bar: 'bg-emerald-500', glow: 'bg-emerald-600/10' },
  amber: { text: 'text-amber-400', soft: 'bg-amber-500/10', ring: 'border-amber-500/20', bar: 'bg-amber-500', glow: 'bg-amber-600/10' },
  slate: { text: 'text-slate-300', soft: 'bg-slate-500/10', ring: 'border-slate-500/20', bar: 'bg-slate-400', glow: 'bg-slate-600/10' },
};
const accent = computed(() => ACCENTS[panel.value.accent] ?? ACCENTS.blue);

const firstName = computed(() => (user.value?.name ?? '').split(' ')[0] || 'there');

const TASK_BADGE = {
  completed: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  in_progress: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  submitted: 'bg-violet-500/10 border-violet-500/20 text-violet-400',
  pending: 'bg-slate-800/60 border-slate-800 text-slate-400',
  missed: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
};
const taskBadge = (s) => TASK_BADGE[s] ?? TASK_BADGE.pending;

const KPI_BADGE = {
  created: 'bg-slate-800/60 border-slate-800 text-slate-400',
  in_progress: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  achieved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
};
const kpiBadge = (s) => KPI_BADGE[s] ?? KPI_BADGE.created;

const label = (s) => (s ?? '').replace(/_/g, ' ');
const fmtDate = (d) => (d ? new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) : '—');
const money = (v) => 'ETB ' + Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
  <Head :title="(panel.title || 'Dashboard') + ' — SITS ERP'" />

  <div class="space-y-8">
    <!-- ===================== HERO ===================== -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full blur-[110px] pointer-events-none" :class="accent.glow"></div>
      <div class="relative z-10 flex flex-wrap items-center justify-between gap-6">
        <div class="min-w-0">
          <p class="text-[11px] font-semibold uppercase tracking-widest" :class="accent.text">{{ panel.roleLabel }}</p>
          <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">
            Welcome back, <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">{{ firstName }}</span>
          </h1>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ panel.subtitle }}</p>
        </div>
        <div v-if="data?.fortnight" class="shrink-0 rounded-2xl border border-slate-800 bg-slate-950/50 px-5 py-3 text-right">
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Current Sprint</p>
          <p class="text-sm font-bold text-white mt-1">{{ data.fortnight.name }}</p>
          <p class="text-xs text-slate-500">{{ fmtDate(data.fortnight.start_date) }} – {{ fmtDate(data.fortnight.end_date) }}</p>
        </div>
      </div>
    </section>

    <!-- ===================== STAT CARDS ===================== -->
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="(s, i) in (panel.stats || [])" :key="i"
           class="p-6 rounded-2xl border border-slate-900 bg-slate-900/35 backdrop-blur-md shadow-md">
        <span class="text-xs text-slate-500 block font-semibold uppercase tracking-wider mb-2">{{ s.label }}</span>
        <span class="text-3xl font-extrabold text-white">{{ s.value }}</span>
        <span class="text-xs text-slate-500 block mt-2 font-medium">{{ s.hint }}</span>
      </div>
    </div>

    <!-- ===================== EMPLOYEE WORKSPACE ===================== -->
    <div v-if="isEmployee && data" class="grid lg:grid-cols-12 gap-6">
      <!-- Upcoming tasks + KPIs -->
      <div class="lg:col-span-8 space-y-6">
        <div class="rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-white flex items-center gap-2"><Icon name="ListChecks" :size="19" :class="accent.text" /> Upcoming Tasks</h3>
            <Link href="/dashboard/tasks" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1">
              Manage all <Icon name="ArrowRight" :size="13" />
            </Link>
          </div>
          <div class="space-y-3">
            <div v-for="t in data.upcomingTasks" :key="t.id"
                 class="flex items-center gap-4 p-3 rounded-xl border border-slate-900 bg-slate-900/20">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-200 truncate">{{ t.title }}</p>
                <p class="text-xs text-slate-500 mt-0.5">
                  <span v-if="t.target">{{ t.target }} · </span>Due {{ fmtDate(t.due_date) }}
                </p>
              </div>
              <div class="w-24 hidden sm:block">
                <div class="bg-slate-950/80 rounded-full h-1.5 overflow-hidden">
                  <div class="h-full rounded-full" :class="accent.bar" :style="`width:${t.completion_pct}%`"></div>
                </div>
              </div>
              <span class="px-2 py-0.5 text-[10px] rounded-md font-bold uppercase tracking-wider border capitalize" :class="taskBadge(t.status)">
                {{ label(t.status) }}
              </span>
            </div>
            <p v-if="!data.upcomingTasks?.length" class="text-center text-sm text-slate-600 italic py-8">
              No open tasks — you're all caught up. 🎉
            </p>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-white flex items-center gap-2"><Icon name="Gauge" :size="19" :class="accent.text" /> My KPIs</h3>
            <Link href="/dashboard/kpis" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1">
              View all <Icon name="ArrowRight" :size="13" />
            </Link>
          </div>
          <div class="space-y-2">
            <div v-for="k in data.kpis" :key="k.id" class="flex items-center gap-3 p-3 rounded-xl border border-slate-900 bg-slate-900/20">
              <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :class="[accent.soft, accent.text]"><Icon name="Gauge" :size="16" /></span>
              <p class="flex-1 text-sm font-medium text-slate-200 truncate">{{ k.title }}</p>
              <span v-if="k.confirmed" class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">Confirmed</span>
              <span class="px-2 py-0.5 text-[10px] rounded-md font-bold uppercase tracking-wider border capitalize" :class="kpiBadge(k.status)">{{ label(k.status) }}</span>
            </div>
            <p v-if="!data.kpis?.length" class="text-center text-sm text-slate-600 italic py-8">No KPIs assigned to you yet.</p>
          </div>
        </div>
      </div>

      <!-- Evaluation + payslip + quick links -->
      <div class="lg:col-span-4 space-y-6">
        <div class="rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
          <h3 class="font-bold text-sm text-white uppercase tracking-wider flex items-center gap-2 mb-4"><Icon name="Star" :size="17" class="text-amber-400" /> Latest Evaluation</h3>
          <template v-if="data.latestEvaluation">
            <div class="flex items-end gap-2">
              <span class="text-4xl font-extrabold text-white">{{ data.latestEvaluation.final_score ?? '—' }}</span>
              <span class="text-sm text-slate-500 mb-1">/ 100</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">{{ data.latestEvaluation.period }}</p>
            <div class="flex items-center gap-2 mt-3">
              <span v-if="data.latestEvaluation.grade" class="text-[11px] font-bold px-2 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400">{{ data.latestEvaluation.grade }}</span>
              <span class="text-[11px] font-semibold px-2 py-1 rounded-lg bg-slate-800/60 border border-slate-800 text-slate-400 capitalize">{{ label(data.latestEvaluation.status) }}</span>
            </div>
            <Link href="/dashboard/evaluations" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1 mt-4">See history <Icon name="ArrowRight" :size="13" /></Link>
          </template>
          <p v-else class="text-sm text-slate-600 italic py-4">No evaluations on record yet.</p>
        </div>

        <div class="rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
          <h3 class="font-bold text-sm text-white uppercase tracking-wider flex items-center gap-2 mb-4"><Icon name="ReceiptText" :size="17" class="text-emerald-400" /> Latest Payslip</h3>
          <template v-if="data.latestPayslip">
            <p class="text-3xl font-extrabold text-white">{{ money(data.latestPayslip.net_pay) }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ data.latestPayslip.period }} · <span class="capitalize">{{ data.latestPayslip.status }}</span></p>
            <Link href="/dashboard/payslips" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1 mt-4">All payslips <Icon name="ArrowRight" :size="13" /></Link>
          </template>
          <p v-else class="text-sm text-slate-600 italic py-4">No payslips issued yet.</p>
        </div>
      </div>
    </div>

    <!-- ===================== DEPARTMENT HEAD WORKSPACE ===================== -->
    <div v-else-if="isDepartment" class="grid lg:grid-cols-12 gap-6">
      <div class="lg:col-span-6 space-y-6">
        <div class="rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-white flex items-center gap-2"><Icon name="Gauge" :size="19" :class="accent.text" /> KPIs to Approve</h3>
            <Link href="/department/kpis" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1">Review <Icon name="ArrowRight" :size="13" /></Link>
          </div>
          <div class="space-y-2">
            <div v-for="k in data.pendingKpis" :key="k.id" class="flex items-center gap-3 p-3 rounded-xl border border-slate-900 bg-slate-900/20">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-200 truncate">{{ k.title }}</p>
                <p class="text-xs text-slate-500">{{ k.employees }}</p>
              </div>
              <span class="text-[9px] font-bold uppercase tracking-wide px-2 py-1 rounded bg-amber-500/10 border border-amber-500/20 text-amber-400">Maker step</span>
            </div>
            <p v-if="!data.pendingKpis?.length" class="text-center text-sm text-slate-600 italic py-8">No KPIs awaiting your approval.</p>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-white flex items-center gap-2"><Icon name="Star" :size="19" class="text-amber-400" /> Evaluations to Score</h3>
            <Link href="/department/evaluations" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1">Score <Icon name="ArrowRight" :size="13" /></Link>
          </div>
          <div class="space-y-2">
            <div v-for="e in data.evaluationsToScore" :key="e.id" class="flex items-center gap-3 p-3 rounded-xl border border-slate-900 bg-slate-900/20">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-200 truncate">{{ e.employee }}</p>
                <p class="text-xs text-slate-500">{{ e.period }}</p>
              </div>
              <span class="text-[9px] font-bold uppercase tracking-wide px-2 py-1 rounded bg-violet-500/10 border border-violet-500/20 text-violet-400">Draft</span>
            </div>
            <p v-if="!data.evaluationsToScore?.length" class="text-center text-sm text-slate-600 italic py-8">No drafts need your input.</p>
          </div>
        </div>
      </div>

      <div class="lg:col-span-6">
        <div class="rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6 h-full">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-white flex items-center gap-2"><Icon name="ListChecks" :size="19" :class="accent.text" /> Team's Open Tasks</h3>
            <Link href="/department/tasks" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1">Manage <Icon name="ArrowRight" :size="13" /></Link>
          </div>
          <div class="space-y-3">
            <div v-for="t in data.openTasks" :key="t.id" class="p-3 rounded-xl border border-slate-900 bg-slate-900/20">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-slate-200 truncate">{{ t.title }}</p>
                <span class="px-2 py-0.5 text-[10px] rounded-md font-bold uppercase tracking-wider border capitalize shrink-0" :class="taskBadge(t.status)">{{ label(t.status) }}</span>
              </div>
              <div class="flex items-center gap-3 mt-2">
                <span class="text-xs text-slate-500 flex-1 truncate">{{ t.employee }} · Due {{ fmtDate(t.due_date) }}</span>
                <div class="w-20 bg-slate-950/80 rounded-full h-1.5 overflow-hidden">
                  <div class="h-full rounded-full" :class="accent.bar" :style="`width:${t.completion_pct}%`"></div>
                </div>
              </div>
            </div>
            <p v-if="!data.openTasks?.length" class="text-center text-sm text-slate-600 italic py-8">No open team tasks right now.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================== OTHER ROLES (capabilities + account) ============ -->
    <div v-else class="grid lg:grid-cols-12 gap-6">
      <div class="lg:col-span-8 rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
        <h3 class="font-bold text-lg text-white mb-4 flex items-center gap-2"><Icon name="ShieldCheck" :size="19" :class="accent.text" /> Your Capabilities</h3>
        <div class="flex flex-wrap gap-2">
          <span v-for="c in (panel.capabilities || [])" :key="c"
                class="text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-800 bg-slate-950/40 text-slate-300">{{ c }}</span>
          <p v-if="!(panel.capabilities || []).length" class="text-sm text-slate-600 italic">No explicit permissions granted.</p>
        </div>
      </div>
      <div class="lg:col-span-4 rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
        <h3 class="font-bold text-sm text-white uppercase tracking-wider mb-4">Account</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">Name</dt><dd class="text-slate-200 font-medium">{{ panel.account?.name }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="text-slate-200 font-medium truncate ml-3">{{ panel.account?.email }}</dd></div>
          <div v-if="panel.account?.department" class="flex justify-between"><dt class="text-slate-500">Department</dt><dd class="text-slate-200 font-medium">{{ panel.account.department }}</dd></div>
          <div v-if="panel.account?.staffNo" class="flex justify-between"><dt class="text-slate-500">Staff No.</dt><dd class="text-slate-200 font-mono text-xs">{{ panel.account.staffNo }}</dd></div>
        </dl>
      </div>
    </div>
  </div>
</template>
