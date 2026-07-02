<script setup>
import GuestLayout from '../../Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  token: { type: String, required: true },
  email: { type: String, default: '' },
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/reset-password', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Reset Password - SITS ERP" />

    <Link :href="route('login')" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-300 transition-colors mb-8 group">
      <span class="group-hover:-translate-x-1 transition-transform">←</span>
      Back to sign in
    </Link>

    <div class="flex flex-col items-center mb-8">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center font-bold text-xl text-white shadow-xl shadow-blue-500/20 mb-4">
        S
      </div>
      <h2 class="text-3xl font-extrabold tracking-tight text-white text-center">Reset Your Password</h2>
      <p class="text-slate-400 mt-2 text-sm text-center">Choose a new password for your account</p>
    </div>

    <div class="rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900/60 to-slate-950/90 backdrop-blur-xl p-8 shadow-2xl">
      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            readonly
            class="w-full bg-slate-950/40 border border-slate-850 rounded-xl px-4 py-3 text-slate-400 cursor-not-allowed text-sm"
          />
          <div v-if="form.errors.email" class="text-xs text-rose-450 mt-1 font-semibold">
            {{ form.errors.email }}
          </div>
        </div>

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
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl pl-10 pr-4 py-3 text-slate-100 placeholder-slate-660 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 disabled:from-blue-800 disabled:to-purple-800 text-white font-semibold py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-blue-500/10 hover:shadow-blue-500/20 hover:scale-[1.02] flex items-center justify-center gap-2 cursor-pointer"
        >
          <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span>{{ form.processing ? 'Resetting...' : 'Reset Password' }}</span>
        </button>
      </form>
    </div>
  </GuestLayout>
</template>
