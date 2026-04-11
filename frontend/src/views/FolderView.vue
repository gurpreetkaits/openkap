<template>
  <div class="animate-fade-in">
    <!-- Breadcrumb & Header -->
    <div class="mb-6">
      <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
        <router-link to="/videos" class="hover:text-gray-700 transition-colors">Library</router-link>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-900 font-medium">{{ folder?.name || 'Loading...' }}</span>
      </nav>

      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-lg flex items-center justify-center"
            :style="{ backgroundColor: folder?.color || '#f97316' }"
          >
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
          </div>
          <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ folder?.name || 'Loading...' }}</h1>
            <p class="text-sm text-gray-500">{{ videos.length }} video{{ videos.length !== 1 ? 's' : '' }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="showEditModal = true"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit
          </button>
          <button
            @click="showDeleteModal = true"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-orange-600 border-t-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">Loading folder...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-24">
      <div class="w-16 h-16 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ error }}</h3>
      <router-link
        to="/videos"
        class="inline-flex items-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm shadow-sm transition-colors"
      >
        Back to Library
      </router-link>
    </div>

    <!-- Videos Grid -->
    <div v-else-if="videos.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-5">
      <div
        v-for="video in videos"
        :key="video.id"
        class="group bg-white rounded-xl border border-gray-100 hover:shadow-lg hover:shadow-gray-200/60 hover:border-gray-200 transition-all duration-200 hover:-translate-y-0.5"
      >
        <!-- Thumbnail Container -->
        <div
          class="relative w-full cursor-pointer overflow-hidden rounded-t-xl"
          style="aspect-ratio: 16/9;"
          @click="openVideo(video.id)"
        >
          <!-- Thumbnail Image -->
          <img
            v-if="video.thumbnail"
            :src="video.thumbnail"
            :alt="video.title"
            class="w-full h-full object-cover"
            loading="lazy"
            @error="$event.target.style.display='none'"
          />
          <!-- Placeholder when no thumbnail -->
          <div
            v-if="!video.thumbnail"
            class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center"
          >
            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>

          <!-- Processing Badge -->
          <div
            v-if="video.conversion_status === 'processing'"
            class="absolute top-2 left-2 z-10 bg-black/70 backdrop-blur-sm text-white text-[10px] font-medium px-2 py-1 rounded-md flex items-center gap-1.5"
          >
            <div class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
            Processing
          </div>

          <!-- Duration Badge -->
          <div class="absolute bottom-2 right-2 z-10 bg-black/80 backdrop-blur-sm text-white text-[11px] font-medium px-2 py-0.5 rounded-md pointer-events-none">
            {{ formatDuration(video.duration) }}
          </div>

          <!-- Favourite Badge -->
          <div v-if="video.is_favourite" class="absolute top-2 right-2 z-10 pointer-events-none">
            <div class="bg-white/90 backdrop-blur-sm rounded-full p-1 shadow-sm">
              <svg class="w-3.5 h-3.5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
          </div>

          <!-- Hover Overlay with Play Button -->
          <div
            class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200"
          >
            <!-- Remove from folder button -->
            <button
              @click.stop="removeFromFolder(video)"
              class="absolute top-2 left-2 p-1.5 bg-white text-red-600 hover:text-red-700 rounded-lg shadow-sm transition-colors z-10"
              title="Remove from folder"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>

            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center shadow-xl shadow-orange-500/25 transform group-hover:scale-100 scale-90 transition-transform duration-200 pointer-events-none">
              <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Video Info -->
        <div class="p-3">
          <!-- Title Row -->
          <div class="flex items-start gap-1.5">
            <h3
              class="flex-1 font-medium text-sm text-gray-900 leading-snug line-clamp-2 cursor-pointer hover:text-orange-600 transition-colors"
              @click="openVideo(video.id)"
            >
              {{ video.title }}
            </h3>
            <!-- Hover Actions: Copy Link -->
            <div class="flex items-center gap-0.5 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity z-20" @click.stop>
              <button
                @click.stop="copyShareLink(video)"
                class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                title="Copy link"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
              </button>
            </div>
          </div>
          <!-- Meta Info -->
          <div class="flex items-center gap-1 mt-1.5 text-[11px] text-gray-400">
            <span>{{ formatDate(video.created_at) }}</span>
          </div>
          <!-- Stats Row -->
          <div class="flex items-center gap-2.5 mt-1.5 text-[11px] text-gray-400">
            <span class="flex items-center gap-1" title="Views">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              {{ video.views_count || 0 }}
            </span>
            <span class="flex items-center gap-1" title="Comments">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
              </svg>
              {{ video.comments_count || 0 }}
            </span>
            <span class="flex items-center gap-1" title="Reactions">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
              </svg>
              {{ video.reactions_count || 0 }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-20">
      <div class="w-16 h-16 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
      </div>
      <h3 class="text-gray-900 font-medium mb-1">This folder is empty</h3>
      <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
        Add videos to this folder from your library.
      </p>
      <router-link
        to="/videos"
        class="inline-flex items-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm shadow-sm transition-colors"
      >
        Browse Library
      </router-link>
    </div>

    <!-- Edit Folder Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showEditModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
          @click.self="showEditModal = false"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-1">Edit Folder</h2>
              <p class="text-sm text-gray-500 mb-6">Update folder details.</p>

              <div class="space-y-4">
                <div>
                  <label for="edit-folder-name" class="block text-sm font-medium text-gray-700 mb-1">Folder Name</label>
                  <input
                    id="edit-folder-name"
                    v-model="editFolderName"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                    @keyup.enter="updateFolder"
                  />
                  <p v-if="editError" class="mt-1 text-sm text-red-600">{{ editError }}</p>
                </div>
              </div>

              <div class="flex justify-end gap-3 mt-6">
                <button
                  @click="showEditModal = false"
                  class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="updateFolder"
                  :disabled="!editFolderName.trim() || updating"
                  class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ updating ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete Folder Modal -->
    <SBDeleteModal
      v-model="showDeleteModal"
      title="Delete Folder"
      :message="`Are you sure you want to delete '${folder?.name}'? Videos in this folder will not be deleted, they will remain in your library.`"
      :loading="deleting"
      @confirm="deleteFolder"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import folderService from '@/services/folderService'
import toast from '@/services/toastService'
import SBDeleteModal from '@/components/Global/SBDeleteModal.vue'

export default {
  name: 'FolderView',
  components: {
    SBDeleteModal
  },
  setup() {
    const route = useRoute()
    const router = useRouter()

    const folder = ref(null)
    const videos = ref([])
    const loading = ref(true)
    const error = ref(null)

    // Edit modal state
    const showEditModal = ref(false)
    const editFolderName = ref('')
    const editError = ref('')
    const updating = ref(false)

    // Delete modal state
    const showDeleteModal = ref(false)
    const deleting = ref(false)

    const folderId = computed(() => route.params.id)

    const fetchFolder = async () => {
      loading.value = true
      error.value = null

      try {
        const folderVideos = await folderService.getFolderVideos(folderId.value)
        videos.value = folderVideos

        // Get folder details from the folders list
        const folders = await folderService.getFolders()
        folder.value = folders.find(f => f.id === parseInt(folderId.value))

        if (!folder.value) {
          error.value = 'Folder not found'
        } else {
          editFolderName.value = folder.value.name
        }
      } catch (err) {
        console.error('Failed to fetch folder:', err)
        error.value = 'Failed to load folder. Please try again.'
      } finally {
        loading.value = false
      }
    }

    const openVideo = (id) => {
      window.location.href = import.meta.env.BASE_URL + `video/${id}`
    }

    const copyShareLink = async (video) => {
      const shareUrl = video.share_url || video.shareUrl
      if (shareUrl) {
        try {
          await navigator.clipboard.writeText(shareUrl)
          toast.success('Link copied to clipboard!')
        } catch {
          toast.error('Failed to copy link.')
        }
      } else {
        toast.error('No share link available')
      }
    }

    const removeFromFolder = async (video) => {
      try {
        await folderService.removeVideoFromFolder(folderId.value, video.id)
        videos.value = videos.value.filter(v => v.id !== video.id)
        toast.success('Video removed from folder')
      } catch (err) {
        console.error('Failed to remove video:', err)
        toast.error('Failed to remove video from folder')
      }
    }

    const updateFolder = async () => {
      if (!editFolderName.value.trim()) return

      editError.value = ''
      updating.value = true

      try {
        const result = await folderService.updateFolder(folderId.value, {
          name: editFolderName.value.trim()
        })
        folder.value = result.folder
        toast.success('Folder updated successfully')
        showEditModal.value = false
      } catch (err) {
        console.error('Failed to update folder:', err)
        editError.value = err.message || 'Failed to update folder'
      } finally {
        updating.value = false
      }
    }

    const deleteFolder = async () => {
      deleting.value = true

      try {
        await folderService.deleteFolder(folderId.value)
        toast.success('Folder deleted successfully')
        router.push('/videos')
      } catch (err) {
        console.error('Failed to delete folder:', err)
        toast.error('Failed to delete folder')
      } finally {
        deleting.value = false
      }
    }

    const formatDuration = (seconds) => {
      if (!seconds || isNaN(seconds)) return '0:00'
      const mins = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${mins}:${secs.toString().padStart(2, '0')}`
    }

    const formatDate = (dateString) => {
      const date = new Date(dateString)
      const now = new Date()
      const diffTime = Math.abs(now - date)
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))

      if (diffDays === 0) return 'Today'
      if (diffDays === 1) return 'Yesterday'
      if (diffDays < 7) return `${diffDays} days ago`
      if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
      if (diffDays < 365) return `${Math.floor(diffDays / 30)} month${Math.floor(diffDays / 30) > 1 ? 's' : ''} ago`
      return `${Math.floor(diffDays / 365)} year${Math.floor(diffDays / 365) > 1 ? 's' : ''} ago`
    }

    onMounted(() => {
      fetchFolder()
    })

    // Watch for route changes
    watch(folderId, () => {
      fetchFolder()
    })

    return {
      folder,
      videos,
      loading,
      error,
      // Edit modal
      showEditModal,
      editFolderName,
      editError,
      updating,
      updateFolder,
      // Delete modal
      showDeleteModal,
      deleting,
      deleteFolder,
      // Methods
      openVideo,
      copyShareLink,
      removeFromFolder,
      formatDuration,
      formatDate
    }
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-content-enter-active {
  transition: all 0.2s ease-out;
}

.modal-content-leave-active {
  transition: all 0.15s ease-in;
}

.modal-content-enter-from {
  opacity: 0;
  transform: scale(0.95) translateY(-10px);
}

.modal-content-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-10px);
}
</style>
