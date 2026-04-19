<template>
  <div class="bg-[#f0f0f0] text-gray-900 h-screen flex flex-col overflow-hidden select-none">

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ error }}</h3>
        <button @click="goBack" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors">Go Back</button>
      </div>
    </div>

    <template v-else>
      <!-- ─── Top Nav ─── -->
      <nav class="h-12 bg-white border-b border-gray-200/80 flex items-center justify-between px-5 z-50 flex-shrink-0">
        <div class="flex items-center gap-3">
          <button @click="goBack" class="text-gray-400 hover:text-gray-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </button>
          <span class="text-sm font-semibold text-gray-800 truncate max-w-[300px]">{{ video?.title || 'Untitled' }}</span>
        </div>
        <div class="flex items-center gap-3">
          <button @click="goBack" class="px-4 py-1.5 text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors">Cancel</button>
          <button
            @click="applyEdits"
            :disabled="isApplying"
            class="px-5 py-1.5 bg-gray-900 hover:bg-gray-800 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2"
          >
            <svg v-if="isApplying" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            {{ isApplying ? `${applyProgress}%` : 'Export' }}
          </button>
        </div>
      </nav>

      <!-- ─── Main Area ─── -->
      <div class="flex flex-1 min-h-0 overflow-hidden p-4 gap-4">

        <!-- ─── Center Column: Video Card + Timeline Card ─── -->
        <div class="flex-1 flex flex-col min-w-0 min-h-0 gap-3">

          <!-- Video (independent, no card wrapper) -->
          <div class="flex-1 min-h-0 overflow-hidden">
            <EditorVideoPreview ref="videoPreviewRef" />
          </div>

          <!-- Playback Controls Card (separate, detached) -->
          <div class="flex-shrink-0 flex items-center justify-center gap-2 bg-white rounded-xl shadow-sm border border-gray-200/50 px-4 h-10">
            <span class="text-[11px] font-mono text-gray-400 tabular-nums">{{ displayCurrentTime }}</span>
            <span class="text-[10px] text-gray-300 mx-0.5">/</span>
            <span class="text-[11px] font-mono text-gray-300 tabular-nums">{{ displayDuration }}</span>
            <div class="w-px h-4 bg-gray-200 mx-2"></div>
            <button @click="skip(-5)" class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
            </button>
            <button @click="togglePlay" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-900 hover:bg-gray-800 text-white transition-colors">
              <svg v-if="!isPlaying" class="w-3.5 h-3.5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
              <svg v-else class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
            </button>
            <button @click="skip(5)" class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 18h2V6h-2zM6 18l8.5-6L6 6z"/></svg>
            </button>
          </div>

          <!-- Timeline Card -->
          <div class="flex-shrink-0 bg-white rounded-xl shadow-sm border border-gray-200/50 overflow-hidden">
            <EditorTimeline @addVideo="showAddVideoModal = true" />
          </div>
        </div>

        <!-- ─── Right Sidebar Card ─── -->
        <div class="w-[280px] flex-shrink-0 bg-white rounded-xl shadow-sm border border-gray-200/50 hidden lg:flex flex-col min-h-0 overflow-hidden">
          <div class="flex-1 overflow-y-auto min-h-0">
            <EditorToolPanel />
          </div>
        </div>
      </div>
    </template>

    <EditorProcessingOverlay @goBack="goBack" />
    <EditorAddVideoModal v-if="showAddVideoModal" @close="showAddVideoModal = false" @select="handleMergeVideoSelect" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import videoService from '@/services/videoService'
import { useToast } from '@/services/toastService'
import { createEditorState } from '@/composables/useEditorState'

import EditorVideoPreview from '@/components/Editor/EditorVideoPreview.vue'
import EditorToolPanel from '@/components/Editor/EditorToolPanel.vue'
import EditorProcessingOverlay from '@/components/Editor/EditorProcessingOverlay.vue'
import EditorTimeline from '@/components/Editor/EditorTimeline.vue'
import EditorAddVideoModal from '@/components/Editor/EditorAddVideoModal.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const state = createEditorState()
const {
  loading, error, video, duration, currentTime, items, overlayFiles, isPlaying,
  isApplying, applyProgress, processingMode,
  addTextItem, addOverlayFile, selectedItem, selectedItemId,
  selectItem, deleteItem, getItemLabel, formatTime, togglePlay,
  trimEnabled, trimStart, trimEnd, mergeVideos, mainVideoIndex, addMergeVideo,
  videoReady,
} = state

const videoPreviewRef = ref(null)
const showAddVideoModal = ref(false)
let pollTimer = null

const displayCurrentTime = computed(() => {
  if (!videoReady.value) return '00:00.00'
  const t = currentTime.value
  const m = Math.floor(t / 60)
  const s = Math.floor(t % 60)
  const ms = Math.floor((t % 1) * 100)
  return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}.${String(ms).padStart(2,'0')}`
})

const displayDuration = computed(() => {
  const d = duration.value || video.value?.duration || 0
  const m = Math.floor(d / 60)
  const s = Math.floor(d % 60)
  const ms = Math.floor((d % 1) * 100)
  return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}.${String(ms).padStart(2,'0')}`
})

function skip(seconds) {
  const el = state.videoEl.value
  if (el) el.currentTime = Math.max(0, Math.min(el.duration || 0, el.currentTime + seconds))
}

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

function handleMergeVideoSelect(v) { addMergeVideo(v) }

async function applyEdits() {
  if (isApplying.value || (!items.value.length && !trimEnabled.value && !mergeVideos.value.length)) return
  isApplying.value = true; applyProgress.value = 0
  try {
    const blurRegions = items.value.filter(i => i.type === 'blur').map(i => ({ x: i.x, y: i.y, width: i.width, height: i.height, start_time: i.entireVideo ? null : i.start_time, end_time: i.entireVideo ? null : i.end_time }))
    const overlayConfigs = items.value.filter(i => i.type === 'overlay').map(i => ({ x: i.x, y: i.y, width: i.width, height: i.height, file_index: i.fileIndex, start_time: i.entireVideo ? null : i.start_time, end_time: i.entireVideo ? null : i.end_time }))
    const textOverlays = items.value.filter(i => i.type === 'text').map(i => ({ text: i.text || 'Text', x: i.x, y: i.y, font_size: i.font_size || 32, font_color: i.font_color || '#ffffff', background_color: i.has_background ? (i.background_color || '#000000') : null, start_time: i.entireVideo ? null : i.start_time, end_time: i.entireVideo ? null : i.end_time }))
    const result = await videoService.applyEdits(video.value.id, blurRegions, overlayConfigs, overlayFiles.value, textOverlays, trimEnabled.value ? trimStart.value : null, trimEnabled.value ? trimEnd.value : null, mergeVideos.value.map(v => v.id), mainVideoIndex.value)
    if (result?.mode === 'async') { processingMode.value = 'async'; return }
    if (result?.mode === 'sync' && result?.output_video_id) { isApplying.value = false; toast.success('Video edits applied!'); router.push(`/video/${result.output_video_id}`); return }
    pollTimer = setInterval(async () => { try { const s = await videoService.getEditStatus(video.value.id); applyProgress.value = s.progress || 0; if (s.status === 'completed') { clearInterval(pollTimer); pollTimer = null; isApplying.value = false; toast.success('Video edits applied!'); router.push(`/video/${s.output_video_id || video.value.id}`) } else if (s.status === 'failed') { clearInterval(pollTimer); pollTimer = null; isApplying.value = false; toast.error(s.error || 'Failed') } } catch {} }, 3000)
  } catch (e) { isApplying.value = false; toast.error(e.message || 'Failed') }
}

function goBack() { if (pollTimer) clearInterval(pollTimer); if (video.value?.id) router.push(`/video/${video.value.id}`); else router.back() }
</script>
