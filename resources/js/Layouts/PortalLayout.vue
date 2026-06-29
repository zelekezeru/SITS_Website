<script setup>
/**
 * Shared portal shell for the Employee and Department Head workspaces.
 *
 * It deliberately reuses AdminLayout so the design language — sidebar, topbar,
 * command palette, toasts, notifications, confirmation modal — is identical to
 * the admin area. The sidebar contents, brand subtitle and notifications are
 * supplied per-role by App\Support\PortalContext (shared via Inertia), so the
 * same component renders the right experience for each role.
 */
import AdminLayout from '@/Layouts/AdminLayout.vue';
</script>

<template>
  <AdminLayout>
    <slot />
  </AdminLayout>
</template>
