<template>
  <div class="animate-fade-in">
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-xl font-bold text-gray-900 tracking-tight">Integrations</h1>
      <p class="text-gray-500 text-sm mt-1">Connect external tools to share videos directly</p>
    </div>

    <!-- Success/Error Alerts -->
    <div v-if="alertMessage" class="mb-6 p-4 rounded-xl border text-sm flex items-center gap-3" :class="alertType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'">
      <svg v-if="alertType === 'success'" class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <svg v-else class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      {{ alertMessage }}
      <button @click="alertMessage = ''" class="ml-auto text-gray-400 hover:text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-xl border border-gray-100 p-12 text-center">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-orange-50 mb-3">
        <div class="animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
      </div>
      <p class="text-sm text-gray-500">Loading integrations...</p>
    </div>

    <!-- Provider Cards -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="provider in providers"
        :key="provider.id"
        class="bg-white rounded-xl border border-gray-100 p-5 hover:shadow-sm transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <!-- Provider Icon -->
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getProviderBgColor(provider.id)">
              <!-- Jira -->
              <svg v-if="provider.id === 'jira'" class="w-5 h-5" :class="getProviderIconColor(provider.id)" viewBox="0 0 24 24" fill="currentColor">
                <path d="M11.571 11.513H0a5.218 5.218 0 0 0 5.232 5.215h2.13v2.057A5.215 5.215 0 0 0 12.575 24V12.518a1.005 1.005 0 0 0-1.005-1.005zm5.723-5.756H5.736a5.215 5.215 0 0 0 5.215 5.214h2.129v2.058a5.218 5.218 0 0 0 5.215 5.214V6.758a1.001 1.001 0 0 0-1.001-1.001zM23.013 0H11.455a5.215 5.215 0 0 0 5.215 5.215h2.129v2.057A5.215 5.215 0 0 0 24.013 12.487V1.005A1.005 1.005 0 0 0 23.013 0z"/>
              </svg>
              <!-- Fallback -->
              <svg v-else class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900">{{ provider.name }}</h3>
              <p class="text-xs text-gray-500 mt-0.5">{{ provider.description }}</p>
            </div>
          </div>

          <!-- Status Badge -->
          <span
            v-if="provider.connected"
            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700"
          >
            Connected
          </span>
        </div>

        <!-- Connected Info -->
        <div v-if="provider.connected && provider.external_user_name" class="mt-3 text-xs text-gray-500">
          Connected as <span class="font-medium text-gray-700">{{ provider.external_user_name }}</span>
        </div>

        <!-- Action Button -->
        <div class="mt-4">
          <button
            v-if="!provider.connected"
            @click="connectProvider(provider.id)"
            :disabled="connecting === provider.id"
            class="w-full px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <div v-if="connecting === provider.id" class="w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></div>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            {{ connecting === provider.id ? 'Connecting...' : 'Connect' }}
          </button>
          <button
            v-else
            @click="confirmDisconnect(provider)"
            :disabled="disconnecting === provider.id"
            class="w-full px-4 py-2 text-sm font-medium rounded-lg border border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <div v-if="disconnecting === provider.id" class="w-4 h-4 border-2 border-red-400 border-t-transparent rounded-full animate-spin"></div>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
            {{ disconnecting === provider.id ? 'Disconnecting...' : 'Disconnect' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Disconnect Confirmation Modal -->
    <Teleport to="body">
      <div v-if="showDisconnectModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm" @click="showDisconnectModal = false"></div>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm relative z-10 border border-gray-100 p-6">
          <div class="text-center">
            <div class="w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">Disconnect {{ disconnectTarget?.name }}?</h3>
            <p class="text-sm text-gray-500 mb-6">You won't be able to share videos to {{ disconnectTarget?.name }} until you reconnect.</p>
            <div class="flex gap-3">
              <button @click="showDisconnectModal = false" class="flex-1 px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all">
                Cancel
              </button>
              <button @click="executeDisconnect" class="flex-1 px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-all">
                Disconnect
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import integrationService from '@/services/integrationService'
import { useToast } from '@/services/toastService'

export default {
  name: 'IntegrationsView',
  setup() {
    const route = useRoute()
    const toast = useToast()
    const providers = ref([])
    const loading = ref(true)
    const connecting = ref(null)
    const disconnecting = ref(null)
    const showDisconnectModal = ref(false)
    const disconnectTarget = ref(null)
    const alertMessage = ref('')
    const alertType = ref('success')

    const fetchProviders = async () => {
      try {
        loading.value = true
        const data = await integrationService.getAvailableProviders()
        if (data) {
          providers.value = data
        }
      } catch (error) {
        toast.error('Failed to load integrations')
      } finally {
        loading.value = false
      }
    }

    const connectProvider = async (providerId) => {
      try {
        connecting.value = providerId
        const url = await integrationService.connect(providerId)
        if (url) {
          window.location.href = url
        }
      } catch (error) {
        toast.error(error.message || 'Failed to connect')
        connecting.value = null
      }
    }

    const confirmDisconnect = (provider) => {
      disconnectTarget.value = provider
      showDisconnectModal.value = true
    }

    const executeDisconnect = async () => {
      if (!disconnectTarget.value) return
      const providerId = disconnectTarget.value.id
      showDisconnectModal.value = false

      try {
        disconnecting.value = providerId
        await integrationService.disconnect(providerId)
        toast.success(`${disconnectTarget.value.name} disconnected`)
        await fetchProviders()
      } catch (error) {
        toast.error(error.message || 'Failed to disconnect')
      } finally {
        disconnecting.value = null
        disconnectTarget.value = null
      }
    }

    const getProviderBgColor = (id) => {
      const colors = {
        jira: 'bg-blue-100',
      }
      return colors[id] || 'bg-gray-100'
    }

    const getProviderIconColor = (id) => {
      const colors = {
        jira: 'text-blue-600',
      }
      return colors[id] || 'text-gray-600'
    }

    onMounted(() => {
      // Check for callback query params
      const connected = route.query.connected
      const error = route.query.error

      if (connected) {
        alertMessage.value = `Successfully connected ${connected}!`
        alertType.value = 'success'
      } else if (error) {
        alertMessage.value = `Connection failed: ${error}`
        alertType.value = 'error'
      }

      fetchProviders()
    })

    return {
      providers,
      loading,
      connecting,
      disconnecting,
      showDisconnectModal,
      disconnectTarget,
      alertMessage,
      alertType,
      connectProvider,
      confirmDisconnect,
      executeDisconnect,
      getProviderBgColor,
      getProviderIconColor,
    }
  }
}
</script>
