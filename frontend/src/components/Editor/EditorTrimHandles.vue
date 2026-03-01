<template>
  <template v-if="trimEnabled">
    <!-- Grey overlay: left trimmed region -->
    <div
      class="absolute top-0 bottom-0 bg-gray-900/20 z-10 pointer-events-none"
      :style="{ left: 0, width: leftPosition + 'px' }"
    ></div>

    <!-- Grey overlay: right trimmed region -->
    <div
      class="absolute top-0 bottom-0 bg-gray-900/20 z-10 pointer-events-none"
      :style="{ left: rightPosition + 'px', right: 0 }"
    ></div>

    <!-- Left trim handle -->
    <div
      class="absolute top-0 bottom-0 w-1.5 bg-yellow-500 z-20 cursor-ew-resize hover:bg-yellow-400 transition-colors"
      :style="{ left: leftPosition + 'px' }"
      @mousedown.stop="startDrag('left', $event)"
    >
      <div class="absolute top-1/2 -translate-y-1/2 -right-1 w-3 h-6 bg-yellow-500 rounded-r flex items-center justify-center">
        <div class="w-0.5 h-3 bg-yellow-800 rounded-full"></div>
      </div>
    </div>

    <!-- Right trim handle -->
    <div
      class="absolute top-0 bottom-0 w-1.5 bg-yellow-500 z-20 cursor-ew-resize hover:bg-yellow-400 transition-colors"
      :style="{ left: (rightPosition - 6) + 'px' }"
      @mousedown.stop="startDrag('right', $event)"
    >
      <div class="absolute top-1/2 -translate-y-1/2 -left-1 w-3 h-6 bg-yellow-500 rounded-l flex items-center justify-center">
        <div class="w-0.5 h-3 bg-yellow-800 rounded-full"></div>
      </div>
    </div>
  </template>
</template>

<script setup>
import { computed } from 'vue'
import { useEditorState } from '@/composables/useEditorState'

const props = defineProps({
  pixelsPerSecond: { type: Number, required: true },
})

const { trimEnabled, trimStart, trimEnd, duration } = useEditorState()

const leftPosition = computed(() => trimStart.value * props.pixelsPerSecond)
const rightPosition = computed(() => trimEnd.value * props.pixelsPerSecond)

function startDrag(handle, e) {
  const startX = e.clientX
  const origStart = trimStart.value
  const origEnd = trimEnd.value

  function onMove(e) {
    const dx = e.clientX - startX
    const dt = dx / props.pixelsPerSecond

    if (handle === 'left') {
      trimStart.value = Math.max(0, Math.min(origEnd - 0.5, origStart + dt))
    } else {
      trimEnd.value = Math.min(duration.value, Math.max(origStart + 0.5, origEnd + dt))
    }
  }

  function onUp() {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}
</script>
