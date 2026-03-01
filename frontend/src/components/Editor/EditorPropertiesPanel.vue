<template>
  <transition name="slide-right">
    <div v-if="selectedItem" class="w-[260px] bg-white border-l border-gray-200 flex flex-col flex-shrink-0 absolute lg:relative right-0 top-0 bottom-0 z-30 shadow-xl lg:shadow-none">
      <div class="p-3 border-b border-gray-100 flex items-center justify-between">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Properties</p>
        <button @click="selectedItemId = null" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="p-3 space-y-4 overflow-y-auto flex-1">
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
  </transition>
</template>

<script setup>
import { useEditorState } from '@/composables/useEditorState'

const { selectedItem, selectedItemId, duration, deleteItem } = useEditorState()
</script>

<style scoped>
.slide-right-enter-active, .slide-right-leave-active { transition: transform 0.2s ease, opacity 0.2s ease; }
.slide-right-enter-from, .slide-right-leave-to { transform: translateX(100%); opacity: 0; }
</style>
