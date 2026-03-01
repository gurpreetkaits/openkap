<template>
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
        @mousedown="canvas.onCanvasMouseDown"
        @mousemove="canvas.onCanvasMouseMove"
        @mouseup="canvas.onCanvasMouseUp"
        @mouseleave="canvas.onCanvasMouseUp"
        @touchstart.prevent="canvas.onTouchStart"
        @touchmove.prevent="canvas.onTouchMove"
        @touchend.prevent="canvas.onTouchEnd"
      ></canvas>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, nextTick } from 'vue'
import Hls from 'hls.js'
import { useEditorState } from '@/composables/useEditorState'
import { useEditorCanvas } from '@/composables/useEditorCanvas'

const state = useEditorState()
const canvas = useEditorCanvas()

const {
  video, videoEl, canvasEl, videoWrapper,
  videoReady, videoWidth, videoHeight,
  duration, currentTime, isPlaying, activeTool, videoWrapperStyle,
} = state

let hls = null

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
