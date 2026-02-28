<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-50">
    <div class="text-center">
      <div v-if="processing" class="space-y-4">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-sky-50">
          <div class="animate-spin rounded-full h-6 w-6 border-2 border-sky-500 border-t-transparent"></div>
        </div>
        <p class="text-sm text-gray-600">Completing Trello connection...</p>
      </div>
      <div v-else-if="error" class="space-y-4">
        <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
        <p class="text-sm text-red-600">{{ error }}</p>
        <button @click="$router.push('/integrations')" class="text-sm text-orange-600 hover:text-orange-700 font-medium">
          Go to Integrations
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'TrelloCallbackView',
  setup() {
    const router = useRouter()
    const processing = ref(true)
    const error = ref('')

    onMounted(async () => {
      try {
        // Extract token from URL fragment (#token=...)
        const hash = window.location.hash.substring(1)
        const params = new URLSearchParams(hash)
        const token = params.get('token')

        // Also check query params for state
        const urlParams = new URLSearchParams(window.location.search)
        const state = urlParams.get('state')

        if (!token) {
          error.value = 'No token received from Trello'
          processing.value = false
          return
        }

        // Send token to backend via the callback endpoint
        const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'
        const callbackUrl = `${API_BASE_URL}/api/integrations/trello/callback?token=${encodeURIComponent(token)}&state=${encodeURIComponent(state || '')}`

        // Navigate the window to complete the OAuth flow (which redirects)
        window.location.href = callbackUrl
      } catch (e) {
        error.value = e.message || 'Failed to complete Trello connection'
        processing.value = false
      }
    })

    return { processing, error }
  }
}
</script>
