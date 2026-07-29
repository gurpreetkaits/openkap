<template>
  <div class="min-h-full bg-gradient-to-br from-orange-50/80 via-white to-rose-50/80">
    <!-- Recording Status Bar (compact, floats during recording) -->
    <div v-if="isRecording" class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-red-100 px-4 py-2.5">
      <div class="max-w-5xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="flex items-center gap-2 bg-red-50 text-red-700 px-3 py-1.5 rounded-full">
            <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
            <span class="text-xs font-semibold tabular-nums">{{ formatTime(recordingTime) }}</span>
          </div>
          <span v-if="isPaused" class="text-xs font-medium text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">Paused</span>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-400">
          <span v-if="uploadedBytes > 0">{{ formatBytes(uploadedBytes) }} uploaded</span>
        </div>
      </div>
    </div>

    <main class="max-w-2xl mx-auto px-4 py-12 md:py-20">
      <!-- Recording Setup -->
      <div v-if="!isRecording && !hasRecorded">
        <!-- Header -->
        <div class="text-center mb-10">
          <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-2xl mb-5 shadow-lg shadow-orange-200">
            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="6"/></svg>
          </div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Record your screen</h1>
          <p class="text-gray-500 max-w-md mx-auto">Capture your screen, camera, and microphone in crisp quality. Share instantly with a link.</p>
        </div>

        <!-- Source Selection -->
        <div class="mb-8">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 text-center">Capture Source</p>
          <div class="grid grid-cols-3 gap-3">
            <button
              v-for="src in sources"
              :key="src.key"
              @click="selectedSource = src.key"
              class="group flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-150"
              :class="selectedSource === src.key
                ? 'border-orange-400 bg-orange-50 shadow-sm'
                : 'border-gray-100 bg-white hover:border-gray-200 hover:bg-gray-50'"
            >
              <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors"
                :class="selectedSource === src.key ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200 group-hover:text-gray-600'">
                <component :is="src.icon" class="w-5 h-5" />
              </div>
              <span class="text-xs font-medium" :class="selectedSource === src.key ? 'text-orange-700' : 'text-gray-600'">{{ src.label }}</span>
            </button>
          </div>
        </div>

        <!-- Options -->
        <div class="mb-8 space-y-2">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 text-center">Options</p>
          <div class="bg-white rounded-xl border border-gray-100 divide-y divide-gray-50 shadow-sm">
            <label class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50/50 transition-colors rounded-t-xl">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" :class="recordingOptions.microphone ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-400'">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                </div>
                <div class="text-left"><div class="text-sm font-medium text-gray-900">Microphone</div><div class="text-xs text-gray-400">Record your voice</div></div>
              </div>
              <input v-model="recordingOptions.microphone" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-checked:bg-orange-500 rounded-full relative transition-colors after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:w-5 after:h-5 after:transition-transform peer-checked:after:translate-x-5"></div>
            </label>
            <label class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50/50 transition-colors rounded-b-xl">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" :class="recordingOptions.camera ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-400'">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </div>
                <div class="text-left"><div class="text-sm font-medium text-gray-900">Camera</div><div class="text-xs text-gray-400">Picture-in-picture overlay</div></div>
              </div>
              <input v-model="recordingOptions.camera" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-checked:bg-orange-500 rounded-full relative transition-colors after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:w-5 after:h-5 after:transition-transform peer-checked:after:translate-x-5"></div>
            </label>
          </div>
        </div>

        <!-- Record Button -->
        <div class="text-center">
          <button
            @click="startRecording"
            :disabled="!canRecord || isStartingRecording"
            class="group relative inline-flex items-center gap-3 px-12 py-4 text-lg font-semibold rounded-2xl text-white bg-gradient-to-r from-orange-500 to-rose-500 hover:from-orange-600 hover:to-rose-600 shadow-lg shadow-orange-200 hover:shadow-xl hover:shadow-orange-300 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:shadow-lg transform active:scale-[0.98]"
          >
            <svg v-if="!isStartingRecording" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="6"/></svg>
            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            {{ isStartingRecording ? 'Starting...' : 'Start Recording' }}
          </button>
          <p class="mt-4 text-xs text-gray-400">You'll choose what to share after clicking</p>
        </div>
      </div>

      <!-- Recording in Progress -->
      <div v-if="isRecording" class="text-center">
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-2">Recording</h2>
          <p class="text-gray-500">Your screen is being captured. Switch tabs — the recording continues.</p>
        </div>

        <div class="bg-gray-900 rounded-2xl overflow-hidden aspect-video max-w-3xl mx-auto mb-8 relative shadow-2xl">
          <video ref="previewVideo" autoplay muted class="w-full h-full object-cover"></video>
          <div class="absolute top-4 left-4 flex items-center gap-2 bg-black/60 backdrop-blur-sm text-white px-3 py-2 rounded-lg text-xs font-mono tabular-nums">
            <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
            {{ formatTime(recordingTime) }}
          </div>
          <!-- Upload progress overlay -->
          <div v-if="uploadedBytes > 0" class="absolute bottom-4 right-4 bg-black/60 backdrop-blur-sm text-white px-3 py-1.5 rounded-lg text-xs">
            {{ formatBytes(uploadedBytes) }} uploaded
          </div>
        </div>

        <div class="flex items-center justify-center gap-3">
          <button v-if="!isPaused" @click="pauseRecording"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.5 3.5A1.5 1.5 0 017 2h6a1.5 1.5 0 011.5 1.5v13a1.5 1.5 0 01-1.5 1.5H7A1.5 1.5 0 015.5 16.5v-13z"/></svg>
            Pause
          </button>
          <button v-if="isPaused" @click="resumeRecording"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-orange-500 hover:bg-orange-600 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/></svg>
            Resume
          </button>
          <button @click="stopRecording"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-red-500 hover:bg-red-600 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><rect x="4" y="4" width="12" height="12" rx="2"/></svg>
            Stop
          </button>
        </div>
      </div>

      <!-- Processing -->
      <div v-if="hasRecorded && isFinishing" class="text-center py-16">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-orange-100 to-orange-200 rounded-2xl mb-6 shadow-lg">
          <svg class="w-10 h-10 text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Saving your recording</h2>
        <p class="text-gray-500 mb-8">Finalizing and preparing your video...</p>
        <div class="w-64 mx-auto bg-gray-100 rounded-full h-1.5 overflow-hidden">
          <div class="h-full bg-gradient-to-r from-orange-400 to-orange-600 rounded-full transition-all duration-500" :style="{ width: uploadProgress + '%' }"></div>
        </div>
        <p class="mt-3 text-xs text-gray-400">{{ uploadProgress }}%</p>
      </div>
    </main>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/stores/auth'
import { buildApiUrl } from '@/config/api'
import toast from '@/services/toastService'
import settingsService from '@/services/settingsService'
import { useZoomTracking } from '@/composables/useZoomTracking'
import ZoomSettingsPanel from '@/components/Zoom/ZoomSettingsPanel.vue'

export default {
  name: 'RecordView',
  components: {
    ZoomSettingsPanel
  },
  setup() {
    const auth = useAuth()

    // Zoom tracking
    const zoomTracking = useZoomTracking()

    // Settings loading state
    const settingsLoaded = ref(false)

    // Recording state
    const isStartingRecording = ref(false)
    const isRecording = ref(false)
    const isPaused = ref(false)
    const hasRecorded = ref(false)
    const isFinishing = ref(false)
    const recordingTime = ref(0)

    // Upload state
    const sessionId = ref(null)
    const uploadedBytes = ref(0)
    const chunksUploaded = ref(0)
    const isUploading = ref(false)
    const uploadQueue = ref([])
    const uploadProgress = ref(0)

    // Recording options
    const recordingOptions = ref({
      screen: true,
      microphone: true,
      camera: false
    })

    const selectedSource = ref('screen')

    // Source options
    const sources = [
      {
        key: 'screen',
        label: 'Full Screen',
        icon: {
          template: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'
        }
      },
      {
        key: 'window',
        label: 'Window',
        icon: {
          template: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="16" rx="2"/><line x1="2" y1="7" x2="22" y2="7"/></svg>'
        }
      },
      {
        key: 'tab',
        label: 'Browser Tab',
        icon: {
          template: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 8h16M4 8a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2V10a2 2 0 00-2-2H4z"/><line x1="8" y1="4" x2="8" y2="6"/></svg>'
        }
      }
    ]

    // Media elements
    const previewVideo = ref(null)

    // MediaRecorder and streams
    let mediaRecorder = null
    let stream = null
    let recordingInterval = null
    let chunkIndex = 0

    const canRecord = computed(() => {
      return recordingOptions.value.screen && settingsLoaded.value
    })

    const formatTime = (seconds) => {
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    }

    const formatBytes = (bytes) => {
      if (bytes === 0) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
    }

    // Start upload session
    const startUploadSession = async () => {
      const timestamp = new Date().toLocaleString()
      const title = `Screen Recording ${timestamp}`

      const response = await fetch(buildApiUrl('/api/stream/start'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${auth.token.value}`
        },
        body: JSON.stringify({
          title,
          mime_type: 'video/webm'
        })
      })

      if (response.status === 401) {
        auth.clearAuth()
        localStorage.setItem('auth_redirect', '/record')
        window.location.href = import.meta.env.BASE_URL + 'login'
        return null
      }

      if (response.status === 403) {
        const errorData = await response.json().catch(() => ({}))
        if (errorData.error === 'video_limit_reached') {
          toast.error(errorData.message || 'You have reached your video limit. Please upgrade to continue.')
          return null
        }
      }

      if (!response.ok) {
        throw new Error('Failed to start upload session')
      }

      const data = await response.json()
      return data.session_id
    }

    // Upload a chunk
    const uploadChunk = async (chunk, index) => {
      if (!sessionId.value) return

      isUploading.value = true

      const formData = new FormData()
      formData.append('chunk', chunk, `chunk_${index}.webm`)
      formData.append('chunk_index', index)

      try {
        const response = await fetch(buildApiUrl(`/api/stream/${sessionId.value}/chunk`), {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${auth.token.value}`
          },
          body: formData
        })

        if (response.ok) {
          const data = await response.json()
          uploadedBytes.value = data.total_size
          chunksUploaded.value = data.chunks_received
          // Estimate progress (will be more accurate as recording continues)
          uploadProgress.value = Math.min(95, chunksUploaded.value * 5)
        }
      } catch (err) {
        console.error('Failed to upload chunk:', err)
        // Add to retry queue
        uploadQueue.value.push({ chunk, index })
      } finally {
        isUploading.value = uploadQueue.value.length > 0
      }
    }

    // Process upload queue
    const processUploadQueue = async () => {
      while (uploadQueue.value.length > 0) {
        const { chunk, index } = uploadQueue.value.shift()
        await uploadChunk(chunk, index)
      }
    }

    // Complete upload
    const completeUpload = async () => {
      if (!sessionId.value) return

      // Wait for any pending uploads
      await processUploadQueue()

      // Build request body with zoom settings and events
      const zoomSettings = zoomTracking.getZoomSettings()
      const requestBody = {
        duration: recordingTime.value,
        zoom_enabled: zoomSettings.zoom_enabled,
        zoom_level: zoomSettings.zoom_level,
        zoom_duration_ms: zoomSettings.zoom_duration_ms,
        zoom_events: zoomSettings.zoom_events
      }

      const response = await fetch(buildApiUrl(`/api/stream/${sessionId.value}/complete`), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${auth.token.value}`
        },
        body: JSON.stringify(requestBody)
      })

      if (!response.ok) {
        throw new Error('Failed to complete upload')
      }

      const data = await response.json()
      return data.video
    }

    // Complete upload with retry logic (3 attempts)
    const completeUploadWithRetry = async (maxRetries = 3) => {
      let lastError = null

      for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
          const video = await completeUpload()
          return video
        } catch (error) {
          console.error(`Upload completion attempt ${attempt} failed:`, error)
          lastError = error

          // Don't retry on 401 (auth error)
          if (error.message?.includes('401')) {
            throw error
          }

          // Wait before retrying (exponential backoff: 1s, 2s, 4s)
          if (attempt < maxRetries) {
            const delay = Math.pow(2, attempt - 1) * 1000
            await new Promise(resolve => setTimeout(resolve, delay))
          }
        }
      }

      // All retries failed - but recording is safe on server (will be auto-recovered)
      throw lastError
    }

    const startRecording = async () => {
      try {
        // Check subscription status first
        let subscription = null
        try {
          subscription = await auth.fetchSubscription()
        } catch (subscriptionError) {
          console.error('Error checking subscription:', subscriptionError)
          toast.error('Unable to verify your subscription status. Please try again.')
          return
        }

        if (subscription && !subscription.can_record) {
          toast.warning('You have reached your video limit. Please upgrade to continue recording.')
          return
        }

        isStartingRecording.value = true

        // Start upload session first
        sessionId.value = await startUploadSession()
        if (!sessionId.value) return

        // Get screen capture (up to 4K)
        const displayMediaOptions = {
          video: {
            width: { ideal: 3840, max: 3840 },
            height: { ideal: 2160, max: 2160 },
            frameRate: { ideal: 60, max: 60 },
            displaySurface: selectedSource.value === 'tab' ? 'browser' : selectedSource.value === 'window' ? 'window' : 'monitor'
          },
          audio: selectedSource.value !== 'tab' // System audio for screen/window, exclude for tab
        }

        // For Chrome: auto-select current tab
        if (selectedSource.value === 'tab') {
          displayMediaOptions.preferCurrentTab = true
        }

        const displayStream = await navigator.mediaDevices.getDisplayMedia(displayMediaOptions)

        // Log actual video resolution
        const videoTrack = displayStream.getVideoTracks()[0]
        if (videoTrack) {
          const settings = videoTrack.getSettings()
          // Recording resolution captured
        }

        // Get microphone audio if enabled
        let audioStream = null
        if (recordingOptions.value.microphone) {
          try {
            audioStream = await navigator.mediaDevices.getUserMedia({
              audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
              },
              video: false
            })
          } catch (audioErr) {
            // Could not get microphone access
          }
        }

        // Mix audio tracks
        const audioContext = new AudioContext()
        const audioDestination = audioContext.createMediaStreamDestination()

        const systemAudioTracks = displayStream.getAudioTracks()
        if (systemAudioTracks.length > 0) {
          const systemSource = audioContext.createMediaStreamSource(new MediaStream(systemAudioTracks))
          systemSource.connect(audioDestination)
        }

        if (audioStream) {
          const micSource = audioContext.createMediaStreamSource(audioStream)
          micSource.connect(audioDestination)
        }

        // Combine video and mixed audio
        const videoTracks = displayStream.getVideoTracks()
        const mixedAudioTracks = audioDestination.stream.getAudioTracks()

        stream = new MediaStream([
          ...videoTracks,
          ...mixedAudioTracks
        ])

        stream._displayStream = displayStream
        stream._audioStream = audioStream
        stream._audioContext = audioContext

        // Set up preview
        if (previewVideo.value) {
          previewVideo.value.srcObject = displayStream
        }

        // Set up MediaRecorder with adaptive bitrate
        chunkIndex = 0

        // Get video resolution to determine appropriate bitrate
        const videoTrackSettings = videoTracks[0]?.getSettings() || {}
        const width = videoTrackSettings.width || 1920
        const height = videoTrackSettings.height || 1080

        // Calculate bitrate based on resolution
        // 4K (3840x2160): 40 Mbps, 1440p: 20 Mbps, 1080p: 12 Mbps, 720p: 8 Mbps
        let videoBitsPerSecond = 12000000 // Default 12 Mbps for 1080p
        if (width >= 3840 || height >= 2160) {
          videoBitsPerSecond = 40000000 // 40 Mbps for 4K
        } else if (width >= 2560 || height >= 1440) {
          videoBitsPerSecond = 20000000 // 20 Mbps for 1440p
        } else if (width >= 1920 || height >= 1080) {
          videoBitsPerSecond = 12000000 // 12 Mbps for 1080p
        } else {
          videoBitsPerSecond = 8000000 // 8 Mbps for 720p and below
        }

        // Always start event tracking for every video (regardless of zoom enabled)
        // Events are stored for all videos, zoom processing only happens if enabled
        zoomTracking.startTracking({ width, height })

        // Try VP9 for better compression at high resolutions
        let options = {
          mimeType: 'video/webm;codecs=vp9',
          videoBitsPerSecond: videoBitsPerSecond
        }

        // Fallback to VP8 if VP9 not supported
        if (!MediaRecorder.isTypeSupported(options.mimeType)) {
          // VP9 not supported, trying VP8
          options = {
            mimeType: 'video/webm;codecs=vp8',
            videoBitsPerSecond: videoBitsPerSecond
          }
        }

        // Fallback to default if neither VP9 nor VP8 supported
        if (!MediaRecorder.isTypeSupported(options.mimeType)) {
          // VP8 not supported, using default codec
          options = { videoBitsPerSecond: videoBitsPerSecond }
        }

        mediaRecorder = new MediaRecorder(stream, options)

        mediaRecorder.ondataavailable = async (event) => {
          if (event.data.size > 0) {
            // Upload chunk immediately
            uploadChunk(event.data, chunkIndex)
            chunkIndex++
          }
        }

        mediaRecorder.onstop = async () => {
          isRecording.value = false
          hasRecorded.value = true
          isFinishing.value = true

          // Stop event tracking (always running during recording)
          zoomTracking.stopTracking()

          // Clean up streams
          if (stream) {
            stream.getTracks().forEach(track => track.stop())
            if (stream._displayStream) {
              stream._displayStream.getTracks().forEach(track => track.stop())
            }
            if (stream._audioStream) {
              stream._audioStream.getTracks().forEach(track => track.stop())
            }
            if (stream._audioContext) {
              stream._audioContext.close()
            }
          }

          // Complete the upload with retry logic
          try {
            const video = await completeUploadWithRetry()
            uploadProgress.value = 100

            // Redirect to video page
            window.location.href = import.meta.env.BASE_URL + `video/${video.id}`
          } catch (err) {
            console.error('Failed to complete upload after retries:', err)
            isFinishing.value = false

            // Show user-friendly message - recording is safe!
            toast.warning('Your recording is being processed and will appear in your library within 5 minutes.', 8000)

            // Redirect to videos page after 3 seconds
            setTimeout(() => {
              window.location.href = import.meta.env.BASE_URL + 'videos'
            }, 3000)
          }
        }

        // Request data every 3 seconds (upload chunks during recording)
        mediaRecorder.start(3000)
        isRecording.value = true
        recordingTime.value = 0

        // Start timer
        recordingInterval = setInterval(() => {
          if (!isPaused.value) {
            recordingTime.value++
          }
        }, 1000)

        isStartingRecording.value = false

      } catch (err) {
        console.error('Error starting recording:', err)
        toast.error('Failed to start recording. Please make sure you grant screen sharing permissions.')
        isStartingRecording.value = false

        // Cancel upload session if started
        if (sessionId.value) {
          fetch(buildApiUrl(`/api/stream/${sessionId.value}/cancel`), {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${auth.token.value}`
            }
          }).catch(() => {})
        }
      }
    }

    const pauseRecording = () => {
      if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.pause()
        isPaused.value = true
      }
    }

    const resumeRecording = () => {
      if (mediaRecorder && mediaRecorder.state === 'paused') {
        mediaRecorder.resume()
        isPaused.value = false
      }
    }

    const stopRecording = () => {
      if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop()
      }

      if (recordingInterval) {
        clearInterval(recordingInterval)
        recordingInterval = null
      }

      isPaused.value = false
    }

    onMounted(async () => {
      // Load user settings and apply defaults for zoom
      try {
        const userSettings = await settingsService.getUserSettings()
        if (userSettings) {
          // Apply user's default zoom settings
          zoomTracking.zoomEnabled.value = userSettings.auto_zoom_enabled ?? false
          zoomTracking.zoomLevel.value = userSettings.default_zoom_level ?? 2.0
          zoomTracking.zoomDurationMs.value = userSettings.default_zoom_duration_ms ?? 500
        }
      } catch (error) {
        console.error('Failed to load user settings:', error)
        // Use defaults if settings can't be loaded
        zoomTracking.zoomEnabled.value = false
      } finally {
        settingsLoaded.value = true
      }
    })

    onUnmounted(() => {
      if (recordingInterval) {
        clearInterval(recordingInterval)
      }

      if (stream) {
        stream.getTracks().forEach(track => track.stop())
        if (stream._displayStream) {
          stream._displayStream.getTracks().forEach(track => track.stop())
        }
        if (stream._audioStream) {
          stream._audioStream.getTracks().forEach(track => track.stop())
        }
        if (stream._audioContext) {
          stream._audioContext.close()
        }
      }

      // Cancel upload session if recording was interrupted
      if (sessionId.value && !hasRecorded.value) {
        fetch(buildApiUrl(`/api/stream/${sessionId.value}/cancel`), {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${auth.token.value}`
          }
        }).catch(() => {})
      }
    })

    return {
      // State
      isStartingRecording,
      isRecording,
      isPaused,
      hasRecorded,
      isFinishing,
      recordingTime,
      recordingOptions,
      selectedSource,
      sources,
      previewVideo,
      canRecord,
      settingsLoaded,
      // Upload state
      uploadedBytes,
      chunksUploaded,
      isUploading,
      uploadProgress,
      // Zoom tracking
      zoomTracking,
      // Methods
      formatTime,
      formatBytes,
      startRecording,
      pauseRecording,
      resumeRecording,
      stopRecording
    }
  }
}
</script>
