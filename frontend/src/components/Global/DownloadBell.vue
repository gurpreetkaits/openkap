<template>
  <div class="relative" ref="bellRef">
    <button
      @click="toggleDropdown"
      class="relative p-2 rounded-lg transition-colors"
      :class="badgeCount > 0
        ? 'text-orange-600 hover:text-orange-700 hover:bg-orange-50'
        : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'"
    >
      <!-- Bell Icon -->
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>

      <!-- Badge -->
      <span
        v-if="badgeCount > 0"
        class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 flex items-center justify-center text-[9px] font-bold text-white bg-orange-500 rounded-full border-2 border-white animate-pulse-once"
      >
        {{ badgeCount > 9 ? '9+' : badgeCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition name="bell-dropdown">
      <div
        v-show="showDropdown"
        class="absolute right-0 mt-2 w-[340px] bg-white rounded-xl shadow-xl border border-gray-200 z-[60] overflow-hidden"
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <h3 class="text-sm font-semibold text-gray-900">Downloads</h3>
          </div>
          <button
            v-if="downloads.length > 0"
            @click="clearAll"
            class="text-[11px] text-gray-400 hover:text-gray-600 font-medium transition-colors"
          >
            Clear all
          </button>
        </div>

        <!-- Downloads List -->
        <div class="max-h-[400px] overflow-y-auto">
          <!-- Empty State -->
          <div v-if="downloads.length === 0" class="px-4 py-8 text-center">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
            </div>
            <p class="text-sm text-gray-500">No active downloads</p>
            <p class="text-xs text-gray-400 mt-1">Downloads will appear here</p>
          </div>

          <!-- Download Items -->
          <div v-else>
            <div
              v-for="download in sortedDownloads"
              :key="download.videoId"
              class="px-4 py-3 border-b border-gray-50 last:border-0 transition-colors"
              :class="{
                'bg-orange-50/40': download.status === 'processing',
                'bg-green-50/40': download.status === 'ready',
                'bg-red-50/30': download.status === 'failed'
              }"
            >
              <div class="flex items-center gap-3">
                <!-- Status Icon -->
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                  :class="{
                    'bg-orange-100': download.status === 'processing',
                    'bg-green-100': download.status === 'ready',
                    'bg-red-100': download.status === 'failed'
                  }"
                >
                  <!-- Processing spinner -->
                  <svg v-if="download.status === 'processing'" class="w-4 h-4 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                  <!-- Ready checkmark -->
                  <svg v-else-if="download.status === 'ready'" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  <!-- Failed icon -->
                  <svg v-else class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ download.videoTitle }}</p>
                  <p class="text-[11px] mt-0.5" :class="{
                    'text-orange-600': download.status === 'processing',
                    'text-green-600': download.status === 'ready',
                    'text-red-500': download.status === 'failed'
                  }">
                    <template v-if="download.status === 'processing'">Converting to MP4...</template>
                    <template v-else-if="download.status === 'ready'">Ready to download</template>
                    <template v-else>Conversion failed</template>
                  </p>
                </div>

                <!-- Action Button -->
                <button
                  v-if="download.status === 'ready'"
                  @click="handleDownload(download)"
                  class="flex-shrink-0 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-[11px] font-semibold rounded-lg transition-colors flex items-center gap-1.5 shadow-sm"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                  Download
                </button>
                <button
                  v-else-if="download.status === 'failed'"
                  @click="removeDownload(download.videoId)"
                  class="flex-shrink-0 p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                  title="Dismiss"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useDownloadTracker } from '@/composables/useDownloadTracker'

export default {
  name: 'DownloadBell',
  setup() {
    const bellRef = ref(null)
    const showDropdown = ref(false)
    const downloading = ref(null)

    const {
      downloads,
      triggerDownload,
      removeDownload,
      clearAll,
      startTracking,
      stopTracking,
      badgeCount
    } = useDownloadTracker()

    const sortedDownloads = computed(() => {
      return [...downloads.value].sort((a, b) => {
        // Ready first, then processing, then failed
        const order = { ready: 0, processing: 1, failed: 2 }
        return (order[a.status] ?? 3) - (order[b.status] ?? 3)
      })
    })

    const toggleDropdown = () => {
      showDropdown.value = !showDropdown.value
    }

    const handleDownload = async (download) => {
      downloading.value = download.videoId
      await triggerDownload(download)
      downloading.value = null
    }

    const handleClickOutside = (event) => {
      if (bellRef.value && !bellRef.value.contains(event.target)) {
        showDropdown.value = false
      }
    }

    onMounted(() => {
      startTracking()
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      stopTracking()
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      bellRef,
      showDropdown,
      downloading,
      downloads,
      sortedDownloads,
      badgeCount,
      toggleDropdown,
      handleDownload,
      removeDownload,
      clearAll
    }
  }
}
</script>

<style scoped>
.bell-dropdown-enter-active,
.bell-dropdown-leave-active {
  transition: all 0.2s ease;
}

.bell-dropdown-enter-from,
.bell-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}

@keyframes pulse-once {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

.animate-pulse-once {
  animation: pulse-once 0.4s ease-in-out;
}
</style>
