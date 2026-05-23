<template>
  <div class="animate-fade-in">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-xl font-bold text-gray-900 tracking-tight">Settings</h1>
      <p class="text-gray-500 text-sm mt-1">Manage your branding and account</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex gap-6" aria-label="Settings tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="setActiveTab(tab.id)"
          class="py-3 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none"
          :class="
            activeTab === tab.id
              ? 'border-orange-500 text-orange-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          "
        >
          <span class="inline-flex items-center gap-2">
            <component :is="tab.iconComponent" />
            {{ tab.label }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-xl border border-gray-100 p-12 text-center">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-orange-50 mb-3">
        <div class="animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
      </div>
      <p class="text-sm text-gray-500">Loading settings...</p>
    </div>

    <!-- BRANDING TAB -->
    <div v-else-if="activeTab === 'branding'" class="space-y-6">
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

    <!-- ACCOUNT TAB -->
    <div v-else-if="activeTab === 'account'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
      <!-- LEFT: Profile Card -->
      <div class="bg-white rounded-xl border border-gray-100">
        <div class="p-5 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900">Profile</h3>
              <p class="text-xs text-gray-500">Your account details</p>
            </div>
          </div>
        </div>

        <!-- Avatar -->
        <div class="p-5 border-b border-gray-100 flex items-center gap-4">
          <div class="relative">
            <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center">
              <img
                v-if="avatarPreview"
                :src="avatarPreview"
                alt="Avatar"
                class="w-full h-full object-cover"
              />
              <span v-else class="text-lg font-semibold text-gray-500">
                {{ getInitials(profileForm.name || user?.name) }}
              </span>
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-gray-900">Profile Photo</div>
            <div class="text-xs text-gray-500 mt-0.5">JPG, PNG or GIF. Max 2MB.</div>
            <div class="mt-2 flex items-center gap-2">
              <button
                @click="$refs.avatarInput.click()"
                :disabled="savingProfile"
                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg disabled:opacity-50"
              >
                {{ avatarPreview ? 'Change' : 'Upload' }}
              </button>
              <button
                v-if="avatarPreview && !pendingAvatarFile"
                @click="removeAvatar"
                :disabled="savingProfile"
                class="px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-200 hover:bg-red-50 rounded-lg disabled:opacity-50"
              >
                Remove
              </button>
              <button
                v-if="pendingAvatarFile"
                @click="clearPendingAvatar"
                :disabled="savingProfile"
                class="text-xs text-gray-500 hover:text-gray-700 disabled:opacity-50"
              >
                Clear
              </button>
            </div>
            <input
              ref="avatarInput"
              type="file"
              accept="image/jpeg,image/jpg,image/png,image/gif"
              class="hidden"
              @change="onAvatarSelected"
            />
          </div>
        </div>

        <!-- Fields -->
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Name</label>
            <input
              v-model="profileForm.name"
              type="text"
              maxlength="255"
              :disabled="savingProfile"
              class="mt-1 w-full px-3 py-2 text-sm bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none disabled:opacity-50"
              placeholder="Your name"
            />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Email</label>
            <div class="text-sm text-gray-900 mt-1 break-all py-2">{{ user?.email || '—' }}</div>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Plan</label>
            <div class="text-sm text-gray-900 mt-1 py-2">
              <span
                class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold"
                :class="isPaid ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600'"
              >
                {{ isPaid ? 'Pro' : 'Free' }}
              </span>
            </div>
          </div>
          <div class="sm:col-span-2">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Member Since</label>
            <div class="text-sm text-gray-900 mt-1 py-2">{{ formatDate(user?.created_at) }}</div>
          </div>
        </div>

        <!-- Save / Cancel -->
        <div class="border-t border-gray-100 px-5 py-4 flex items-center justify-between">
          <p v-if="profileDirty" class="text-xs text-orange-600">You have unsaved changes.</p>
          <span v-else></span>
          <div class="flex items-center gap-2">
            <button
              v-if="profileDirty"
              @click="resetProfileForm"
              :disabled="savingProfile"
              class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              @click="saveProfile"
              :disabled="savingProfile || !profileDirty"
              class="px-5 py-2 text-xs font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <svg v-if="savingProfile" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              {{ savingProfile ? 'Saving…' : 'Save Profile' }}
            </button>
          </div>
        </div>
      </div>

      <!-- RIGHT: Export cards stacked -->
      <div class="space-y-6">
        <!-- Recordings -->
        <div class="bg-white rounded-xl border border-gray-100">
          <div class="p-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-gray-900">Recordings</h3>
                <p class="text-xs text-gray-500">Download all your recording files</p>
              </div>
            </div>
          </div>

          <div class="p-5 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
              <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <div class="min-w-0">
                <div class="text-sm font-medium text-gray-900">
                  {{ exportLoading ? 'Loading…' : `${videoCount} ${videoCount === 1 ? 'recording' : 'recordings'}` }}
                </div>
                <div v-if="lastRecordingsExportAt" class="text-xs text-gray-500">
                  Last exported {{ formatRelative(lastRecordingsExportAt) }}
                </div>
                <div v-else class="text-xs text-gray-500">Packaged as a single .zip download</div>
              </div>
            </div>
            <button
              @click="exportRecordingsZip"
              :disabled="exportingRecordings || videoCount === 0"
              class="px-4 py-2 text-xs font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 flex-shrink-0"
            >
              <svg v-if="exportingRecordings" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              {{ exportingRecordings ? 'Preparing…' : 'Export as Zip' }}
            </button>
          </div>
        </div>

        <!-- Metadata / Transcriptions -->
        <div class="bg-white rounded-xl border border-gray-100">
          <div class="p-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-gray-900">Metadata</h3>
                <p class="text-xs text-gray-500">Titles, share links, transcriptions and summaries</p>
              </div>
            </div>
          </div>

          <div class="p-5 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
              <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              <div class="min-w-0">
                <div class="text-sm font-medium text-gray-900">JSON export</div>
                <div v-if="lastMetadataExportAt" class="text-xs text-gray-500">
                  Last exported {{ formatRelative(lastMetadataExportAt) }}
                </div>
                <div v-else class="text-xs text-gray-500">Machine-readable, includes transcripts</div>
              </div>
            </div>
            <button
              @click="exportMetadataJson"
              :disabled="exportingMetadata || videoCount === 0"
              class="px-4 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 flex-shrink-0"
            >
              <svg v-if="exportingMetadata" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              {{ exportingMetadata ? 'Preparing…' : 'Export Metadata' }}
            </button>
          </div>
        </div>

        <p v-if="videoCount === 0 && !exportLoading" class="text-xs text-gray-500 text-center">
          You don't have any recordings yet.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, h, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import settingsService from '@/services/settingsService'
import videoService from '@/services/videoService'
import toast from '@/services/toastService'
import { useAuth } from '@/stores/auth'
import { useBranding } from '@/composables/useBranding'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'
const LAST_RECORDINGS_EXPORT_KEY = 'openkap_last_recordings_export_at'
const LAST_METADATA_EXPORT_KEY = 'openkap_last_metadata_export_at'

const route = useRoute()
const router = useRouter()
const auth = useAuth()
const branding = useBranding()

const loading = ref(true)
const savingBranding = ref(false)
const uploadingLogo = ref(false)
const logoPreview = ref(null)
const exportLoading = ref(false)
const exportingRecordings = ref(false)
const exportingMetadata = ref(false)
const videoCount = ref(0)
const lastRecordingsExportAt = ref(localStorage.getItem(LAST_RECORDINGS_EXPORT_KEY) || null)
const lastMetadataExportAt = ref(localStorage.getItem(LAST_METADATA_EXPORT_KEY) || null)

// Profile editing
const profileForm = ref({ name: '' })
const savingProfile = ref(false)
const pendingAvatarFile = ref(null)
const avatarPreview = ref(null)
const initialProfile = ref({ name: '', avatar_url: null })

const profileDirty = computed(() => {
  return (
    profileForm.value.name !== initialProfile.value.name ||
    pendingAvatarFile.value !== null ||
    avatarPreview.value !== initialProfile.value.avatar_url
  )
})

const user = computed(() => auth.user.value)
const isPaid = computed(() => auth.subscription.value?.is_active || false)

const presetColors = ['#F97316', '#EF4444', '#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EC4899', '#1F2937']

const settings = ref({
  organization_logo: '',
  brand_color: '#F97316'
})

// ---- Tabs ----
const tabs = [
  {
    id: 'branding',
    label: 'Branding',
    iconComponent: () =>
      h(
        'svg',
        { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' },
        [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            d: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
          }),
        ],
      ),
  },
  {
    id: 'account',
    label: 'Account',
    iconComponent: () =>
      h(
        'svg',
        { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' },
        [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
          }),
        ],
      ),
  },
]

const activeTab = ref(route.query.tab === 'account' ? 'account' : 'branding')

function setActiveTab(id) {
  activeTab.value = id
  router.replace({ query: { ...route.query, tab: id } })
}

// Lazy-load the video count the first time Account tab is opened
watch(activeTab, (val) => {
  if (val === 'account' && videoCount.value === 0 && !exportLoading.value) {
    loadVideoCount()
  }
})

// ---- Formatting helpers ----
function formatDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })
  } catch {
    return '—'
  }
}

function formatRelative(iso) {
  if (!iso) return ''
  const date = new Date(iso)
  const diffMs = Date.now() - date.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  if (diffMin < 1) return 'just now'
  if (diffMin < 60) return `${diffMin} min ago`
  const diffH = Math.floor(diffMin / 60)
  if (diffH < 24) return `${diffH} hour${diffH === 1 ? '' : 's'} ago`
  const diffD = Math.floor(diffH / 24)
  if (diffD < 7) return `${diffD} day${diffD === 1 ? '' : 's'} ago`
  return formatDate(iso)
}

function getInitials(name) {
  if (!name) return '?'
  return name
    .split(' ')
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

// ---- Profile editing ----
function syncProfileFromUser() {
  const u = user.value
  profileForm.value.name = u?.name || ''
  initialProfile.value = { name: u?.name || '', avatar_url: u?.avatar_url || null }
  avatarPreview.value = u?.avatar_url || null
  pendingAvatarFile.value = null
}

function onAvatarSelected(event) {
  const file = event.target.files[0]
  if (!file) return
  if (file.size > 2 * 1024 * 1024) {
    toast.error('Avatar must be under 2MB')
    event.target.value = ''
    return
  }
  pendingAvatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
  event.target.value = ''
}

function clearPendingAvatar() {
  pendingAvatarFile.value = null
  avatarPreview.value = initialProfile.value.avatar_url
}

function resetProfileForm() {
  syncProfileFromUser()
}

async function removeAvatar() {
  if (savingProfile.value) return
  savingProfile.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const response = await fetch(`${API_BASE_URL}/api/profile/avatar`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
    })
    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      throw new Error(err.message || 'Failed to remove avatar')
    }
    await auth.fetchUser()
    syncProfileFromUser()
    toast.success('Avatar removed')
  } catch (e) {
    console.error('Remove avatar failed:', e)
    toast.error(e.message || 'Failed to remove avatar')
  } finally {
    savingProfile.value = false
  }
}

async function saveProfile() {
  if (savingProfile.value || !profileDirty.value) return
  savingProfile.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const formData = new FormData()
    if (profileForm.value.name !== initialProfile.value.name) {
      formData.append('name', profileForm.value.name)
    }
    if (pendingAvatarFile.value) {
      formData.append('avatar', pendingAvatarFile.value)
    }

    const response = await fetch(`${API_BASE_URL}/api/profile`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: formData,
    })

    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      const msg =
        err?.errors && Object.values(err.errors)[0]?.[0]
          ? Object.values(err.errors)[0][0]
          : err?.message || 'Failed to save profile'
      throw new Error(msg)
    }

    await auth.fetchUser()
    syncProfileFromUser()
    toast.success('Profile updated')
  } catch (e) {
    console.error('Save profile failed:', e)
    toast.error(e.message || 'Failed to save profile')
  } finally {
    savingProfile.value = false
  }
}

// ---- Branding ----
onMounted(async () => {
  syncProfileFromUser()

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

  if (activeTab.value === 'account') {
    loadVideoCount()
  }
})

watch(user, () => {
  // Keep form in sync if user is refreshed externally (and no edit in progress)
  if (!profileDirty.value) syncProfileFromUser()
})

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
      brand_color: settings.value.brand_color,
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

// ---- Account / Export ----
async function loadVideoCount() {
  exportLoading.value = true
  try {
    const videos = await videoService.getVideos()
    videoCount.value = Array.isArray(videos) ? videos.length : 0
  } catch (error) {
    console.error('Failed to load recording count:', error)
    toast.error('Could not load your recordings')
  } finally {
    exportLoading.value = false
  }
}

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

function getAuthToken() {
  return localStorage.getItem('auth_token')
}

async function fetchAndDownload(url, filename, errorMessage) {
  const token = getAuthToken()
  const response = await fetch(url, {
    method: 'GET',
    headers: {
      Accept: '*/*',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
  })
  if (!response.ok) {
    throw new Error(errorMessage || `Download failed: ${response.statusText}`)
  }
  const blob = await response.blob()
  downloadBlob(blob, filename)
}

async function exportRecordingsZip() {
  if (exportingRecordings.value) return
  if (videoCount.value === 0) {
    toast.error('No recordings to export')
    return
  }
  exportingRecordings.value = true
  try {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19)
    await fetchAndDownload(
      `${API_BASE_URL}/api/account/export/recordings`,
      `openkap-recordings-${timestamp}.zip`,
      'Could not prepare recordings archive',
    )
    const now = new Date().toISOString()
    localStorage.setItem(LAST_RECORDINGS_EXPORT_KEY, now)
    lastRecordingsExportAt.value = now
    toast.success('Recordings exported')
  } catch (error) {
    console.error('Failed to export recordings:', error)
    toast.error(error.message || 'Export failed')
  } finally {
    exportingRecordings.value = false
  }
}

async function exportMetadataJson() {
  if (exportingMetadata.value) return
  if (videoCount.value === 0) {
    toast.error('No recordings to export')
    return
  }
  exportingMetadata.value = true
  try {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19)
    await fetchAndDownload(
      `${API_BASE_URL}/api/account/export/metadata`,
      `openkap-metadata-${timestamp}.json`,
      'Could not prepare metadata export',
    )
    const now = new Date().toISOString()
    localStorage.setItem(LAST_METADATA_EXPORT_KEY, now)
    lastMetadataExportAt.value = now
    toast.success('Metadata exported')
  } catch (error) {
    console.error('Failed to export metadata:', error)
    toast.error(error.message || 'Export failed')
  } finally {
    exportingMetadata.value = false
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
