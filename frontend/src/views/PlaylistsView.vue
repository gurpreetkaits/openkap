<template>
  <div class="animate-fade-in">
    <!-- Action Bar -->
    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-100">
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Playlist
      </button>

      <div class="ml-auto text-xs text-gray-400" v-if="!loading && playlists.length > 0">
        {{ playlists.length }} {{ playlists.length === 1 ? 'playlist' : 'playlists' }}
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-orange-600 border-t-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">Loading playlists...</p>
    </div>

    <!-- Playlists Grid -->
    <div v-else-if="playlists.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <div
        v-for="playlist in playlists"
        :key="playlist.id"
        class="group relative bg-white border border-gray-100 rounded-2xl overflow-hidden hover:border-orange-200/60 hover:shadow-md hover:shadow-orange-500/5 transition-all duration-200 cursor-pointer"
        @click="openPlaylist(playlist.id)"
      >
        <!-- Top Banner -->
        <div class="h-20 relative overflow-hidden" :style="{ background: getPlaylistGradient(playlist) }">
          <!-- Decorative pattern -->
          <div class="absolute inset-0 opacity-[0.08]">
            <svg class="w-full h-full" viewBox="0 0 200 80" fill="none">
              <circle cx="160" cy="10" r="40" fill="white"/>
              <circle cx="30" cy="70" r="25" fill="white"/>
              <circle cx="120" cy="60" r="15" fill="white"/>
            </svg>
          </div>
          <!-- Video count badge -->
          <div class="absolute top-3 left-3 flex items-center gap-1.5 bg-white/90 backdrop-blur-sm rounded-lg px-2.5 py-1 shadow-sm">
            <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-xs font-semibold text-gray-700">{{ playlist.videos_count }}</span>
          </div>
          <!-- Visibility badge -->
          <div class="absolute top-3 right-3">
            <span
              class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full shadow-sm"
              :class="playlist.is_public
                ? 'text-emerald-700 bg-emerald-50/90 backdrop-blur-sm border border-emerald-200/60'
                : 'text-gray-600 bg-white/90 backdrop-blur-sm border border-gray-200/60'"
            >
              <svg v-if="playlist.is_public" class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <svg v-else class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
              {{ playlist.is_public ? 'Public' : 'Private' }}
            </span>
          </div>
          <!-- Hover Actions (on banner) -->
          <div class="absolute bottom-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all duration-200" @click.stop>
            <button
              @click="sharePlaylist(playlist)"
              class="p-1.5 text-white/80 hover:text-white bg-black/20 hover:bg-black/40 backdrop-blur-sm rounded-lg transition-colors"
              :title="playlist.is_public ? 'Copy share link' : 'Make public to share'"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
              </svg>
            </button>
            <button
              @click="editPlaylist(playlist)"
              class="p-1.5 text-white/80 hover:text-white bg-black/20 hover:bg-black/40 backdrop-blur-sm rounded-lg transition-colors"
              title="Edit playlist"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            <button
              @click="confirmDelete(playlist)"
              class="p-1.5 text-white/80 hover:text-red-300 bg-black/20 hover:bg-black/40 backdrop-blur-sm rounded-lg transition-colors"
              title="Delete playlist"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-4">
          <!-- Title -->
          <h3 class="text-sm font-semibold text-gray-900 truncate group-hover:text-orange-600 transition-colors flex items-center gap-1.5">
            {{ playlist.title }}
            <svg v-if="playlist.has_password" class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Password protected">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </h3>

          <!-- Description -->
          <p v-if="playlist.description" class="text-xs text-gray-500 mt-1 line-clamp-2 leading-relaxed">{{ playlist.description }}</p>
          <p v-else class="text-xs text-gray-300 mt-1 italic">No description</p>

          <!-- Footer -->
          <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-50">
            <div class="flex items-center gap-1.5 text-xs text-gray-400">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ formatDate(playlist.created_at) }}
            </div>
            <div class="flex items-center gap-1 text-xs text-gray-400">
              <span>{{ playlist.videos_count }} {{ playlist.videos_count === 1 ? 'video' : 'videos' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-20">
      <div class="w-16 h-16 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
      </div>
      <h3 class="text-gray-900 font-medium mb-1">No playlists yet</h3>
      <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
        Create playlists to organize your videos into collections.
      </p>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium text-sm shadow-sm transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Create your first playlist
      </button>
    </div>

    <!-- Create/Edit Playlist Modal -->
    <SBModal v-model="showCreateModal" :title="editingPlaylist ? 'Edit Playlist' : 'Create Playlist'">
      <form @submit.prevent="savePlaylist" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
          <input
            v-model="playlistForm.title"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
            placeholder="My Playlist"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
          <textarea
            v-model="playlistForm.description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm resize-none"
            placeholder="Add a description..."
          ></textarea>
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="saving || !playlistForm.title.trim()"
            class="px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 text-white rounded-lg font-medium text-sm transition-colors"
          >
            {{ saving ? 'Saving...' : (editingPlaylist ? 'Save Changes' : 'Create Playlist') }}
          </button>
        </div>
      </form>
    </SBModal>

    <!-- Delete Confirmation Modal -->
    <SBDeleteModal
      v-model="showDeleteModal"
      title="Delete Playlist"
      :message="`Are you sure you want to delete '${playlistToDelete?.title}'? This action cannot be undone.`"
      :loading="deleting"
      @confirm="deletePlaylist"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import playlistService from '@/services/playlistService'
import toast from '@/services/toastService'
import { formatDistanceToNow } from 'date-fns'
import { SBModal, SBDeleteModal } from '@/components/Global'

const router = useRouter()
const playlists = ref([])
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)

const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const editingPlaylist = ref(null)
const playlistToDelete = ref(null)

const playlistForm = ref({
  title: '',
  description: ''
})

async function fetchPlaylists() {
  loading.value = true
  try {
    playlists.value = await playlistService.getPlaylists()
  } catch (error) {
    console.error('Failed to fetch playlists:', error)
    toast.error('Failed to load playlists')
  } finally {
    loading.value = false
  }
}

function openPlaylist(id) {
  router.push(`/playlist/${id}`)
}

function editPlaylist(playlist) {
  editingPlaylist.value = playlist
  playlistForm.value = {
    title: playlist.title,
    description: playlist.description || ''
  }
  showCreateModal.value = true
}

function closeModal() {
  showCreateModal.value = false
  editingPlaylist.value = null
  playlistForm.value = { title: '', description: '' }
}

async function savePlaylist() {
  if (!playlistForm.value.title.trim()) return

  saving.value = true
  try {
    if (editingPlaylist.value) {
      await playlistService.updatePlaylist(editingPlaylist.value.id, playlistForm.value)
      toast.success('Playlist updated')
    } else {
      await playlistService.createPlaylist(playlistForm.value)
      toast.success('Playlist created')
    }
    closeModal()
    await fetchPlaylists()
  } catch (error) {
    console.error('Failed to save playlist:', error)
    toast.error(error.message || 'Failed to save playlist')
  } finally {
    saving.value = false
  }
}

function confirmDelete(playlist) {
  playlistToDelete.value = playlist
  showDeleteModal.value = true
}

async function deletePlaylist() {
  if (!playlistToDelete.value) return

  deleting.value = true
  try {
    await playlistService.deletePlaylist(playlistToDelete.value.id)
    toast.success('Playlist deleted')
    showDeleteModal.value = false
    playlistToDelete.value = null
    await fetchPlaylists()
  } catch (error) {
    console.error('Failed to delete playlist:', error)
    toast.error('Failed to delete playlist')
  } finally {
    deleting.value = false
  }
}

async function sharePlaylist(playlist) {
  if (!playlist.is_public) {
    try {
      const result = await playlistService.toggleSharing(playlist.id)
      playlist.is_public = result.is_public
      playlist.share_url = result.share_url
      if (result.share_url) {
        await navigator.clipboard.writeText(result.share_url)
        toast.success('Playlist is now public. Link copied!')
      }
    } catch (error) {
      toast.error('Failed to make playlist public')
    }
  } else if (playlist.share_url) {
    try {
      await navigator.clipboard.writeText(playlist.share_url)
      toast.success('Link copied to clipboard!')
    } catch (err) {
      toast.error('Failed to copy link')
    }
  }
}

const gradients = [
  'linear-gradient(135deg, #f97316, #fb923c)',
  'linear-gradient(135deg, #8b5cf6, #a78bfa)',
  'linear-gradient(135deg, #3b82f6, #60a5fa)',
  'linear-gradient(135deg, #10b981, #34d399)',
  'linear-gradient(135deg, #ec4899, #f472b6)',
  'linear-gradient(135deg, #f59e0b, #fbbf24)',
  'linear-gradient(135deg, #6366f1, #818cf8)',
  'linear-gradient(135deg, #14b8a6, #2dd4bf)',
]

function getPlaylistGradient(playlist) {
  const index = playlist.id % gradients.length
  return gradients[index]
}

function formatDate(date) {
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true })
  } catch {
    return date
  }
}

onMounted(() => {
  fetchPlaylists()
})
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
