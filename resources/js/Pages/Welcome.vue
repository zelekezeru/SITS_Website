<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Welcome to SITS ERP" />
  
  <div class="relative min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between items-center p-6 overflow-hidden">
    <!-- Glowing background accents -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] rounded-full bg-blue-600/10 blur-[120px] pointer-events-none"></div>
    <div class="absolute top-1/3 left-1/3 -translate-x-1/2 -translate-y-1/2 w-[300px] h-[300px] rounded-full bg-purple-600/10 blur-[100px] pointer-events-none"></div>

    <!-- Top Navigation -->
    <header class="w-full max-w-5xl flex items-center justify-between py-6 relative z-10">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center font-bold text-base text-white shadow-lg shadow-blue-500/20">
          S
        </div>
        <span class="text-lg font-bold tracking-tight text-white">SITS ERP</span>
      </div>
      <Link href="/login" class="text-sm font-medium text-slate-400 hover:text-white transition-colors">
        Sign In
      </Link>
    </header>

    <!-- Centered Hero / CTA -->
    <main class="w-full max-w-2xl text-center my-auto relative z-10 flex flex-col items-center">
      <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight leading-tight">
        Enterprise Management 
        <span class="block bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent mt-2">
          Simplified.
        </span>
      </h1>
      
      <p class="mt-6 text-base md:text-lg text-slate-400 max-w-md leading-relaxed">
        SITS ERP unifies financial ledgers, inventory streams, resource planning, and deep analytics in one fluid interface.
      </p>
      
      <div class="mt-10 flex flex-col sm:flex-row gap-4 items-center justify-center w-full sm:w-auto">
        <Link href="/login" class="w-full sm:w-auto px-8 py-3.5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-semibold rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 hover:scale-[1.02]">
          Sign In to System
        </Link>
        <Link href="/register" class="w-full sm:w-auto px-8 py-3.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 hover:bg-slate-900/80 text-slate-300 hover:text-white font-semibold rounded-xl transition-all duration-200">
          Create Account
        </Link>
      </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-5xl py-6 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-600 relative z-10 gap-2">
      <p>© 2026 SITS ERP. All rights reserved.</p>
      <div class="flex gap-4">
        <a href="#" class="hover:text-slate-400 transition-colors">Privacy Policy</a>
        <a href="#" class="hover:text-slate-400 transition-colors">Terms of Service</a>
      </div>
    </footer>
  </div>
</template>
