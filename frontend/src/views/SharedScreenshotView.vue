<template>
  <div class="min-h-screen bg-gray-900 flex items-center justify-center p-4">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center">
      <svg class="animate-spin h-8 w-8 text-white" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center">
      <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </div>
      <h2 class="text-white text-xl font-medium mb-2">Screenshot Not Found</h2>
      <p class="text-gray-400">{{ error }}</p>
    </div>

    <!-- Screenshot View -->
    <div v-else-if="screenshot" class="w-full max-w-5xl">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-white text-lg font-medium truncate">{{ screenshot.title }}</h1>
        <div class="flex items-center gap-2">
          <button
            @click="copyLink"
            class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
            </svg>
            Copy Link
          </button>
          <button
            @click="downloadScreenshot"
            class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download
          </button>
        </div>
      </div>

      <!-- Image -->
      <div class="relative bg-black rounded-lg overflow-hidden shadow-2xl">
        <img
          :src="screenshot.image_url"
          :alt="screenshot.title"
          class="w-full h-auto max-h-[80vh] object-contain"
          @error="handleImageError"
        />
      </div>

      <!-- Footer -->
      <div class="mt-4 text-center">
        <p class="text-gray-400 text-sm">
          Shared via <a href="/" class="text-orange-400 hover:text-orange-300">ScreenBuddy</a>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import screenshotService from '@/services/screenshotService'

export default {
  name: 'SharedScreenshotView',
  setup() {
    const route = useRoute()
    const screenshot = ref(null)
    const loading = ref(true)
    const error = ref(null)

    const fetchScreenshot = async () => {
      try {
        const token = route.params.token
        const data = await screenshotService.getSharedScreenshot(token)

        if (!data) {
          error.value = 'This screenshot is no longer available or the link has expired.'
          return
        }

        screenshot.value = data
      } catch (err) {
        console.error('Failed to fetch screenshot:', err)
        error.value = 'This screenshot is no longer available or the link has expired.'
      } finally {
        loading.value = false
      }
    }

    const copyLink = async () => {
      try {
        await navigator.clipboard.writeText(window.location.href)
        // Could add a toast notification here
        alert('Link copied to clipboard!')
      } catch (err) {
        console.error('Failed to copy link:', err)
      }
    }

    const downloadScreenshot = async () => {
      if (!screenshot.value?.image_url) return

      try {
        const response = await fetch(screenshot.value.image_url)
        if (!response.ok) throw new Error('Failed to fetch image')

        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url

        const title = screenshot.value.title || 'screenshot'
        const extension = blob.type.split('/')[1] || 'png'
        link.download = `${title}.${extension}`

        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (err) {
        console.error('Failed to download:', err)
        alert('Failed to download screenshot')
      }
    }

    const handleImageError = () => {
      error.value = 'Failed to load the screenshot image.'
      screenshot.value = null
    }

    onMounted(() => {
      fetchScreenshot()
    })

    return {
      screenshot,
      loading,
      error,
      copyLink,
      downloadScreenshot,
      handleImageError
    }
  }
}
</script>
