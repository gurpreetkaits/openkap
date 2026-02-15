<template>
  <div class="bg-[#FAFAFA] text-slate-900 h-screen flex flex-col overflow-hidden selection:bg-orange-100 selection:text-orange-700">

    <!-- Subtle Background Grid -->
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 32px 32px; opacity: 0.4;"></div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Screenshot Not Found</h3>
        <p class="text-gray-500 mb-6">{{ error }}</p>
        <a href="/" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors inline-block">
          Go Home
        </a>
      </div>
    </div>

    <!-- Main Content -->
    <template v-else-if="screenshot">
      <!-- Navigation -->
      <nav class="h-14 border-b border-gray-200/60 bg-white/80 backdrop-blur-md flex items-center justify-between px-4 lg:px-6 z-50 fixed top-0 w-full">
        <div class="flex items-center gap-3">
          <a href="/" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
            <img :src="branding.logoUrl.value || '/logo.png'" alt="ScreenSense" class="w-8 h-8 rounded-lg shadow-sm" />
            <span class="text-sm font-semibold text-gray-900">ScreenSense</span>
          </a>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="downloadScreenshot"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download
          </button>
          <button
            @click="showShareModal = true"
            class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium shadow-md shadow-orange-200 transition-all flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            Share
          </button>
        </div>
      </nav>

      <!-- Share Modal -->
      <div v-if="showShareModal" class="fixed inset-0 z-[60] flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm transition-opacity" @click="showShareModal = false"></div>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md relative z-10 border border-gray-100 overflow-hidden transform transition-all duration-200 mx-4">
          <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-sm font-semibold text-gray-900">Share Screenshot</h3>
            <button @click="showShareModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <div class="p-5">
            <div class="flex gap-4 mb-6">
              <button @click="shareViaEmail" class="flex-1 flex flex-col items-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-orange-500 hover:bg-orange-50/50 transition-all group">
                <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <span class="text-xs font-medium text-gray-600 group-hover:text-orange-700">Email</span>
              </button>
              <button @click="shareOnTwitter" class="flex-1 flex flex-col items-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-orange-500 hover:bg-orange-50/50 transition-all group">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                  </svg>
                </div>
                <span class="text-xs font-medium text-gray-600 group-hover:text-blue-700">Twitter</span>
              </button>
            </div>

            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2 block">Public Link</label>
            <div class="flex gap-2">
              <div class="flex-1 relative">
                <input type="text" :value="shareUrl" class="w-full pl-9 pr-3 py-2 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-orange-500 text-gray-600" readonly>
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
              </div>
              <button @click="copyLink" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded-lg text-xs font-medium transition-colors">
                {{ copied ? 'Copied!' : 'Copy' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Layout -->
      <main class="flex-1 flex flex-col items-center justify-center p-3 lg:p-8 pt-14 z-10 relative">

        <div class="w-full max-w-5xl flex flex-col">

          <!-- Screenshot Title Above Container -->
          <div class="w-full mb-4">
            <div class="flex items-center gap-3 mb-1">
              <h1 class="text-2xl font-bold text-gray-900 tracking-tight leading-tight">
                {{ screenshot.title }}
              </h1>
            </div>
            <div class="flex items-center gap-3 text-sm text-gray-500">
              <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ formatTimeAgo(screenshot.created_at) }}
              </span>
            </div>
          </div>

          <!-- Screenshot Container -->
          <div class="relative w-full bg-black rounded-xl shadow-2xl ring-1 ring-black/10 overflow-hidden">
            <img
              :src="screenshot.image_url"
              :alt="screenshot.title"
              class="w-full h-auto max-h-[calc(100vh-200px)] object-contain"
              @error="handleImageError"
            />
          </div>

          <!-- Footer -->
          <div class="mt-6 text-center">
            <p class="text-gray-400 text-sm">
              Shared via <a href="/" class="text-orange-500 hover:text-orange-600 font-medium">ScreenSense</a>
            </p>
          </div>

        </div>
      </main>
    </template>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import screenshotService from '@/services/screenshotService'
import { useBranding } from '@/composables/useBranding'

export default {
  name: 'SharedScreenshotView',
  setup() {
    const route = useRoute()
    const branding = useBranding()
    const screenshot = ref(null)
    const loading = ref(true)
    const error = ref(null)
    const showShareModal = ref(false)
    const copied = ref(false)

    const shareUrl = computed(() => window.location.href)

    const fetchScreenshot = async () => {
      try {
        const token = route.params.token
        const data = await screenshotService.getSharedScreenshot(token)

        if (!data) {
          error.value = 'This screenshot is no longer available or the link has expired.'
          return
        }

        screenshot.value = data

        // Apply owner's branding
        if (data.branding) {
          if (data.branding.brand_color) {
            branding.setBrandColor(data.branding.brand_color)
          }
          if (data.branding.logo_url) {
            branding.setLogoUrl(data.branding.logo_url)
          }
        }
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
        copied.value = true
        setTimeout(() => {
          copied.value = false
        }, 2000)
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

    const shareViaEmail = () => {
      const subject = encodeURIComponent(screenshot.value?.title || 'Check out this screenshot')
      const body = encodeURIComponent(`Check out this screenshot: ${window.location.href}`)
      window.open(`mailto:?subject=${subject}&body=${body}`)
    }

    const shareOnTwitter = () => {
      const text = encodeURIComponent(screenshot.value?.title || 'Check out this screenshot')
      const url = encodeURIComponent(window.location.href)
      window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank')
    }

    const handleImageError = () => {
      error.value = 'Failed to load the screenshot image.'
      screenshot.value = null
    }

    const formatTimeAgo = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      const now = new Date()
      const diffMs = now - date
      const diffSec = Math.floor(diffMs / 1000)
      const diffMin = Math.floor(diffSec / 60)
      const diffHour = Math.floor(diffMin / 60)
      const diffDay = Math.floor(diffHour / 24)

      if (diffSec < 60) return 'Just now'
      if (diffMin < 60) return `${diffMin} minute${diffMin > 1 ? 's' : ''} ago`
      if (diffHour < 24) return `${diffHour} hour${diffHour > 1 ? 's' : ''} ago`
      if (diffDay < 7) return `${diffDay} day${diffDay > 1 ? 's' : ''} ago`
      return date.toLocaleDateString()
    }

    onMounted(() => {
      fetchScreenshot()
    })

    return {
      branding,
      screenshot,
      loading,
      error,
      showShareModal,
      copied,
      shareUrl,
      copyLink,
      downloadScreenshot,
      shareViaEmail,
      shareOnTwitter,
      handleImageError,
      formatTimeAgo
    }
  }
}
</script>
