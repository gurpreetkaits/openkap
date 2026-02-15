<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-orange-600 border-t-transparent"></div>
        <p class="mt-4 text-gray-500">Loading playlist...</p>
      </div>
    </div>

    <!-- Password Required State -->
    <div v-else-if="passwordRequired" class="flex items-center justify-center min-h-screen">
      <div class="w-full max-w-sm mx-auto p-8">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8 text-center">
          <div class="w-16 h-16 mx-auto mb-6 bg-amber-100 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-gray-900 mb-2">Password Protected</h2>
          <p class="text-gray-500 mb-6 text-sm">This playlist is password protected. Enter the password to view it.</p>
          <form @submit.prevent="submitPassword" class="space-y-4">
            <input
              v-model="passwordInput"
              type="password"
              placeholder="Enter password"
              required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': passwordError }"
            />
            <p v-if="passwordError" class="text-sm text-red-600">{{ passwordError }}</p>
            <button
              type="submit"
              :disabled="submittingPassword || !passwordInput"
              class="w-full px-4 py-2.5 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 text-white rounded-lg font-medium text-sm transition-colors"
            >
              {{ submittingPassword ? 'Checking...' : 'Unlock Playlist' }}
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center max-w-md mx-auto p-8">
        <div class="w-16 h-16 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Playlist Not Available</h2>
        <p class="text-gray-500 mb-6">{{ error }}</p>
        <a
          href="/"
          class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm transition-colors"
        >
          Go to Homepage
        </a>
      </div>
    </div>

    <!-- Playlist Content -->
    <template v-else-if="playlist">
      <!-- Header -->
      <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <a href="/" class="flex items-center gap-2">
                <img src="/logo.png" alt="ScreenSense" class="w-8 h-8 rounded-lg" />
                <span class="text-gray-900 font-semibold text-lg hidden sm:inline">ScreenSense</span>
              </a>
            </div>
            <a
              href="/login"
              class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium text-sm transition-colors"
            >
              Sign Up Free
            </a>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Playlist Info -->
        <div class="mb-8">
          <div class="flex items-start gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-orange-100 to-orange-200 rounded-xl flex items-center justify-center flex-shrink-0">
              <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
            </div>
            <div class="flex-1">
              <h1 class="text-2xl font-bold text-gray-900">{{ playlist.title }}</h1>
              <p v-if="playlist.description" class="text-gray-600 mt-1">{{ playlist.description }}</p>
              <div class="flex items-center gap-3 text-sm text-gray-500 mt-2">
                <span>{{ playlist.videos_count }} {{ playlist.videos_count === 1 ? 'video' : 'videos' }}</span>
                <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                <span>Shared playlist</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Videos List -->
        <div v-if="playlist.videos && playlist.videos.length > 0" class="space-y-4">
          <div
            v-for="(video, index) in playlist.videos"
            :key="video.id"
            class="group flex items-center gap-4 p-4 bg-white border border-gray-200 rounded-xl hover:shadow-md hover:border-orange-200 transition-all cursor-pointer"
            @click="openVideo(video)"
          >
            <!-- Position -->
            <div class="w-8 h-8 flex items-center justify-center text-sm font-medium text-gray-400">
              {{ index + 1 }}
            </div>

            <!-- Thumbnail -->
            <div class="relative w-40 sm:w-48 flex-shrink-0 aspect-video rounded-lg overflow-hidden bg-gray-900">
              <img
                v-if="video.thumbnail"
                :src="video.thumbnail"
                :alt="video.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
              />
              <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900">
                <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
              </div>

              <!-- Duration Badge -->
              <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                {{ formatDuration(video.duration) }}
              </div>

              <!-- Play Overlay -->
              <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                <div class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-orange-600 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Video Info -->
            <div class="flex-1 min-w-0">
              <h3 class="font-medium text-gray-900 group-hover:text-orange-600 transition-colors text-base sm:text-lg line-clamp-2">
                {{ video.title }}
              </h3>
              <p v-if="video.description" class="text-sm text-gray-500 mt-1 line-clamp-1 hidden sm:block">
                {{ video.description }}
              </p>
              <div class="flex items-center gap-2 text-xs text-gray-400 mt-2">
                <span>{{ formatDate(video.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-16">
          <div class="w-16 h-16 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>
          <h3 class="text-gray-900 font-medium mb-1">No videos in this playlist</h3>
          <p class="text-sm text-gray-500">This playlist is empty.</p>
        </div>
      </main>

      <!-- Footer -->
      <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
              <img src="/logo.png" alt="ScreenSense" class="w-6 h-6 rounded-lg" />
              <span class="text-gray-600 text-sm">ScreenSense - Screen Recording Made Simple</span>
            </div>
            <a
              href="/login"
              class="text-orange-600 hover:text-orange-700 text-sm font-medium"
            >
              Create your own playlists for free
            </a>
          </div>
        </div>
      </footer>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import playlistService from '@/services/playlistService'
import { formatDistanceToNow } from 'date-fns'

const route = useRoute()
const playlist = ref(null)
const loading = ref(true)
const error = ref(null)
const passwordRequired = ref(false)
const passwordInput = ref('')
const passwordError = ref('')
const submittingPassword = ref(false)

async function fetchPlaylist(password = null) {
  loading.value = true
  error.value = null
  passwordRequired.value = false

  try {
    playlist.value = await playlistService.getSharedPlaylist(route.params.token, password)
  } catch (err) {
    console.error('Failed to fetch shared playlist:', err)
    if (err.passwordRequired) {
      passwordRequired.value = true
      if (password) {
        passwordError.value = 'Incorrect password'
      }
    } else {
      error.value = err.message || 'This playlist is not available.'
    }
  } finally {
    loading.value = false
  }
}

async function submitPassword() {
  if (!passwordInput.value) return
  passwordError.value = ''
  submittingPassword.value = true
  try {
    await fetchPlaylist(passwordInput.value)
  } finally {
    submittingPassword.value = false
  }
}

function openVideo(video) {
  if (video.share_url) {
    window.location.href = video.share_url
  }
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

onMounted(() => {
  fetchPlaylist()
})
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
