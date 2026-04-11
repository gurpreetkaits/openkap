<template>
  <div class="min-h-screen bg-[#fafaf9] flex">
    <!-- Left panel — branding (hidden on mobile) -->
    <div class="hidden lg:flex lg:w-1/2 bg-stone-900 relative overflow-hidden flex-col justify-between p-12">
      <!-- Subtle orb -->
      <div class="absolute top-[-100px] left-[-80px] w-[420px] h-[420px] rounded-full"
           style="background:radial-gradient(circle,rgba(249,115,22,.35) 0%,transparent 70%);filter:blur(80px);pointer-events:none"></div>
      <div class="absolute bottom-[-60px] right-[-60px] w-[280px] h-[280px] rounded-full"
           style="background:radial-gradient(circle,rgba(234,88,12,.2) 0%,transparent 70%);filter:blur(60px);pointer-events:none"></div>

      <div class="relative z-10">
        <div class="flex items-center gap-2.5">
          <img src="/logo.png" alt="OpenKap" class="w-9 h-9 rounded-xl" />
          <span class="text-white font-bold text-lg tracking-tight">OpenKap</span>
        </div>
      </div>

      <div class="relative z-10 space-y-8">
        <div>
          <h1 class="text-3xl font-bold text-white leading-tight">
            Record it. Share it.<br>
            <span style="background:linear-gradient(135deg,#f97316,#ea580c);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Move on.</span>
          </h1>
          <p class="mt-3 text-stone-400 text-sm leading-relaxed max-w-xs">
            Skip the meeting. Send a quick screen recording instead — your team watches it on their own time.
          </p>
        </div>

        <div class="space-y-3">
          <div v-for="feat in features" :key="feat" class="flex items-center gap-3">
            <div class="w-5 h-5 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center flex-shrink-0">
              <svg class="w-3 h-3 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            <span class="text-stone-300 text-sm">{{ feat }}</span>
          </div>
        </div>
      </div>

      <div class="relative z-10">
        <p class="text-stone-600 text-xs">© {{ new Date().getFullYear() }} OpenKap</p>
      </div>
    </div>

    <!-- Right panel — login form -->
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
      <!-- Mobile logo -->
      <div class="lg:hidden mb-8 text-center">
        <img src="/logo.png" alt="OpenKap" class="w-12 h-12 rounded-2xl shadow mx-auto mb-3" />
        <h2 class="text-lg font-bold text-stone-900">OpenKap</h2>
      </div>

      <div class="w-full max-w-sm">
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-stone-900">Welcome back</h2>
          <p class="mt-1 text-sm text-stone-500">Sign in to access your workspace</p>
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-5 flex items-start gap-2.5 bg-red-50 border border-red-100 rounded-xl p-3.5">
          <svg class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-xs text-red-700">{{ errorMessage }}</p>
        </div>

        <!-- Google Button -->
        <button
          @click="loginWithGoogle"
          :disabled="loading"
          class="w-full flex items-center justify-center gap-3 px-4 py-3.5 bg-white border border-stone-200 rounded-xl text-sm font-medium text-stone-700 shadow-sm hover:border-stone-300 hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-orange-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          <span v-if="!loading">Continue with Google</span>
          <span v-else class="flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Redirecting…
          </span>
        </button>

        <div class="mt-4 flex items-center gap-3 text-stone-300">
          <div class="flex-1 h-px bg-stone-100"></div>
          <span class="text-xs text-stone-400">Secure · No password needed</span>
          <div class="flex-1 h-px bg-stone-100"></div>
        </div>

        <p class="mt-6 text-center text-xs text-stone-400">
          By continuing you agree to our
          <a href="/terms" class="text-orange-600 hover:underline">Terms</a>
          and
          <a href="/privacy-policy" class="text-orange-600 hover:underline">Privacy Policy</a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/stores/auth'

const route = useRoute()
const auth = useAuth()

const error = ref(false)
const errorMessage = ref('')
const loading = ref(false)

const features = [
  'Record your screen in one click',
  'Share instantly with a link',
  'AI transcripts & summaries',
  'No meetings, no scheduling',
]

function loginWithGoogle() {
  loading.value = true
  const redirect = route.query.redirect
  if (redirect) {
    localStorage.setItem('auth_redirect', redirect)
  }
  auth.loginWithGoogle()
}

onMounted(() => {
  const errorParam = route.query.error
  if (errorParam) {
    error.value = true
    errorMessage.value = errorParam === 'authentication_failed'
      ? 'Authentication failed. Please try again.'
      : 'An error occurred. Please try again.'
  }
})
</script>
