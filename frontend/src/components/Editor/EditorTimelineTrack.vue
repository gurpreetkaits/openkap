<template>
  <div class="flex border-b border-gray-100 last:border-0">
    <!-- Track label -->
    <div class="w-16 flex-shrink-0 flex items-center px-2 bg-gray-50 border-r border-gray-100">
      <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider truncate">{{ label }}</span>
    </div>

    <!-- Track content -->
    <div class="flex-1 relative h-8 min-w-0" @click="onTrackClick">
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
  // Click on empty area to seek
  const rect = e.currentTarget.getBoundingClientRect()
  const x = e.clientX - rect.left + e.currentTarget.scrollLeft
  const time = x / props.pixelsPerSecond
  emit('seek', time)
}
</script>
