<template>
  <div class="animate-fade-in">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-orange-600 border-t-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">Loading playlist...</p>
    </div>

    <template v-else-if="playlist">
      <!-- Header -->
      <div class="flex items-start justify-between mb-8">
        <div class="flex items-start gap-4">
          <button
            @click="router.push('/playlists')"
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-xl font-semibold text-gray-900">{{ playlist.title }}</h1>
              <span v-if="playlist.is_public" class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Public
              </span>
            </div>
            <p v-if="playlist.description" class="text-sm text-gray-500 mt-1">{{ playlist.description }}</p>
            <div class="flex items-center gap-3 text-xs text-gray-400 mt-2">
              <span>{{ playlist.videos_count }} {{ playlist.videos_count === 1 ? 'video' : 'videos' }}</span>
              <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
              <span>{{ formatDate(playlist.created_at) }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Sort Toggle -->
          <div class="relative" data-dropdown-menu>
            <button
              @click="showSortMenu = !showSortMenu"
              class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
              </svg>
              {{ playlist.sort_by === 'manual' ? 'Manual Order' : 'Date Added' }}
            </button>
            <div v-if="showSortMenu" class="absolute right-0 mt-1 w-40 bg-white border border-gray-100 rounded-lg shadow-lg z-10">
              <button
                @click="updateSortBy('manual')"
                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 transition-colors flex items-center gap-2"
                :class="playlist.sort_by === 'manual' ? 'text-orange-600' : 'text-gray-700'"
              >
                <svg v-if="playlist.sort_by === 'manual'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span :class="playlist.sort_by !== 'manual' ? 'ml-6' : ''">Manual Order</span>
              </button>
              <button
                @click="updateSortBy('date_added')"
                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 transition-colors flex items-center gap-2"
                :class="playlist.sort_by === 'date_added' ? 'text-orange-600' : 'text-gray-700'"
              >
                <svg v-if="playlist.sort_by === 'date_added'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span :class="playlist.sort_by !== 'date_added' ? 'ml-6' : ''">Date Added</span>
              </button>
            </div>
          </div>

          <!-- Visibility Dropdown -->
          <div class="relative" data-dropdown-menu>
            <button
              @click="showVisibilityMenu = !showVisibilityMenu"
              class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="playlist.is_public ? 'text-green-700 bg-green-50 hover:bg-green-100' : 'text-gray-600 hover:bg-gray-100'"
            >
              <!-- Public icon -->
              <svg v-if="playlist.is_public" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <!-- Private icon -->
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
              {{ playlist.is_public ? 'Public' : 'Private' }}
              <svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div v-if="showVisibilityMenu" class="absolute right-0 mt-1 w-44 bg-white border border-gray-100 rounded-lg shadow-lg z-10">
              <button
                @click="setVisibility(false)"
                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 transition-colors flex items-center gap-2"
                :class="!playlist.is_public ? 'text-orange-600' : 'text-gray-700'"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Private
                <svg v-if="!playlist.is_public" class="w-4 h-4 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              </button>
              <button
                @click="setVisibility(true)"
                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 transition-colors flex items-center gap-2"
                :class="playlist.is_public ? 'text-orange-600' : 'text-gray-700'"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Public
                <svg v-if="playlist.is_public" class="w-4 h-4 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Password Lock Button (visible when public) -->
          <button
            v-if="playlist.is_public"
            @click="showPasswordModal = true"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
            :class="playlist.has_password ? 'text-amber-700 bg-amber-50 hover:bg-amber-100' : 'text-gray-600 hover:bg-gray-100'"
            :title="playlist.has_password ? 'Change or remove password' : 'Set password'"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            {{ playlist.has_password ? 'Locked' : 'Lock' }}
          </button>

          <!-- Copy Link Button (visible when public) -->
          <button
            v-if="playlist.is_public"
            @click="copyShareLink"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            title="Copy share link"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
            </svg>
            Copy Link
          </button>

          <!-- Add Videos Button -->
          <button
            @click="showAddVideosModal = true"
            class="flex items-center gap-2 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm shadow-sm transition-colors"
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
          class="group flex items-center gap-4 p-3 bg-white border border-gray-100 rounded-xl hover:border-orange-200 hover:shadow-sm transition-all"
          :class="{ 'cursor-move': playlist.sort_by === 'manual' }"
          :draggable="playlist.sort_by === 'manual'"
          @dragstart="onDragStart($event, index)"
          @dragover.prevent="onDragOver($event, index)"
          @drop="onDrop($event, index)"
          @dragend="onDragEnd"
        >
          <!-- Drag Handle (only in manual mode) -->
          <div v-if="playlist.sort_by === 'manual'" class="text-gray-300 hover:text-gray-500 cursor-move">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
            </svg>
          </div>

          <!-- Position Number -->
          <div class="w-8 h-8 flex items-center justify-center text-sm font-medium text-gray-400">
            {{ index + 1 }}
          </div>

          <!-- Thumbnail -->
          <div
            class="relative w-32 flex-shrink-0 aspect-video rounded-lg overflow-hidden bg-gray-900 cursor-pointer"
            @click="openVideo(video.id)"
          >
            <img
              v-if="video.thumbnail"
              :src="video.thumbnail"
              :alt="video.title"
              class="w-full h-full object-cover"
              loading="lazy"
            />
            <div class="absolute bottom-1 right-1 bg-black/70 text-white text-[9px] px-1 py-0.5 rounded font-medium">
              {{ formatDuration(video.duration) }}
            </div>
          </div>

          <!-- Video Info -->
          <div class="flex-1 min-w-0 cursor-pointer" @click="openVideo(video.id)">
            <h3 class="font-medium text-gray-900 hover:text-orange-600 transition-colors truncate text-sm">
              {{ video.title }}
            </h3>
            <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
              <span>{{ formatDate(video.created_at) }}</span>
              <span v-if="video.added_at" class="text-gray-400">
                Added {{ formatDate(video.added_at) }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              @click.stop="openVideo(video.id)"
              class="p-2 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
              title="Play video"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
              </svg>
            </button>
            <button
              @click.stop="removeVideo(video)"
              class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              title="Remove from playlist"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty Playlist -->
      <div v-else class="text-center py-16">
        <div class="w-16 h-16 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
          <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
          </svg>
        </div>
        <h3 class="text-gray-900 font-medium mb-1">No videos in this playlist</h3>
        <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
          Add videos to this playlist to start organizing your content.
        </p>
        <button
          @click="showAddVideosModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm shadow-sm transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Add Videos
        </button>
      </div>
    </template>

    <!-- Password Modal -->
    <SBModal v-model="showPasswordModal" :title="playlist?.has_password ? 'Change Password' : 'Set Password'">
      <form @submit.prevent="savePassword" class="space-y-4">
        <p class="text-sm text-gray-500">
          {{ playlist?.has_password ? 'Change or remove the password for this shared playlist.' : 'Add a password to protect this shared playlist. Viewers will need to enter the password to access it.' }}
        </p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            v-model="passwordForm.password"
            type="password"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
            placeholder="Enter password (min 4 characters)"
            :required="!playlist?.has_password"
            minlength="4"
          />
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
          <button
            v-if="playlist?.has_password"
            type="button"
            @click="removePassword"
            :disabled="savingPassword"
            class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors"
          >
            Remove Password
          </button>
          <div v-else></div>
          <div class="flex gap-2">
            <button
              type="button"
              @click="showPasswordModal = false"
              class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="savingPassword || (!passwordForm.password || passwordForm.password.length < 4)"
              class="px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 text-white rounded-lg font-medium text-sm transition-colors"
            >
              {{ savingPassword ? 'Saving...' : 'Set Password' }}
            </button>
          </div>
        </div>
      </form>
    </SBModal>

    <!-- Add Videos Modal -->
    <SBModal v-model="showAddVideosModal" title="Add Videos to Playlist" size="lg">
      <div v-if="loadingVideos" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-orange-600 border-t-transparent"></div>
        <p class="mt-3 text-sm text-gray-500">Loading videos...</p>
      </div>

      <div v-else-if="availableVideos.length === 0" class="text-center py-8">
        <p class="text-gray-500">No videos available to add.</p>
      </div>

      <div v-else class="space-y-2 max-h-96 overflow-y-auto">
        <div
          v-for="video in availableVideos"
          :key="video.id"
          class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer"
          @click="toggleVideoSelection(video)"
        >
          <div class="flex-shrink-0">
            <div
              class="w-5 h-5 border-2 rounded flex items-center justify-center transition-colors"
              :class="selectedVideos.includes(video.id) ? 'bg-orange-600 border-orange-600' : 'border-gray-300'"
            >
              <svg v-if="selectedVideos.includes(video.id)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </div>
          </div>
          <div class="w-20 aspect-video rounded overflow-hidden bg-gray-200 flex-shrink-0">
            <img v-if="video.thumbnail" :src="video.thumbnail" :alt="video.title" class="w-full h-full object-cover" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ video.title }}</p>
            <p class="text-xs text-gray-500">{{ formatDuration(video.duration) }}</p>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-100">
        <span class="text-sm text-gray-500">{{ selectedVideos.length }} selected</span>
        <div class="flex gap-2">
          <button
            @click="showAddVideosModal = false"
            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
          >
            Cancel
          </button>
          <button
            @click="addSelectedVideos"
            :disabled="selectedVideos.length === 0 || addingVideos"
            class="px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 text-white rounded-lg font-medium text-sm transition-colors"
          >
            {{ addingVideos ? 'Adding...' : 'Add to Playlist' }}
          </button>
        </div>
      </div>
    </SBModal>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
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
const showVisibilityMenu = ref(false)
const showAddVideosModal = ref(false)
const showPasswordModal = ref(false)
const savingPassword = ref(false)
const passwordForm = ref({ password: '' })
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

async function setVisibility(makePublic) {
  showVisibilityMenu.value = false
  if (playlist.value.is_public === makePublic) return

  try {
    const result = await playlistService.toggleSharing(playlist.value.id)
    playlist.value.is_public = result.is_public
    playlist.value.share_url = result.share_url

    if (result.is_public) {
      toast.success('Playlist is now public')
    } else {
      toast.success('Playlist is now private')
    }
  } catch (error) {
    console.error('Failed to update visibility:', error)
    toast.error('Failed to update visibility')
  }
}

async function copyShareLink() {
  if (!playlist.value.share_url) return
  try {
    await navigator.clipboard.writeText(playlist.value.share_url)
    toast.success('Link copied to clipboard!')
  } catch (error) {
    toast.error('Failed to copy link')
  }
}

async function savePassword() {
  if (!passwordForm.value.password || passwordForm.value.password.length < 4) return

  savingPassword.value = true
  try {
    const result = await playlistService.setPassword(playlist.value.id, passwordForm.value.password)
    playlist.value.has_password = result.has_password
    showPasswordModal.value = false
    passwordForm.value.password = ''
    toast.success('Password set successfully')
  } catch (error) {
    console.error('Failed to set password:', error)
    toast.error(error.message || 'Failed to set password')
  } finally {
    savingPassword.value = false
  }
}

async function removePassword() {
  savingPassword.value = true
  try {
    const result = await playlistService.removePassword(playlist.value.id)
    playlist.value.has_password = result.has_password
    showPasswordModal.value = false
    passwordForm.value.password = ''
    toast.success('Password removed')
  } catch (error) {
    console.error('Failed to remove password:', error)
    toast.error(error.message || 'Failed to remove password')
  } finally {
    savingPassword.value = false
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
  if (!event.target.closest('[data-dropdown-menu]')) {
    showSortMenu.value = false
    showVisibilityMenu.value = false
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

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
</style>
