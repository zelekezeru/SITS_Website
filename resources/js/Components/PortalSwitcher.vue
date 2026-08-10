<script setup>
/**
 * Cross-app portal switcher — the "jump to another SITS site" block inside the
 * avatar dropdown of every layout (public site, ERP, Library, Website Admin).
 *
 * It renders `page.props.portals` verbatim; the list, its order and its role
 * gating all come from App\Support\PortalDirectory server-side, so the four
 * layouts can no longer drift apart. Add a portal there, not here.
 */
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  /** Key of the portal we're currently inside, so it can be marked. */
  current: { type: String, default: null },
  /**
   * 'dark'   — dropdown rows in always-dark layouts (public site, ERP, Website Admin)
   * 'auto'   — dropdown rows in layouts that follow the light/dark class (Library)
   * 'drawer' — roomier rows for the public site's mobile drawer
   */
  variant: { type: String, default: 'dark' },
});

defineEmits(['navigate']);

const page = usePage();
const portals = computed(() => page.props.portals ?? []);

const itemClass = computed(() => {
  if (props.variant === 'auto') {
    return 'w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors';
  }
  if (props.variant === 'drawer') {
    return 'w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-300 hover:bg-slate-900 hover:text-white transition';
  }
  return 'w-full flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/50 transition-colors';
});

const iconClass = computed(() =>
  props.variant === 'auto' ? 'text-slate-400 dark:text-slate-500 shrink-0' : 'text-slate-500 shrink-0',
);

const iconSize = computed(() => (props.variant === 'drawer' ? 17 : 15));

const currentClass = computed(() =>
  props.variant === 'auto'
    ? 'text-indigo-600 dark:text-indigo-400'
    : 'text-indigo-400',
);
</script>

<template>
  <component
    v-for="portal in portals"
    :key="portal.key"
    :is="portal.external ? 'a' : Link"
    :href="portal.href"
    :target="portal.target || undefined"
    :rel="portal.target === '_blank' ? 'noopener' : undefined"
    :class="[itemClass, portal.key === current ? currentClass : '']"
    :title="portal.description"
    @click="$emit('navigate')"
  >
    <Icon :name="portal.icon" :size="iconSize" :class="iconClass" />
    <span class="truncate">{{ portal.label }}</span>
    <span
      v-if="portal.key === current"
      class="ml-auto h-1.5 w-1.5 rounded-full bg-current shrink-0"
      aria-hidden="true"
    />
  </component>
</template>
