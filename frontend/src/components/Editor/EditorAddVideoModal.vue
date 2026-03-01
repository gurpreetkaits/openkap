<template>
  <SBModal title="Add Video" @close="$emit('close')">
    <!-- Tabs -->
    <div class="flex items-center p-1 bg-gray-100 rounded-lg mb-4">
      <button
        v-for="tab in ['library', 'upload']"
        :key="tab"
        @click="activeTab = tab"
        :class="activeTab === tab ? 'text-gray-900 bg-white shadow-sm' : 'text-gray-500 hover:text-gray-900'"
        class="flex-1 px-3 py-1.5 rounded-[6px] text-sm font-medium transition-all capitalize"
      >{{ tab === 'library' ? 'From Library' : 'Upload' }}</button>
    </div>

    <!-- From Library -->
    <template v-if="activeTab === 'library'">
      <!-- Search -->
      <div class="mb-3">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search videos..."
          class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all"
        />
      </div>

      <!-- Loading -->
      <div v-if="loadingVideos" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-5 w-5 border-2 border-orange-500 border-t-transparent"></div>
      </div>

      <!-- Video list -->
      <div v-else class="max-h-64 overflow-y-auto space-y-1.5">
        <div v-if="!filteredVideos.length" class="text-center py-6 text-xs text-gray-400">
          No videos found
        </div>
        <div
          v-for="v in filteredVideos"
          :key="v.id"
          @click="selectVideo(v)"
          class="flex items-center gap-3 p-2 rounded-lg border border-gray-200 hover:border-orange-300 hover:bg-orange-50/50 cursor-pointer transition-all"
        >
          <div class="w-16 h-10 bg-gray-200 rounded flex-shrink-0 overflow-hidden">
            <img v-if="v.thumbnail_url" :src="v.thumbnail_url" class="w-full h-full object-cover" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-gray-900 truncate">{{ v.title }}</p>
            <p class="text-[10px] text-gray-400">{{ formatDuration(v.duration) }}</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Upload -->
    <template v-else>
      <div v-if="!uploading" class="text-center py-8">
        <label class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          Choose Video File
          <input type="file" accept="video/*" class="hidden" @change="onUpload" />
        </label>
        <p class="text-xs text-gray-400 mt-2">Upload a video to merge with the current one</p>
      </div>
      <div v-else class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent mb-2"></div>
        <p class="text-xs text-gray-500">Uploading video...</p>
      </div>
    </template>
  </SBModal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import videoService from '@/services/videoService'
import { useEditorState } from '@/composables/useEditorState'
import { useToast } from '@/services/toastService'

const emit = defineEmits(['close', 'select'])

const { video } = useEditorState()
const toast = useToast()

const activeTab = ref('library')
const searchQuery = ref('')
const videos = ref([])
const loadingVideos = ref(false)
const uploading = ref(false)

const filteredVideos = computed(() => {
  let list = videos.value.filter(v => v.id !== video.value?.id)
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(v => v.title?.toLowerCase().includes(q))
  }
  return list
})

function formatDuration(seconds) {
  if (!seconds) return '--:--'
  const m = Math.floor(seconds / 60)
  const s = Math.floor(seconds % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}

function selectVideo(v) {
  emit('select', v)
}

async function onUpload(e) {
  const file = e.target.files?.[0]
  if (!file) return
  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('video', file)
    formData.append('title', file.name.replace(/\.[^/.]+$/, ''))
    const uploaded = await videoService.uploadVideo(formData)
    if (uploaded) {
      emit('select', uploaded)
      toast.success('Video uploaded')
    }
  } catch (err) {
    toast.error(err.message || 'Upload failed')
  } finally {
    uploading.value = false
  }
}

onMounted(async () => {
  loadingVideos.value = true
  try {
    videos.value = await videoService.getVideos()
  } catch {
    videos.value = []
  } finally {
    loadingVideos.value = false
  }
})
</script>
