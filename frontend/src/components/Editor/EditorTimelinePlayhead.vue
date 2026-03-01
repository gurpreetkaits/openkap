<template>
  <div
    class="absolute top-0 bottom-0 w-0.5 bg-orange-500 z-20 pointer-events-auto cursor-col-resize"
    :style="{ left: position + 'px' }"
    @mousedown.stop="startDrag"
  >
    <!-- Playhead handle -->
    <div class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-orange-500 rounded-sm rotate-45 -mt-1"></div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useEditorState } from '@/composables/useEditorState'

const props = defineProps({
  pixelsPerSecond: { type: Number, required: true },
})

const emit = defineEmits(['seek'])

const { currentTime } = useEditorState()

const position = computed(() => currentTime.value * props.pixelsPerSecond)

function startDrag(e) {
  const startX = e.clientX
  const startTime = currentTime.value

  function onMove(e) {
    const dx = e.clientX - startX
    const dt = dx / props.pixelsPerSecond
    emit('seek', startTime + dt)
  }

  function onUp() {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}
</script>
