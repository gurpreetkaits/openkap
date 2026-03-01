<template>
  <div class="bg-white border-t border-gray-200 flex-shrink-0">
    <!-- Controls bar -->
    <div class="h-9 flex items-center px-3 gap-2 border-b border-gray-100">
      <button @click="togglePlay" class="text-gray-500 hover:text-orange-600 transition-colors flex-shrink-0">
        <svg v-if="!isPlaying" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
      </button>
      <span class="text-[10px] text-gray-500 font-mono flex-shrink-0 w-8 text-center">{{ displayCurrentTime }}</span>
      <span class="text-[10px] text-gray-400">/</span>
      <span class="text-[10px] text-gray-500 font-mono flex-shrink-0 w-8 text-center">{{ displayDuration }}</span>

      <div class="flex-1"></div>

      <!-- Trim toggle -->
      <button
        @click="toggleTrim"
        :class="trimEnabled ? 'text-yellow-600 bg-yellow-50' : 'text-gray-400 hover:text-gray-600'"
        class="px-2 py-1 rounded text-[10px] font-medium transition-colors flex items-center gap-1"
        title="Toggle trim"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
        </svg>
        Trim
      </button>

      <!-- Add Video -->
      <button
        @click="$emit('addVideo')"
        class="px-2 py-1 rounded text-[10px] font-medium text-gray-400 hover:text-gray-600 transition-colors flex items-center gap-1"
        title="Add video to merge"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add Video
      </button>

      <!-- Zoom -->
      <div class="flex items-center gap-1 flex-shrink-0">
        <button @click="timeline.zoomOut()" :disabled="timeline.zoom.value <= 1" class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 disabled:opacity-30 transition-colors rounded">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
        </button>
        <span class="text-[10px] text-gray-400 w-6 text-center">{{ timeline.zoom.value }}x</span>
        <button @click="timeline.zoomIn()" :disabled="timeline.zoom.value >= 20" class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 disabled:opacity-30 transition-colors rounded">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </button>
      </div>
    </div>

    <!-- Timeline scroll area -->
    <div
      ref="timelineContainer"
      class="overflow-x-auto relative"
      style="min-height: 140px;"
      @click="onRulerClick"
    >
      <div :style="{ width: timeline.totalTimelineWidth.value + 'px', minWidth: '100%' }">
        <!-- Ruler -->
        <div class="h-5 border-b border-gray-100 relative">
          <template v-for="mark in timeline.generateRulerMarks()" :key="mark.time">
            <div class="absolute top-0 bottom-0 border-l border-gray-200" :style="{ left: mark.position + 'px' }">
              <span class="text-[9px] text-gray-400 pl-1 select-none">{{ mark.label }}</span>
            </div>
          </template>
        </div>

        <!-- Tracks area (relative, for playhead + trim handles) -->
        <div class="relative">
          <!-- Video track -->
          <div class="flex border-b border-gray-100">
            <div class="w-16 flex-shrink-0 flex items-center px-2 bg-gray-50 border-r border-gray-100">
              <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Video</span>
            </div>
            <div class="flex-1 relative h-8 min-w-0">
              <!-- First block -->
              <div
                :class="[
                  'absolute h-6 rounded flex items-center px-2 text-[10px] font-medium top-1 overflow-hidden transition-all duration-200',
                  mergeVideo ? 'cursor-grab active:cursor-grabbing' : '',
                  firstBlock.isMain ? 'bg-gray-200 text-gray-600' : 'bg-indigo-200 text-indigo-700',
                  dragTarget === 'first' ? 'ring-2 ring-orange-400 z-10' : '',
                ]"
                :style="{ left: 0, width: timeline.timeToPixels(firstBlock.duration) + 'px' }"
                @mousedown.stop="mergeVideo ? onVideoBlockDragStart('first', $event) : null"
                @dragover.prevent="onVideoBlockDragOver('first', $event)"
                @drop.prevent="onVideoBlockDrop('first')"
              >
                <span class="truncate">{{ firstBlock.title }}</span>
              </div>
              <!-- Second block (merge) -->
              <div
                v-if="mergeVideo"
                :class="[
                  'absolute h-6 rounded flex items-center px-2 text-[10px] font-medium top-1 overflow-hidden cursor-grab active:cursor-grabbing transition-all duration-200',
                  secondBlock.isMain ? 'bg-gray-200 text-gray-600' : 'bg-indigo-200 text-indigo-700',
                  dragTarget === 'second' ? 'ring-2 ring-orange-400 z-10' : '',
                ]"
                :style="{ left: timeline.timeToPixels(firstBlock.duration) + 'px', width: timeline.timeToPixels(secondBlock.duration) + 'px' }"
                @mousedown.stop="onVideoBlockDragStart('second', $event)"
                @dragover.prevent="onVideoBlockDragOver('second', $event)"
                @drop.prevent="onVideoBlockDrop('second')"
              >
                <span class="truncate">{{ secondBlock.title }}</span>
              </div>
            </div>
          </div>

          <!-- Blur track -->
          <EditorTimelineTrack
            type="blur"
            label="Blur"
            :pixelsPerSecond="timeline.pixelsPerSecond.value"
            @select="selectItem"
            @updateItem="handleUpdateItem"
            @seek="timeline.seekToTime"
          />

          <!-- Text track -->
          <EditorTimelineTrack
            type="text"
            label="Text"
            :pixelsPerSecond="timeline.pixelsPerSecond.value"
            @select="selectItem"
            @updateItem="handleUpdateItem"
            @seek="timeline.seekToTime"
          />

          <!-- Overlay track -->
          <EditorTimelineTrack
            type="overlay"
            label="Overlay"
            :pixelsPerSecond="timeline.pixelsPerSecond.value"
            @select="selectItem"
            @updateItem="handleUpdateItem"
            @seek="timeline.seekToTime"
          />

          <!-- Playhead -->
          <EditorTimelinePlayhead
            :pixelsPerSecond="timeline.pixelsPerSecond.value"
            @seek="timeline.seekToTime"
          />

          <!-- Trim handles -->
          <EditorTrimHandles :pixelsPerSecond="timeline.pixelsPerSecond.value" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useEditorState } from '@/composables/useEditorState'
import { useEditorTimeline } from '@/composables/useEditorTimeline'
import EditorTimelineTrack from './EditorTimelineTrack.vue'
import EditorTimelinePlayhead from './EditorTimelinePlayhead.vue'
import EditorTrimHandles from './EditorTrimHandles.vue'

defineEmits(['addVideo'])

const state = useEditorState()
const {
  isPlaying, currentTime, duration, video, videoReady,
  items, selectItem, togglePlay, formatTime,
  trimEnabled, trimStart, trimEnd, mergeVideo, mergePosition,
} = state

const timeline = useEditorTimeline()

const timelineContainer = ref(null)
const dragSource = ref(null)
const dragTarget = ref(null)

// Computed video blocks based on merge position
const firstBlock = computed(() => {
  if (mergeVideo.value && mergePosition.value === 'before') {
    return { title: mergeVideo.value.title, duration: mergeVideo.value.duration || 10, isMain: false }
  }
  return { title: video.value?.title || 'Video', duration: duration.value, isMain: true }
})

const secondBlock = computed(() => {
  if (!mergeVideo.value) return null
  if (mergePosition.value === 'before') {
    return { title: video.value?.title || 'Video', duration: duration.value, isMain: true }
  }
  return { title: mergeVideo.value.title, duration: mergeVideo.value.duration || 10, isMain: false }
})

const displayCurrentTime = computed(() => {
  if (!videoReady.value) return '--:--'
  return formatTime(currentTime.value)
})

const displayDuration = computed(() => {
  if (!videoReady.value && !video.value?.duration) return '--:--'
  return formatTime(duration.value || video.value?.duration || 0)
})

function toggleTrim() {
  trimEnabled.value = !trimEnabled.value
  if (trimEnabled.value) {
    trimStart.value = 0
    trimEnd.value = duration.value
  }
}

function handleUpdateItem(updated) {
  const idx = items.value.findIndex(i => i.id === updated.id)
  if (idx !== -1) {
    items.value[idx] = { ...items.value[idx], ...updated }
  }
}

// --- Video block drag to reorder ---
function onVideoBlockDragStart(position, e) {
  dragSource.value = position
  dragTarget.value = null

  const startX = e.clientX
  let hasMoved = false

  function onMove(ev) {
    const dx = Math.abs(ev.clientX - startX)
    if (dx > 20 && !hasMoved) {
      hasMoved = true
    }
    if (hasMoved) {
      // Determine which block we're over
      const container = timelineContainer.value
      if (!container) return
      const trackEl = container.querySelector('.relative.h-8')
      if (!trackEl) return
      const rect = trackEl.getBoundingClientRect()
      const x = ev.clientX - rect.left
      const firstWidth = timeline.timeToPixels(firstBlock.value.duration)
      dragTarget.value = x < firstWidth ? 'first' : 'second'
    }
  }

  function onUp() {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
    if (hasMoved && dragTarget.value && dragTarget.value !== dragSource.value) {
      // Swap: toggle merge position
      mergePosition.value = mergePosition.value === 'after' ? 'before' : 'after'
    }
    dragSource.value = null
    dragTarget.value = null
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function onVideoBlockDragOver(position, e) {
  if (dragSource.value && dragSource.value !== position) {
    dragTarget.value = position
  }
}

function onVideoBlockDrop(position) {
  if (dragSource.value && dragSource.value !== position) {
    mergePosition.value = mergePosition.value === 'after' ? 'before' : 'after'
  }
  dragSource.value = null
  dragTarget.value = null
}

function onRulerClick(e) {
  if (e.target.closest('.absolute')) return
  const container = timelineContainer.value
  if (!container) return
  const rect = container.getBoundingClientRect()
  const x = e.clientX - rect.left + container.scrollLeft
  timeline.seekToTime(timeline.pixelsToTime(x))
}

function updateContainerWidth() {
  if (timelineContainer.value) {
    timeline.containerWidth.value = timelineContainer.value.clientWidth
  }
}

onMounted(() => {
  updateContainerWidth()
  window.addEventListener('resize', updateContainerWidth)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateContainerWidth)
})
</script>
