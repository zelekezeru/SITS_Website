<script setup>
import GuestLayout from '../../Layouts/GuestLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const form = useForm({
  password: '',
  password_confirmation: '',
});

const logout = () => router.post('/logout');

const submit = () => {
  form.post('/password/force-change', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Set a New Password - SITS ERP" />

    <!-- Logo & Title -->
    <div class="flex flex-col items-center mb-8">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center font-bold text-xl text-white shadow-xl shadow-blue-500/20 mb-4">
        S
      </div>
      <h2 class="text-3xl font-extrabold tracking-tight text-white text-center">Set a New Password</h2>
      <p class="text-slate-400 mt-2 text-sm text-center max-w-sm">
        You're signing in with a default or recovery password. Choose a new password to continue.
      </p>
    </div>

    <!-- Glassmorphic Card -->
    <div class="rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900/60 to-slate-950/90 backdrop-blur-xl p-8 shadow-2xl">
      <form @submit.prevent="submit" class="space-y-5">
        <!-- New Password -->
        <div>
          <label for="password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">New Password</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 pointer-events-none text-sm">🔒</span>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              minlength="8"
              placeholder="••••••••"
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl pl-10 pr-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.password ? 'border-rose-500/50 focus:border-rose-500/50 focus:ring-rose-500/50' : 'border-slate-850'"
            />
          </div>
          <div v-if="form.errors.password" class="text-xs text-rose-450 mt-1 font-semibold">
            {{ form.errors.password }}
          </div>
        </div>

        <!-- Confirm Password -->
        <div>
          <label for="password_confirmation" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Confirm New Password</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 pointer-events-none text-sm">🔒</span>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              required
              minlength="8"
              placeholder="••••••••"
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl pl-10 pr-4 py-3 text-slate-100 placeholder-slate-660 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm border-slate-850"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 disabled:from-blue-800 disabled:to-purple-800 text-white font-semibold py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-blue-500/10 hover:shadow-blue-500/20 hover:scale-[1.02] flex items-center justify-center gap-2 cursor-pointer"
        >
          <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span>{{ form.processing ? 'Saving...' : 'Set Password & Continue' }}</span>
        </button>
      </form>

      <div class="mt-6 pt-5 border-t border-slate-900 text-center">
        <button @click="logout" class="text-sm text-slate-500 hover:text-slate-300 transition-colors cursor-pointer">
          Sign out instead
        </button>
      </div>
    </div>
  </GuestLayout>
</template>
