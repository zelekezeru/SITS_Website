import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Lightweight i18n helper. Reads the active locale and its UI strings from the
 * shared Inertia props (HandleInertiaRequests), so `t('key')` works anywhere
 * without an extra dependency. Falls back to the key (or a given fallback).
 */
export function useTranslations() {
  const page = usePage();
  const locale = computed(() => page.props.locale ?? 'en');
  const t = (key, fallback) => page.props.translations?.[key] ?? fallback ?? key;
  return { t, locale };
}
