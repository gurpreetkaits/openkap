<template>
  <div class="space-y-1">
    <!-- Loading -->
    <div v-if="loadingTranscription" class="text-center py-6">
      <div class="inline-block animate-spin rounded-full h-5 w-5 border-2 border-orange-500 border-t-transparent"></div>
      <p class="text-xs text-gray-400 mt-2">Loading transcription...</p>
    </div>

    <!-- No transcription -->
    <div v-else-if="!segments.length" class="text-center py-6">
      <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      <p class="text-xs text-gray-500">No transcription available</p>
      <p class="text-[10px] text-gray-400 mt-1">Generate a transcription from the video detail page first.</p>
    </div>

    <!-- Segments list -->
    <template v-else>
      <!-- Saving indicator -->
      <div v-if="saving" class="flex items-center gap-1.5 px-2 py-1">
        <div class="animate-spin rounded-full h-3 w-3 border-2 border-orange-500 border-t-transparent"></div>
        <span class="text-[10px] text-orange-500 font-medium">Saving...</span>
      </div>

      <div
        v-for="(segment, index) in segments"
        :key="index"
        :ref="el => { if (el) segmentRefs[index] = el }"
      >
        <!-- Timestamp -->
        <div class="flex items-center gap-1 mb-0.5 px-2">
          <span class="text-[10px] text-gray-400/60 font-mono tabular-nums">
            {{ formatTimestamp(segment.start) }} – {{ formatTimestamp(segment.end) }}
          </span>
        </div>

        <!-- Editing textarea -->
        <textarea
          v-if="editingIndex === index"
          ref="editInput"
          v-model="editValue"
          @blur="commitEdit(index)"
          @keydown="onEditKeyDown($event, index)"
          :rows="Math.max(1, Math.ceil((editValue || '').length / 35))"
          class="w-full bg-orange-500/5 border border-orange-500/30 rounded-md px-2 py-1.5 text-sm leading-relaxed outline-none focus:ring-1 focus:ring-orange-500/30 resize-none"
        ></textarea>

        <!-- Display text -->
        <p
          v-else
          @click="onSegmentClick(segment)"
          @dblclick="onSegmentDoubleClick(index)"
          :class="[
            'text-sm leading-relaxed rounded-md px-2 py-1.5 cursor-pointer transition-colors duration-100',
            'hover:bg-gray-100',
            isSegmentActive(segment) ? 'bg-orange-500/10 text-orange-700' : ''
          ]"
        >{{ segment.text }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { useEditorState } from '@/composables/useEditorState'
import videoService from '@/services/videoService'
import { useToast } from '@/services/toastService'

const { video, videoEl, currentTime } = useEditorState()
const toast = useToast()

const loadingTranscription = ref(false)
const segments = ref([])
const originalSegments = ref([])
const saving = ref(false)

const editingIndex = ref(null)
const editValue = ref('')
const editInput = ref(null)
const segmentRefs = ref({})

let clickTimer = null

function formatTimestamp(seconds) {
  if (seconds == null || isNaN(seconds)) return '0:00'
  const m = Math.floor(seconds / 60)
  const s = Math.floor(seconds % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}

function isSegmentActive(segment) {
  const t = currentTime.value
  return t >= segment.start && t <= segment.end
}

// Single click: seek video to segment start
function onSegmentClick(segment) {
  if (editingIndex.value !== null) return
  clickTimer = setTimeout(() => {
    clickTimer = null
    const el = videoEl.value
    if (el) {
      el.currentTime = segment.start
      currentTime.value = segment.start
    }
  }, 200)
}

// Double click: enter inline edit mode
function onSegmentDoubleClick(index) {
  if (clickTimer) {
    clearTimeout(clickTimer)
    clickTimer = null
  }
  editValue.value = segments.value[index].text
  editingIndex.value = index
  nextTick(() => {
    const el = editInput.value
    // editInput is an array of refs since it's inside v-for; grab the first one
    const textarea = Array.isArray(el) ? el[0] : el
    if (textarea) {
      textarea.focus()
      textarea.select()
    }
  })
}

function onEditKeyDown(e, index) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    commitEdit(index)
  } else if (e.key === 'Escape') {
    editValue.value = segments.value[index].text
    editingIndex.value = null
  }
}

async function commitEdit(index) {
  const trimmed = (editValue.value || '').trim()
  if (editingIndex.value !== index) return
  editingIndex.value = null

  if (!trimmed || trimmed === segments.value[index].text) {
    return
  }

  // Update segment locally
  segments.value[index] = { ...segments.value[index], text: trimmed }

  // Save to backend
  await saveSegments()
}

async function saveSegments() {
  if (saving.value) return
  saving.value = true
  try {
    // Reconstruct full text from segments
    const fullText = segments.value.map(s => s.text).join(' ')
    await videoService.updateTranscription(video.value.id, fullText, segments.value)
    originalSegments.value = JSON.parse(JSON.stringify(segments.value))
    toast.success('Transcription saved')
  } catch (e) {
    toast.error(e.message || 'Failed to save transcription')
  } finally {
    saving.value = false
  }
}

async function loadTranscription() {
  if (!video.value?.id) return
  loadingTranscription.value = true
  try {
    const data = await videoService.getTranscription(video.value.id)
    // data.transcription is { transcription: "...", segments: [...], generated_at: "..." }
    const transcription = data?.transcription
    if (transcription?.segments?.length) {
      segments.value = transcription.segments.map(s => ({
        start: s.start,
        end: s.end,
        text: s.text,
        words: s.words || null,
      }))
    } else if (transcription?.transcription) {
      // Fallback: plain text with no segments, make one big segment
      segments.value = [{
        start: 0,
        end: video.value.duration || 0,
        text: transcription.transcription,
        words: null,
      }]
    } else {
      segments.value = []
    }
    originalSegments.value = JSON.parse(JSON.stringify(segments.value))
  } catch {
    segments.value = []
  } finally {
    loadingTranscription.value = false
  }
}

// Auto-scroll active segment into view
watch(currentTime, () => {
  if (editingIndex.value !== null) return
  const idx = segments.value.findIndex(s => currentTime.value >= s.start && currentTime.value <= s.end)
  if (idx >= 0 && segmentRefs.value[idx]) {
    segmentRefs.value[idx].scrollIntoView?.({ behavior: 'smooth', block: 'nearest' })
  }
})

onMounted(() => {
  loadTranscription()
})

watch(() => video.value?.id, () => {
  loadTranscription()
})
</script>
