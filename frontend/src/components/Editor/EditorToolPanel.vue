<template>
  <div class="w-[320px] flex-col min-h-0 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden hidden lg:flex">
    <!-- Tool Tabs -->
    <div class="shrink-0 px-3 pt-3 pb-2 border-b border-gray-100">
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
    <div v-if="activeTool === 'overlay'" class="shrink-0 p-3 border-b border-gray-100">
      <label class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Upload Video
        <input type="file" accept="video/*" class="hidden" @change="addOverlayFile" />
      </label>
    </div>
    <div v-if="activeTool === 'text'" class="shrink-0 p-3 border-b border-gray-100">
      <button @click="addTextItem" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Text
      </button>
    </div>

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto min-h-0">
      <!-- Items List -->
      <div class="p-3">
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

      <!-- Properties (inline, shown when item selected) -->
      <div v-if="selectedItem" class="border-t border-gray-100">
        <div class="p-3 flex items-center justify-between">
          <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Properties</p>
          <button @click="selectedItemId = null" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="px-3 pb-3 space-y-4">
          <!-- Position -->
          <div>
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Position</p>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[10px] text-gray-400 block mb-0.5">X %</label>
                <input type="number" v-model.number="selectedItem.x" min="0" max="100" step="0.1"
                  class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all" />
              </div>
              <div>
                <label class="text-[10px] text-gray-400 block mb-0.5">Y %</label>
                <input type="number" v-model.number="selectedItem.y" min="0" max="100" step="0.1"
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
                <input type="number" v-model.number="selectedItem.width" min="1" max="100" step="0.1"
                  class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all" />
              </div>
              <div>
                <label class="text-[10px] text-gray-400 block mb-0.5">Height %</label>
                <input type="number" v-model.number="selectedItem.height" min="1" max="100" step="0.1"
                  class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all" />
              </div>
            </div>
          </div>

          <!-- Text Content -->
          <div v-if="selectedItem.type === 'text'">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Content</p>
            <textarea v-model="selectedItem.text" rows="3" maxlength="200"
              class="w-full px-2 py-1.5 text-xs bg-gray-100 border border-transparent rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all resize-none"></textarea>
            <p class="text-[10px] text-gray-400 mt-1">{{ (selectedItem.text || '').length }}/200</p>
          </div>

          <!-- Text Style -->
          <div v-if="selectedItem.type === 'text'">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Style</p>
            <div class="space-y-2">
              <div>
                <label class="text-[10px] text-gray-400 block mb-1">Font Size: {{ selectedItem.font_size }}px</label>
                <input type="range" v-model.number="selectedItem.font_size" min="12" max="120" step="1" class="w-full accent-orange-500" />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="text-[10px] text-gray-400 block mb-0.5">Color</label>
                  <input type="color" v-model="selectedItem.font_color" class="w-full h-8 rounded-lg border border-gray-200 cursor-pointer" />
                </div>
                <div>
                  <label class="text-[10px] text-gray-400 block mb-0.5">Background</label>
                  <div class="flex items-center gap-1">
                    <input type="color" v-model="selectedItem.background_color" :disabled="!selectedItem.has_background"
                      class="flex-1 h-8 rounded-lg border border-gray-200 cursor-pointer disabled:opacity-30" />
                    <input type="checkbox" v-model="selectedItem.has_background"
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
    </div>
  </div>
</template>

<script setup>
import { useEditorState } from '@/composables/useEditorState'

const {
  activeTool, items, selectedItemId, selectedItem, duration,
  selectItem, deleteItem, addTextItem, addOverlayFile, getItemLabel, formatTime,
} = useEditorState()

const tools = ['blur', 'overlay', 'text']
</script>
