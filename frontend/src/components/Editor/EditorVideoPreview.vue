<template>
  <div class="w-full h-full flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Outer container: background + padding -->
    <div
      class="relative flex items-center justify-center transition-all duration-300 max-w-full max-h-full"
      :style="outerContainerStyle"
    >
      <!-- Video wrapper — only this gets roundness -->
      <div
        ref="videoWrapper"
        class="relative overflow-hidden transition-all duration-300 shadow-lg"
        :style="innerVideoStyle"
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
      </div>

      <!-- Camera overlay (inside the background container) -->
      <div
        v-if="video?.has_camera && video?.camera_url && cameraEnabled"
        class="absolute transition-all duration-300"
        :style="cameraOverlayStyle"
      >
        <div class="w-full h-full overflow-hidden shadow-lg ring-1 ring-white/20" :style="{ borderRadius: cameraRoundness + 'px' }">
          <video
            ref="cameraVideoEl"
            :src="video.camera_url"
            class="w-full h-full object-cover"
            muted playsinline preload="metadata"
          ></video>
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

// Background style for the outer container
const outerContainerStyle = computed(() => {
  const bg = styleBackground.value
  const pad = stylePadding.value

  // No background mode — just show video directly
  if (bg.type === 'none' && pad === 0) {
    return { maxWidth: '100%', maxHeight: '100%' }
  }

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

// Inner video container style (the actual video with roundness)
const innerVideoStyle = computed(() => {
  const style = {
    borderRadius: styleRoundness.value + 'px',
    ...videoWrapperStyle.value,
  }
  if (styleShadow.value && stylePadding.value > 0) {
    style.boxShadow = `0 4px 24px rgba(0,0,0,0.3)`
  }
  return style
})

// Camera overlay positioning
const cameraOverlayStyle = computed(() => {
  const pad = stylePadding.value
  const size = cameraSize.value
  const pos = cameraPosition.value
  const shape = cameraShape.value

  // Width as percentage of the video area
  const widthPx = size + '%'
  let aspectRatio = '3/4' // portrait
  if (shape === 'circle') aspectRatio = '1/1'
  if (shape === 'square') aspectRatio = '1/1'

  const margin = Math.max(pad + 8, 12) + 'px'
  const style = {
    width: widthPx,
    aspectRatio,
    zIndex: 20,
  }

  // Position based on quadrant
  if (pos.includes('bottom')) style.bottom = margin
  else style.top = margin
  if (pos.includes('right')) style.right = margin
  else style.left = margin

  return style
})

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
})
</script>
