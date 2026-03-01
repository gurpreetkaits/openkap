<template>
  <div v-if="isApplying" class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm"></div>
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 relative z-10 border border-gray-100 text-center">
      <!-- Sync mode: spinner + progress -->
      <template v-if="processingMode !== 'async'">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-500 border-t-transparent mx-auto mb-4"></div>
        <h3 class="text-sm font-semibold text-gray-900 mb-1">Creating New Video With Edits</h3>
        <p class="text-xs text-gray-500 mb-4">This may take a few minutes.</p>
        <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2">
          <div class="bg-orange-500 h-1.5 rounded-full transition-all duration-500" :style="{ width: applyProgress + '%' }"></div>
        </div>
        <p class="text-[11px] text-gray-400">{{ applyProgress }}%</p>
      </template>

      <!-- Async mode: friendly message -->
      <template v-else>
        <div class="w-12 h-12 mx-auto mb-4 bg-orange-100 rounded-full flex items-center justify-center">
          <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-900 mb-1">Edits Submitted</h3>
        <p class="text-xs text-gray-500 mb-4">Your edits are being applied! We'll notify you when your new video is ready.</p>
        <button
          @click="$emit('goBack')"
          class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors"
        >
          Back to Videos
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { useEditorState } from '@/composables/useEditorState'

defineEmits(['goBack'])

const { isApplying, applyProgress, processingMode } = useEditorState()
</script>
