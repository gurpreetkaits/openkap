<template>
  <div class="p-6 max-w-6xl mx-auto">
    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse">
      <div class="flex items-center gap-4 mb-8">
        <div class="w-12 h-12 rounded-lg bg-gray-200"></div>
        <div>
          <div class="h-6 bg-gray-200 rounded w-48 mb-2"></div>
          <div class="h-4 bg-gray-200 rounded w-32"></div>
        </div>
      </div>
      <div class="grid gap-4 md:grid-cols-3 mb-8">
        <div v-for="i in 3" :key="i" class="bg-white rounded-xl border border-gray-200 p-4">
          <div class="h-4 bg-gray-200 rounded w-20 mb-2"></div>
          <div class="h-6 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-12">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
      </div>
      <h2 class="text-xl font-semibold text-gray-900 mb-2">Access Denied</h2>
      <p class="text-gray-500 mb-4">You don't have access to this workspace.</p>
      <router-link to="/workspaces" class="text-orange-600 hover:text-orange-700 font-medium">
        Back to Workspaces
      </router-link>
    </div>

    <!-- Workspace Content -->
    <div v-else-if="workspace">
      <!-- Header -->
      <div class="flex items-start justify-between mb-8">
        <div class="flex items-center gap-4">
          <router-link to="/workspaces" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </router-link>
          <div class="flex items-center gap-3">
            <div v-if="workspace.logo_url" class="w-12 h-12 rounded-lg overflow-hidden">
              <img :src="workspace.logo_url" :alt="workspace.name" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">{{ workspace.name }}</h1>
                <span
                  v-if="workspace.has_active_subscription"
                  class="px-2 py-1 text-xs font-medium text-orange-700 bg-orange-100 rounded-full"
                >
                  {{ workspace.subscription_plan === 'team_plus' ? 'Team Plus' : 'Team' }}
                </span>
                <span
                  v-else
                  class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full"
                >
                  Free
                </span>
              </div>
              <p v-if="workspace.description" class="text-sm text-gray-500 mt-1">{{ workspace.description }}</p>
            </div>
          </div>
        </div>
        <div v-if="workspace.is_admin" class="flex items-center gap-2">
          <router-link
            :to="`/workspace/${slug}/members`"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Members
          </router-link>
          <router-link
            :to="`/workspace/${slug}/settings`"
            class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </router-link>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid gap-4 md:grid-cols-3 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-500 mb-1">Members</p>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="text-xl font-bold text-gray-900">{{ workspace.members_count }}</span>
            <span class="text-sm text-gray-500">/ {{ workspace.max_members }}</span>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-500 mb-1">Videos</p>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <span class="text-xl font-bold text-gray-900">{{ workspace.videos_count }}</span>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-500 mb-1">Storage Used</p>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
            </svg>
            <span class="text-lg font-semibold text-gray-900">{{ workspace.storage_used_gb?.toFixed(1) }} GB</span>
            <span class="text-sm text-gray-500">/ {{ workspace.max_storage_gb }} GB</span>
          </div>
          <div class="mt-2 w-full bg-gray-200 rounded-full h-1.5">
            <div
              class="bg-orange-500 h-1.5 rounded-full"
              :style="{ width: workspace.storage_usage_percent + '%' }"
            ></div>
          </div>
        </div>

      </div>

      <!-- Upgrade Banner -->
      <div
        v-if="!workspace.has_active_subscription"
        class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-8 flex items-center justify-between"
      >
        <div>
          <h3 class="font-semibold text-amber-800">Upgrade to Team Plan</h3>
          <p class="text-sm text-amber-700">Get video encoding, more storage, and advanced features for your team.</p>
        </div>
        <button
          @click="startCheckout"
          :disabled="checkingOut"
          class="px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <div v-if="checkingOut" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
          {{ checkingOut ? 'Redirecting...' : 'Upgrade Now' }}
        </button>
      </div>

      <!-- Videos Section -->
      <div>
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold text-gray-900">Videos</h2>
          <button
            v-if="workspace.can_record"
            class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            New Recording
          </button>
        </div>

        <!-- Videos Loading -->
        <div v-if="videosLoading" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div v-for="i in 3" :key="i" class="bg-white rounded-xl border border-gray-200 overflow-hidden animate-pulse">
            <div class="aspect-video bg-gray-200"></div>
            <div class="p-4">
              <div class="h-5 bg-gray-200 rounded w-3/4 mb-2"></div>
              <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            </div>
          </div>
        </div>

        <!-- Empty Videos -->
        <div v-else-if="videos.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No videos yet</h3>
          <p class="text-gray-500 mb-4">Start recording to share videos with your team.</p>
          <button
            v-if="workspace.can_record"
            class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            Start Recording
          </button>
        </div>

        <!-- Videos Grid -->
        <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="video in videos"
            :key="video.id"
            class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
          >
            <div class="relative aspect-video bg-gray-100">
              <img
                v-if="video.thumbnail_url"
                :src="video.thumbnail_url"
                :alt="video.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
              </div>
              <span class="absolute bottom-2 right-2 px-1.5 py-0.5 text-xs font-medium text-white bg-black/70 rounded">
                {{ formatDuration(video.duration) }}
              </span>
            </div>
            <div class="p-4">
              <h3 class="font-medium text-gray-900 truncate">{{ video.title }}</h3>
              <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                <span>{{ video.user?.name }}</span>
                <span class="text-gray-300">|</span>
                <span>{{ formatDate(video.created_at) }}</span>
              </div>
              <span
                v-if="video.conversion_status !== 'ready'"
                class="inline-block mt-2 px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full"
              >
                {{ video.conversion_status === 'processing' ? 'Processing...' : video.conversion_status }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import workspaceService from '@/services/workspaceService'

export default {
  name: 'WorkspaceDetailView',
  setup() {
    const route = useRoute()
    const slug = route.params.slug

    const workspace = ref(null)
    const videos = ref([])
    const loading = ref(true)
    const videosLoading = ref(true)
    const error = ref(false)
    const checkingOut = ref(false)

    const fetchWorkspace = async () => {
      loading.value = true
      error.value = false
      try {
        workspace.value = await workspaceService.getWorkspace(slug)
      } catch (err) {
        console.error('Failed to fetch workspace:', err)
        error.value = true
      } finally {
        loading.value = false
      }
    }

    const fetchVideos = async () => {
      videosLoading.value = true
      try {
        videos.value = await workspaceService.getWorkspaceVideos(slug)
      } catch (err) {
        console.error('Failed to fetch videos:', err)
      } finally {
        videosLoading.value = false
      }
    }

    const formatDuration = (seconds) => {
      const mins = Math.floor(seconds / 60)
      const secs = seconds % 60
      return `${mins}:${secs.toString().padStart(2, '0')}`
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })
    }

    const startCheckout = async () => {
      checkingOut.value = true
      try {
        await workspaceService.startCheckout(slug)
      } catch (err) {
        console.error('Checkout error:', err)
        alert(err.message || 'Failed to start checkout. Please try again.')
        checkingOut.value = false
      }
    }

    onMounted(() => {
      fetchWorkspace()
      fetchVideos()
    })

    return {
      slug,
      workspace,
      videos,
      loading,
      videosLoading,
      error,
      checkingOut,
      formatDuration,
      formatDate,
      startCheckout
    }
  }
}
</script>
