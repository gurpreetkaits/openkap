<template>
  <div class="min-h-screen flex items-center justify-center bg-base-200 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Logo and Title -->
      <div class="text-center">
        <img src="/logo.png" alt="ScreenSense" class="mx-auto w-16 h-16 rounded-2xl shadow-lg" />
        <h2 class="mt-6 text-2xl font-bold">
          Welcome to ScreenSense
        </h2>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="alert alert-error">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Login Card -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <div class="text-center">
            <p class="text-base-content/60 text-sm">Sign in to access your recordings</p>
          </div>

          <!-- Google Sign In Button -->
          <button
            @click="loginWithGoogle"
            class="btn btn-outline btn-block gap-3 mt-4"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continue with Google
          </button>

          <div class="divider text-xs text-base-content/50">Secure authentication</div>

          <!-- Features -->
          <div class="space-y-3">
            <div class="flex items-center text-sm text-base-content/70">
              <svg class="w-5 h-5 text-success mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              Unlimited screen recordings
            </div>
            <div class="flex items-center text-sm text-base-content/70">
              <svg class="w-5 h-5 text-success mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              One-click shareable links
            </div>
            <div class="flex items-center text-sm text-base-content/70">
              <svg class="w-5 h-5 text-success mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              Self-host or use our cloud
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center text-xs text-base-content/50">
        By signing in, you agree to our Terms of Service and Privacy Policy
      </p>
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

function loginWithGoogle() {
  // Store the redirect URL before navigating away
  const redirect = route.query.redirect
  if (redirect) {
    localStorage.setItem('auth_redirect', redirect)
  }
  auth.loginWithGoogle()
}

onMounted(() => {
  // Check for error in query params
  const errorParam = route.query.error
  if (errorParam) {
    error.value = true
    errorMessage.value = errorParam === 'authentication_failed'
      ? 'Authentication failed. Please try again.'
      : 'An error occurred. Please try again.'
  }
})
</script>
