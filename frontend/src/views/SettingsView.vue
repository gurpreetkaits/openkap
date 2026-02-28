<template>
  <div class="animate-fade-in">
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-xl font-bold text-gray-900 tracking-tight">Settings</h1>
      <p class="text-gray-500 text-sm mt-1">Manage your recording and playback preferences</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-xl border border-gray-100 p-12 text-center">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-orange-50 mb-3">
        <div class="animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
      </div>
      <p class="text-sm text-gray-500">Loading settings...</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Branding Card (Paid only) -->
      <div class="bg-white rounded-xl border border-gray-100" :class="{ 'opacity-60 pointer-events-none': !isPaid }">
        <!-- Header -->
        <div class="p-5 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-900">Custom Branding</h3>
                <span v-if="!isPaid" class="text-[10px] font-medium text-orange-700 bg-orange-50 border border-orange-200/60 px-1.5 py-0.5 rounded-full">Pro</span>
              </div>
              <p class="text-xs text-gray-500">Add your organization logo and brand color to shared videos</p>
            </div>
          </div>
        </div>

        <!-- Organization Logo -->
        <div class="p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h4 class="text-sm font-medium text-gray-900">Organization Logo</h4>
              <p class="text-xs text-gray-500 mt-0.5">Displayed on shared video pages. Max 2MB, PNG/JPG/SVG/WebP.</p>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <!-- Logo Preview -->
            <div
              class="w-16 h-16 rounded-xl border-2 border-dashed flex items-center justify-center flex-shrink-0 overflow-hidden"
              :class="logoPreview ? 'border-gray-200 bg-white' : 'border-gray-300 bg-gray-50'"
            >
              <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="w-full h-full object-contain p-1" />
              <svg v-else class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
              <button
                @click="$refs.logoInput.click()"
                :disabled="uploadingLogo"
                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg transition-colors disabled:opacity-50 flex items-center gap-1.5"
              >
                <svg v-if="uploadingLogo" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ uploadingLogo ? 'Uploading...' : (logoPreview ? 'Change' : 'Upload') }}
              </button>
              <button
                v-if="logoPreview"
                @click="removeLogo"
                :disabled="uploadingLogo"
                class="px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-200 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50"
              >
                Remove
              </button>
            </div>

            <input
              ref="logoInput"
              type="file"
              accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
              class="hidden"
              @change="onLogoSelected"
            />
          </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-gray-100"></div>

        <!-- Brand Color -->
        <div class="p-5">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-medium text-gray-900">Brand Color</h4>
              <p class="text-xs text-gray-500 mt-0.5">Used as accent color on shared video pages</p>
            </div>

            <div class="flex items-center gap-3">
              <!-- Color swatches -->
              <div class="flex items-center gap-1.5">
                <button
                  v-for="color in presetColors"
                  :key="color"
                  @click="settings.brand_color = color"
                  class="w-6 h-6 rounded-full border-2 transition-all hover:scale-110"
                  :class="settings.brand_color === color ? 'border-gray-900 ring-2 ring-offset-1 ring-gray-300' : 'border-white shadow-sm'"
                  :style="{ backgroundColor: color }"
                  :title="color"
                />
              </div>

              <!-- Custom picker -->
              <div class="relative">
                <button
                  @click="$refs.colorInput.click()"
                  class="w-8 h-8 rounded-lg border border-gray-200 overflow-hidden cursor-pointer hover:ring-2 hover:ring-gray-300 hover:ring-offset-1 transition-all"
                  :style="{ backgroundColor: settings.brand_color }"
                  title="Pick custom color"
                />
                <input
                  ref="colorInput"
                  type="color"
                  v-model="settings.brand_color"
                  class="absolute inset-0 opacity-0 w-0 h-0"
                />
              </div>

              <!-- Hex input -->
              <input
                v-model="settings.brand_color"
                type="text"
                maxlength="7"
                class="w-20 px-2 py-1.5 text-xs font-mono text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none"
                placeholder="#F97316"
              />
            </div>
          </div>
        </div>

        <!-- Branding Save -->
        <div class="border-t border-gray-100 px-5 py-4 flex items-center justify-between">
          <p v-if="!isPaid" class="text-xs text-gray-500">
            <router-link to="/subscription" class="text-orange-600 hover:text-orange-700 font-medium">Upgrade to Pro</router-link> to unlock custom branding.
          </p>
          <span v-else></span>
          <button
            @click="saveBranding"
            :disabled="savingBranding || !isPaid"
            class="px-5 py-2 text-xs font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <svg v-if="savingBranding" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ savingBranding ? 'Saving...' : 'Save Branding' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import settingsService from '@/services/settingsService'
import toast from '@/services/toastService'
import { useAuth } from '@/stores/auth'
import { useBranding } from '@/composables/useBranding'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

const auth = useAuth()
const branding = useBranding()
const loading = ref(true)
const saving = ref(false)
const savingBranding = ref(false)
const uploadingLogo = ref(false)
const logoPreview = ref(null)

const isPaid = computed(() => auth.subscription.value?.is_active || false)

const presetColors = ['#F97316', '#EF4444', '#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EC4899', '#1F2937']

const settings = ref({
  // bunny_encoding_enabled: true, // Bunny disabled - encoding costs too high
  organization_logo: '',
  brand_color: '#F97316'
})

onMounted(async () => {
  try {
    const userSettings = await settingsService.getUserSettings()
    if (userSettings) {
      settings.value = { ...settings.value, ...userSettings }
      if (settings.value.organization_logo) {
        logoPreview.value = `${API_BASE_URL}/storage/${settings.value.organization_logo}`
      }
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
    await settingsService.updateUserSettings({
      // bunny_encoding_enabled: settings.value.bunny_encoding_enabled // Bunny disabled
    })
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
      if (settings.value.organization_logo) {
        const url = `${API_BASE_URL}/storage/${settings.value.organization_logo}`
        logoPreview.value = url
        branding.setLogoUrl(url)
      } else {
        logoPreview.value = null
        branding.setLogoUrl(null)
      }
      branding.setBrandColor(settings.value.brand_color || null)
    }
    toast.success('Settings reset to defaults')
  } catch (error) {
    console.error('Failed to reset settings:', error)
    toast.error('Failed to reset settings')
  } finally {
    saving.value = false
  }
}

const onLogoSelected = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    toast.error('Logo must be under 2MB')
    return
  }

  uploadingLogo.value = true
  try {
    const result = await settingsService.uploadLogo(file)
    if (result) {
      settings.value.organization_logo = result.path
      logoPreview.value = result.url
      branding.setLogoUrl(result.url)
      toast.success('Logo uploaded successfully')
    }
  } catch (error) {
    console.error('Failed to upload logo:', error)
    toast.error(error.message || 'Failed to upload logo')
  } finally {
    uploadingLogo.value = false
    event.target.value = ''
  }
}

const removeLogo = async () => {
  uploadingLogo.value = true
  try {
    await settingsService.removeLogo()
    settings.value.organization_logo = ''
    logoPreview.value = null
    branding.setLogoUrl(null)
    toast.success('Logo removed')
  } catch (error) {
    console.error('Failed to remove logo:', error)
    toast.error('Failed to remove logo')
  } finally {
    uploadingLogo.value = false
  }
}

const saveBranding = async () => {
  savingBranding.value = true
  try {
    await settingsService.updateUserSettings({
      brand_color: settings.value.brand_color
    })
    branding.setBrandColor(settings.value.brand_color)
    toast.success('Branding saved successfully')
  } catch (error) {
    console.error('Failed to save branding:', error)
    toast.error(error.message || 'Failed to save branding')
  } finally {
    savingBranding.value = false
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
