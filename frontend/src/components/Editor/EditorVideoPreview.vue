<template>
  <div class="w-full h-full flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Background container: background + padding + shadow -->
    <div
      class="relative flex items-center justify-center transition-all duration-300 max-w-full max-h-full"
      :style="backgroundStyle"
    >
      <!-- Video container: roundness applied here -->
      <div
        ref="videoWrapper"
        class="relative overflow-hidden transition-all duration-300"
        :style="videoStyle"
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
          :style="{ cursor: activeTool === 'blur' ? 'crosshair' : activeTool === 'text' ? 'text' : 'default' }"
          @mousedown="canvas.onCanvasMouseDown"
          @mousemove="canvas.onCanvasMouseMove"
          @mouseup="canvas.onCanvasMouseUp"
          @mouseleave="canvas.onCanvasMouseUp"
          @touchstart.prevent="canvas.onTouchStart"
          @touchmove.prevent="canvas.onTouchMove"
          @touchend.prevent="canvas.onTouchEnd"
        ></canvas>

        <!-- Camera overlay (inside video container, draggable) -->
        <div
          v-if="video?.has_camera && video?.camera_url && cameraEnabled"
          class="absolute transition-none cursor-grab active:cursor-grabbing"
          :class="{ 'transition-all duration-300': !isDraggingCamera }"
          :style="cameraOverlayStyle"
          @mousedown.stop.prevent="startCameraDrag"
          @touchstart.stop.prevent="startCameraDragTouch"
        >
          <div class="w-full h-full overflow-hidden shadow-lg ring-1 ring-white/20" :style="{ borderRadius: cameraRoundness + 'px' }">
            <video
              ref="cameraVideoEl"
              :src="video.camera_url"
              class="w-full h-full object-cover pointer-events-none"
              muted playsinline preload="metadata"
            ></video>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import Hls from 'hls.js'
import { useEditorState } from '@/composables/useEditorState'
import { useEditorCanvas } from '@/composables/useEditorCanvas'

const state = useEditorState()
const canvas = useEditorCanvas()

const {
  video, videoEl, canvasEl, videoWrapper,
  videoReady, videoWidth, videoHeight,
  duration, currentTime, isPlaying, activeTool, videoWrapperStyle,
  styleBackground, stylePadding, styleRoundness, styleShadow,
  cameraEnabled, cameraPosition, cameraSize, cameraRoundness, cameraShape,
} = state

const cameraVideoEl = ref(null)
let hls = null

// Camera drag state
const isDraggingCamera = ref(false)
const cameraDragPos = ref({ x: null, y: null }) // percentage-based position (0-100)
let dragStart = { mouseX: 0, mouseY: 0, startX: 0, startY: 0 }

// Background container style
const backgroundStyle = computed(() => {
  const bg = styleBackground.value
  const pad = stylePadding.value

  let background = 'transparent'
  if (bg.type === 'solid') {
    background = bg.color
  } else if (bg.type === 'gradient') {
    const dir = bg.gradientDirection === 'br' ? '135deg' : bg.gradientDirection === 'r' ? '90deg' : '180deg'
    background = `linear-gradient(${dir}, ${bg.gradientFrom}, ${bg.gradientTo})`
  } else if (bg.type === 'image' && bg.imageUrl) {
    background = `url(${bg.imageUrl}) center/cover no-repeat`
  }

  const style = {
    background,
    padding: pad + 'px',
    borderRadius: '12px',
    maxWidth: '100%',
    maxHeight: '100%',
  }

  if (styleShadow.value && bg.type !== 'none') {
    style.boxShadow = `0 ${Math.max(pad * 0.2, 4)}px ${Math.max(pad * 0.7, 16)}px rgba(0,0,0,0.25)`
  }

  return style
})

// Video container style (roundness goes here)
const videoStyle = computed(() => {
  const style = {
    borderRadius: styleRoundness.value + 'px',
    ...videoWrapperStyle.value,
  }
  if (styleShadow.value && stylePadding.value > 0) {
    style.boxShadow = '0 4px 24px rgba(0,0,0,0.3)'
  }
  return style
})

// Get default position from quadrant preset
function getDefaultCameraPos() {
  const pos = cameraPosition.value
  const margin = 3 // percent from edge
  return {
    x: pos.includes('right') ? 100 - cameraSize.value - margin : margin,
    y: pos.includes('bottom') ? 100 - getCameraHeightPercent() - margin : margin,
  }
}

function getCameraHeightPercent() {
  const shape = cameraShape.value
  const size = cameraSize.value
  if (shape === 'circle' || shape === 'square') return size
  // portrait 3:4 — height is size * (4/3) but capped
  if (!videoWidth.value || !videoHeight.value) return size * 1.33
  const ar = videoWidth.value / videoHeight.value
  return size * (4 / 3) * ar
}

// Camera overlay positioning
const cameraOverlayStyle = computed(() => {
  const size = cameraSize.value
  const shape = cameraShape.value

  let aspectRatio = '3/4' // portrait
  if (shape === 'circle' || shape === 'square') aspectRatio = '1/1'

  // Use drag position if set, otherwise derive from quadrant
  const pos = cameraDragPos.value.x !== null ? cameraDragPos.value : getDefaultCameraPos()

  return {
    width: size + '%',
    aspectRatio,
    zIndex: 20,
    left: pos.x + '%',
    top: pos.y + '%',
  }
})

// Reset drag position when quadrant preset changes
watch(cameraPosition, () => {
  cameraDragPos.value = { x: null, y: null }
})

// Drag handlers
function startCameraDrag(e) {
  const wrapper = videoWrapper.value
  if (!wrapper) return
  isDraggingCamera.value = true

  const rect = wrapper.getBoundingClientRect()
  const currentPos = cameraDragPos.value.x !== null ? cameraDragPos.value : getDefaultCameraPos()

  dragStart = {
    mouseX: e.clientX,
    mouseY: e.clientY,
    startX: currentPos.x,
    startY: currentPos.y,
    width: rect.width,
    height: rect.height,
  }

  window.addEventListener('mousemove', onCameraDrag)
  window.addEventListener('mouseup', stopCameraDrag)
}

function startCameraDragTouch(e) {
  const touch = e.touches[0]
  if (!touch) return
  const wrapper = videoWrapper.value
  if (!wrapper) return
  isDraggingCamera.value = true

  const rect = wrapper.getBoundingClientRect()
  const currentPos = cameraDragPos.value.x !== null ? cameraDragPos.value : getDefaultCameraPos()

  dragStart = {
    mouseX: touch.clientX,
    mouseY: touch.clientY,
    startX: currentPos.x,
    startY: currentPos.y,
    width: rect.width,
    height: rect.height,
  }

  window.addEventListener('touchmove', onCameraDragTouch, { passive: false })
  window.addEventListener('touchend', stopCameraDrag)
}

function onCameraDrag(e) {
  const dx = ((e.clientX - dragStart.mouseX) / dragStart.width) * 100
  const dy = ((e.clientY - dragStart.mouseY) / dragStart.height) * 100
  applyDragDelta(dx, dy)
}

function onCameraDragTouch(e) {
  e.preventDefault()
  const touch = e.touches[0]
  if (!touch) return
  const dx = ((touch.clientX - dragStart.mouseX) / dragStart.width) * 100
  const dy = ((touch.clientY - dragStart.mouseY) / dragStart.height) * 100
  applyDragDelta(dx, dy)
}

function applyDragDelta(dx, dy) {
  const size = cameraSize.value
  const heightPct = getCameraHeightPercent()
  const x = Math.max(0, Math.min(100 - size, dragStart.startX + dx))
  const y = Math.max(0, Math.min(100 - heightPct, dragStart.startY + dy))
  cameraDragPos.value = { x, y }
}

function stopCameraDrag() {
  isDraggingCamera.value = false
  window.removeEventListener('mousemove', onCameraDrag)
  window.removeEventListener('mouseup', stopCameraDrag)
  window.removeEventListener('touchmove', onCameraDragTouch)
  window.removeEventListener('touchend', stopCameraDrag)
}

// Sync camera video with main video
watch(isPlaying, () => {
  const cam = cameraVideoEl.value
  const vid = videoEl.value
  if (!cam || !vid) return
  if (isPlaying.value) cam.play().catch(() => {})
  else cam.pause()
})

watch(currentTime, () => {
  const cam = cameraVideoEl.value
  const vid = videoEl.value
  if (!cam || !vid) return
  if (Math.abs(cam.currentTime - vid.currentTime) > 0.5) {
    cam.currentTime = vid.currentTime
  }
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
  nextTick(() => {
    canvas.resizeCanvas()
    canvas.startRenderLoop()
  })
}

function onTimeUpdate() {
  currentTime.value = videoEl.value?.currentTime || 0
}

function onResize() {
  canvas.resizeCanvas()
}

defineExpose({ initVideo })

onMounted(() => {
  window.addEventListener('resize', onResize)
})

onBeforeUnmount(() => {
  if (hls) {
    hls.destroy()
    hls = null
  }
  canvas.stopRenderLoop()
  window.removeEventListener('resize', onResize)
  stopCameraDrag()
})
</script>
