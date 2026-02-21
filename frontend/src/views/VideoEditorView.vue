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
            :disabled="isApplying || !items.length"
            class="px-4 py-1.5 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg text-xs font-medium shadow-sm transition-colors flex items-center gap-2"
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
      <div class="flex flex-1 overflow-hidden relative z-10">

        <!-- Left Panel -->
        <div class="w-[260px] bg-white border-r border-gray-200 flex-col flex-shrink-0 hidden lg:flex">
          <!-- Tool Tabs -->
          <div class="p-3 border-b border-gray-100">
            <div class="flex items-center p-1 bg-gray-100 rounded-lg">
              <button
                v-for="tool in ['blur', 'overlay', 'text']"
                :key="tool"
                @click="activeTool = tool"
                :class="activeTool === tool ? 'text-gray-900 bg-white shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                class="flex-1 px-2 py-1.5 rounded-[6px] text-[13px] font-medium transition-all capitalize"
              >{{ tool }}</button>
            </div>
          </div>

          <!-- Action -->
          <div v-if="activeTool === 'overlay'" class="p-3 border-b border-gray-100">
            <label class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Upload Video
              <input type="file" accept="video/*" class="hidden" @change="addOverlayFile" />
            </label>
          </div>
          <div v-if="activeTool === 'text'" class="p-3 border-b border-gray-100">
            <button @click="addTextItem" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Add Text
            </button>
          </div>

          <!-- Items -->
          <div class="flex-1 overflow-y-auto p-3">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Items ({{ items.length }})</p>
            <div v-if="!items.length" class="text-xs text-gray-400 text-center py-6">
              {{ activeTool === 'blur' ? 'Draw on the video to add blur' : activeTool === 'text' ? 'Click "Add Text" or click on the video' : 'Upload a video overlay' }}
            </div>
            <div
              v-for="item in items"
              :key="item.id"
              @click="selectItem(item.id)"
              :class="selectedItemId === item.id ? 'border-orange-400 bg-orange-50' : 'border-gray-200/80 hover:border-gray-300 hover:bg-gray-50'"
              class="flex items-center gap-2.5 px-3 py-2 border rounded-lg mb-1.5 cursor-pointer transition-all"
            >
              <div
                :class="item.type === 'blur' ? 'bg-blue-100 text-blue-600' : item.type === 'text' ? 'bg-purple-100 text-purple-600' : 'bg-green-100 text-green-600'"
                class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
              >
                <svg v-if="item.type === 'blur'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243"/></svg>
                <svg v-else-if="item.type === 'text'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-medium text-gray-900 truncate">{{ getItemLabel(item) }}</p>
                <p class="text-[10px] text-gray-400">{{ item.entireVideo ? 'Entire video' : `${formatTime(item.start_time)} – ${formatTime(item.end_time)}` }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Center -->
        <div class="flex-1 flex flex-col min-w-0">

          <!-- Mobile tools strip -->
          <div class="lg:hidden flex items-center gap-2 px-3 py-2 bg-white border-b border-gray-100 overflow-x-auto">
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

          <!-- Video -->
          <div class="flex-1 flex items-center justify-center p-4 relative">
            <div ref="videoWrapper" class="relative bg-black rounded-xl shadow-2xl ring-1 ring-black/10 overflow-hidden" :style="videoWrapperStyle">
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
                :style="{ cursor: activeTool === 'blur' ? 'crosshair' : activeTool === 'text' ? 'text' : 'default' }"
                @mousedown="onCanvasMouseDown"
                @mousemove="onCanvasMouseMove"
                @mouseup="onCanvasMouseUp"
                @mouseleave="onCanvasMouseUp"
                @touchstart.prevent="onTouchStart"
                @touchmove.prevent="onTouchMove"
                @touchend.prevent="onTouchEnd"
              ></canvas>
            </div>
          </div>

          <!-- Timeline -->
          <div class="h-14 bg-white border-t border-gray-200 flex items-center px-4 gap-3 flex-shrink-0">
            <button @click="togglePlay" class="text-gray-500 hover:text-orange-600 transition-colors flex-shrink-0">
              <svg v-if="!isPlaying" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
              <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
            </button>
            <span class="text-xs text-gray-500 w-10 text-center font-mono flex-shrink-0">{{ displayCurrentTime }}</span>
            <div class="flex-1 relative h-1.5 bg-gray-200 rounded-full cursor-pointer group" ref="seekBar" @mousedown="onSeekMouseDown">
              <div class="absolute inset-y-0 left-0 bg-orange-500 rounded-full" :style="{ width: seekPercent + '%' }"></div>
              <div v-for="item in items" :key="'tm-'+item.id"
                class="absolute top-1/2 -translate-y-1/2 h-3 rounded-sm opacity-30"
                :class="item.type === 'blur' ? 'bg-blue-500' : item.type === 'text' ? 'bg-purple-500' : 'bg-green-500'"
                :style="getTimelineMarkerStyle(item)"></div>
              <div class="absolute top-1/2 -translate-y-1/2 w-3 h-3 bg-white rounded-full shadow ring-2 ring-orange-500 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" :style="{ left: seekPercent + '%', marginLeft: '-6px' }"></div>
            </div>
            <span class="text-xs text-gray-500 w-10 text-center font-mono flex-shrink-0">{{ displayDuration }}</span>
          </div>
        </div>

        <!-- Right Panel: Properties -->
        <transition name="slide-right">
          <div v-if="selectedItem" class="w-[260px] bg-white border-l border-gray-200 flex flex-col flex-shrink-0 absolute lg:relative right-0 top-0 bottom-0 z-30 shadow-xl lg:shadow-none">
            <div class="p-3 border-b border-gray-100 flex items-center justify-between">
              <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Properties</p>
              <button @click="selectedItemId = null" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <div class="p-3 space-y-4 overflow-y-auto flex-1">
              <!-- Position -->
              <div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Position</p>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="text-[10px] text-gray-400 block mb-0.5">X %</label>
                    <input type="number" v-model.number="selectedItem.x" min="0" max="100" step="0.1" @input="renderCanvas"
                      class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all" />
                  </div>
                  <div>
                    <label class="text-[10px] text-gray-400 block mb-0.5">Y %</label>
                    <input type="number" v-model.number="selectedItem.y" min="0" max="100" step="0.1" @input="renderCanvas"
                      class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all" />
                  </div>
                </div>
              </div>

              <!-- Size -->
              <div v-if="selectedItem.type !== 'text'">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Size</p>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="text-[10px] text-gray-400 block mb-0.5">Width %</label>
                    <input type="number" v-model.number="selectedItem.width" min="1" max="100" step="0.1" @input="renderCanvas"
                      class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all" />
                  </div>
                  <div>
                    <label class="text-[10px] text-gray-400 block mb-0.5">Height %</label>
                    <input type="number" v-model.number="selectedItem.height" min="1" max="100" step="0.1" @input="renderCanvas"
                      class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all" />
                  </div>
                </div>
              </div>

              <!-- Text Content -->
              <div v-if="selectedItem.type === 'text'">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Content</p>
                <textarea v-model="selectedItem.text" rows="3" maxlength="200" @input="renderCanvas"
                  class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all resize-none"></textarea>
                <p class="text-[10px] text-gray-400 mt-1">{{ (selectedItem.text || '').length }}/200</p>
              </div>

              <!-- Text Style -->
              <div v-if="selectedItem.type === 'text'">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Style</p>
                <div class="space-y-2">
                  <div>
                    <label class="text-[10px] text-gray-400 block mb-1">Font Size: {{ selectedItem.font_size }}px</label>
                    <input type="range" v-model.number="selectedItem.font_size" min="12" max="120" step="1" @input="renderCanvas" class="w-full accent-orange-500" />
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="text-[10px] text-gray-400 block mb-0.5">Color</label>
                      <input type="color" v-model="selectedItem.font_color" @input="renderCanvas" class="w-full h-8 rounded-lg border border-gray-200 cursor-pointer" />
                    </div>
                    <div>
                      <label class="text-[10px] text-gray-400 block mb-0.5">Background</label>
                      <div class="flex items-center gap-1">
                        <input type="color" v-model="selectedItem.background_color" :disabled="!selectedItem.has_background" @input="renderCanvas"
                          class="flex-1 h-8 rounded-lg border border-gray-200 cursor-pointer disabled:opacity-30" />
                        <input type="checkbox" v-model="selectedItem.has_background" @change="renderCanvas"
                          class="rounded border-gray-300 text-orange-500 focus:ring-orange-500" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Time Range -->
              <div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Time Range</p>
                <label class="flex items-center gap-2 mb-2 cursor-pointer">
                  <input type="checkbox" v-model="selectedItem.entireVideo" class="rounded border-gray-300 text-orange-500 focus:ring-orange-500" />
                  <span class="text-xs text-gray-600">Entire video</span>
                </label>
                <div v-if="!selectedItem.entireVideo" class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="text-[10px] text-gray-400 block mb-0.5">Start (s)</label>
                    <input type="number" v-model.number="selectedItem.start_time" min="0" :max="duration" step="0.1"
                      class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all" />
                  </div>
                  <div>
                    <label class="text-[10px] text-gray-400 block mb-0.5">End (s)</label>
                    <input type="number" v-model.number="selectedItem.end_time" min="0" :max="duration" step="0.1"
                      class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all" />
                  </div>
                </div>
              </div>

              <!-- Delete -->
              <button @click="deleteItem(selectedItem.id)"
                class="w-full px-4 py-2 text-sm font-medium text-red-600 bg-white rounded-lg border border-red-200 hover:bg-red-50 transition-colors">
                Delete
              </button>
            </div>
          </div>
        </transition>
      </div>
    </template>

    <!-- Processing Overlay -->
    <div v-if="isApplying" class="fixed inset-0 z-[60] flex items-center justify-center">
      <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm"></div>
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 relative z-10 border border-gray-100 text-center">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-500 border-t-transparent mx-auto mb-4"></div>
        <h3 class="text-sm font-semibold text-gray-900 mb-1">Processing Edits</h3>
        <p class="text-xs text-gray-500 mb-4">This may take a few minutes.</p>
        <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2">
          <div class="bg-orange-500 h-1.5 rounded-full transition-all duration-500" :style="{ width: applyProgress + '%' }"></div>
        </div>
        <p class="text-[11px] text-gray-400">{{ applyProgress }}%</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import videoService from '@/services/videoService'
import { useToast } from '@/services/toastService'
import Hls from 'hls.js'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(true)
const error = ref(null)
const video = ref(null)
const videoEl = ref(null)
const canvasEl = ref(null)
const videoWrapper = ref(null)
const seekBar = ref(null)

const activeTool = ref('blur')
const items = ref([])
const selectedItemId = ref(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const videoReady = ref(false)
const videoWidth = ref(0)
const videoHeight = ref(0)

const isApplying = ref(false)
const applyProgress = ref(0)
let pollTimer = null
let hls = null
let animFrame = null
let nextItemId = 1

const overlayFiles = ref([])

// Interaction state
const isDrawing = ref(false)
const drawStart = ref(null)
const drawCurrent = ref(null)
const isDragging = ref(false)
const dragOffset = ref({ x: 0, y: 0 })
const isResizing = ref(false)
const resizeHandle = ref(null)
const isSeeking = ref(false)

const selectedItem = computed(() => {
  if (!selectedItemId.value) return null
  return items.value.find(i => i.id === selectedItemId.value) || null
})

const seekPercent = computed(() => {
  if (!duration.value) return 0
  return (currentTime.value / duration.value) * 100
})

const displayCurrentTime = computed(() => {
  if (!videoReady.value) return '--:--'
  return formatTime(currentTime.value)
})

const displayDuration = computed(() => {
  if (!videoReady.value && !video.value?.duration) return '--:--'
  return formatTime(duration.value || video.value?.duration || 0)
})

const videoWrapperStyle = computed(() => {
  if (!videoWidth.value || !videoHeight.value) return { width: '100%', maxHeight: '100%' }
  return {
    aspectRatio: `${videoWidth.value / videoHeight.value}`,
    maxWidth: '100%',
    maxHeight: '100%',
  }
})

function getItemLabel(item) {
  const idx = items.value.filter(i => i.type === item.type).indexOf(item) + 1
  if (item.type === 'blur') return `Blur ${idx}`
  if (item.type === 'text') return (item.text || 'Text').substring(0, 20)
  return item.fileName || `Overlay ${idx}`
}

function selectItem(id) {
  selectedItemId.value = id
}

// --- Lifecycle ---

onMounted(async () => {
  window.addEventListener('resize', onResize)
  try {
    const data = await videoService.getVideo(route.params.id)
    if (!data) { error.value = 'Video not found'; return }
    video.value = data
    if (data.duration) duration.value = data.duration
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
  window.removeEventListener('resize', onResize)
  document.removeEventListener('mousemove', onSeekMouseMove)
  document.removeEventListener('mouseup', onSeekMouseUp)
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
  if (el.duration && !isNaN(el.duration)) duration.value = el.duration
  videoWidth.value = el.videoWidth
  videoHeight.value = el.videoHeight
  videoReady.value = true
  nextTick(() => { resizeCanvas(); startRenderLoop() })
}

function resizeCanvas() {
  const canvas = canvasEl.value
  const wrapper = videoWrapper.value
  if (!canvas || !wrapper) return
  canvas.width = wrapper.clientWidth
  canvas.height = wrapper.clientHeight
}

function startRenderLoop() {
  const loop = () => { renderCanvas(); animFrame = requestAnimationFrame(loop) }
  loop()
}

function onTimeUpdate() { currentTime.value = videoEl.value?.currentTime || 0 }

function togglePlay() {
  const el = videoEl.value
  if (!el) return
  if (el.paused) { el.play(); isPlaying.value = true }
  else { el.pause(); isPlaying.value = false }
}

// --- Seek ---

function onSeekMouseDown(e) {
  isSeeking.value = true
  seekToPosition(e)
  document.addEventListener('mousemove', onSeekMouseMove)
  document.addEventListener('mouseup', onSeekMouseUp)
}

function onSeekMouseMove(e) { if (isSeeking.value) seekToPosition(e) }

function onSeekMouseUp() {
  isSeeking.value = false
  document.removeEventListener('mousemove', onSeekMouseMove)
  document.removeEventListener('mouseup', onSeekMouseUp)
}

function seekToPosition(e) {
  const bar = seekBar.value
  if (!bar) return
  const rect = bar.getBoundingClientRect()
  const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
  const el = videoEl.value
  if (el && duration.value) { el.currentTime = pct * duration.value; currentTime.value = el.currentTime }
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
  return { left: (start / duration.value) * 100 + '%', width: Math.max(0.5, ((end - start) / duration.value) * 100) + '%' }
}

// --- Canvas helpers ---

function getCanvasPercent(e) {
  const canvas = canvasEl.value
  if (!canvas) return { x: 0, y: 0 }
  const rect = canvas.getBoundingClientRect()
  return { x: ((e.clientX - rect.left) / rect.width) * 100, y: ((e.clientY - rect.top) / rect.height) * 100 }
}

function getTouchPercent(e) {
  const t = e.touches[0] || e.changedTouches[0]
  if (!t) return { x: 0, y: 0 }
  const canvas = canvasEl.value
  if (!canvas) return { x: 0, y: 0 }
  const rect = canvas.getBoundingClientRect()
  return { x: ((t.clientX - rect.left) / rect.width) * 100, y: ((t.clientY - rect.top) / rect.height) * 100 }
}

function hitTest(pos) {
  for (let i = items.value.length - 1; i >= 0; i--) {
    const item = items.value[i]
    if (item.type === 'text') {
      if (pos.x >= item.x - 1 && pos.x <= item.x + 12 && pos.y >= item.y - 5 && pos.y <= item.y + 2) return item
    } else {
      if (pos.x >= item.x && pos.x <= item.x + item.width && pos.y >= item.y && pos.y <= item.y + item.height) return item
    }
  }
  return null
}

function hitTestResize(pos, item) {
  if (!item || item.type === 'text') return null
  const hs = 2
  for (const c of [
    { name: 'se', x: item.x + item.width, y: item.y + item.height },
    { name: 'sw', x: item.x, y: item.y + item.height },
    { name: 'ne', x: item.x + item.width, y: item.y },
    { name: 'nw', x: item.x, y: item.y },
  ]) { if (Math.abs(pos.x - c.x) < hs && Math.abs(pos.y - c.y) < hs) return c.name }
  return null
}

// --- Canvas mouse ---

function onCanvasMouseDown(e) {
  const pos = getCanvasPercent(e)

  if (selectedItem.value) {
    const handle = hitTestResize(pos, selectedItem.value)
    if (handle) { isResizing.value = true; resizeHandle.value = handle; return }
  }

  const hit = hitTest(pos)
  if (hit) { selectedItemId.value = hit.id; isDragging.value = true; dragOffset.value = { x: pos.x - hit.x, y: pos.y - hit.y }; return }

  if (activeTool.value === 'text') {
    const newItem = { id: nextItemId++, type: 'text', text: 'Text', x: Math.round(pos.x * 10) / 10, y: Math.round(pos.y * 10) / 10, font_size: 32, font_color: '#ffffff', background_color: '#000000', has_background: true, start_time: 0, end_time: duration.value, entireVideo: true }
    items.value.push(newItem); selectedItemId.value = newItem.id; return
  }

  if (activeTool.value === 'blur') { isDrawing.value = true; drawStart.value = pos; drawCurrent.value = pos; selectedItemId.value = null }
}

function onCanvasMouseMove(e) {
  const pos = getCanvasPercent(e)
  if (isDrawing.value) { drawCurrent.value = pos; return }
  if (isDragging.value && selectedItem.value) {
    const item = selectedItem.value
    if (item.type === 'text') { item.x = clamp(pos.x - dragOffset.value.x, 0, 95); item.y = clamp(pos.y - dragOffset.value.y, 0, 95) }
    else { item.x = clamp(pos.x - dragOffset.value.x, 0, 100 - item.width); item.y = clamp(pos.y - dragOffset.value.y, 0, 100 - item.height) }
    return
  }
  if (isResizing.value && selectedItem.value && resizeHandle.value) { applyResize(pos); return }
}

function onCanvasMouseUp() {
  if (isDrawing.value && drawStart.value && drawCurrent.value) {
    const x = Math.min(drawStart.value.x, drawCurrent.value.x)
    const y = Math.min(drawStart.value.y, drawCurrent.value.y)
    const w = Math.abs(drawCurrent.value.x - drawStart.value.x)
    const h = Math.abs(drawCurrent.value.y - drawStart.value.y)
    if (w > 1 && h > 1) {
      const newItem = { id: nextItemId++, type: 'blur', x: round1(x), y: round1(y), width: round1(w), height: round1(h), start_time: 0, end_time: duration.value, entireVideo: true }
      items.value.push(newItem); selectedItemId.value = newItem.id
    }
  }
  isDrawing.value = false; isDragging.value = false; isResizing.value = false
  drawStart.value = null; drawCurrent.value = null; resizeHandle.value = null
}

// --- Touch ---

function onTouchStart(e) {
  const pos = getTouchPercent(e)
  if (selectedItem.value) { const h = hitTestResize(pos, selectedItem.value); if (h) { isResizing.value = true; resizeHandle.value = h; return } }
  const hit = hitTest(pos)
  if (hit) { selectedItemId.value = hit.id; isDragging.value = true; dragOffset.value = { x: pos.x - hit.x, y: pos.y - hit.y }; return }
  if (activeTool.value === 'text') {
    const newItem = { id: nextItemId++, type: 'text', text: 'Text', x: round1(pos.x), y: round1(pos.y), font_size: 32, font_color: '#ffffff', background_color: '#000000', has_background: true, start_time: 0, end_time: duration.value, entireVideo: true }
    items.value.push(newItem); selectedItemId.value = newItem.id; return
  }
  if (activeTool.value === 'blur') { isDrawing.value = true; drawStart.value = pos; drawCurrent.value = pos; selectedItemId.value = null }
}

function onTouchMove(e) {
  const pos = getTouchPercent(e)
  if (isDrawing.value) { drawCurrent.value = pos; return }
  if (isDragging.value && selectedItem.value) {
    const item = selectedItem.value
    if (item.type === 'text') { item.x = clamp(pos.x - dragOffset.value.x, 0, 95); item.y = clamp(pos.y - dragOffset.value.y, 0, 95) }
    else { item.x = clamp(pos.x - dragOffset.value.x, 0, 100 - item.width); item.y = clamp(pos.y - dragOffset.value.y, 0, 100 - item.height) }
    return
  }
  if (isResizing.value && selectedItem.value && resizeHandle.value) applyResize(pos)
}

function onTouchEnd() { onCanvasMouseUp() }

// --- Resize helper ---

function applyResize(pos) {
  const item = selectedItem.value
  const h = resizeHandle.value
  if (h.includes('e')) item.width = Math.max(2, Math.min(100 - item.x, pos.x - item.x))
  if (h.includes('w')) { const nx = clamp(pos.x, 0, item.x + item.width - 2); item.width += item.x - nx; item.x = nx }
  if (h.includes('s')) item.height = Math.max(2, Math.min(100 - item.y, pos.y - item.y))
  if (h.includes('n')) { const ny = clamp(pos.y, 0, item.y + item.height - 2); item.height += item.y - ny; item.y = ny }
}

function clamp(v, min, max) { return Math.max(min, Math.min(max, v)) }
function round1(v) { return Math.round(v * 10) / 10 }

// --- Add items ---

function addTextItem() {
  const newItem = { id: nextItemId++, type: 'text', text: 'Text', x: 10, y: 50, font_size: 32, font_color: '#ffffff', background_color: '#000000', has_background: true, start_time: 0, end_time: duration.value, entireVideo: true }
  items.value.push(newItem); selectedItemId.value = newItem.id; activeTool.value = 'text'
}

function addOverlayFile(e) {
  const file = e.target.files?.[0]
  if (!file) return
  const fileIndex = overlayFiles.value.length
  overlayFiles.value.push(file)
  const newItem = { id: nextItemId++, type: 'overlay', fileName: file.name, fileIndex, x: 10, y: 10, width: 30, height: 30, start_time: 0, end_time: duration.value, entireVideo: true }
  items.value.push(newItem); selectedItemId.value = newItem.id; activeTool.value = 'overlay'
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
  if (w === 0 || h === 0) { resizeCanvas(); return }

  ctx.clearRect(0, 0, w, h)

  for (const item of items.value) {
    const isSelected = item.id === selectedItemId.value

    if (item.type === 'text') {
      const px = (item.x / 100) * w
      const py = (item.y / 100) * h
      const fontSize = Math.max(10, (item.font_size / 100) * h * 0.8)

      ctx.font = `bold ${fontSize}px sans-serif`
      const metrics = ctx.measureText(item.text || 'Text')
      const tw = metrics.width + 16
      const th = fontSize + 12
      const bx = px - 4
      const by = py - fontSize - 2

      if (item.has_background && item.background_color) {
        ctx.fillStyle = item.background_color + '80'
        ctx.fillRect(bx, by, tw, th)
      }

      ctx.fillStyle = item.font_color || '#ffffff'
      ctx.fillText(item.text || 'Text', px + 4, py)

      ctx.strokeStyle = isSelected ? '#f97316' : '#a855f7'
      ctx.lineWidth = isSelected ? 2 : 1
      ctx.setLineDash(isSelected ? [] : [4, 4])
      ctx.strokeRect(bx, by, tw, th)
      ctx.setLineDash([])

    } else {
      const px = (item.x / 100) * w
      const py = (item.y / 100) * h
      const pw = (item.width / 100) * w
      const ph = (item.height / 100) * h

      if (item.type === 'blur') {
        ctx.fillStyle = isSelected ? 'rgba(59,130,246,0.25)' : 'rgba(59,130,246,0.15)'
        ctx.fillRect(px, py, pw, ph)
        ctx.strokeStyle = isSelected ? '#f97316' : '#3b82f6'
        ctx.lineWidth = isSelected ? 2 : 1
        ctx.setLineDash(isSelected ? [] : [4, 4])
        ctx.strokeRect(px, py, pw, ph)
        ctx.setLineDash([])
      } else {
        ctx.fillStyle = isSelected ? 'rgba(34,197,94,0.15)' : 'rgba(34,197,94,0.08)'
        ctx.fillRect(px, py, pw, ph)
        ctx.strokeStyle = isSelected ? '#f97316' : '#22c55e'
        ctx.lineWidth = isSelected ? 2 : 1
        ctx.strokeRect(px, py, pw, ph)
        ctx.fillStyle = '#22c55e'
        ctx.font = '11px sans-serif'
        ctx.fillText(item.fileName || 'Overlay', px + 4, py + 14)
      }

      if (isSelected) {
        ctx.fillStyle = '#f97316'
        for (const [cx, cy] of [[px, py], [px + pw, py], [px, py + ph], [px + pw, py + ph]]) {
          ctx.fillRect(cx - 3, cy - 3, 6, 6)
        }
      }
    }
  }

  // Drawing preview
  if (isDrawing.value && drawStart.value && drawCurrent.value) {
    const sx = (Math.min(drawStart.value.x, drawCurrent.value.x) / 100) * w
    const sy = (Math.min(drawStart.value.y, drawCurrent.value.y) / 100) * h
    const sw = (Math.abs(drawCurrent.value.x - drawStart.value.x) / 100) * w
    const sh = (Math.abs(drawCurrent.value.y - drawStart.value.y) / 100) * h
    ctx.fillStyle = 'rgba(59,130,246,0.15)'
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

    await videoService.applyEdits(video.value.id, blurRegions, overlayConfigs, overlayFiles.value, textOverlays)

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
  if (video.value?.id) router.push(`/video/${video.value.id}`)
  else router.back()
}

function onResize() { resizeCanvas() }
</script>

<style scoped>
.slide-right-enter-active, .slide-right-leave-active { transition: transform 0.2s ease, opacity 0.2s ease; }
.slide-right-enter-from, .slide-right-leave-to { transform: translateX(100%); opacity: 0; }
</style>
