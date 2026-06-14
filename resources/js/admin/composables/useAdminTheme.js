import { useDark, useToggle } from '@vueuse/core';
import { computed } from 'vue';

/**
 * Admin dark/light theme management.
 *
 * Persists the choice in localStorage (key: admin-theme) and reflects it on
 * <html data-admin-theme="..."> so admin.scss variables switch accordingly.
 * Independent from the frontend's `data-theme` attribute — toggling the admin
 * theme never affects the public site.
 */
const isDark = useDark({
  storageKey: 'admin-theme',
  valueDark: 'dark',
  valueLight: 'light',
  attribute: 'data-admin-theme',
  selector: 'html',
});

const toggleDark = useToggle(isDark);

export function useAdminTheme() {
  const themeMode = computed(() => (isDark.value ? 'dark' : 'light'));

  return {
    isDark,
    themeMode,
    toggleDark,
  };
}
