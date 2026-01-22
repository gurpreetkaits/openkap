<template>
  <div class="animate-fade-in max-w-2xl mx-auto py-6 px-4">
    <!-- Loading State -->
    <div v-if="loading" class="card bg-base-100 border border-base-300 shadow-sm">
      <div class="card-body items-center text-center">
        <span class="loading loading-spinner loading-md text-primary"></span>
        <p class="text-sm text-base-content/60 mt-2">Loading settings...</p>
      </div>
    </div>

    <div v-else class="space-y-6">
      <!-- Page Header -->
      <div>
        <h1 class="text-2xl font-bold text-base-content">Settings</h1>
        <p class="text-sm text-base-content/60 mt-1">Manage your recording and playback preferences</p>
      </div>

      <!-- Auto-Zoom Settings Card -->
      <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
        <!-- Card Header with Toggle -->
        <div class="p-4 border-b border-base-200 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-base-content">Auto-Zoom Effects</h3>
              <p class="text-xs text-base-content/60">Automatically zoom to clicks during recording</p>
            </div>
          </div>

          <!-- Toggle Switch -->
          <input
            type="checkbox"
            class="toggle toggle-primary"
            v-model="settings.auto_zoom_enabled"
          />
        </div>

        <!-- Expandable Settings (shown when enabled) -->
        <transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 max-h-0"
          enter-to-class="opacity-100 max-h-96"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 max-h-96"
          leave-to-class="opacity-0 max-h-0"
        >
          <div v-if="settings.auto_zoom_enabled" class="overflow-hidden">
            <div class="p-4 pt-0 space-y-5">
              <!-- Zoom Level -->
              <div class="pt-4">
                <div class="flex items-center justify-between mb-3">
                  <div>
                    <p class="text-sm font-medium text-base-content">Zoom Level</p>
                    <p class="text-xs text-base-content/60">How much to zoom in on click events</p>
                  </div>
                  <span class="badge badge-primary">
                    {{ settings.default_zoom_level.toFixed(1) }}x
                  </span>
                </div>
                <input
                  type="range"
                  v-model.number="settings.default_zoom_level"
                  min="1.2"
                  max="4"
                  step="0.1"
                  class="range range-primary range-sm"
                />
                <div class="flex justify-between text-xs text-base-content/40 mt-1">
                  <span>Subtle (1.2x)</span>
                  <span>Extreme (4x)</span>
                </div>
              </div>

              <!-- Animation Speed -->
              <div>
                <div class="flex items-center justify-between mb-3">
                  <div>
                    <p class="text-sm font-medium text-base-content">Animation Speed</p>
                    <p class="text-xs text-base-content/60">Duration of zoom in/out transitions</p>
                  </div>
                  <span class="badge badge-primary">
                    {{ animationSpeedLabel }}
                  </span>
                </div>
                <input
                  type="range"
                  v-model.number="settings.default_zoom_duration_ms"
                  min="100"
                  max="2000"
                  step="100"
                  class="range range-primary range-sm"
                />
                <div class="flex justify-between text-xs text-base-content/40 mt-1">
                  <span>Fast (100ms)</span>
                  <span>Slow (2000ms)</span>
                </div>
              </div>
            </div>
          </div>
        </transition>

        <!-- Disabled State Message -->
        <div v-if="!settings.auto_zoom_enabled" class="px-4 pb-4">
          <div class="bg-base-200 rounded-lg p-3 text-center">
            <p class="text-xs text-base-content/60">Enable auto-zoom to configure zoom settings</p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-between">
        <button
          @click="resetSettings"
          :disabled="saving"
          class="btn btn-ghost"
        >
          Reset to Defaults
        </button>

        <button
          @click="saveSettings"
          :disabled="saving"
          class="btn btn-primary"
        >
          <span v-if="saving" class="loading loading-spinner loading-sm"></span>
          {{ saving ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import settingsService from '@/services/settingsService'
import toast from '@/services/toastService'

const loading = ref(true)
const saving = ref(false)

const settings = ref({
  auto_zoom_enabled: true,
  default_zoom_level: 2.0,
  default_zoom_duration_ms: 500
})

const animationSpeedLabel = computed(() => {
  const ms = settings.value.default_zoom_duration_ms
  if (ms <= 200) return `${ms}ms (Very Fast)`
  if (ms <= 400) return `${ms}ms (Fast)`
  if (ms <= 700) return `${ms}ms (Normal)`
  if (ms <= 1200) return `${ms}ms (Slow)`
  return `${ms}ms (Very Slow)`
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
    console.log('Saving settings:', settings.value)
    const result = await settingsService.updateUserSettings(settings.value)
    console.log('Settings saved, result:', result)
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
