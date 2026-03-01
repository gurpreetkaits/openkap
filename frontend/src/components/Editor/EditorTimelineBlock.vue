<template>
  <div
    class="absolute h-6 rounded cursor-pointer flex items-center overflow-hidden text-[10px] font-medium select-none group"
    :class="[
      isSelected ? 'ring-2 ring-orange-500 z-10' : 'z-0',
      colorClasses.bg,
      colorClasses.text,
    ]"
    :style="blockStyle"
    @mousedown.stop="onMouseDown"
    @click.stop="$emit('select', item.id)"
  >
    <!-- Left resize handle -->
    <div
      class="absolute left-0 top-0 bottom-0 w-1.5 cursor-ew-resize opacity-0 group-hover:opacity-100 rounded-l"
      :class="colorClasses.handle"
      @mousedown.stop="onResizeStart('left', $event)"
    ></div>

    <!-- Label -->
    <span class="truncate px-2">{{ label }}</span>

    <!-- Right resize handle -->
    <div
      class="absolute right-0 top-0 bottom-0 w-1.5 cursor-ew-resize opacity-0 group-hover:opacity-100 rounded-r"
      :class="colorClasses.handle"
      @mousedown.stop="onResizeStart('right', $event)"
    ></div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useEditorState } from '@/composables/useEditorState'

const props = defineProps({
  item: { type: Object, required: true },
  pixelsPerSecond: { type: Number, required: true },
  isSelected: { type: Boolean, default: false },
})

const emit = defineEmits(['select', 'update'])

const { duration, getItemLabel } = useEditorState()

const label = computed(() => getItemLabel(props.item))

const colorClasses = computed(() => {
  const type = props.item.type
  if (type === 'blur') return { bg: 'bg-blue-200', text: 'text-blue-800', handle: 'bg-blue-400' }
  if (type === 'text') return { bg: 'bg-purple-200', text: 'text-purple-800', handle: 'bg-purple-400' }
  return { bg: 'bg-green-200', text: 'text-green-800', handle: 'bg-green-400' }
})

const blockStyle = computed(() => {
  const start = props.item.entireVideo ? 0 : (props.item.start_time || 0)
  const end = props.item.entireVideo ? duration.value : (props.item.end_time || duration.value)
  return {
    left: (start * props.pixelsPerSecond) + 'px',
    width: Math.max(8, (end - start) * props.pixelsPerSecond) + 'px',
  }
})

function onMouseDown(e) {
  emit('select', props.item.id)

  const startX = e.clientX
  const origStart = props.item.entireVideo ? 0 : (props.item.start_time || 0)
  const origEnd = props.item.entireVideo ? duration.value : (props.item.end_time || duration.value)
  const itemDuration = origEnd - origStart
  let moved = false

  function onMove(e) {
    moved = true
    const dx = e.clientX - startX
    const dt = dx / props.pixelsPerSecond
    let newStart = Math.max(0, origStart + dt)
    let newEnd = newStart + itemDuration
    if (newEnd > duration.value) {
      newEnd = duration.value
      newStart = newEnd - itemDuration
    }
    emit('update', { ...props.item, start_time: round2(newStart), end_time: round2(newEnd), entireVideo: false })
  }

  function onUp() {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function onResizeStart(edge, e) {
  emit('select', props.item.id)

  const startX = e.clientX
  const origStart = props.item.entireVideo ? 0 : (props.item.start_time || 0)
  const origEnd = props.item.entireVideo ? duration.value : (props.item.end_time || duration.value)

  function onMove(e) {
    const dx = e.clientX - startX
    const dt = dx / props.pixelsPerSecond

    if (edge === 'left') {
      const newStart = Math.max(0, Math.min(origEnd - 0.1, origStart + dt))
      emit('update', { ...props.item, start_time: round2(newStart), end_time: origEnd, entireVideo: false })
    } else {
      const newEnd = Math.min(duration.value, Math.max(origStart + 0.1, origEnd + dt))
      emit('update', { ...props.item, start_time: origStart, end_time: round2(newEnd), entireVideo: false })
    }
  }

  function onUp() {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function round2(v) {
  return Math.round(v * 100) / 100
}
</script>
