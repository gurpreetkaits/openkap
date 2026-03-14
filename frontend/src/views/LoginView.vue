<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Logo and Title -->
      <div class="text-center">
        <img src="/logo.png" alt="OpenKap" class="mx-auto w-16 h-16 rounded-2xl shadow-lg" />
        <h2 class="mt-6 text-2xl font-bold text-gray-900">
          Welcome to OpenKap
        </h2>
        <p class="mt-2 text-base text-gray-500">The fastest way to record and share your screen</p>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
          <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="ml-3 text-sm text-red-700">{{ errorMessage }}</p>
        </div>
      </div>

      <!-- Login Card -->
      <div class="bg-white shadow-lg rounded-2xl p-8">
        <div class="space-y-6">
          <div class="text-center">
            <p class="text-gray-500 text-sm">Sign in to access your recordings</p>
          </div>

          <!-- Google Sign In Button -->
          <button
            @click="loginWithGoogle"
            class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-xl shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors"
          >
            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continue with Google
          </button>

          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">Secure authentication</span>
            </div>
          </div>

          <!-- Features -->
          <div class="space-y-3">
            <div class="flex items-center text-sm text-gray-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              Record your screen in one click
            </div>
            <div class="flex items-center text-sm text-gray-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              Share instantly with a link
            </div>
            <div class="flex items-center text-sm text-gray-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              AI transcripts, summaries, and comments
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center text-xs text-gray-500">
        By signing in, you agree to our <a href="/terms" class="text-orange-600 hover:text-orange-700 underline">Terms of Service</a> and <a href="/privacy-policy" class="text-orange-600 hover:text-orange-700 underline">Privacy Policy</a>
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
