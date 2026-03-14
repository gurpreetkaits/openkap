<template>
  <div class="animate-fade-in px-7 py-6">
    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-5">
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 text-white text-[13px] font-semibold rounded-lg hover:bg-black hover:shadow-md hover:-translate-y-px transition-all"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 12 12">
          <line x1="6" y1="1" x2="6" y2="11"/><line x1="1" y1="6" x2="11" y2="6"/>
        </svg>
        New Playlist
      </button>
      <div class="text-xs text-gray-400" v-if="!loading && playlists.length > 0">
        {{ playlists.length }} {{ playlists.length === 1 ? 'playlist' : 'playlists' }}
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-orange-600 border-t-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">Loading playlists...</p>
    </div>

    <!-- Playlists Grid -->
    <div v-else-if="playlists.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
      <div
        v-for="playlist in playlists"
        :key="playlist.id"
        class="group bg-white border border-gray-100 rounded-xl overflow-hidden cursor-pointer transition-all duration-200 hover:border-gray-200 hover:shadow-lg hover:shadow-black/[0.06] hover:-translate-y-0.5"
        @click="openPlaylist(playlist.id)"
      >
        <!-- Cover -->
        <div class="h-[110px] relative overflow-hidden" :style="{ background: getCoverGradient(playlist) }">
          <!-- Decorative pattern -->
          <div class="absolute inset-0 opacity-[0.15]">
            <svg class="w-full h-full" viewBox="0 0 280 110" fill="none" preserveAspectRatio="xMidYMid slice">
              <circle :cx="240 - (playlist.id % 3) * 30" :cy="20 + (playlist.id % 3) * 15" :r="60 + (playlist.id % 3) * 10" fill="white"/>
              <circle :cx="40 + (playlist.id % 2) * 20" :cy="90 - (playlist.id % 2) * 10" :r="40 - (playlist.id % 2) * 10" fill="white"/>
            </svg>
          </div>

          <!-- Thumbnail stack -->
          <div v-if="playlist.videos_count > 0" class="absolute inset-0 flex items-center justify-center gap-1 px-2.5 py-2.5">
            <div
              v-for="n in Math.min(playlist.videos_count, 3)"
              :key="n"
              class="flex-1 h-full rounded-[5px] overflow-hidden bg-white/[0.06] border border-white/[0.08]"
            >
              <div class="w-full h-full p-1 flex flex-col gap-0.5">
                <div class="h-[5px] bg-white/[0.06] rounded-sm mb-0.5"></div>
                <div class="h-[1.5px] rounded-sm" :class="n % 2 === 0 ? 'w-4/5 bg-white/[0.2]' : 'w-3/5 bg-white/[0.08]'"></div>
                <div class="h-[1.5px] rounded-sm" :class="n % 2 === 1 ? 'w-4/5 bg-white/[0.2]' : 'w-3/4 bg-white/[0.08]'"></div>
                <div class="h-[1.5px] rounded-sm" :class="n % 2 === 0 ? 'w-[45%] bg-white/[0.08]' : 'w-3/5 bg-white/[0.2]'"></div>
                <div class="h-[1.5px] rounded-sm w-[70%] bg-white/[0.08]"></div>
              </div>
            </div>
          </div>
          <!-- Empty cover icon -->
          <div v-else class="absolute inset-0 flex items-center justify-center">
            <svg class="w-7 h-7 text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 28 28">
              <rect x="3" y="5" width="22" height="18" rx="2.5"/>
              <polygon points="11,10 11,18 19,14" fill="currentColor" stroke="none"/>
            </svg>
          </div>

          <!-- Views badge (top left) -->
          <div class="absolute top-2 left-2 flex items-center gap-1 bg-black/45 backdrop-blur-sm border border-white/10 text-white/85 rounded-[5px] px-2 py-[3px] text-[11px]">
            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 10 10">
              <path d="M1 5C1 5 2.5 2 5 2S9 5 9 5 7.5 8 5 8 1 5 1 5z"/>
              <circle cx="5" cy="5" r="1.5"/>
            </svg>
            {{ playlist.total_views || 0 }}
          </div>

          <!-- Visibility badge (top right) -->
          <div class="absolute top-2 right-2 flex items-center gap-1 bg-black/55 backdrop-blur-sm border border-white/[0.12] text-white/90 rounded-[5px] px-2 py-[3px] text-[11px] font-medium">
            <svg class="w-[9px] h-[9px]" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 9 9">
              <template v-if="playlist.is_public">
                <circle cx="4.5" cy="4.5" r="3.5"/>
                <path d="M1 4.5h7M4.5 1a5.5 5.5 0 011.5 3.5A5.5 5.5 0 014.5 8a5.5 5.5 0 01-1.5-3.5A5.5 5.5 0 014.5 1z"/>
              </template>
              <template v-else>
                <rect x="1" y="2" width="7" height="6" rx="1"/>
                <path d="M3 2V1.5a.5.5 0 011 0V2M5 2V1.5a.5.5 0 011 0V2"/>
              </template>
            </svg>
            {{ playlist.is_public ? 'Public' : 'Private' }}
          </div>

          <!-- Hover actions -->
          <div class="absolute bottom-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200" @click.stop>
            <button
              @click="sharePlaylist(playlist)"
              class="p-1.5 bg-black/20 hover:bg-black/40 backdrop-blur-sm rounded-md text-white/80 hover:text-white transition-colors"
              :title="playlist.is_public ? 'Copy share link' : 'Make public to share'"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
              </svg>
            </button>
            <button
              @click="editPlaylist(playlist)"
              class="p-1.5 bg-black/20 hover:bg-black/40 backdrop-blur-sm rounded-md text-white/80 hover:text-white transition-colors"
              title="Edit playlist"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            <button
              @click="confirmDelete(playlist)"
              class="p-1.5 bg-black/20 hover:bg-black/40 backdrop-blur-sm rounded-md text-white/80 hover:text-red-300 transition-colors"
              title="Delete playlist"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Meta -->
        <div class="px-3.5 pt-3 pb-3.5">
          <h3 class="text-[13.5px] font-semibold text-gray-900 truncate mb-0.5 tracking-tight group-hover:text-orange-600 transition-colors">
            {{ playlist.title }}
            <svg v-if="playlist.has_password" class="inline w-3 h-3 text-amber-500 ml-0.5 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </h3>
          <p v-if="playlist.description" class="text-xs text-gray-400 truncate mb-2.5">{{ playlist.description }}</p>
          <p v-else class="text-xs text-gray-300 truncate mb-2.5 italic">No description</p>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1 text-[11.5px] text-gray-400">
              <svg class="w-[11px] h-[11px]" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 11 11">
                <circle cx="5.5" cy="5.5" r="4"/><path d="M5.5 3v3l1.8 1.5"/>
              </svg>
              {{ formatDate(playlist.created_at) }}
            </div>
            <div class="text-[11.5px] text-gray-400">
              {{ playlist.videos_count }} {{ playlist.videos_count === 1 ? 'video' : 'videos' }}
            </div>
          </div>
        </div>
      </div>

      <!-- New Playlist Card -->
      <div
        class="border-[1.5px] border-dashed border-gray-200 rounded-xl min-h-[200px] flex flex-col items-center justify-center gap-2 cursor-pointer transition-all hover:bg-white hover:border-gray-300"
        @click="showCreateModal = true"
      >
        <div class="w-[38px] h-[38px] bg-gray-50 border border-gray-100 rounded-[9px] flex items-center justify-center text-gray-400">
          <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 18 18">
            <line x1="9" y1="3" x2="9" y2="15"/><line x1="3" y1="9" x2="15" y2="9"/>
          </svg>
        </div>
        <div class="text-[13px] font-medium text-gray-500">Create playlist</div>
        <div class="text-[11.5px] text-gray-400">Group related videos together</div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-20">
      <div class="w-[52px] h-[52px] mx-auto mb-3.5 bg-white border border-gray-100 rounded-xl flex items-center justify-center text-gray-300">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24">
          <rect x="2" y="4" width="20" height="16" rx="2.5"/>
          <polygon points="9,8 9,16 17,12" fill="currentColor" stroke="none"/>
        </svg>
      </div>
      <h3 class="text-sm font-medium text-gray-700 mb-1">No playlists yet</h3>
      <p class="text-[12.5px] text-gray-400 max-w-[260px] mx-auto leading-relaxed mb-6">
        Create playlists to organize your videos into collections.
      </p>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 hover:bg-black text-white rounded-lg font-semibold text-[13px] shadow-sm hover:shadow-md hover:-translate-y-px transition-all"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 12 12">
          <line x1="6" y1="1" x2="6" y2="11"/><line x1="1" y1="6" x2="11" y2="6"/>
        </svg>
        Create your first playlist
      </button>
    </div>

    <!-- Create/Edit Playlist Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showCreateModal"
          class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/25 backdrop-blur-[5px]"
          @click.self="closeModal"
        >
          <div class="bg-white border border-gray-200 rounded-xl p-6 w-[440px] max-w-[90vw] shadow-2xl animate-modal-up">
            <h2 class="text-xl font-medium text-gray-900 mb-1" style="font-family: 'Georgia', serif; font-style: italic;">
              {{ editingPlaylist ? 'Edit playlist' : 'New playlist' }}
            </h2>
            <p class="text-xs text-gray-400 mb-5">
              {{ editingPlaylist ? 'Update your playlist details.' : 'Create a collection of related videos.' }}
            </p>

            <form @submit.prevent="savePlaylist">
              <div class="mb-3.5">
                <label class="block text-[11.5px] font-medium text-gray-500 mb-1.5">Name</label>
                <input
                  v-model="playlistForm.title"
                  type="text"
                  required
                  class="w-full bg-gray-50 border border-gray-100 rounded-lg px-3 py-2.5 text-[13.5px] text-gray-900 placeholder-gray-400 outline-none focus:bg-white focus:border-gray-300 transition-all"
                  placeholder="e.g. Onboarding, Sprint demos..."
                />
              </div>
              <div class="mb-3.5">
                <label class="block text-[11.5px] font-medium text-gray-500 mb-1.5">
                  Description <span class="text-gray-300">(optional)</span>
                </label>
                <input
                  v-model="playlistForm.description"
                  type="text"
                  class="w-full bg-gray-50 border border-gray-100 rounded-lg px-3 py-2.5 text-[13.5px] text-gray-900 placeholder-gray-400 outline-none focus:bg-white focus:border-gray-300 transition-all"
                  placeholder="What's this playlist about?"
                />
              </div>
              <div class="mb-3.5" v-if="!editingPlaylist">
                <label class="block text-[11.5px] font-medium text-gray-500 mb-1.5">Visibility</label>
                <select
                  v-model="playlistForm.visibility"
                  class="w-full bg-gray-50 border border-gray-100 rounded-lg px-3 py-2.5 text-[13px] text-gray-700 outline-none cursor-pointer focus:border-gray-300"
                >
                  <option value="private">Private — only you can see this</option>
                  <option value="public">Public — anyone with the link</option>
                </select>
              </div>
              <div class="flex justify-end gap-2 mt-5">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-4 py-2 text-[13px] font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="saving || !playlistForm.title.trim()"
                  class="px-4 py-2 bg-gray-900 text-white rounded-lg text-[13px] font-semibold hover:bg-black hover:shadow-md transition-all disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                  {{ saving ? 'Saving...' : (editingPlaylist ? 'Save changes' : 'Create playlist') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

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
import { SBDeleteModal } from '@/components/Global'

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
  description: '',
  visibility: 'private'
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
    description: playlist.description || '',
    visibility: playlist.is_public ? 'public' : 'private'
  }
  showCreateModal.value = true
}

function closeModal() {
  showCreateModal.value = false
  editingPlaylist.value = null
  playlistForm.value = { title: '', description: '', visibility: 'private' }
}

async function savePlaylist() {
  if (!playlistForm.value.title.trim()) return

  saving.value = true
  try {
    if (editingPlaylist.value) {
      await playlistService.updatePlaylist(editingPlaylist.value.id, {
        title: playlistForm.value.title,
        description: playlistForm.value.description
      })
      toast.success('Playlist updated')
    } else {
      await playlistService.createPlaylist({
        title: playlistForm.value.title,
        description: playlistForm.value.description
      })
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

const coverGradients = [
  'linear-gradient(145deg, #1a1a1a 0%, #2e2e2e 100%)',
  'linear-gradient(145deg, #262626 0%, #1a1a1a 60%, #111 100%)',
  'linear-gradient(145deg, #0f0f0f 0%, #222 50%, #1a1a1a 100%)',
  'linear-gradient(145deg, #181818 0%, #0d0d0d 100%)',
  'linear-gradient(145deg, #141414 0%, #1c1c1c 100%)',
]

function getCoverGradient(playlist) {
  return coverGradients[playlist.id % coverGradients.length]
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

@keyframes modalUp {
  from { opacity: 0; transform: translateY(7px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-modal-up { animation: modalUp 0.2s cubic-bezier(0.16, 1, 0.3, 1); }

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
