<template>
  <div class="animate-fade-in max-w-2xl mx-auto py-6 px-4">
    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
      <div class="animate-spin w-6 h-6 border-2 border-orange-500 border-t-transparent rounded-full mx-auto"></div>
      <p class="text-sm text-gray-500 mt-2">Loading settings...</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Page Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your recording and playback preferences</p>
      </div>

      <!-- Bunny Encoding Settings Card -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Card Header with Toggle -->
        <div class="p-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900">HD Encoding</h3>
              <p class="text-xs text-gray-500">Use Bunny CDN for HD video encoding and faster playback</p>
            </div>
          </div>

          <!-- Toggle Switch -->
          <button
            @click="settings.bunny_encoding_enabled = !settings.bunny_encoding_enabled"
            :class="[
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2',
              settings.bunny_encoding_enabled ? 'bg-orange-500' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                settings.bunny_encoding_enabled ? 'translate-x-5' : 'translate-x-0'
              ]"
            />
          </button>
        </div>

        <!-- Info message -->
        <div class="px-4 pb-4">
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500" v-if="settings.bunny_encoding_enabled">
              Videos will be encoded via Bunny CDN for HD quality and adaptive streaming.
            </p>
            <p class="text-xs text-gray-500" v-else>
              Videos will be stored and served locally without HD encoding.
            </p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-between">
        <button
          @click="resetSettings"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-50"
        >
          Reset to Defaults
        </button>

        <button
          @click="saveSettings"
          :disabled="saving"
          class="px-6 py-2 text-sm font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ saving ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import settingsService from '@/services/settingsService'
import toast from '@/services/toastService'

const loading = ref(true)
const saving = ref(false)

const settings = ref({
  bunny_encoding_enabled: true
})

onMounted(async () => {
  try {
    const userSettings = await settingsService.getUserSettings()
    if (userSettings) {
      settings.value = { ...settings.value, ...userSettings }
    }
  } catch (error) {
    console.error('Failed to fetch settings:', error)
    toast.error('Failed to load settings')
  } finally {
    loading.value = false
  }
})

const saveSettings = async () => {
  saving.value = true

  try {
    const result = await settingsService.updateUserSettings(settings.value)
    toast.success('Settings saved successfully')
  } catch (error) {
    console.error('Failed to save settings:', error)
    toast.error(error.message || 'Failed to save settings')
  } finally {
    saving.value = false
  }
}

const resetSettings = async () => {
  saving.value = true

  try {
    const defaultSettings = await settingsService.resetUserSettings()
    if (defaultSettings) {
      settings.value = { ...settings.value, ...defaultSettings }
    }
    toast.success('Settings reset to defaults')
  } catch (error) {
    console.error('Failed to reset settings:', error)
    toast.error('Failed to reset settings')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
