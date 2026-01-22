<template>
  <div class="animate-fade-in max-w-7xl mx-auto p-6 lg:p-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-xl font-semibold text-base-content">Favourites</h1>
        <p class="text-sm text-base-content/60 mt-1">Your starred videos</p>
      </div>

      <!-- View Toggle -->
      <div class="join border border-base-300 bg-base-100 shadow-sm">
        <button
          @click="viewMode = 'grid'"
          class="join-item btn btn-sm btn-ghost"
          :class="viewMode === 'grid' ? 'btn-active' : ''"
          title="Grid view"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
          </svg>
        </button>
        <button
          @click="viewMode = 'list'"
          class="join-item btn btn-sm btn-ghost"
          :class="viewMode === 'list' ? 'btn-active' : ''"
          title="List view"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <span class="loading loading-spinner loading-lg text-primary"></span>
      <p class="mt-4 text-sm text-base-content/60">Loading favourites...</p>
    </div>

    <!-- Grid View -->
    <div v-else-if="videos.length > 0 && viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div
        v-for="video in videos"
        :key="video.id"
        class="group relative"
      >
        <!-- Thumbnail -->
        <div
          class="relative aspect-video bg-neutral rounded-xl overflow-hidden mb-3 border border-base-300 shadow-sm group-hover:shadow-md transition-all cursor-pointer"
          @click="openVideo(video.id)"
        >
          <img
            v-if="video.thumbnail"
            :src="video.thumbnail"
            :alt="video.title"
            class="w-full h-full object-cover opacity-90 group-hover:scale-105 group-hover:opacity-100 transition-all duration-500"
            loading="lazy"
          />
          <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-neutral to-neutral-focus">
            <svg class="w-10 h-10 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>

          <!-- Duration Badge -->
          <div class="badge badge-neutral badge-sm absolute bottom-2 right-2 z-10">
            {{ formatDuration(video.duration) }}
          </div>

          <!-- Favourite Badge -->
          <div class="absolute top-2 left-2 bg-primary text-primary-content p-1 rounded-lg">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
          </div>

          <!-- Hover Overlay -->
          <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-200 flex flex-col justify-between p-3">
            <!-- Top Actions -->
            <div class="flex justify-between items-start">
              <button
                @click.stop="removeFavourite(video)"
                class="btn btn-circle btn-sm btn-primary"
                title="Remove from favourites"
              >
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
              </button>
              <div class="flex gap-2">
                <button @click.stop="shareVideo(video)" class="btn btn-circle btn-sm bg-base-100 hover:text-info" title="Copy Link">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                  </svg>
                </button>
                <button @click.stop="downloadVideo(video)" class="btn btn-circle btn-sm bg-base-100 hover:text-success" title="Download">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Center Play Button -->
            <div class="flex justify-center">
              <div class="w-10 h-10 bg-base-100/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg text-primary scale-90 hover:scale-110 transition-transform">
                <svg class="w-4 h-4 ml-0.5 fill-current" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
                </svg>
              </div>
            </div>

            <div class="h-8"></div>
          </div>
        </div>

        <!-- Video Info -->
        <div class="px-1">
          <h3
            class="font-medium text-base-content text-[14px] leading-snug truncate group-hover:text-primary transition-colors cursor-pointer"
            @click="openVideo(video.id)"
          >
            {{ video.title }}
          </h3>
          <div class="flex items-center gap-2 text-[12px] text-base-content/60 mt-1">
            <span>{{ formatDate(video.created_at) }}</span>
            <span class="w-0.5 h-0.5 bg-base-content/30 rounded-full"></span>
            <span class="flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              {{ video.views_count || 0 }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-else-if="videos.length > 0 && viewMode === 'list'" class="space-y-3">
      <div
        v-for="video in videos"
        :key="video.id"
        class="group cursor-pointer flex items-center gap-4 p-3 card bg-base-100 border border-base-300 hover:border-primary/30 hover:shadow-md transition-all duration-200"
        @click="openVideo(video.id)"
      >
        <!-- Thumbnail -->
        <div class="relative w-40 flex-shrink-0 aspect-video rounded-lg overflow-hidden bg-neutral">
          <img
            v-if="video.thumbnail"
            :src="video.thumbnail"
            :alt="video.title"
            class="w-full h-full object-cover"
            loading="lazy"
          />
          <div class="badge badge-neutral badge-xs absolute bottom-1.5 right-1.5">
            {{ formatDuration(video.duration) }}
          </div>
          <div class="absolute top-1.5 left-1.5 bg-primary text-primary-content p-0.5 rounded">
            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
          </div>
        </div>

        <!-- Video Info -->
        <div class="flex-1 min-w-0">
          <h3 class="font-medium text-base-content group-hover:text-primary transition-colors truncate text-[15px] mb-1">
            {{ video.title }}
          </h3>
          <div class="flex items-center gap-3 text-[12px] text-base-content/60">
            <span>{{ formatDate(video.created_at) }}</span>
            <div class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <span>{{ video.views_count || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button @click.stop="removeFavourite(video)" class="btn btn-ghost btn-sm btn-circle text-primary hover:bg-primary/10" title="Remove from favourites">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
          </button>
          <button @click.stop="shareVideo(video)" class="btn btn-ghost btn-sm btn-circle hover:text-info" title="Copy Link">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
          </button>
          <button @click.stop="downloadVideo(video)" class="btn btn-ghost btn-sm btn-circle hover:text-success" title="Download">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-20">
      <div class="w-16 h-16 mx-auto mb-6 bg-base-200 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
        </svg>
      </div>
      <h3 class="text-base-content font-medium mb-1">No favourites yet</h3>
      <p class="text-sm text-base-content/60 max-w-md mx-auto mb-6">
        Mark videos as favourites to see them here.
      </p>
      <router-link
        to="/videos"
        class="btn btn-neutral"
      >
        Browse Library
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import videoService from '@/services/videoService'
import toast from '@/services/toastService'
import { formatDistanceToNow } from 'date-fns'

const router = useRouter()
const videos = ref([])
const loading = ref(true)
const viewMode = ref('grid')

async function fetchFavourites() {
  loading.value = true
  try {
    videos.value = await videoService.getFavourites()
  } catch (error) {
    console.error('Failed to fetch favourites:', error)
    toast.error('Failed to load favourites')
  } finally {
    loading.value = false
  }
}

function openVideo(id) {
  router.push(`/video/${id}`)
}

async function shareVideo(video) {
  if (video.share_url) {
    try {
      await navigator.clipboard.writeText(video.share_url)
      toast.success('Link copied to clipboard!')
    } catch (err) {
      console.error('Failed to copy:', err)
      toast.error('Failed to copy link')
    }
  }
}

async function downloadVideo(video) {
  if (!video.url) return
  try {
    toast.success('Starting download...')
    const response = await fetch(video.url)
    const blob = await response.blob()
    const blobUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = blobUrl
    link.download = `${video.title || 'video'}.webm`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(blobUrl)
    toast.success('Download complete!')
  } catch (err) {
    console.error('Failed to download:', err)
    toast.error('Failed to download video')
  }
}

async function removeFavourite(video) {
  try {
    await videoService.toggleFavourite(video.id)
    videos.value = videos.value.filter(v => v.id !== video.id)
    toast.success('Removed from favourites')
  } catch (error) {
    console.error('Failed to remove favourite:', error)
    toast.error('Failed to update favourite')
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
  fetchFavourites()
})
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
</style>
