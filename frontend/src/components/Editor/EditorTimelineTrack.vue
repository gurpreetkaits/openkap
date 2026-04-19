<template>
  <div class="flex border-b border-gray-100 last:border-0 group/track">
    <!-- Track label with icons -->
    <div class="w-20 flex-shrink-0 flex items-center justify-center px-1.5 bg-white border-r border-gray-100">
      <div class="flex items-center gap-1">
        <button class="w-5 h-5 flex items-center justify-center text-gray-300 hover:text-gray-500 transition-colors rounded" title="Lock">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
        </button>
        <button class="w-5 h-5 flex items-center justify-center text-gray-300 hover:text-gray-500 transition-colors rounded" title="Visibility">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </button>
        <button class="w-5 h-5 flex items-center justify-center text-gray-300 hover:text-gray-500 transition-colors rounded" title="Volume">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.508c-1.141 0-2.318.664-2.66 1.905A9.76 9.76 0 001.5 12c0 .898.121 1.768.35 2.595.341 1.24 1.518 1.905 2.659 1.905h1.93l4.5 4.5c.945.945 2.561.276 2.561-1.06V4.06z"/></svg>
        </button>
      </div>
    </div>

    <!-- Track content -->
    <div class="flex-1 relative h-10 min-w-0 bg-gray-50/30" @click="onTrackClick">
      <EditorTimelineBlock
        v-for="item in trackItems"
        :key="item.id"
        :item="item"
        :pixelsPerSecond="pixelsPerSecond"
        :isSelected="selectedItemId === item.id"
        @select="$emit('select', $event)"
        @update="$emit('updateItem', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useEditorState } from '@/composables/useEditorState'
import EditorTimelineBlock from './EditorTimelineBlock.vue'

const props = defineProps({
  type: { type: String, required: true },
  label: { type: String, required: true },
  pixelsPerSecond: { type: Number, required: true },
})

const emit = defineEmits(['select', 'updateItem', 'seek'])

const { items, selectedItemId } = useEditorState()

const trackItems = computed(() => items.value.filter(i => i.type === props.type))

function onTrackClick(e) {
  const rect = e.currentTarget.getBoundingClientRect()
  const x = e.clientX - rect.left + e.currentTarget.scrollLeft
  const time = x / props.pixelsPerSecond
  emit('seek', time)
}
</script>
