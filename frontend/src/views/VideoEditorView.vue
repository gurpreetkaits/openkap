<template>
  <div class="bg-gray-900 text-white h-screen flex flex-col overflow-hidden select-none">

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex items-center justify-center h-screen">
      <div class="text-center">
        <p class="text-red-400 mb-4">{{ error }}</p>
        <button @click="$router.back()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm">Go Back</button>
      </div>
    </div>

    <template v-else>
      <!-- Nav -->
      <nav class="h-12 bg-gray-800 border-b border-gray-700 flex items-center justify-between px-4 flex-shrink-0">
        <div class="flex items-center gap-3">
          <button @click="goBack" class="text-gray-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </button>
          <span class="text-sm font-semibold">Video Editor</span>
          <span class="text-xs text-gray-500 truncate max-w-[200px]">{{ video?.title }}</span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="goBack" class="px-3 py-1.5 text-gray-400 hover:text-white text-xs font-medium transition-colors">
            Cancel
          </button>
          <button
            @click="applyEdits"
            :disabled="isApplying || (!items.length)"
            class="px-4 py-1.5 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white rounded-lg text-xs font-medium transition-colors flex items-center gap-2"
          >
            <svg v-if="isApplying" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ isApplying ? `Processing ${applyProgress}%` : 'Apply Edits' }}
          </button>
        </div>
      </nav>

      <!-- Main Area -->
      <div class="flex flex-1 overflow-hidden">
        <!-- Left Panel: Tools & Items -->
        <div class="w-60 bg-gray-800 border-r border-gray-700 flex flex-col flex-shrink-0">
          <!-- Tool Toggles -->
          <div class="p-3 border-b border-gray-700">
            <p class="text-[10px] uppercase tracking-wider text-gray-500 mb-2 font-semibold">Tools</p>
            <div class="flex gap-2">
              <button
                @click="activeTool = 'blur'"
                :class="activeTool === 'blur' ? 'bg-orange-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
                class="flex-1 py-2 rounded-lg text-xs font-medium transition-colors flex items-center justify-center gap-1.5"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
                Blur
              </button>
              <button
                @click="activeTool = 'overlay'"
                :class="activeTool === 'overlay' ? 'bg-orange-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
                class="flex-1 py-2 rounded-lg text-xs font-medium transition-colors flex items-center justify-center gap-1.5"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0h4a1 1 0 011 1v14a1 1 0 01-1 1H3a1 1 0 01-1-1V5a1 1 0 011-1h4"/>
                </svg>
                Overlay
              </button>
            </div>
          </div>

          <!-- Add Overlay Button -->
          <div v-if="activeTool === 'overlay'" class="p-3 border-b border-gray-700">
            <label class="w-full py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-xs font-medium transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Add Overlay Video
              <input type="file" accept="video/*" class="hidden" @change="addOverlayFile" />
            </label>
          </div>

          <!-- Items List -->
          <div class="flex-1 overflow-y-auto p-3">
            <p class="text-[10px] uppercase tracking-wider text-gray-500 mb-2 font-semibold">Items ({{ items.length }})</p>
            <div v-if="!items.length" class="text-xs text-gray-500 text-center py-4">
              {{ activeTool === 'blur' ? 'Click and drag on the video to add a blur region' : 'Upload an overlay video to begin' }}
            </div>
            <div
              v-for="(item, index) in items"
              :key="item.id"
              @click="selectedItemId = item.id"
              :class="selectedItemId === item.id ? 'border-orange-500 bg-gray-700/50' : 'border-gray-700 hover:border-gray-600'"
              class="border rounded-lg p-2.5 mb-2 cursor-pointer transition-colors"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div
                    :class="item.type === 'blur' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400'"
                    class="w-6 h-6 rounded flex items-center justify-center"
                  >
                    <svg v-if="item.type === 'blur'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243"/>
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs font-medium">{{ item.type === 'blur' ? `Blur ${index + 1}` : item.fileName || `Overlay ${index + 1}` }}</p>
                    <p class="text-[10px] text-gray-500">
                      {{ item.entireVideo ? 'Entire video' : `${formatTime(item.start_time)} - ${formatTime(item.end_time)}` }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Center: Video + Canvas -->
        <div class="flex-1 flex flex-col bg-gray-900">
          <!-- Video Container -->
          <div class="flex-1 flex items-center justify-center p-4 relative">
            <div
              ref="videoWrapper"
              class="relative bg-black"
              :style="videoWrapperStyle"
            >
              <video
                ref="videoEl"
                class="w-full h-full"
                @loadedmetadata="onVideoLoaded"
                @timeupdate="onTimeUpdate"
                @ended="isPlaying = false"
                playsinline
              ></video>
              <canvas
                ref="canvasEl"
                class="absolute top-0 left-0 w-full h-full"
                :style="{ cursor: activeTool === 'blur' ? 'crosshair' : 'default' }"
                @mousedown="onCanvasMouseDown"
                @mousemove="onCanvasMouseMove"
                @mouseup="onCanvasMouseUp"
                @mouseleave="onCanvasMouseUp"
              ></canvas>
            </div>
          </div>

          <!-- Bottom: Playback Controls -->
          <div class="h-14 bg-gray-800 border-t border-gray-700 flex items-center px-4 gap-3 flex-shrink-0">
            <button @click="togglePlay" class="text-white hover:text-orange-400 transition-colors">
              <svg v-if="!isPlaying" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
              </svg>
              <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
              </svg>
            </button>
            <span class="text-xs text-gray-400 w-20 text-center font-mono">{{ formatTime(currentTime) }}</span>
            <div class="flex-1 relative h-1.5 bg-gray-700 rounded-full cursor-pointer group" @click="seek">
              <div class="absolute inset-y-0 left-0 bg-orange-500 rounded-full" :style="{ width: seekPercent + '%' }"></div>
              <!-- Item markers on timeline -->
              <div
                v-for="item in items"
                :key="'marker-' + item.id"
                class="absolute top-1/2 -translate-y-1/2 h-3 rounded-sm opacity-50"
                :class="item.type === 'blur' ? 'bg-blue-400' : 'bg-green-400'"
                :style="getTimelineMarkerStyle(item)"
              ></div>
            </div>
            <span class="text-xs text-gray-400 w-20 text-center font-mono">{{ formatTime(duration) }}</span>
          </div>
        </div>

        <!-- Right Panel: Properties -->
        <div v-if="selectedItem" class="w-60 bg-gray-800 border-l border-gray-700 flex flex-col flex-shrink-0">
          <div class="p-3 border-b border-gray-700 flex items-center justify-between">
            <p class="text-xs font-semibold">Properties</p>
            <button @click="selectedItemId = null" class="text-gray-500 hover:text-white">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="p-3 space-y-3 overflow-y-auto flex-1">
            <!-- Position -->
            <div>
              <p class="text-[10px] uppercase tracking-wider text-gray-500 mb-1.5 font-semibold">Position</p>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="text-[10px] text-gray-500 block mb-0.5">X %</label>
                  <input type="number" v-model.number="selectedItem.x" min="0" max="100" step="0.1"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-xs focus:border-orange-500 focus:outline-none"
                    @input="renderCanvas"
                  />
                </div>
                <div>
                  <label class="text-[10px] text-gray-500 block mb-0.5">Y %</label>
                  <input type="number" v-model.number="selectedItem.y" min="0" max="100" step="0.1"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-xs focus:border-orange-500 focus:outline-none"
                    @input="renderCanvas"
                  />
                </div>
              </div>
            </div>

            <!-- Size -->
            <div>
              <p class="text-[10px] uppercase tracking-wider text-gray-500 mb-1.5 font-semibold">Size</p>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="text-[10px] text-gray-500 block mb-0.5">Width %</label>
                  <input type="number" v-model.number="selectedItem.width" min="1" max="100" step="0.1"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-xs focus:border-orange-500 focus:outline-none"
                    @input="renderCanvas"
                  />
                </div>
                <div>
                  <label class="text-[10px] text-gray-500 block mb-0.5">Height %</label>
                  <input type="number" v-model.number="selectedItem.height" min="1" max="100" step="0.1"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-xs focus:border-orange-500 focus:outline-none"
                    @input="renderCanvas"
                  />
                </div>
              </div>
            </div>

            <!-- Time Range -->
            <div>
              <p class="text-[10px] uppercase tracking-wider text-gray-500 mb-1.5 font-semibold">Time Range</p>
              <label class="flex items-center gap-2 mb-2 cursor-pointer">
                <input type="checkbox" v-model="selectedItem.entireVideo" class="rounded bg-gray-700 border-gray-600 text-orange-500 focus:ring-orange-500" />
                <span class="text-xs text-gray-300">Entire video</span>
              </label>
              <div v-if="!selectedItem.entireVideo" class="grid grid-cols-2 gap-2">
                <div>
                  <label class="text-[10px] text-gray-500 block mb-0.5">Start (s)</label>
                  <input type="number" v-model.number="selectedItem.start_time" min="0" :max="duration" step="0.1"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-xs focus:border-orange-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label class="text-[10px] text-gray-500 block mb-0.5">End (s)</label>
                  <input type="number" v-model.number="selectedItem.end_time" min="0" :max="duration" step="0.1"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-xs focus:border-orange-500 focus:outline-none"
                  />
                </div>
              </div>
            </div>

            <!-- Delete -->
            <button
              @click="deleteItem(selectedItem.id)"
              class="w-full py-2 bg-red-600/20 hover:bg-red-600/40 text-red-400 rounded-lg text-xs font-medium transition-colors"
            >
              Delete Item
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import videoService from '@/services/videoService'
import { showToast } from '@/services/toastService'
import Hls from 'hls.js'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref(null)
const video = ref(null)
const videoEl = ref(null)
const canvasEl = ref(null)
const videoWrapper = ref(null)

const activeTool = ref('blur')
const items = ref([])
const selectedItemId = ref(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const videoWidth = ref(0)
const videoHeight = ref(0)

const isApplying = ref(false)
const applyProgress = ref(0)
let pollTimer = null
let hls = null
let animFrame = null
let nextItemId = 1

// Overlay files stored separately
const overlayFiles = ref([])

// Drawing state
const isDrawing = ref(false)
const drawStart = ref(null)
const drawCurrent = ref(null)

// Drag state
const isDragging = ref(false)
const dragOffset = ref({ x: 0, y: 0 })

// Resize state
const isResizing = ref(false)
const resizeHandle = ref(null)

const selectedItem = computed(() => {
  if (!selectedItemId.value) return null
  return items.value.find(i => i.id === selectedItemId.value) || null
})

const seekPercent = computed(() => {
  if (!duration.value) return 0
  return (currentTime.value / duration.value) * 100
})

const videoWrapperStyle = computed(() => {
  if (!videoWidth.value || !videoHeight.value) return { width: '100%', maxHeight: '100%' }
  const aspect = videoWidth.value / videoHeight.value
  return {
    aspectRatio: `${aspect}`,
    maxWidth: '100%',
    maxHeight: '100%',
  }
})

onMounted(async () => {
  try {
    const id = route.params.id
    const data = await videoService.getVideo(id)
    if (!data) {
      error.value = 'Video not found'
      return
    }
    video.value = data
    loading.value = false

    await nextTick()
    initVideo()
  } catch (e) {
    error.value = e.message || 'Failed to load video'
    loading.value = false
  }
})

onBeforeUnmount(() => {
  if (hls) { hls.destroy(); hls = null }
  if (pollTimer) clearInterval(pollTimer)
  if (animFrame) cancelAnimationFrame(animFrame)
})

function initVideo() {
  const el = videoEl.value
  if (!el) return

  const hlsUrl = video.value.hls_url
  const streamUrl = video.value.url

  if (hlsUrl && Hls.isSupported()) {
    hls = new Hls()
    hls.loadSource(hlsUrl)
    hls.attachMedia(el)
  } else if (hlsUrl && el.canPlayType('application/vnd.apple.mpegurl')) {
    el.src = hlsUrl
  } else if (streamUrl) {
    el.src = streamUrl
  }
}

function onVideoLoaded() {
  const el = videoEl.value
  duration.value = el.duration || 0
  videoWidth.value = el.videoWidth
  videoHeight.value = el.videoHeight

  nextTick(() => {
    resizeCanvas()
    startRenderLoop()
  })
}

function resizeCanvas() {
  const canvas = canvasEl.value
  const wrapper = videoWrapper.value
  if (!canvas || !wrapper) return
  canvas.width = wrapper.clientWidth
  canvas.height = wrapper.clientHeight
}

function startRenderLoop() {
  const loop = () => {
    renderCanvas()
    animFrame = requestAnimationFrame(loop)
  }
  loop()
}

function onTimeUpdate() {
  currentTime.value = videoEl.value?.currentTime || 0
}

function togglePlay() {
  const el = videoEl.value
  if (!el) return
  if (el.paused) {
    el.play()
    isPlaying.value = true
  } else {
    el.pause()
    isPlaying.value = false
  }
}

function seek(e) {
  const rect = e.currentTarget.getBoundingClientRect()
  const pct = (e.clientX - rect.left) / rect.width
  const el = videoEl.value
  if (el && duration.value) {
    el.currentTime = pct * duration.value
  }
}

function formatTime(seconds) {
  if (!seconds || isNaN(seconds)) return '0:00'
  const m = Math.floor(seconds / 60)
  const s = Math.floor(seconds % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}

function getTimelineMarkerStyle(item) {
  if (!duration.value) return { display: 'none' }
  const start = item.entireVideo ? 0 : (item.start_time || 0)
  const end = item.entireVideo ? duration.value : (item.end_time || duration.value)
  const left = (start / duration.value) * 100
  const width = Math.max(0.5, ((end - start) / duration.value) * 100)
  return { left: left + '%', width: width + '%' }
}

// --- Canvas events ---

function getCanvasPercent(e) {
  const canvas = canvasEl.value
  if (!canvas) return { x: 0, y: 0 }
  const rect = canvas.getBoundingClientRect()
  return {
    x: ((e.clientX - rect.left) / rect.width) * 100,
    y: ((e.clientY - rect.top) / rect.height) * 100,
  }
}

function hitTest(pos) {
  // Check items in reverse (topmost first)
  for (let i = items.value.length - 1; i >= 0; i--) {
    const item = items.value[i]
    if (pos.x >= item.x && pos.x <= item.x + item.width &&
        pos.y >= item.y && pos.y <= item.y + item.height) {
      return item
    }
  }
  return null
}

function hitTestResize(pos, item) {
  if (!item) return null
  const handleSize = 2 // percentage
  const corners = [
    { name: 'se', x: item.x + item.width, y: item.y + item.height },
    { name: 'sw', x: item.x, y: item.y + item.height },
    { name: 'ne', x: item.x + item.width, y: item.y },
    { name: 'nw', x: item.x, y: item.y },
  ]
  for (const c of corners) {
    if (Math.abs(pos.x - c.x) < handleSize && Math.abs(pos.y - c.y) < handleSize) {
      return c.name
    }
  }
  return null
}

function onCanvasMouseDown(e) {
  const pos = getCanvasPercent(e)

  // Check if clicking a resize handle on the selected item
  if (selectedItem.value) {
    const handle = hitTestResize(pos, selectedItem.value)
    if (handle) {
      isResizing.value = true
      resizeHandle.value = handle
      return
    }
  }

  // Check if clicking an existing item
  const hit = hitTest(pos)
  if (hit) {
    selectedItemId.value = hit.id
    isDragging.value = true
    dragOffset.value = { x: pos.x - hit.x, y: pos.y - hit.y }
    return
  }

  // Start drawing (blur tool only)
  if (activeTool.value === 'blur') {
    isDrawing.value = true
    drawStart.value = pos
    drawCurrent.value = pos
    selectedItemId.value = null
  }
}

function onCanvasMouseMove(e) {
  const pos = getCanvasPercent(e)

  if (isDrawing.value) {
    drawCurrent.value = pos
    return
  }

  if (isDragging.value && selectedItem.value) {
    const item = selectedItem.value
    item.x = Math.max(0, Math.min(100 - item.width, pos.x - dragOffset.value.x))
    item.y = Math.max(0, Math.min(100 - item.height, pos.y - dragOffset.value.y))
    return
  }

  if (isResizing.value && selectedItem.value && resizeHandle.value) {
    const item = selectedItem.value
    const h = resizeHandle.value

    if (h.includes('e')) {
      item.width = Math.max(2, Math.min(100 - item.x, pos.x - item.x))
    }
    if (h.includes('w')) {
      const newX = Math.max(0, Math.min(item.x + item.width - 2, pos.x))
      item.width = item.width + (item.x - newX)
      item.x = newX
    }
    if (h.includes('s')) {
      item.height = Math.max(2, Math.min(100 - item.y, pos.y - item.y))
    }
    if (h.includes('n')) {
      const newY = Math.max(0, Math.min(item.y + item.height - 2, pos.y))
      item.height = item.height + (item.y - newY)
      item.y = newY
    }
  }
}

function onCanvasMouseUp() {
  if (isDrawing.value && drawStart.value && drawCurrent.value) {
    const x = Math.min(drawStart.value.x, drawCurrent.value.x)
    const y = Math.min(drawStart.value.y, drawCurrent.value.y)
    const w = Math.abs(drawCurrent.value.x - drawStart.value.x)
    const h = Math.abs(drawCurrent.value.y - drawStart.value.y)

    if (w > 1 && h > 1) {
      const newItem = {
        id: nextItemId++,
        type: 'blur',
        x: Math.round(x * 10) / 10,
        y: Math.round(y * 10) / 10,
        width: Math.round(w * 10) / 10,
        height: Math.round(h * 10) / 10,
        start_time: 0,
        end_time: duration.value,
        entireVideo: true,
      }
      items.value.push(newItem)
      selectedItemId.value = newItem.id
    }
  }

  isDrawing.value = false
  isDragging.value = false
  isResizing.value = false
  drawStart.value = null
  drawCurrent.value = null
  resizeHandle.value = null
}

function addOverlayFile(e) {
  const file = e.target.files?.[0]
  if (!file) return

  const fileIndex = overlayFiles.value.length
  overlayFiles.value.push(file)

  const newItem = {
    id: nextItemId++,
    type: 'overlay',
    fileName: file.name,
    fileIndex,
    x: 10,
    y: 10,
    width: 30,
    height: 30,
    start_time: 0,
    end_time: duration.value,
    entireVideo: true,
  }
  items.value.push(newItem)
  selectedItemId.value = newItem.id
  activeTool.value = 'overlay'

  // Reset file input
  e.target.value = ''
}

function deleteItem(id) {
  items.value = items.value.filter(i => i.id !== id)
  if (selectedItemId.value === id) selectedItemId.value = null
}

// --- Canvas rendering ---

function renderCanvas() {
  const canvas = canvasEl.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  const w = canvas.width
  const h = canvas.height

  if (w === 0 || h === 0) {
    resizeCanvas()
    return
  }

  ctx.clearRect(0, 0, w, h)

  // Draw items
  for (const item of items.value) {
    const px = (item.x / 100) * w
    const py = (item.y / 100) * h
    const pw = (item.width / 100) * w
    const ph = (item.height / 100) * h

    const isSelected = item.id === selectedItemId.value

    if (item.type === 'blur') {
      ctx.fillStyle = isSelected ? 'rgba(59, 130, 246, 0.3)' : 'rgba(59, 130, 246, 0.2)'
      ctx.fillRect(px, py, pw, ph)
      ctx.strokeStyle = isSelected ? '#f97316' : '#3b82f6'
      ctx.lineWidth = isSelected ? 2 : 1
      ctx.setLineDash(isSelected ? [] : [4, 4])
      ctx.strokeRect(px, py, pw, ph)
      ctx.setLineDash([])
    } else {
      ctx.fillStyle = isSelected ? 'rgba(34, 197, 94, 0.2)' : 'rgba(34, 197, 94, 0.1)'
      ctx.fillRect(px, py, pw, ph)
      ctx.strokeStyle = isSelected ? '#f97316' : '#22c55e'
      ctx.lineWidth = isSelected ? 2 : 1
      ctx.strokeRect(px, py, pw, ph)

      // Label
      ctx.fillStyle = '#22c55e'
      ctx.font = '11px sans-serif'
      ctx.fillText(item.fileName || 'Overlay', px + 4, py + 14)
    }

    // Resize handles for selected item
    if (isSelected) {
      const handleSize = 6
      ctx.fillStyle = '#f97316'
      const corners = [
        [px, py], [px + pw, py],
        [px, py + ph], [px + pw, py + ph],
      ]
      for (const [cx, cy] of corners) {
        ctx.fillRect(cx - handleSize / 2, cy - handleSize / 2, handleSize, handleSize)
      }
    }
  }

  // Draw in-progress rectangle
  if (isDrawing.value && drawStart.value && drawCurrent.value) {
    const sx = (Math.min(drawStart.value.x, drawCurrent.value.x) / 100) * w
    const sy = (Math.min(drawStart.value.y, drawCurrent.value.y) / 100) * h
    const sw = (Math.abs(drawCurrent.value.x - drawStart.value.x) / 100) * w
    const sh = (Math.abs(drawCurrent.value.y - drawStart.value.y) / 100) * h

    ctx.fillStyle = 'rgba(59, 130, 246, 0.2)'
    ctx.fillRect(sx, sy, sw, sh)
    ctx.strokeStyle = '#3b82f6'
    ctx.lineWidth = 2
    ctx.setLineDash([6, 3])
    ctx.strokeRect(sx, sy, sw, sh)
    ctx.setLineDash([])
  }
}

// --- Apply ---

async function applyEdits() {
  if (isApplying.value || !items.value.length) return

  isApplying.value = true
  applyProgress.value = 0

  try {
    const blurRegions = items.value
      .filter(i => i.type === 'blur')
      .map(i => ({
        x: i.x,
        y: i.y,
        width: i.width,
        height: i.height,
        start_time: i.entireVideo ? null : i.start_time,
        end_time: i.entireVideo ? null : i.end_time,
      }))

    const overlayConfigs = items.value
      .filter(i => i.type === 'overlay')
      .map(i => ({
        x: i.x,
        y: i.y,
        width: i.width,
        height: i.height,
        file_index: i.fileIndex,
        start_time: i.entireVideo ? null : i.start_time,
        end_time: i.entireVideo ? null : i.end_time,
      }))

    const files = overlayFiles.value

    await videoService.applyEdits(video.value.id, blurRegions, overlayConfigs, files)

    // Start polling
    pollTimer = setInterval(async () => {
      try {
        const status = await videoService.getEditStatus(video.value.id)
        applyProgress.value = status.progress || 0

        if (status.status === 'completed') {
          clearInterval(pollTimer)
          pollTimer = null
          isApplying.value = false
          showToast('Video edits applied successfully! A new copy was created.', 'success')
          const targetId = status.output_video_id || video.value.id
          router.push(`/video/${targetId}`)
        } else if (status.status === 'failed') {
          clearInterval(pollTimer)
          pollTimer = null
          isApplying.value = false
          showToast(status.error || 'Edit processing failed', 'error')
        }
      } catch {
        // ignore polling errors
      }
    }, 3000)
  } catch (e) {
    isApplying.value = false
    showToast(e.message || 'Failed to apply edits', 'error')
  }
}

function goBack() {
  if (video.value?.id) {
    router.push(`/video/${video.value.id}`)
  } else {
    router.back()
  }
}

// Watch for window resize
function onResize() {
  resizeCanvas()
}

onMounted(() => {
  window.addEventListener('resize', onResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize)
})
</script>
