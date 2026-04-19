<template>
  <div class="flex flex-col">

    <!-- ─── Toolbar ─── -->
    <div class="h-9 flex items-center px-3 gap-1.5 border-b border-gray-100">
      <button class="tl-icon" title="Trim" @click="toggleTrim" :class="trimEnabled ? '!text-orange-600 bg-orange-50' : ''">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
      </button>
      <button class="tl-icon" title="Split">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
      </button>

      <div class="flex-1"></div>

      <!-- Zoom -->
      <div class="flex items-center gap-1">
        <button @click="timeline.zoomOut()" :disabled="timeline.zoom.value <= 1" class="tl-icon !w-5 !h-5">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" d="M5 12h14"/></svg>
        </button>
        <input type="range" :min="1" :max="20" v-model.number="timeline.zoom.value" class="w-16 h-1 accent-gray-400 cursor-pointer" />
        <button @click="timeline.zoomIn()" :disabled="timeline.zoom.value >= 20" class="tl-icon !w-5 !h-5">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
        </button>
      </div>
    </div>

    <!-- ─── Ruler + Track ─── -->
    <div
      ref="timelineContainer"
      class="overflow-x-auto relative"
      :style="{ height: (90 + (zoomKeyframes.length ? 30 : 0) + (hasBg ? 20 : 0)) + 'px' }"
      @click="onTimelineClick"
    >
      <div :style="{ width: timeline.totalTimelineWidth.value + 'px', minWidth: '100%' }" class="h-full flex flex-col">

        <!-- Ruler -->
        <div class="h-5 relative flex-shrink-0">
          <template v-for="mark in timeline.generateRulerMarks()" :key="mark.time">
            <div class="absolute top-0 bottom-0" :style="{ left: mark.position + 'px' }">
              <div class="w-px h-2.5 bg-gray-300/60"></div>
              <span class="text-[9px] text-gray-400 font-mono pl-0.5 select-none leading-none">{{ mark.label }}</span>
            </div>
          </template>
        </div>

        <!-- Track area -->
        <div class="flex-1 relative mx-0 my-1.5">

          <!-- Video block -->
          <div
            v-for="(block, idx) in videoBlocks"
            :key="block.isMain ? 'main' : block.id"
            class="absolute top-0 bottom-0 rounded-xl overflow-hidden border-2 transition-all cursor-pointer group/vblock"
            :class="block.isMain ? 'border-blue-400/60 bg-gray-200' : 'border-indigo-400/60 bg-indigo-100'"
            :style="{ left: blockLeft(idx) + 'px', width: timeline.timeToPixels(block.duration) + 'px' }"
            @mousedown.stop="mergeVideos.length ? onVideoBlockDragStart(idx, $event) : null"
          >
            <!-- Tick marks (visual filler like the reference) -->
            <div class="absolute inset-0 flex items-center opacity-20 pointer-events-none">
              <div v-for="n in Math.max(1, Math.floor(timeline.timeToPixels(block.duration) / 12))" :key="n" class="w-px h-full bg-gray-400 flex-shrink-0" :style="{ marginLeft: '12px' }"></div>
            </div>

            <!-- Label -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-[1]">
              <span class="text-[10px] font-medium text-gray-500 truncate px-6">{{ block.title }}</span>
            </div>

            <!-- Left trim handle -->
            <div
              class="absolute left-0 top-0 bottom-0 w-2.5 bg-blue-500 cursor-ew-resize opacity-0 group-hover/vblock:opacity-100 transition-opacity flex items-center justify-center rounded-l-lg z-[2]"
              @mousedown.stop="startTrimDrag('left', block, $event)"
            >
              <div class="w-0.5 h-5 bg-white/70 rounded-full"></div>
            </div>

            <!-- Right trim handle -->
            <div
              class="absolute right-0 top-0 bottom-0 w-2.5 bg-blue-500 cursor-ew-resize opacity-0 group-hover/vblock:opacity-100 transition-opacity flex items-center justify-center rounded-r-lg z-[2]"
              @mousedown.stop="startTrimDrag('right', block, $event)"
            >
              <div class="w-0.5 h-5 bg-white/70 rounded-full"></div>
            </div>

            <!-- Remove -->
            <button
              v-if="!block.isMain"
              @click.stop="removeMergeVideo(block.id)"
              class="absolute top-1 right-1 hidden group-hover/vblock:flex items-center justify-center w-4 h-4 rounded-full bg-red-500 text-white hover:bg-red-600 z-[3]"
            >
              <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Playhead -->
          <div
            class="absolute top-0 bottom-0 z-20 pointer-events-auto cursor-col-resize"
            :style="{ left: (playheadPosition - 0.5) + 'px' }"
            @mousedown.stop="startPlayheadDrag"
          >
            <div class="w-px h-full bg-gray-900"></div>
            <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-gray-900 rounded-full border-2 border-white shadow-md"></div>
          </div>

          <!-- Trim overlays -->
          <EditorTrimHandles :pixelsPerSecond="timeline.pixelsPerSecond.value" />
        </div>

        <!-- ─── Background Track ─── -->
        <div v-if="hasBg" class="h-4 relative mx-0 mb-1 flex-shrink-0">
          <div class="absolute left-1 top-0 bottom-0 flex items-center z-10 pointer-events-none">
            <span class="text-[8px] font-semibold text-gray-400 uppercase tracking-wider bg-white/80 px-1 rounded">BG</span>
          </div>
          <div
            class="absolute top-0.5 bottom-0.5 left-0 rounded-md border border-gray-200/60 overflow-hidden"
            :style="{ ...bgTrackStyle, width: timeline.timeToPixels(duration) + 'px' }"
          ></div>
        </div>

        <!-- ─── Zoom Track ─── -->
        <div v-if="zoomKeyframes.length" class="h-6 relative mx-0 mb-1.5 flex-shrink-0">
          <!-- Label -->
          <div class="absolute left-1 top-0 bottom-0 flex items-center z-10 pointer-events-none">
            <span class="text-[8px] font-semibold text-orange-400 uppercase tracking-wider bg-white/80 px-1 rounded">Zoom</span>
          </div>

          <!-- Zoom blocks -->
          <div
            v-for="kf in zoomKeyframes" :key="'zt-' + kf.id"
            class="absolute top-0.5 bottom-0.5 rounded-md cursor-pointer transition-colors group/zblock"
            :class="selectedZoomId === kf.id
              ? 'bg-orange-500 ring-2 ring-orange-300'
              : 'bg-orange-400/70 hover:bg-orange-500/80'"
            :style="{
              left: timeline.timeToPixels(kf.time) + 'px',
              width: Math.max(20, timeline.timeToPixels(kf.duration)) + 'px',
            }"
            @click.stop="selectZoomBlock(kf)"
            @mousedown.stop="startZoomBlockDrag(kf, $event)"
          >
            <!-- Zoom label -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none overflow-hidden">
              <span class="text-[9px] font-bold text-white/90 drop-shadow-sm">{{ kf.scale.toFixed(1) }}x</span>
            </div>

            <!-- Left resize handle -->
            <div
              class="absolute left-0 top-0 bottom-0 w-1.5 cursor-ew-resize opacity-0 group-hover/zblock:opacity-100 transition-opacity rounded-l-md bg-orange-700/50 z-[2]"
              @mousedown.stop="startZoomResize(kf, 'left', $event)"
            ></div>

            <!-- Right resize handle -->
            <div
              class="absolute right-0 top-0 bottom-0 w-1.5 cursor-ew-resize opacity-0 group-hover/zblock:opacity-100 transition-opacity rounded-r-md bg-orange-700/50 z-[2]"
              @mousedown.stop="startZoomResize(kf, 'right', $event)"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useEditorState } from '@/composables/useEditorState'
import { useEditorTimeline } from '@/composables/useEditorTimeline'
import EditorTrimHandles from './EditorTrimHandles.vue'

defineEmits(['addVideo'])

const state = useEditorState()
const {
  isPlaying, currentTime, duration, video, videoReady,
  items, selectItem, togglePlay, formatTime,
  trimEnabled, trimStart, trimEnd,
  mergeVideos, mainVideoIndex, removeMergeVideo, reorderVideoBlocks,
  zoomKeyframes, selectedZoomId, updateZoomKeyframe,
  styleBackground, stylePadding,
} = state

const timeline = useEditorTimeline()
const timelineContainer = ref(null)
const dragSourceIdx = ref(null)
const dropTargetIdx = ref(null)

const videoBlocks = computed(() => {
  const mainBlock = { id: null, title: video.value?.title || 'Video', duration: duration.value, isMain: true }
  if (!mergeVideos.value.length) return [mainBlock]
  const blocks = mergeVideos.value.map(v => ({ id: v.id, title: v.title, duration: v.duration || 10, isMain: false }))
  blocks.splice(Math.min(mainVideoIndex.value, blocks.length), 0, mainBlock)
  return blocks
})

function blockLeft(idx) {
  let left = 0
  for (let i = 0; i < idx; i++) left += timeline.timeToPixels(videoBlocks.value[i].duration)
  return left
}

const playheadPosition = computed(() => currentTime.value * timeline.pixelsPerSecond.value)

const hasBg = computed(() => styleBackground.value.type !== 'none' || stylePadding.value > 0)
const bgTrackStyle = computed(() => {
  const bg = styleBackground.value
  if (bg.type === 'solid') return { background: bg.color }
  if (bg.type === 'gradient') {
    const dir = bg.gradientDirection === 'br' ? '135deg' : bg.gradientDirection === 'r' ? '90deg' : '180deg'
    return { background: `linear-gradient(${dir}, ${bg.gradientFrom}, ${bg.gradientTo})` }
  }
  if (bg.type === 'image' && bg.imageUrl) return { background: `url(${bg.imageUrl}) center/cover no-repeat` }
  return { background: '#e5e7eb' }
})

function toggleTrim() {
  trimEnabled.value = !trimEnabled.value
  if (trimEnabled.value) { trimStart.value = 0; trimEnd.value = duration.value }
}

function onTimelineClick(e) {
  if (e.target.closest('.cursor-ew-resize') || e.target.closest('.cursor-col-resize') || e.target.closest('button')) return
  const container = timelineContainer.value
  if (!container) return
  const x = e.clientX - container.getBoundingClientRect().left + container.scrollLeft
  timeline.seekToTime(timeline.pixelsToTime(x))
}

function startPlayheadDrag(e) {
  const startX = e.clientX, startTime = currentTime.value
  const onMove = (ev) => { const dt = (ev.clientX - startX) / timeline.pixelsPerSecond.value; timeline.seekToTime(Math.max(0, Math.min(duration.value, startTime + dt))) }
  const onUp = () => { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp) }
  document.addEventListener('mousemove', onMove); document.addEventListener('mouseup', onUp)
}

function startTrimDrag(edge, block, e) {
  if (!block.isMain) return
  if (!trimEnabled.value) { trimEnabled.value = true; trimStart.value = 0; trimEnd.value = duration.value }
  const startX = e.clientX, origStart = trimStart.value, origEnd = trimEnd.value
  const onMove = (ev) => { const dt = (ev.clientX - startX) / timeline.pixelsPerSecond.value; if (edge === 'left') trimStart.value = Math.max(0, Math.min(origEnd - 0.5, origStart + dt)); else trimEnd.value = Math.min(duration.value, Math.max(origStart + 0.5, origEnd + dt)) }
  const onUp = () => { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp) }
  document.addEventListener('mousemove', onMove); document.addEventListener('mouseup', onUp)
}

function onVideoBlockDragStart(sourceIdx, e) {
  dragSourceIdx.value = sourceIdx; dropTargetIdx.value = null
  const startX = e.clientX; let hasMoved = false
  const onMove = (ev) => { if (Math.abs(ev.clientX - startX) > 20 && !hasMoved) hasMoved = true; if (!hasMoved) return; const container = timelineContainer.value; if (!container) return; const x = ev.clientX - container.getBoundingClientRect().left + container.scrollLeft; let acc = 0, target = videoBlocks.value.length - 1; for (let i = 0; i < videoBlocks.value.length; i++) { const w = timeline.timeToPixels(videoBlocks.value[i].duration); if (x < acc + w / 2) { target = i; break }; acc += w }; dropTargetIdx.value = target !== sourceIdx ? target : null }
  const onUp = () => { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); if (hasMoved && dropTargetIdx.value !== null) { const blocks = [...videoBlocks.value]; const [moved] = blocks.splice(dragSourceIdx.value, 1); blocks.splice(dropTargetIdx.value, 0, moved); reorderVideoBlocks(blocks) }; dragSourceIdx.value = null; dropTargetIdx.value = null }
  document.addEventListener('mousemove', onMove); document.addEventListener('mouseup', onUp)
}

// --- Zoom block interactions ---
function selectZoomBlock(kf) {
  selectedZoomId.value = selectedZoomId.value === kf.id ? null : kf.id
}

function startZoomBlockDrag(kf, e) {
  if (e.target.closest('.cursor-ew-resize')) return // let resize handle instead
  const startX = e.clientX
  const origTime = kf.time
  let moved = false

  const onMove = (ev) => {
    const dt = (ev.clientX - startX) / timeline.pixelsPerSecond.value
    if (!moved && Math.abs(dt) < 0.05) return
    moved = true
    const newTime = Math.max(0, Math.min(duration.value - kf.duration, origTime + dt))
    updateZoomKeyframe(kf.id, { time: Math.round(newTime * 100) / 100 })
  }
  const onUp = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }
  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function startZoomResize(kf, edge, e) {
  const startX = e.clientX
  const origTime = kf.time
  const origDuration = kf.duration

  const onMove = (ev) => {
    const dt = (ev.clientX - startX) / timeline.pixelsPerSecond.value
    if (edge === 'left') {
      const newTime = Math.max(0, Math.min(origTime + origDuration - 0.3, origTime + dt))
      const newDur = origDuration - (newTime - origTime)
      updateZoomKeyframe(kf.id, { time: Math.round(newTime * 100) / 100, duration: Math.round(newDur * 100) / 100 })
    } else {
      const newDur = Math.max(0.3, Math.min(duration.value - origTime, origDuration + dt))
      updateZoomKeyframe(kf.id, { duration: Math.round(newDur * 100) / 100 })
    }
  }
  const onUp = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }
  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function updateContainerWidth() { if (timelineContainer.value) timeline.containerWidth.value = timelineContainer.value.clientWidth }
onMounted(() => { updateContainerWidth(); window.addEventListener('resize', updateContainerWidth) })
onBeforeUnmount(() => { window.removeEventListener('resize', updateContainerWidth) })
</script>

<style scoped>
.tl-icon {
  @apply w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-30 disabled:cursor-not-allowed;
}
</style>
