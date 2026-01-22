<template>
  <div class="animate-fade-in max-w-7xl mx-auto p-6 lg:p-8">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <span class="loading loading-spinner loading-lg text-primary"></span>
      <p class="mt-4 text-sm text-base-content/60">Loading playlist...</p>
    </div>

    <template v-else-if="playlist">
      <!-- Header -->
      <div class="flex items-start justify-between mb-8">
        <div class="flex items-start gap-4">
          <button
            @click="router.push('/playlists')"
            class="btn btn-ghost btn-sm btn-circle"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-xl font-semibold text-base-content">{{ playlist.title }}</h1>
              <span v-if="playlist.is_public" class="badge badge-success gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Public
              </span>
            </div>
            <p v-if="playlist.description" class="text-sm text-base-content/60 mt-1">{{ playlist.description }}</p>
            <div class="flex items-center gap-3 text-xs text-base-content/40 mt-2">
              <span>{{ playlist.videos_count }} {{ playlist.videos_count === 1 ? 'video' : 'videos' }}</span>
              <span class="w-1 h-1 bg-base-content/30 rounded-full"></span>
              <span>{{ formatDate(playlist.created_at) }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Sort Toggle -->
          <div class="dropdown dropdown-end">
            <label
              tabindex="0"
              @click="showSortMenu = !showSortMenu"
              class="btn btn-ghost btn-sm gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
              </svg>
              {{ playlist.sort_by === 'manual' ? 'Manual Order' : 'Date Added' }}
            </label>
            <ul v-if="showSortMenu" tabindex="0" class="dropdown-content menu p-2 shadow-lg bg-base-100 rounded-box w-40 border border-base-300">
              <li>
                <a
                  @click="updateSortBy('manual')"
                  :class="playlist.sort_by === 'manual' ? 'active' : ''"
                >
                  <svg v-if="playlist.sort_by === 'manual'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Manual Order
                </a>
              </li>
              <li>
                <a
                  @click="updateSortBy('date_added')"
                  :class="playlist.sort_by === 'date_added' ? 'active' : ''"
                >
                  <svg v-if="playlist.sort_by === 'date_added'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Date Added
                </a>
              </li>
            </ul>
          </div>

          <!-- Share Button -->
          <button
            @click="toggleSharing"
            class="btn btn-sm gap-2"
            :class="playlist.is_public ? 'btn-success' : 'btn-ghost'"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            {{ playlist.is_public ? 'Shared' : 'Share' }}
          </button>

          <!-- Add Videos Button -->
          <button
            @click="showAddVideosModal = true"
            class="btn btn-primary btn-sm gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Videos
          </button>
        </div>
      </div>

      <!-- Videos List -->
      <div v-if="playlist.videos && playlist.videos.length > 0" class="space-y-3">
        <div
          v-for="(video, index) in playlist.videos"
          :key="video.id"
          class="card card-side bg-base-100 border border-base-300 hover:border-primary/30 hover:shadow-md transition-all group"
          :class="{ 'cursor-move': playlist.sort_by === 'manual' }"
          draggable="true"
          @dragstart="onDragStart($event, index)"
          @dragover.prevent="onDragOver($event, index)"
          @drop="onDrop($event, index)"
          @dragend="onDragEnd"
        >
          <div class="flex items-center gap-4 p-3 w-full">
            <!-- Drag Handle (only in manual mode) -->
            <div v-if="playlist.sort_by === 'manual'" class="text-base-content/30 hover:text-base-content/60 cursor-move">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
              </svg>
            </div>

            <!-- Position Number -->
            <div class="w-8 h-8 flex items-center justify-center text-sm font-medium text-base-content/40">
              {{ index + 1 }}
            </div>

            <!-- Thumbnail -->
            <div
              class="relative w-32 flex-shrink-0 aspect-video rounded-lg overflow-hidden bg-neutral cursor-pointer"
              @click="openVideo(video.id)"
            >
              <img
                v-if="video.thumbnail"
                :src="video.thumbnail"
                :alt="video.title"
                class="w-full h-full object-cover"
                loading="lazy"
              />
              <div class="badge badge-neutral badge-sm absolute bottom-1 right-1 text-[9px]">
                {{ formatDuration(video.duration) }}
              </div>
            </div>

            <!-- Video Info -->
            <div class="flex-1 min-w-0 cursor-pointer" @click="openVideo(video.id)">
              <h3 class="font-medium text-base-content hover:text-primary transition-colors truncate text-sm">
                {{ video.title }}
              </h3>
              <div class="flex items-center gap-2 text-xs text-base-content/60 mt-1">
                <span>{{ formatDate(video.created_at) }}</span>
                <span v-if="video.added_at" class="text-base-content/40">
                  Added {{ formatDate(video.added_at) }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                @click.stop="openVideo(video.id)"
                class="btn btn-ghost btn-sm btn-circle hover:text-primary"
                title="Play video"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
                </svg>
              </button>
              <button
                @click.stop="removeVideo(video)"
                class="btn btn-ghost btn-sm btn-circle hover:text-error"
                title="Remove from playlist"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty Playlist -->
      <div v-else class="text-center py-16">
        <div class="w-16 h-16 mx-auto mb-6 bg-base-200 rounded-full flex items-center justify-center">
          <svg class="w-8 h-8 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
          </svg>
        </div>
        <h3 class="text-base-content font-medium mb-1">No videos in this playlist</h3>
        <p class="text-sm text-base-content/60 max-w-md mx-auto mb-6">
          Add videos to this playlist to start organizing your content.
        </p>
        <button
          @click="showAddVideosModal = true"
          class="btn btn-primary gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Add Videos
        </button>
      </div>
    </template>

    <!-- Add Videos Modal -->
    <SBModal v-model="showAddVideosModal" title="Add Videos to Playlist" size="lg">
      <div v-if="loadingVideos" class="text-center py-8">
        <span class="loading loading-spinner loading-md text-primary"></span>
        <p class="mt-3 text-sm text-base-content/60">Loading videos...</p>
      </div>

      <div v-else-if="availableVideos.length === 0" class="text-center py-8">
        <p class="text-base-content/60">No videos available to add.</p>
      </div>

      <div v-else class="space-y-2 max-h-96 overflow-y-auto">
        <div
          v-for="video in availableVideos"
          :key="video.id"
          class="flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 cursor-pointer"
          @click="toggleVideoSelection(video)"
        >
          <div class="flex-shrink-0">
            <input
              type="checkbox"
              :checked="selectedVideos.includes(video.id)"
              class="checkbox checkbox-primary checkbox-sm"
              @click.stop
              @change="toggleVideoSelection(video)"
            />
          </div>
          <div class="w-20 aspect-video rounded overflow-hidden bg-base-300 flex-shrink-0">
            <img v-if="video.thumbnail" :src="video.thumbnail" :alt="video.title" class="w-full h-full object-cover" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-base-content truncate">{{ video.title }}</p>
            <p class="text-xs text-base-content/60">{{ formatDuration(video.duration) }}</p>
          </div>
        </div>
      </div>

      <div class="divider my-2"></div>
      <div class="flex items-center justify-between">
        <span class="text-sm text-base-content/60">{{ selectedVideos.length }} selected</span>
        <div class="flex gap-2">
          <button
            @click="showAddVideosModal = false"
            class="btn btn-ghost"
          >
            Cancel
          </button>
          <button
            @click="addSelectedVideos"
            :disabled="selectedVideos.length === 0 || addingVideos"
            class="btn btn-primary"
          >
            <span v-if="addingVideos" class="loading loading-spinner loading-sm"></span>
            {{ addingVideos ? 'Adding...' : 'Add to Playlist' }}
          </button>
        </div>
      </div>
    </SBModal>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import playlistService from '@/services/playlistService'
import videoService from '@/services/videoService'
import toast from '@/services/toastService'
import { formatDistanceToNow } from 'date-fns'
import { SBModal } from '@/components/Global'

const route = useRoute()
const router = useRouter()

const playlist = ref(null)
const loading = ref(true)
const showSortMenu = ref(false)
const showAddVideosModal = ref(false)
const loadingVideos = ref(false)
const addingVideos = ref(false)
const availableVideos = ref([])
const selectedVideos = ref([])

// Drag and drop state
const draggedIndex = ref(null)

async function fetchPlaylist() {
  loading.value = true
  try {
    playlist.value = await playlistService.getPlaylist(route.params.id)
  } catch (error) {
    console.error('Failed to fetch playlist:', error)
    toast.error('Failed to load playlist')
    router.push('/playlists')
  } finally {
    loading.value = false
  }
}

async function fetchAvailableVideos() {
  loadingVideos.value = true
  try {
    const allVideos = await videoService.getVideos()
    const playlistVideoIds = (playlist.value?.videos || []).map(v => v.id)
    availableVideos.value = allVideos.filter(v => !playlistVideoIds.includes(v.id))
  } catch (error) {
    console.error('Failed to fetch videos:', error)
    toast.error('Failed to load videos')
  } finally {
    loadingVideos.value = false
  }
}

function toggleVideoSelection(video) {
  const index = selectedVideos.value.indexOf(video.id)
  if (index === -1) {
    selectedVideos.value.push(video.id)
  } else {
    selectedVideos.value.splice(index, 1)
  }
}

async function addSelectedVideos() {
  if (selectedVideos.value.length === 0) return

  addingVideos.value = true
  try {
    for (const videoId of selectedVideos.value) {
      await playlistService.addVideo(playlist.value.id, videoId)
    }
    toast.success(`Added ${selectedVideos.value.length} video(s) to playlist`)
    showAddVideosModal.value = false
    selectedVideos.value = []
    await fetchPlaylist()
  } catch (error) {
    console.error('Failed to add videos:', error)
    toast.error(error.message || 'Failed to add videos')
  } finally {
    addingVideos.value = false
  }
}

async function removeVideo(video) {
  try {
    await playlistService.removeVideo(playlist.value.id, video.id)
    toast.success('Video removed from playlist')
    await fetchPlaylist()
  } catch (error) {
    console.error('Failed to remove video:', error)
    toast.error('Failed to remove video')
  }
}

async function toggleSharing() {
  try {
    const result = await playlistService.toggleSharing(playlist.value.id)
    playlist.value.is_public = result.is_public
    playlist.value.share_url = result.share_url

    if (result.is_public && result.share_url) {
      await navigator.clipboard.writeText(result.share_url)
      toast.success('Playlist is now public. Link copied!')
    } else {
      toast.success('Playlist is now private')
    }
  } catch (error) {
    console.error('Failed to toggle sharing:', error)
    toast.error('Failed to update sharing')
  }
}

async function updateSortBy(sortBy) {
  showSortMenu.value = false
  if (playlist.value.sort_by === sortBy) return

  try {
    await playlistService.updateSortBy(playlist.value.id, sortBy)
    playlist.value.sort_by = sortBy
    await fetchPlaylist()
    toast.success('Sort order updated')
  } catch (error) {
    console.error('Failed to update sort order:', error)
    toast.error('Failed to update sort order')
  }
}

// Drag and drop handlers
function onDragStart(event, index) {
  if (playlist.value.sort_by !== 'manual') return
  draggedIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
}

function onDragOver(event, index) {
  if (playlist.value.sort_by !== 'manual') return
  event.dataTransfer.dropEffect = 'move'
}

async function onDrop(event, index) {
  if (playlist.value.sort_by !== 'manual' || draggedIndex.value === null) return

  const fromIndex = draggedIndex.value
  const toIndex = index

  if (fromIndex === toIndex) return

  // Reorder locally
  const videos = [...playlist.value.videos]
  const [removed] = videos.splice(fromIndex, 1)
  videos.splice(toIndex, 0, removed)
  playlist.value.videos = videos

  // Update server
  const videoIds = videos.map(v => v.id)
  try {
    await playlistService.reorderVideos(playlist.value.id, videoIds)
  } catch (error) {
    console.error('Failed to reorder videos:', error)
    toast.error('Failed to save new order')
    await fetchPlaylist()
  }
}

function onDragEnd() {
  draggedIndex.value = null
}

function openVideo(id) {
  router.push(`/video/${id}`)
}

function formatDuration(seconds) {
  if (!seconds || isNaN(seconds)) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

function formatDate(date) {
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true })
  } catch {
    return date
  }
}

// Close sort menu when clicking outside
function handleClickOutside(event) {
  if (showSortMenu.value && !event.target.closest('.dropdown')) {
    showSortMenu.value = false
  }
}

watch(showAddVideosModal, (newValue) => {
  if (newValue) {
    fetchAvailableVideos()
    selectedVideos.value = []
  }
})

onMounted(() => {
  fetchPlaylist()
  document.addEventListener('click', handleClickOutside)
})
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
</style>
