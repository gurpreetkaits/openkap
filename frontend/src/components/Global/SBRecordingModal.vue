<template>
  <Teleport to="body">
    <dialog
      class="modal"
      :class="{ 'modal-open': show }"
    >
      <!-- Modal Backdrop -->
      <div class="modal-backdrop bg-black/60 backdrop-blur-md" @click="handleBackdropClick"></div>

      <!-- Modal Content -->
      <div class="modal-box max-w-4xl relative">
        <!-- Close Button -->
        <button
          v-if="!isRecording && !isFinishing && !isSaving"
          @click="closeModal"
          class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 z-10"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>

        <!-- Loading State - Checking Subscription -->
        <div v-if="isCheckingSubscription" class="p-8 text-center">
          <div class="flex justify-center mb-4">
            <span class="loading loading-spinner loading-lg text-primary"></span>
          </div>
          <p class="text-base-content/70">Checking your account...</p>
        </div>

        <!-- Recording Setup -->
        <div v-else-if="!isRecording && !hasRecorded" class="p-8 text-center">
          <!-- Header -->
          <div class="mb-8">
            <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-7 h-7 text-primary" fill="currentColor" viewBox="0 0 20 20">
                <circle cx="10" cy="10" r="6"/>
              </svg>
            </div>
            <h2 class="text-2xl font-bold mb-2">Start Recording</h2>
            <p class="text-base-content/70">Select your recording options below</p>
          </div>

          <!-- Recording Options Cards -->
          <div class="grid grid-cols-2 gap-4 mb-8 max-w-md mx-auto">
            <!-- Screen Option -->
            <div class="card bg-primary/10 border-2 border-primary">
              <div class="card-body items-center p-6">
                <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center mb-3">
                  <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <h3 class="font-semibold text-sm">Screen</h3>
                <p class="text-xs text-base-content/50">Always included</p>
              </div>
            </div>

            <!-- Microphone Option -->
            <label class="cursor-pointer">
              <input v-model="recordingOptions.microphone" type="checkbox" class="peer sr-only">
              <div class="card bg-base-100 border-2 border-base-300 transition-all peer-checked:border-primary peer-checked:bg-primary/10 hover:shadow-md">
                <div class="card-body items-center p-6">
                  <div class="w-12 h-12 bg-base-200 peer-checked:bg-primary/20 rounded-full flex items-center justify-center mb-3 transition-colors">
                    <svg class="w-6 h-6 text-base-content/60 peer-checked:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                  </div>
                  <h3 class="font-semibold text-sm">Microphone</h3>
                  <p class="text-xs text-base-content/50">{{ recordingOptions.microphone ? 'Enabled' : 'Disabled' }}</p>
                </div>
              </div>
            </label>
          </div>

          <!-- Start Recording Button -->
          <button
            @click="startRecording"
            :disabled="isStartingRecording"
            class="btn btn-primary btn-lg gap-2"
          >
            <span v-if="isStartingRecording" class="loading loading-spinner"></span>
            <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <circle cx="10" cy="10" r="7"/>
            </svg>
            {{ isStartingRecording ? 'Starting...' : 'Start Recording' }}
          </button>

          <p class="mt-4 text-sm text-base-content/50">
            Click to select what to share and start recording
          </p>
        </div>

        <!-- Recording in Progress -->
        <div v-if="isRecording" class="p-6">
          <!-- Recording Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="badge badge-error gap-2 p-3">
              <div class="w-2.5 h-2.5 bg-error rounded-full animate-pulse"></div>
              <span class="font-semibold">REC {{ formatTime(recordingTime) }}</span>
            </div>
            <span class="text-sm text-base-content/50">{{ formatBytes(uploadedBytes) }} uploaded</span>
          </div>

          <!-- Recording Preview -->
          <div class="bg-base-300 rounded-lg aspect-video mb-6 relative overflow-hidden">
            <video
              ref="previewVideo"
              autoplay
              muted
              class="w-full h-full object-contain"
            ></video>
          </div>

          <!-- Recording Controls -->
          <div class="flex items-center justify-center gap-4">
            <button
              @click="pauseRecording"
              v-if="!isPaused"
              class="btn btn-ghost gap-2"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M5 4h3v12H5V4zm7 0h3v12h-3V4z"/>
              </svg>
              Pause
            </button>

            <button
              @click="resumeRecording"
              v-if="isPaused"
              class="btn btn-primary gap-2"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
              </svg>
              Resume
            </button>

            <button
              @click="stopRecording"
              class="btn btn-error gap-2"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <rect x="4" y="4" width="12" height="12" rx="2"/>
              </svg>
              Stop Recording
            </button>
          </div>
        </div>

        <!-- Review Recording -->
        <div v-if="hasRecorded && !isFinishing && !isSaving" class="p-8 text-center">
          <div class="w-16 h-16 bg-success/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h2 class="text-2xl font-bold mb-2">Recording Complete!</h2>
          <p class="text-base-content/70 mb-2">
            Duration: {{ formatTime(recordingTime) }} &bull; Size: {{ formatBytes(uploadedBytes) }}
          </p>
          <p class="text-base-content/50 text-sm mb-6">
            Would you like to save this recording or discard it?
          </p>

          <div class="flex items-center justify-center gap-4">
            <button
              @click="discardRecording"
              :disabled="isDiscarding"
              class="btn btn-ghost gap-2"
            >
              <span v-if="isDiscarding" class="loading loading-spinner loading-sm"></span>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              {{ isDiscarding ? 'Discarding...' : 'Discard' }}
            </button>

            <button
              @click="saveRecording"
              :disabled="isDiscarding"
              class="btn btn-primary gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              Save Recording
            </button>
          </div>
        </div>

        <!-- Saving State -->
        <div v-if="isSaving" class="p-8 text-center">
          <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="loading loading-spinner loading-lg text-primary"></span>
          </div>
          <h2 class="text-2xl font-bold mb-2">Saving Your Video</h2>
          <p class="text-base-content/70">Finalizing and preparing your recording...</p>
        </div>
      </div>
    </dialog>
  </Teleport>
</template>

<script>
import { ref, watch, onUnmounted } from 'vue'
import { useAuth } from '@/stores/auth'
import { buildApiUrl } from '@/config/api'
import toast from '@/services/toastService'

export default {
  name: 'SBRecordingModal',
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'recording-complete', 'upgrade'],
  setup(props, { emit }) {
    const auth = useAuth()

    // Subscription state
    const isCheckingSubscription = ref(false)
    const canRecordVideo = ref(true)

    // Recording state
    const isStartingRecording = ref(false)
    const isRecording = ref(false)
    const isPaused = ref(false)
    const hasRecorded = ref(false)
    const isFinishing = ref(false)
    const isSaving = ref(false)
    const isDiscarding = ref(false)
    const recordingTime = ref(0)

    // Upload state
    const sessionId = ref(null)
    const uploadedBytes = ref(0)
    const chunksUploaded = ref(0)
    const uploadQueue = ref([])

    // Recording options
    const recordingOptions = ref({
      screen: true,
      microphone: true
    })

    // Media elements
    const previewVideo = ref(null)

    // MediaRecorder and streams
    let mediaRecorder = null
    let stream = null
    let recordingInterval = null
    let chunkIndex = 0

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

    const checkSubscription = async () => {
      isCheckingSubscription.value = true
      try {
        const subscription = await auth.fetchSubscription()
        canRecordVideo.value = subscription ? subscription.can_record : true
      } catch (err) {
        console.error('Error checking subscription:', err)
        canRecordVideo.value = true
      } finally {
        isCheckingSubscription.value = false
      }
    }

    const closeModal = () => {
      if (!isRecording.value && !isFinishing.value && !isSaving.value) {
        resetState()
        emit('close')
      }
    }

    const handleBackdropClick = () => {
      if (!isRecording.value && !isFinishing.value && !isSaving.value && !hasRecorded.value) {
        closeModal()
      }
    }

    const resetState = () => {
      isStartingRecording.value = false
      isRecording.value = false
      isPaused.value = false
      hasRecorded.value = false
      isFinishing.value = false
      isSaving.value = false
      isDiscarding.value = false
      recordingTime.value = 0
      sessionId.value = null
      uploadedBytes.value = 0
      chunksUploaded.value = 0
      uploadQueue.value = []
      chunkIndex = 0
      canRecordVideo.value = true
    }

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
        localStorage.setItem('auth_redirect', '/videos')
        window.location.href = '/login'
        return null
      }

      if (response.status === 403) {
        const errorData = await response.json().catch(() => ({}))
        if (errorData.error === 'video_limit_reached') {
          canRecordVideo.value = false
          return null
        }
      }

      if (!response.ok) {
        throw new Error('Failed to start upload session')
      }

      const data = await response.json()
      return data.session_id
    }

    const uploadChunk = async (chunk, index) => {
      if (!sessionId.value) return

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
        }
      } catch (err) {
        console.error('Failed to upload chunk:', err)
        uploadQueue.value.push({ chunk, index })
      }
    }

    const processUploadQueue = async () => {
      while (uploadQueue.value.length > 0) {
        const { chunk, index } = uploadQueue.value.shift()
        await uploadChunk(chunk, index)
      }
    }

    const completeUpload = async () => {
      if (!sessionId.value) return

      await processUploadQueue()

      const response = await fetch(buildApiUrl(`/api/stream/${sessionId.value}/complete`), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${auth.token.value}`
        },
        body: JSON.stringify({
          duration: recordingTime.value
        })
      })

      if (!response.ok) {
        throw new Error('Failed to complete upload')
      }

      const data = await response.json()
      return data.video
    }

    const completeUploadWithRetry = async (maxRetries = 3) => {
      let lastError = null

      for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
          const video = await completeUpload()
          return video
        } catch (error) {
          lastError = error
          if (error.message?.includes('401')) {
            throw error
          }
          if (attempt < maxRetries) {
            const delay = Math.pow(2, attempt - 1) * 1000
            await new Promise(resolve => setTimeout(resolve, delay))
          }
        }
      }

      throw lastError
    }

    const cancelUpload = async () => {
      if (!sessionId.value) return

      try {
        await fetch(buildApiUrl(`/api/stream/${sessionId.value}/cancel`), {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${auth.token.value}`
          }
        })
      } catch (err) {
        console.error('Failed to cancel upload:', err)
      }
    }

    const startRecording = async () => {
      try {
        isStartingRecording.value = true

        sessionId.value = await startUploadSession()
        if (!sessionId.value) {
          isStartingRecording.value = false
          if (!canRecordVideo.value) {
            toast.error('You\'ve reached your video limit. Please upgrade your plan to record more videos.')
          }
          return
        }

        const displayMediaOptions = {
          video: {
            width: { ideal: 3840, max: 3840 },
            height: { ideal: 2160, max: 2160 },
            frameRate: { ideal: 60, max: 60 },
            displaySurface: 'monitor'
          },
          audio: true
        }

        const displayStream = await navigator.mediaDevices.getDisplayMedia(displayMediaOptions)

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
            console.warn('Could not get microphone access:', audioErr)
          }
        }

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

        const videoTracks = displayStream.getVideoTracks()
        const mixedAudioTracks = audioDestination.stream.getAudioTracks()

        stream = new MediaStream([
          ...videoTracks,
          ...mixedAudioTracks
        ])

        stream._displayStream = displayStream
        stream._audioStream = audioStream
        stream._audioContext = audioContext

        if (previewVideo.value) {
          previewVideo.value.srcObject = displayStream
        }

        chunkIndex = 0
        const videoTrackSettings = videoTracks[0]?.getSettings() || {}
        const width = videoTrackSettings.width || 1920
        const height = videoTrackSettings.height || 1080

        let videoBitsPerSecond = 12000000
        if (width >= 3840 || height >= 2160) {
          videoBitsPerSecond = 40000000
        } else if (width >= 2560 || height >= 1440) {
          videoBitsPerSecond = 20000000
        } else if (width >= 1920 || height >= 1080) {
          videoBitsPerSecond = 12000000
        } else {
          videoBitsPerSecond = 8000000
        }

        let options = {
          mimeType: 'video/webm;codecs=vp9',
          videoBitsPerSecond
        }

        if (!MediaRecorder.isTypeSupported(options.mimeType)) {
          options = {
            mimeType: 'video/webm;codecs=vp8',
            videoBitsPerSecond
          }
        }

        if (!MediaRecorder.isTypeSupported(options.mimeType)) {
          options = { videoBitsPerSecond }
        }

        mediaRecorder = new MediaRecorder(stream, options)

        mediaRecorder.ondataavailable = async (event) => {
          if (event.data.size > 0) {
            uploadChunk(event.data, chunkIndex)
            chunkIndex++
          }
        }

        mediaRecorder.onstop = async () => {
          isRecording.value = false
          hasRecorded.value = true

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
        }

        displayStream.getVideoTracks()[0].onended = () => {
          if (isRecording.value) {
            stopRecording()
          }
        }

        mediaRecorder.start(3000)
        isRecording.value = true
        recordingTime.value = 0

        recordingInterval = setInterval(() => {
          if (!isPaused.value) {
            recordingTime.value++
          }
        }, 1000)

        isStartingRecording.value = false

      } catch (err) {
        console.error('Error starting recording:', err)

        if (err.name === 'NotAllowedError') {
          toast.error('Screen sharing was cancelled or denied.')
        } else {
          toast.error('Failed to start recording. Please make sure you grant screen sharing permissions.')
        }

        isStartingRecording.value = false

        if (sessionId.value) {
          cancelUpload()
          sessionId.value = null
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

    const saveRecording = async () => {
      isSaving.value = true
      try {
        const video = await completeUploadWithRetry()
        emit('recording-complete', video)
        window.location.href = `/video/${video.id}`
      } catch (err) {
        console.error('Failed to save recording after retries:', err)
        isSaving.value = false
        toast.warning('Your recording is being processed and will appear in your library within 5 minutes.', 8000)
        setTimeout(() => {
          emit('close')
          window.location.href = '/videos'
        }, 3000)
      }
    }

    const discardRecording = async () => {
      isDiscarding.value = true
      try {
        await cancelUpload()
        toast.success('Recording discarded')
        resetState()
        emit('close')
      } catch (err) {
        console.error('Failed to discard recording:', err)
        toast.error('Failed to discard recording')
        isDiscarding.value = false
      }
    }

    watch(() => props.show, (newVal) => {
      if (newVal) {
        checkSubscription()
      } else if (!isRecording.value && !isFinishing.value && !isSaving.value) {
        resetState()
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
      }

      if (sessionId.value && !hasRecorded.value) {
        cancelUpload()
      }
    })

    return {
      isCheckingSubscription,
      canRecordVideo,
      isStartingRecording,
      isRecording,
      isPaused,
      hasRecorded,
      isFinishing,
      isSaving,
      isDiscarding,
      recordingTime,
      recordingOptions,
      previewVideo,
      uploadedBytes,
      formatTime,
      formatBytes,
      closeModal,
      handleBackdropClick,
      startRecording,
      pauseRecording,
      resumeRecording,
      stopRecording,
      saveRecording,
      discardRecording
    }
  }
}
</script>
