<script setup>
import GuestLayout from '../Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Sign In - SITS ERP" />

    <!-- Back to home -->
    <Link href="/" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-300 transition-colors mb-8 group">
      <span class="group-hover:-translate-x-1 transition-transform">←</span>
      Back to landing page
    </Link>

    <!-- Logo & Title -->
    <div class="flex flex-col items-center mb-8">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center font-bold text-xl text-white shadow-xl shadow-blue-500/20 mb-4">
        S
      </div>
      <h2 class="text-3xl font-extrabold tracking-tight text-white text-center">Welcome Back</h2>
      <p class="text-slate-400 mt-2 text-sm text-center">Sign in to manage your enterprise operations</p>
    </div>

    <!-- Glassmorphic Card -->
    <div class="rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900/60 to-slate-950/90 backdrop-blur-xl p-8 shadow-2xl">
      <form @submit.prevent="submit" class="space-y-6">
        
        <!-- Email Input -->
        <div>
          <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 pointer-events-none text-sm">
              ✉️
            </span>
            <input 
              id="email"
              v-model="form.email" 
              type="email" 
              required 
              placeholder="you@company.com" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl pl-10 pr-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.email ? 'border-rose-500/50 focus:border-rose-500/50 focus:ring-rose-500/50' : 'border-slate-850'"
            />
          </div>
          <!-- Inline Error -->
          <div v-if="form.errors.email" class="text-xs text-rose-450 mt-2 font-semibold tracking-wide">
            {{ form.errors.email }}
          </div>
        </div>

        <!-- Password Input -->
        <div>
          <div class="flex justify-between items-center mb-2">
            <label for="password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Password</label>
            <a href="#" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Forgot Password?</a>
          </div>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 pointer-events-none text-sm">
              🔒
            </span>
            <input 
              id="password"
              v-model="form.password" 
              type="password" 
              required 
              placeholder="••••••••" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl pl-10 pr-4 py-3 text-slate-100 placeholder-slate-660 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.password ? 'border-rose-500/50 focus:border-rose-500/50 focus:ring-rose-500/50' : 'border-slate-850'"
            />
          </div>
          <!-- Inline Error -->
          <div v-if="form.errors.password" class="text-xs text-rose-450 mt-2 font-semibold tracking-wide">
            {{ form.errors.password }}
          </div>
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center">
          <input 
            id="remember_me"
            v-model="form.remember" 
            type="checkbox" 
            class="w-4 h-4 rounded border-slate-850 bg-slate-950/60 text-blue-600 focus:ring-0 focus:ring-offset-0 focus:outline-none cursor-pointer"
          />
          <label for="remember_me" class="ml-2 text-sm text-slate-400 select-none cursor-pointer hover:text-slate-350 transition-colors">
            Keep me signed in on this device
          </label>
        </div>

        <!-- Sign In Button -->
        <button 
          type="submit" 
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 disabled:from-blue-800 disabled:to-purple-800 text-white font-semibold py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-blue-500/10 hover:shadow-blue-500/20 hover:scale-[1.02] flex items-center justify-center gap-2 cursor-pointer"
        >
          <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span>{{ form.processing ? 'Signing In...' : 'Sign In to Dashboard' }}</span>
        </button>
      </form>

      <div class="mt-8 pt-6 border-t border-slate-900 text-center">
        <p class="text-sm text-slate-400">
          Don't have an enterprise account? 
          <Link href="/register" class="text-blue-400 hover:text-blue-300 font-semibold transition-colors">Sign Up</Link>
        </p>
      </div>
    </div>
  </GuestLayout>
</template>
