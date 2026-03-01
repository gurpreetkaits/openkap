<template>
  <div class="space-y-3">
    <!-- Loading -->
    <div v-if="loadingTranscription" class="text-center py-6">
      <div class="inline-block animate-spin rounded-full h-5 w-5 border-2 border-orange-500 border-t-transparent"></div>
      <p class="text-xs text-gray-400 mt-2">Loading transcription...</p>
    </div>

    <!-- No transcription -->
    <div v-else-if="!transcriptionText && !editing" class="text-center py-6">
      <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      <p class="text-xs text-gray-500">No transcription available</p>
      <p class="text-[10px] text-gray-400 mt-1">Generate a transcription from the video detail page first.</p>
    </div>

    <!-- Editor -->
    <template v-else>
      <textarea
        v-model="editText"
        rows="12"
        :disabled="saving"
        class="w-full px-3 py-2 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all resize-none leading-relaxed"
        placeholder="Transcription text..."
      ></textarea>

      <!-- Dirty indicator + actions -->
      <div class="flex items-center justify-between">
        <span v-if="isDirty" class="text-[10px] text-orange-500 font-medium">Unsaved changes</span>
        <span v-else class="text-[10px] text-gray-400">{{ editText ? 'Up to date' : '' }}</span>
        <div class="flex items-center gap-2">
          <button
            v-if="isDirty"
            @click="cancelEdit"
            :disabled="saving"
            class="px-3 py-1 text-xs text-gray-500 hover:text-gray-700 disabled:opacity-50 transition-colors"
          >Cancel</button>
          <button
            @click="saveTranscription"
            :disabled="!isDirty || saving"
            class="px-3 py-1 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 disabled:cursor-not-allowed rounded-lg transition-colors"
          >{{ saving ? 'Saving...' : 'Save' }}</button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useEditorState } from '@/composables/useEditorState'
import videoService from '@/services/videoService'
import { useToast } from '@/services/toastService'

const { video } = useEditorState()
const toast = useToast()

const loadingTranscription = ref(false)
const transcriptionText = ref('')
const editText = ref('')
const saving = ref(false)
const editing = ref(false)

const isDirty = computed(() => editText.value !== transcriptionText.value)

async function loadTranscription() {
  if (!video.value?.id) return
  loadingTranscription.value = true
  try {
    const data = await videoService.getTranscription(video.value.id)
    transcriptionText.value = data?.transcription || ''
    editText.value = transcriptionText.value
    if (transcriptionText.value) editing.value = true
  } catch {
    transcriptionText.value = ''
    editText.value = ''
  } finally {
    loadingTranscription.value = false
  }
}

function cancelEdit() {
  editText.value = transcriptionText.value
}

async function saveTranscription() {
  if (!isDirty.value || saving.value) return
  saving.value = true
  try {
    await videoService.updateTranscription(video.value.id, editText.value)
    transcriptionText.value = editText.value
    toast.success('Transcription saved')
  } catch (e) {
    toast.error(e.message || 'Failed to save transcription')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadTranscription()
})

watch(() => video.value?.id, () => {
  loadTranscription()
})
</script>
