<template>
  <SBModal
    v-model="showModal"
    size="2xl"
    :closable="!isUploading"
    :close-on-backdrop="!isUploading"
    padding="none"
    @close="handleClose"
  >
    <template #header>
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Upload videos</h3>
        <p v-if="quotaLine" class="mt-0.5 text-xs text-gray-500">{{ quotaLine }}</p>
      </div>
    </template>

    <div class="px-6 py-5">
      <!-- Dropzone (hidden once any file is queued) -->
      <div
        v-if="files.length === 0"
        class="relative flex flex-col items-center justify-center gap-3 px-6 py-12 border-2 border-dashed rounded-xl transition-colors cursor-pointer"
        :class="dragActive ? 'border-orange-400 bg-orange-50' : 'border-gray-300 hover:border-gray-400 bg-gray-50/40'"
        @click="pickFiles"
        @dragenter.prevent="dragActive = true"
        @dragover.prevent="dragActive = true"
        @dragleave.prevent="dragActive = false"
        @drop.prevent="onDrop"
      >
        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 text-orange-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
          </svg>
        </div>
        <div class="text-center">
          <p class="text-sm font-medium text-gray-800">
            Drop video files here or
            <span class="text-orange-600 underline">browse</span>
          </p>
          <p class="mt-1 text-xs text-gray-500">MP4, WebM, or MOV · select multiple at once</p>
        </div>
      </div>

      <!-- File list -->
      <div v-else class="space-y-2">
        <div
          v-for="(item, idx) in files"
          :key="item.id"
          class="flex items-center gap-3 px-3 py-2.5 bg-white border border-gray-200 rounded-lg"
        >
          <!-- Status icon -->
          <div class="flex-shrink-0 w-8 h-8 rounded-md flex items-center justify-center"
               :class="statusBg(item.status)">
            <svg v-if="item.status === 'done'" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else-if="item.status === 'error' || item.status === 'rejected'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <svg v-else-if="item.status === 'uploading' || item.status === 'probing'" class="w-4 h-4 text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <svg v-else class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-baseline gap-2">
              <p class="text-sm font-medium text-gray-800 truncate">{{ item.file.name }}</p>
              <span class="text-xs text-gray-400 flex-shrink-0">{{ formatBytes(item.file.size) }}</span>
              <span v-if="item.duration > 0" class="text-xs text-gray-400 flex-shrink-0">· {{ formatDuration(item.duration) }}</span>
            </div>

            <!-- Progress bar -->
            <div v-if="item.status === 'uploading' || item.status === 'done'" class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div
                class="h-full transition-all duration-200 ease-out"
                :class="item.status === 'done' ? 'bg-green-500' : 'bg-orange-500'"
                :style="{ width: item.progress + '%' }"
              ></div>
            </div>

            <!-- Status text -->
            <p v-if="item.status === 'probing'" class="mt-1 text-[11px] text-gray-500">
              Reading duration…
            </p>
            <p v-else-if="item.status === 'uploading'" class="mt-1 text-[11px] text-gray-500">
              {{ item.progress }}%
            </p>
            <p v-else-if="item.status === 'done'" class="mt-1 text-[11px] text-green-600">
              Uploaded
            </p>
            <p v-else-if="item.status === 'error' || item.status === 'rejected'" class="mt-1 text-[11px] text-red-600 break-words" :title="item.error">
              {{ item.error || 'Upload failed' }}
            </p>
            <p v-else-if="item.status === 'skipped'" class="mt-1 text-[11px] text-amber-600">
              Skipped · {{ item.error || 'quota reached' }}
            </p>
          </div>

          <button
            v-if="['queued', 'error', 'skipped', 'rejected'].includes(item.status)"
            @click="removeFile(idx)"
            class="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors"
            title="Remove"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Add more files -->
        <button
          v-if="!isUploading && !allFinished"
          @click="pickFiles"
          class="w-full flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-orange-600 border border-dashed border-gray-300 hover:border-orange-300 rounded-lg transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Add more files
        </button>
      </div>

      <input
        ref="fileInput"
        type="file"
        accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov"
        multiple
        class="hidden"
        @change="onFilesPicked"
      />

      <!-- Quota warning -->
      <div v-if="quotaWarning" class="mt-3 flex items-start gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <p class="text-xs text-amber-800">{{ quotaWarning }}</p>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-between gap-3">
        <div class="text-xs text-gray-500">
          <template v-if="isUploading">
            Uploading {{ doneCount + 1 }} of {{ uploadableCount }}…
          </template>
          <template v-else-if="isProbing">
            Reading video metadata…
          </template>
          <template v-else-if="allFinished && files.length > 0">
            {{ doneCount }} uploaded<span v-if="errorCount"> · {{ errorCount }} failed</span><span v-if="skippedCount"> · {{ skippedCount }} skipped</span>
          </template>
          <template v-else-if="files.length > 0">
            {{ uploadableCount }} ready to upload
          </template>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="handleClose"
            :disabled="isUploading"
            class="px-3.5 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ allFinished ? 'Close' : 'Cancel' }}
          </button>
          <button
            v-if="!allFinished"
            @click="startUpload"
            :disabled="!canStart"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-lg shadow-sm shadow-orange-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
          >
            <svg v-if="isUploading || isProbing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ uploadButtonLabel }}
          </button>
        </div>
      </div>
    </template>
  </SBModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import SBModal from './SBModal.vue'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

const props = defineProps({
  show: Boolean,
  remainingQuota: { type: Number, default: null }, // null = unlimited
})

const emit = defineEmits(['close', 'update:show', 'uploaded', 'quota-exceeded'])

const showModal = computed({
  get: () => props.show,
  set: (v) => {
    if (!v) emit('close')
    emit('update:show', v)
  },
})

const fileInput = ref(null)
const dragActive = ref(false)
const files = ref([])
let nextId = 0

const isUploading = computed(() => files.value.some(f => f.status === 'uploading'))
const isProbing = computed(() => files.value.some(f => f.status === 'probing'))
const uploadableCount = computed(() => files.value.filter(f => f.status === 'queued' || f.status === 'uploading').length)
const doneCount = computed(() => files.value.filter(f => f.status === 'done').length)
const errorCount = computed(() => files.value.filter(f => ['error', 'rejected'].includes(f.status)).length)
const skippedCount = computed(() => files.value.filter(f => f.status === 'skipped').length)
const allFinished = computed(() => files.value.length > 0 && files.value.every(f => ['done', 'error', 'skipped', 'rejected'].includes(f.status)))
const canStart = computed(() => !isUploading.value && !isProbing.value && uploadableCount.value > 0)
const uploadButtonLabel = computed(() => {
  if (isProbing.value) return 'Reading metadata…'
  if (isUploading.value) return 'Uploading…'
  return uploadableCount.value > 1 ? `Upload ${uploadableCount.value} videos` : 'Upload'
})

const quotaLine = computed(() => {
  if (props.remainingQuota === null || props.remainingQuota === undefined) return 'Unlimited uploads on your plan'
  const r = props.remainingQuota - doneCount.value
  return `Free plan · ${Math.max(0, r)} upload${r === 1 ? '' : 's'} remaining`
})

const quotaWarning = computed(() => {
  if (props.remainingQuota === null || props.remainingQuota === undefined) return ''
  const queued = files.value.filter(f => f.status === 'queued').length
  const remaining = props.remainingQuota - doneCount.value
  if (queued > remaining) {
    const overflow = queued - remaining
    return `Your free plan has ${remaining} upload${remaining === 1 ? '' : 's'} left. ${overflow} file${overflow === 1 ? '' : 's'} will be skipped — upgrade to Pro for unlimited uploads.`
  }
  return ''
})

const ALLOWED_MIMES = ['video/mp4', 'video/webm', 'video/quicktime']
const ALLOWED_EXT = ['.mp4', '.webm', '.mov']

function isAllowedFile(f) {
  if (ALLOWED_MIMES.includes(f.type)) return true
  const name = f.name.toLowerCase()
  return ALLOWED_EXT.some(ext => name.endsWith(ext))
}

function pickFiles() {
  fileInput.value?.click()
}

function onFilesPicked(e) {
  addFiles(Array.from(e.target.files || []))
  if (fileInput.value) fileInput.value.value = ''
}

function onDrop(e) {
  dragActive.value = false
  addFiles(Array.from(e.dataTransfer?.files || []))
}

function addFiles(incoming) {
  for (const f of incoming) {
    const item = {
      id: ++nextId,
      file: f,
      // probing | queued | uploading | done | error | skipped | rejected
      status: isAllowedFile(f) ? 'probing' : 'rejected',
      progress: 0,
      duration: 0,
      error: isAllowedFile(f) ? '' : 'Unsupported file type — MP4, WebM, or MOV only',
      xhr: null,
    }
    files.value.push(item)
    if (item.status === 'probing') {
      probeDuration(item)
    }
  }
}

/**
 * Read the video's duration via a hidden HTMLVideoElement so we can submit it
 * with the upload — the backend's videos.duration column is NOT NULL.
 */
function probeDuration(item) {
  const url = URL.createObjectURL(item.file)
  const video = document.createElement('video')
  video.preload = 'metadata'
  video.muted = true

  const cleanup = () => {
    URL.revokeObjectURL(url)
    video.removeAttribute('src')
    video.load?.()
  }

  const timeout = setTimeout(() => {
    cleanup()
    if (item.status === 'probing') {
      item.status = 'rejected'
      item.error = 'Could not read duration — file may be corrupted'
    }
  }, 15000)

  video.addEventListener('loadedmetadata', () => {
    clearTimeout(timeout)
    const d = Number.isFinite(video.duration) ? Math.max(1, Math.round(video.duration)) : 0
    cleanup()
    if (d <= 0) {
      item.status = 'rejected'
      item.error = 'Could not read duration — file may be corrupted'
      return
    }
    item.duration = d
    item.status = 'queued'
  })

  video.addEventListener('error', () => {
    clearTimeout(timeout)
    cleanup()
    item.status = 'rejected'
    item.error = 'Unreadable video file'
  })

  video.src = url
}

function removeFile(idx) {
  files.value.splice(idx, 1)
}

function statusBg(status) {
  switch (status) {
    case 'done': return 'bg-green-50'
    case 'error': return 'bg-red-50'
    case 'skipped': return 'bg-amber-50'
    case 'uploading': return 'bg-orange-50'
    default: return 'bg-gray-100'
  }
}

function formatBytes(bytes) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`
}

function formatDuration(seconds) {
  const s = Math.max(0, Math.round(seconds))
  const m = Math.floor(s / 60)
  const r = s % 60
  return `${m}:${r.toString().padStart(2, '0')}`
}

function extractErrorMessage(xhr) {
  let data = null
  try { data = JSON.parse(xhr.responseText) } catch (_) { /* not JSON */ }
  if (data) {
    if (data.errors && typeof data.errors === 'object') {
      const firstField = Object.keys(data.errors)[0]
      const firstMsg = firstField && Array.isArray(data.errors[firstField]) ? data.errors[firstField][0] : null
      if (firstMsg) return firstMsg
    }
    if (data.message) return data.message
    if (data.error) return data.error
  }
  if (xhr.status === 0) return 'Network error — check your connection'
  if (xhr.status >= 500) return `Server error (${xhr.status}) — please try again`
  return `Upload failed (${xhr.status})`
}

async function startUpload() {
  let remainingThisSession = props.remainingQuota

  for (const item of files.value) {
    if (item.status !== 'queued') continue

    if (remainingThisSession !== null && remainingThisSession !== undefined && remainingThisSession <= 0) {
      item.status = 'skipped'
      item.error = 'free plan limit reached'
      continue
    }

    try {
      await uploadOne(item)
      if (item.status === 'done' && remainingThisSession !== null && remainingThisSession !== undefined) {
        remainingThisSession -= 1
      }
      if (item.quotaExceeded) {
        emit('quota-exceeded')
        // Mark any remaining queued as skipped
        for (const rest of files.value) {
          if (rest.status === 'queued') {
            rest.status = 'skipped'
            rest.error = 'free plan limit reached'
          }
        }
        break
      }
    } catch (_) {
      // uploadOne already sets item.status/error
    }
  }

  if (doneCount.value > 0) {
    emit('uploaded', doneCount.value)
  }
}

function uploadOne(item) {
  return new Promise((resolve, reject) => {
    const title = item.file.name.replace(/\.[^/.]+$/, '') || `Upload ${new Date().toLocaleString()}`
    const formData = new FormData()
    formData.append('video', item.file)
    formData.append('title', title)
    if (item.duration > 0) {
      formData.append('duration', String(item.duration))
    }

    const xhr = new XMLHttpRequest()
    item.xhr = xhr
    item.status = 'uploading'
    item.progress = 0
    item.error = ''

    xhr.upload.addEventListener('progress', (e) => {
      if (e.lengthComputable) {
        item.progress = Math.min(99, Math.round((e.loaded / e.total) * 100))
      }
    })

    xhr.addEventListener('load', () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        item.progress = 100
        item.status = 'done'
        resolve()
        return
      }
      const msg = extractErrorMessage(xhr)
      try {
        const data = JSON.parse(xhr.responseText)
        if (data?.error === 'video_limit_reached') item.quotaExceeded = true
      } catch (_) { /* ignore */ }
      item.status = 'error'
      item.error = msg
      reject(new Error(msg))
    })

    xhr.addEventListener('error', () => {
      item.status = 'error'
      item.error = 'Network error — check your connection'
      reject(new Error(item.error))
    })

    xhr.addEventListener('abort', () => {
      item.status = 'error'
      item.error = 'Cancelled'
      reject(new Error('Cancelled'))
    })

    xhr.open('POST', `${API_BASE_URL}/api/videos`)
    const token = localStorage.getItem('auth_token')
    if (token) xhr.setRequestHeader('Authorization', `Bearer ${token}`)
    xhr.setRequestHeader('Accept', 'application/json')
    xhr.send(formData)
  })
}

function handleClose() {
  if (isUploading.value) return
  emit('close')
}

watch(() => props.show, (s) => {
  if (s) {
    files.value = []
    dragActive.value = false
  }
})
</script>
