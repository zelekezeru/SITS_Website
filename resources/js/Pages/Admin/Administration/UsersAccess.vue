<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
  module: { type: Object, required: true },
  routeName: { type: String, required: true },
  users: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
  permissions: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const searchQuery = ref('');
const permissionSearch = ref('');

const activeTab = computed(() => {
  if (props.routeName === 'admin.users.approvals') return 'approvals';
  if (props.routeName === 'admin.users.roles') return 'roles';
  return 'all';
});

const filteredUsers = computed(() => {
  let list = props.users;
  if (activeTab.value === 'approvals') {
    list = list.filter(u => !u.is_approved);
  }
  if (searchQuery.value.trim() !== '') {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(u => 
      u.name.toLowerCase().includes(q) || 
      u.email.toLowerCase().includes(q)
    );
  }
  return list;
});

const filteredPermissions = computed(() => {
  if (permissionSearch.value.trim() === '') return props.permissions;
  const q = permissionSearch.value.toLowerCase();
  return props.permissions.filter(p => p.name.toLowerCase().includes(q));
});

const toggleUserApproval = async (id, currentStatus) => {
  const actionText = currentStatus ? 'deactivate' : 'approve and activate';
  const confirmed = await confirm({
    title: `${currentStatus ? 'Deactivate' : 'Approve'} User`,
    message: `Are you sure you want to ${actionText} this user account?`,
  });
  if (confirmed) {
    router.post(`/admin/users/${id}/toggle`, {}, { preserveScroll: true });
  }
};

const changeUserRole = async (id, roleName) => {
  const confirmed = await confirm({
    title: 'Change User Role',
    message: `Are you sure you want to change this user's role to "${roleName}"?`,
  });
  if (confirmed) {
    router.post(`/admin/users/${id}/role`, { role: roleName }, { preserveScroll: true });
  }
};

const hasPermission = (role, permissionName) => {
  if (role.name === 'President / Super Admin') return true;
  return (role.permissions || []).some(p => p.name === permissionName);
};
</script>

<template>
  <Head title="Users & Access — SITS ERP" />

  <div class="space-y-6">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
          <Icon name="KeyRound" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
        </div>
      </div>
    </section>

    <!-- Sub-tab Navigation -->
    <div class="flex border-b border-slate-900 gap-1">
      <Link
        href="/admin/users"
        class="pb-3 px-4 text-xs font-bold tracking-wide transition-colors whitespace-nowrap cursor-pointer"
        :class="activeTab === 'all' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-350'"
      >
        All Users
      </Link>
      <Link
        href="/admin/users/approvals"
        class="pb-3 px-4 text-xs font-bold tracking-wide transition-colors whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
        :class="activeTab === 'approvals' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-350'"
      >
        <span>Pending Approvals</span>
        <span 
          v-if="users.filter(u => !u.is_approved).length > 0"
          class="bg-amber-500/10 text-amber-400 border border-amber-500/20 px-1.5 py-0.5 rounded text-[10px]"
        >
          {{ users.filter(u => !u.is_approved).length }}
        </span>
      </Link>
      <Link
        href="/admin/users/roles"
        class="pb-3 px-4 text-xs font-bold tracking-wide transition-colors whitespace-nowrap cursor-pointer"
        :class="activeTab === 'roles' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-350'"
      >
        Roles & Permissions Matrix
      </Link>
    </div>

    <!-- ─── TAB: USERS LIST & PENDING APPROVALS ─── -->
    <div v-if="activeTab === 'all' || activeTab === 'approvals'" class="space-y-4">
      <!-- Search -->
      <div class="flex max-w-md">
        <input 
          v-model="searchQuery"
          type="text"
          placeholder="Search by name or email..."
          class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2.5 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-xs"
        />
      </div>

      <!-- Users Table -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
                <th class="pb-3">User Name</th>
                <th class="pb-3">Email Address</th>
                <th class="pb-3" v-if="activeTab === 'all'">System Role</th>
                <th class="pb-3 text-right">Approved Status</th>
              </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-900">
              <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-900/40 transition-colors">
                <td class="py-4 font-semibold text-slate-200">{{ user.name }}</td>
                <td class="py-4 text-slate-400 font-mono text-xs">{{ user.email }}</td>
                <td class="py-4" v-if="activeTab === 'all'">
                  <select 
                    :value="user.roles?.[0]?.name || ''"
                    @change="changeUserRole(user.id, $event.target.value)"
                    class="bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-lg px-3 py-1.5 text-slate-200 text-xs focus:outline-none"
                  >
                    <option value="" disabled>Select Role</option>
                    <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                  </select>
                </td>
                <td class="py-4 text-right">
                  <button 
                    @click="toggleUserApproval(user.id, user.is_approved)"
                    class="px-3 py-1 text-xs rounded-full font-bold border cursor-pointer hover:scale-102 transition-all"
                    :class="user.is_approved 
                      ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400 hover:bg-emerald-500/20' 
                      : 'bg-amber-500/10 border-amber-500/25 text-amber-400 hover:bg-amber-500/20'"
                  >
                    {{ user.is_approved ? 'Approved' : 'Approve & Activate' }}
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredUsers.length">
                <td colspan="4" class="py-8 text-center text-slate-650 italic text-xs">
                  No users found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ─── TAB: ROLES & PERMISSIONS MATRIX ─── -->
    <div v-if="activeTab === 'roles'" class="space-y-4">
      <!-- Search Permissions -->
      <div class="flex max-w-md">
        <input 
          v-model="permissionSearch"
          type="text"
          placeholder="Search permissions..."
          class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2.5 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-xs"
        />
      </div>

      <!-- Matrix Grid -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-max">
            <thead>
              <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
                <th class="pb-3 pr-4 sticky left-0 bg-slate-950/20 backdrop-blur-md">Permission</th>
                <th v-for="role in roles" :key="role.id" class="pb-3 text-center px-4 font-mono text-[10px] max-w-[120px] truncate" :title="role.name">
                  {{ role.name }}
                </th>
              </tr>
            </thead>
            <tbody class="text-xs divide-y divide-slate-900">
              <tr v-for="perm in filteredPermissions" :key="perm.id" class="hover:bg-slate-900/40 transition-colors">
                <!-- Permission label -->
                <td class="py-3 font-semibold text-slate-300 capitalize sticky left-0 bg-slate-950/10 backdrop-blur-md pr-4">
                  {{ perm.name.replace(/_/g, ' ') }}
                </td>
                <!-- Role columns -->
                <td v-for="role in roles" :key="role.id" class="py-3 text-center px-4">
                  <span v-if="hasPermission(role, perm.name)" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-450 border border-emerald-500/20">
                    ✓
                  </span>
                  <span v-else class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-800/40 text-slate-600 border border-slate-850">
                    ✕
                  </span>
                </td>
              </tr>
              <tr v-if="!filteredPermissions.length">
                <td :colspan="roles.length + 1" class="py-8 text-center text-slate-650 italic">
                  No permissions found matching query.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
