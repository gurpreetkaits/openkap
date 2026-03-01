<template>
  <div class="bg-[#FAFAFA] text-slate-900 h-screen flex flex-col overflow-hidden select-none selection:bg-orange-100 selection:text-orange-700">

    <!-- Background Grid -->
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 32px 32px; opacity: 0.4;"></div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ error }}</h3>
        <button @click="goBack" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors">Go Back</button>
      </div>
    </div>

    <template v-else>
      <!-- Nav -->
      <nav class="h-14 border-b border-gray-200/60 bg-white/80 backdrop-blur-md flex items-center justify-between px-4 lg:px-6 z-50 relative">
        <div class="flex items-center gap-3">
          <button @click="goBack" class="text-gray-400 hover:text-gray-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </button>
          <span class="text-sm font-semibold text-gray-900">Video Editor</span>
          <span class="text-xs text-gray-400 truncate max-w-[200px] hidden sm:inline">{{ video?.title }}</span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="goBack" class="px-3 py-1.5 text-gray-500 hover:text-gray-700 text-xs font-medium transition-colors">Cancel</button>
          <button
            @click="applyEdits"
            :disabled="isApplying || (!items.length && !trimEnabled && !mergeVideoId)"
            class="px-4 py-1.5 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg text-xs font-medium shadow-sm transition-colors flex items-center gap-2"
          >
            <svg v-if="isApplying" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ isApplying ? `Processing ${applyProgress}%` : 'Create New With Edits' }}
          </button>
        </div>
      </nav>

      <!-- Main Area -->
      <div class="flex flex-1 overflow-hidden relative z-10 gap-3 p-3 pt-0">
        <!-- Left Sidebar: Flow (Transcript) — toggleable with motion -->
        <div
          v-if="showFlow"
          v-motion
          :initial="{ opacity: 0, x: -280, width: 0 }"
          :enter="{ opacity: 1, x: 0, width: 280, transition: { type: 'spring', stiffness: 300, damping: 30 } }"
          :leave="{ opacity: 0, x: -280, width: 0, transition: { type: 'spring', stiffness: 300, damping: 30 } }"
          class="flex-col min-h-0 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden hidden lg:flex flex-shrink-0"
          style="width: 280px;"
        >
          <div class="shrink-0 px-3 pt-3 pb-2 flex items-center justify-between border-b border-gray-100">
            <h2 class="text-xs font-medium text-gray-500 uppercase tracking-wider">Flow</h2>
            <button @click="showFlow = false" class="h-6 w-6 flex items-center justify-center text-gray-400 hover:text-gray-700 rounded transition-colors" title="Hide transcript">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
            </button>
          </div>
          <div class="flex-1 overflow-y-auto">
            <EditorTranscriptionPanel />
          </div>
        </div>

        <!-- Center: Video + Timeline -->
        <div class="flex-1 flex flex-col min-w-0 min-h-0 relative">
          <!-- Flow toggle button (shown when Flow panel is hidden) -->
          <button
            v-if="!showFlow"
            v-motion
            :initial="{ opacity: 0, scale: 0.8 }"
            :enter="{ opacity: 1, scale: 1, transition: { type: 'spring', stiffness: 400, damping: 25, delay: 150 } }"
            @click="showFlow = true"
            class="absolute top-2 left-2 z-10 hidden lg:inline-flex items-center gap-1.5 h-7 px-2.5 text-xs font-medium text-white/80 hover:text-white bg-black/40 hover:bg-black/60 backdrop-blur-sm rounded-lg transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
            Flow
          </button>

          <!-- Mobile tools strip -->
          <div class="lg:hidden flex items-center gap-2 px-3 py-2 bg-white border-b border-gray-100 overflow-x-auto rounded-xl mb-2">
            <div class="flex items-center p-0.5 bg-gray-100 rounded-lg flex-shrink-0">
              <button v-for="tool in ['blur', 'overlay', 'text']" :key="'m'+tool" @click="activeTool = tool"
                :class="activeTool === tool ? 'text-gray-900 bg-white shadow-sm' : 'text-gray-500'"
                class="px-3 py-1 rounded-md text-xs font-medium transition-all capitalize">{{ tool }}</button>
            </div>
            <button v-if="activeTool === 'text'" @click="addTextItem" class="flex-shrink-0 px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">+ Text</button>
            <label v-if="activeTool === 'overlay'" class="flex-shrink-0 px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
              + Video <input type="file" accept="video/*" class="hidden" @change="addOverlayFile" />
            </label>
            <span class="text-[10px] text-gray-400 flex-shrink-0 ml-auto">{{ items.length }} items</span>
          </div>

          <!-- Video Preview -->
          <EditorVideoPreview ref="videoPreviewRef" />

          <!-- Timeline -->
          <EditorTimeline @addVideo="showAddVideoModal = true" />
        </div>

        <!-- Right Sidebar: Tools + Properties -->
        <EditorToolPanel />
      </div>
    </template>

    <!-- Processing Overlay -->
    <EditorProcessingOverlay @goBack="goBack" />

    <!-- Add Video Modal -->
    <EditorAddVideoModal
      v-if="showAddVideoModal"
      @close="showAddVideoModal = false"
      @select="handleMergeVideoSelect"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import videoService from '@/services/videoService'
import { useToast } from '@/services/toastService'
import { createEditorState } from '@/composables/useEditorState'

import EditorVideoPreview from '@/components/Editor/EditorVideoPreview.vue'
import EditorToolPanel from '@/components/Editor/EditorToolPanel.vue'
import EditorTranscriptionPanel from '@/components/Editor/EditorTranscriptionPanel.vue'
import EditorProcessingOverlay from '@/components/Editor/EditorProcessingOverlay.vue'
import EditorTimeline from '@/components/Editor/EditorTimeline.vue'
import EditorAddVideoModal from '@/components/Editor/EditorAddVideoModal.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

// Create and provide editor state
const state = createEditorState()
const {
  loading, error, video, duration, items, overlayFiles,
  isApplying, applyProgress, processingMode, activeTool, showFlow,
  addTextItem, addOverlayFile,
  trimEnabled, trimStart, trimEnd, mergeVideoId, mergeVideo, mergePosition,
} = state

const videoPreviewRef = ref(null)
const showAddVideoModal = ref(false)
let pollTimer = null

// --- Lifecycle ---

onMounted(async () => {
  try {
    const data = await videoService.getVideo(route.params.id)
    if (!data) { error.value = 'Video not found'; return }
    video.value = data
    if (data.duration) duration.value = data.duration
    loading.value = false
    await nextTick()
    videoPreviewRef.value?.initVideo()
  } catch (e) {
    error.value = e.message || 'Failed to load video'
    loading.value = false
  }
})

// --- Merge ---

function handleMergeVideoSelect(selectedVideo) {
  mergeVideoId.value = selectedVideo.id
  mergeVideo.value = selectedVideo
  showAddVideoModal.value = false
}

// --- Apply Edits ---

async function applyEdits() {
  if (isApplying.value || (!items.value.length && !trimEnabled.value && !mergeVideoId.value)) return
  isApplying.value = true
  applyProgress.value = 0

  try {
    const blurRegions = items.value.filter(i => i.type === 'blur').map(i => ({
      x: i.x, y: i.y, width: i.width, height: i.height,
      start_time: i.entireVideo ? null : i.start_time, end_time: i.entireVideo ? null : i.end_time,
    }))

    const overlayConfigs = items.value.filter(i => i.type === 'overlay').map(i => ({
      x: i.x, y: i.y, width: i.width, height: i.height, file_index: i.fileIndex,
      start_time: i.entireVideo ? null : i.start_time, end_time: i.entireVideo ? null : i.end_time,
    }))

    const textOverlays = items.value.filter(i => i.type === 'text').map(i => ({
      text: i.text || 'Text', x: i.x, y: i.y, font_size: i.font_size || 32,
      font_color: i.font_color || '#ffffff',
      background_color: i.has_background ? (i.background_color || '#000000') : null,
      start_time: i.entireVideo ? null : i.start_time, end_time: i.entireVideo ? null : i.end_time,
    }))

    const result = await videoService.applyEdits(
      video.value.id, blurRegions, overlayConfigs, overlayFiles.value, textOverlays,
      trimEnabled.value ? trimStart.value : null,
      trimEnabled.value ? trimEnd.value : null,
      mergeVideoId.value,
      mergePosition.value
    )

    // Check processing mode
    if (result?.mode === 'async') {
      processingMode.value = 'async'
      return
    }

    if (result?.mode === 'sync' && result?.output_video_id) {
      // Sync: job already completed on server
      isApplying.value = false
      toast.success('Video edits applied! A new copy was created.')
      router.push(`/video/${result.output_video_id}`)
      return
    }

    // Fallback: poll for completion (legacy behavior)
    pollTimer = setInterval(async () => {
      try {
        const status = await videoService.getEditStatus(video.value.id)
        applyProgress.value = status.progress || 0
        if (status.status === 'completed') {
          clearInterval(pollTimer); pollTimer = null; isApplying.value = false
          toast.success('Video edits applied! A new copy was created.')
          router.push(`/video/${status.output_video_id || video.value.id}`)
        } else if (status.status === 'failed') {
          clearInterval(pollTimer); pollTimer = null; isApplying.value = false
          toast.error(status.error || 'Processing failed')
        }
      } catch { /* polling error, ignore */ }
    }, 3000)
  } catch (e) {
    isApplying.value = false
    toast.error(e.message || 'Failed to apply edits')
  }
}

function goBack() {
  if (pollTimer) clearInterval(pollTimer)
  if (video.value?.id) router.push(`/video/${video.value.id}`)
  else router.back()
}
</script>
