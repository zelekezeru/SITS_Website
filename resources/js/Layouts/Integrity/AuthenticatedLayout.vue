<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash ?? {});

const initials = computed(() =>
  (user.value?.name ?? 'U').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <header class="sticky top-0 z-30 border-b border-slate-900 bg-slate-950/80 backdrop-blur-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center gap-2">
        <Link :href="route('integrity.dashboard')" class="flex items-center gap-2.5 shrink-0">
          <span class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/25 flex items-center justify-center text-amber-400">
            <Icon name="ShieldCheck" :size="18" />
          </span>
          <span class="font-bold text-white tracking-tight hidden sm:inline">Academic Integrity</span>
        </Link>

        <nav class="flex items-center gap-1 ml-3">
          <Link
            :href="route('integrity.dashboard')"
            class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="route().current('integrity.dashboard') ? 'bg-slate-900 text-white' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900/60'"
          >
            Dashboard
          </Link>
          <Link
            :href="route('integrity.history')"
            class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="route().current('integrity.history') ? 'bg-slate-900 text-white' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900/60'"
          >
            History
          </Link>
        </nav>

        <div class="flex-1"></div>

        <Link href="/portal" class="hidden sm:flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-300 transition-colors">
          <Icon name="ArrowLeft" :size="14" />
          Portal
        </Link>

        <div class="flex items-center gap-2 pl-3 ml-1 border-l border-slate-900">
          <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-amber-500 to-orange-600 flex items-center justify-center text-xs font-bold text-white shrink-0">
            {{ initials }}
          </div>
          <span class="text-xs font-semibold text-slate-300 hidden md:inline">{{ user?.name }}</span>
        </div>
      </div>
    </header>

    <Transition
      enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2"
      leave-active-class="transition duration-200 ease-in" leave-to-class="opacity-0"
    >
      <div v-if="flash.success || flash.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <div
          class="rounded-xl border px-4 py-3 text-sm font-medium flex items-center gap-2"
          :class="flash.success ? 'border-emerald-500/30 bg-emerald-950/40 text-emerald-300' : 'border-rose-500/30 bg-rose-950/40 text-rose-300'"
        >
          <Icon :name="flash.success ? 'CheckCircle2' : 'AlertTriangle'" :size="16" class="shrink-0" />
          {{ flash.success || flash.error }}
        </div>
      </div>
    </Transition>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <slot />
    </main>
  </div>
</template>
