<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import LibraryLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  mustVerifyEmail: Boolean,
  status: String,
  from: { type: String, default: 'website' },
  hasPendingDeactivation: Boolean,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const userAvatar = computed(() => {
  const img = user.value?.profile_image;
  return img ? (img.startsWith('http') ? img : `/storage/${img}`) : '/img/user.png';
});

// Dynamic layout determination
const layout = computed(() => {
  if (props.from === 'library') {
    return LibraryLayout;
  }
  if (props.from === 'erp' || props.from === 'admin') {
    return AdminLayout;
  }
  return WebsiteLayout;
});

// Initials for avatar fallback
const initials = computed(() => (user.value?.name || 'U').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());

// Profile Information Form
const infoForm = useForm({
  name: user.value?.name || '',
  email: user.value?.email || '',
});

const updateProfileInfo = () => {
  infoForm.patch(route('profile.update'), {
    preserveScroll: true,
  });
};

// Profile Image Upload Form
const imageInput = ref(null);
const imageForm = useForm({
  profile_image: null,
});

const triggerImageSelect = () => {
  imageInput.value.click();
};

const handleImageChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    imageForm.profile_image = file;
    imageForm.post(route('profile.uploadProfileImage'), {
      preserveScroll: true,
      forceFormData: true,
    });
  }
};

// Password Update Form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  passwordForm.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => passwordForm.reset(),
  });
};

// Account Deletion Form & Modal
const showDeleteConfirm = ref(false);
const deleteForm = useForm({
  password: '',
});

const deleteAccount = () => {
  deleteForm.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteConfirm.value = false;
      deleteForm.reset();
    },
  });
};
</script>

<template>
  <Head title="My Profile — SITS" />

  <component :is="layout">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-250">
        Profile Settings
      </h2>
    </template>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-8">
      
      <!-- Pending Deactivation / Deletion Warning -->
      <div 
        v-if="hasPendingDeactivation || status === 'deactivation-requested'" 
        class="rounded-2xl border border-amber-200 dark:border-amber-950 bg-amber-500/5 p-4 sm:p-5 flex items-start gap-3.5 shadow-sm"
      >
        <Icon name="AlertTriangle" :size="20" class="text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
        <div>
          <h4 class="font-bold text-sm text-amber-800 dark:text-amber-400">Account Deactivation Request Pending</h4>
          <p class="text-xs text-amber-705 dark:text-amber-500/80 mt-1">
            Your request to delete/deactivate this account has been logged and is currently pending administrator review.
          </p>
        </div>
      </div>
      
      <!-- ===================== PROFILE HERO ===================== -->
      <section class="relative overflow-hidden rounded-3xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 p-6 sm:p-8 shadow-sm">
        <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-indigo-500/10 blur-[110px] pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col sm:flex-row items-center gap-6">
          <!-- Avatar Frame & Upload Trigger -->
          <div class="relative group cursor-pointer" @click="triggerImageSelect">
            <div class="w-24 h-24 rounded-2xl overflow-hidden ring-4 ring-indigo-500/20 bg-gradient-to-tr from-indigo-500 to-cyan-400 flex items-center justify-center text-3xl font-bold text-white shadow-xl">
              <img v-if="user?.profile_image" :src="userAvatar" alt="Avatar" class="w-full h-full object-cover" />
              <span v-else>{{ initials }}</span>
            </div>
            
            <!-- Upload Overlay -->
            <div class="absolute inset-0 bg-black/60 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col items-center justify-center text-white text-[10px] font-bold gap-1">
              <Icon name="Upload" :size="16" />
              <span>Change Photo</span>
            </div>
            
            <input 
              ref="imageInput" 
              type="file" 
              class="hidden" 
              accept="image/*" 
              @change="handleImageChange"
            />
          </div>
          
          <div class="min-w-0 text-center sm:text-left flex-1">
            <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ user?.name }}</h2>
            <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">{{ user?.email }}</p>
            
            <div class="flex flex-wrap justify-center sm:justify-start gap-1.5 mt-3">
              <span 
                v-for="r in user?.roles" 
                :key="r" 
                class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-indigo-500/10 border border-indigo-500/20 text-indigo-650 dark:text-indigo-400"
              >
                {{ r }}
              </span>
            </div>
          </div>

          <div v-if="imageForm.processing" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold animate-pulse">
            Uploading image...
          </div>
        </div>
      </section>

      <!-- ===================== FORMS CONTAINER ===================== -->
      <div class="grid lg:grid-cols-12 gap-8">
        
        <!-- Profile details -->
        <div class="lg:col-span-6 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900/25 p-6 shadow-sm">
          <h3 class="font-bold text-lg text-gray-900 dark:text-white flex items-center gap-2 mb-1">
            <Icon name="User" :size="19" class="text-indigo-600 dark:text-indigo-400" /> 
            Profile Info
          </h3>
          <p class="text-xs text-gray-500 dark:text-slate-400 mb-6">Update your account's profile name and email address.</p>

          <form @submit.prevent="updateProfileInfo" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-2">Name</label>
              <input 
                v-model="infoForm.name" 
                type="text" 
                required 
                class="w-full bg-gray-50 dark:bg-slate-950/60 border border-gray-300 dark:border-slate-800 focus:border-indigo-500 rounded-xl px-4 py-2.5 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition" 
              />
              <p v-if="infoForm.errors.name" class="text-xs text-rose-500 mt-1">{{ infoForm.errors.name }}</p>
            </div>
            
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
              <input 
                v-model="infoForm.email" 
                type="email" 
                required 
                class="w-full bg-gray-50 dark:bg-slate-950/60 border border-gray-300 dark:border-slate-800 focus:border-indigo-500 rounded-xl px-4 py-2.5 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition" 
              />
              <p v-if="infoForm.errors.email" class="text-xs text-rose-500 mt-1">{{ infoForm.errors.email }}</p>
            </div>

            <div class="pt-2 flex items-center justify-between">
              <button 
                type="submit" 
                :disabled="infoForm.processing" 
                class="text-sm font-semibold bg-indigo-650 hover:bg-indigo-600 text-white px-5 py-2.5 rounded-xl transition shadow-md shadow-indigo-500/10 cursor-pointer disabled:opacity-50"
              >
                {{ infoForm.processing ? 'Saving...' : 'Save Changes' }}
              </button>
              
              <span v-if="infoForm.recentlySuccessful" class="text-xs text-emerald-500 dark:text-emerald-400 font-semibold flex items-center gap-1">
                <Icon name="Check" :size="14" /> Saved successfully
              </span>
            </div>
          </form>
        </div>

        <!-- Security / Password change -->
        <div class="lg:col-span-6 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900/25 p-6 shadow-sm">
          <h3 class="font-bold text-lg text-gray-900 dark:text-white flex items-center gap-2 mb-1">
            <Icon name="ShieldCheck" :size="19" class="text-emerald-600 dark:text-emerald-400" /> 
            Security Settings
          </h3>
          <p class="text-xs text-gray-500 dark:text-slate-400 mb-6">Update your account password to ensure security.</p>

          <form @submit.prevent="updatePassword" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-2">Current Password</label>
              <input 
                v-model="passwordForm.current_password" 
                type="password" 
                required 
                class="w-full bg-gray-50 dark:bg-slate-950/60 border border-gray-300 dark:border-slate-800 focus:border-indigo-500 rounded-xl px-4 py-2.5 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition" 
              />
              <p v-if="passwordForm.errors.current_password" class="text-xs text-rose-505 mt-1">{{ passwordForm.errors.current_password }}</p>
            </div>
            
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-2">New Password</label>
              <input 
                v-model="passwordForm.password" 
                type="password" 
                required 
                class="w-full bg-gray-50 dark:bg-slate-950/60 border border-gray-300 dark:border-slate-800 focus:border-indigo-500 rounded-xl px-4 py-2.5 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition" 
              />
              <p v-if="passwordForm.errors.password" class="text-xs text-rose-505 mt-1">{{ passwordForm.errors.password }}</p>
            </div>
            
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-2">Confirm New Password</label>
              <input 
                v-model="passwordForm.password_confirmation" 
                type="password" 
                required 
                class="w-full bg-gray-50 dark:bg-slate-950/60 border border-gray-300 dark:border-slate-800 focus:border-indigo-500 rounded-xl px-4 py-2.5 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition" 
              />
              <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-rose-505 mt-1">{{ passwordForm.errors.password_confirmation }}</p>
            </div>

            <div class="pt-2 flex items-center justify-between">
              <button 
                type="submit" 
                :disabled="passwordForm.processing" 
                class="text-sm font-semibold bg-emerald-650 hover:bg-emerald-650 text-white px-5 py-2.5 rounded-xl transition shadow-md shadow-emerald-500/10 cursor-pointer disabled:opacity-50"
              >
                {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
              </button>
              
              <span v-if="passwordForm.recentlySuccessful" class="text-xs text-emerald-505 dark:text-emerald-400 font-semibold flex items-center gap-1">
                <Icon name="Check" :size="14" /> Password updated
              </span>
            </div>
          </form>
        </div>

      </div>

      <!-- ===================== DANGER ZONE / DELETION ===================== -->
      <section class="rounded-2xl border border-rose-200 dark:border-rose-950 bg-rose-500/5 p-6 shadow-sm">
        <h3 class="font-bold text-lg text-rose-700 dark:text-rose-450 flex items-center gap-2 mb-1">
          <Icon name="AlertTriangle" :size="19" /> 
          Danger Zone
        </h3>
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-6">
          Once you delete your account, all of its resources and data will be permanently deleted.
        </p>

        <button 
          @click="showDeleteConfirm = true" 
          :disabled="hasPendingDeactivation"
          class="text-sm font-semibold bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 rounded-xl transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ hasPendingDeactivation ? 'Deletion Request Pending' : 'Delete Account' }}
        </button>
      </section>
      
      <!-- ===================== CONFIRMATION MODAL ===================== -->
      <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
        
        <div class="relative w-full max-w-md rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl p-6 overflow-hidden">
          <h4 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Delete Account Confirmation</h4>
          <p class="text-xs text-gray-500 dark:text-slate-400 mb-5">
            Are you sure you want to delete your account? Please enter your password to confirm.
          </p>

          <form @submit.prevent="deleteAccount" class="space-y-4">
            <div>
              <input 
                v-model="deleteForm.password" 
                type="password" 
                placeholder="Enter Password" 
                required 
                class="w-full bg-gray-50 dark:bg-slate-950/60 border border-gray-300 dark:border-slate-800 focus:border-rose-500 rounded-xl px-4 py-2.5 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-rose-500 text-sm transition" 
              />
              <p v-if="deleteForm.errors.password" class="text-xs text-rose-505 mt-1">{{ deleteForm.errors.password }}</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
              <button 
                type="button" 
                @click="showDeleteConfirm = false" 
                class="text-xs font-semibold px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-gray-700 dark:text-slate-200"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                :disabled="deleteForm.processing" 
                class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-4 py-2 rounded-xl"
              >
                Confirm Delete
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </component>
</template>
