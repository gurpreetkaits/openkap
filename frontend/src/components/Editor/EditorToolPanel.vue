<template>
  <div class="w-[260px] bg-white border-r border-gray-200 flex-col flex-shrink-0 hidden lg:flex">
    <!-- Tool Tabs -->
    <div class="p-3 border-b border-gray-100">
      <div class="flex items-center p-1 bg-gray-100 rounded-lg">
        <button
          v-for="tool in tools"
          :key="tool"
          @click="activeTool = tool"
          :class="activeTool === tool ? 'text-gray-900 bg-white shadow-sm' : 'text-gray-500 hover:text-gray-900'"
          class="flex-1 px-2 py-1.5 rounded-[6px] text-[13px] font-medium transition-all capitalize"
        >{{ tool }}</button>
      </div>
    </div>

    <!-- Action Buttons -->
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
    <div v-if="activeTool === 'transcript'" class="p-3 border-b border-gray-100">
      <p class="text-[11px] text-gray-400">Edit your video's transcription text below.</p>
    </div>

    <!-- Transcript Panel (inline) -->
    <div v-if="activeTool === 'transcript'" class="flex-1 overflow-y-auto p-3">
      <EditorTranscriptionPanel />
    </div>

    <!-- Items List -->
    <div v-else class="flex-1 overflow-y-auto p-3">
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
</template>

<script setup>
import { useEditorState } from '@/composables/useEditorState'
import EditorTranscriptionPanel from './EditorTranscriptionPanel.vue'

const {
  activeTool, items, selectedItemId,
  selectItem, addTextItem, addOverlayFile, getItemLabel, formatTime,
} = useEditorState()

const tools = ['blur', 'overlay', 'text', 'transcript']
</script>
