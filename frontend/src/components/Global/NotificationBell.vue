<template>
  <div class="relative" ref="dropdownRef">
    <button
      @click="toggleDropdown"
      data-notification-bell
      class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <span
        v-if="totalBadge > 0"
        class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 flex items-center justify-center text-[9px] font-bold text-white bg-orange-500 rounded-full border-2 border-white"
      >
        {{ totalBadge > 9 ? '9+' : totalBadge }}
      </span>
    </button>

    <!-- Dropdown Panel -->
    <Transition name="dropdown">
      <div
        v-show="showDropdown"
        class="absolute right-0 mt-2 w-[480px] bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden"
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
          <button
            v-if="unreadCount > 0"
            @click="handleMarkAllAsRead"
            class="text-xs text-orange-600 hover:text-orange-700 font-medium"
          >
            Mark all as read
          </button>
        </div>

        <!-- Tabs -->
        <div class="px-2 py-2 border-b border-gray-100">
          <div class="flex gap-1">
            <button
              v-for="tab in notificationTabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="px-2.5 py-1 text-[11px] font-medium rounded-lg transition-all"
              :class="activeTab === tab.id
                ? 'bg-orange-100 text-orange-700'
                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
            >
              {{ tab.label }}
              <span v-if="tab.id === 'downloads' && processingCount > 0" class="ml-1 px-1.5 py-0.5 text-[9px] font-bold bg-orange-500 text-white rounded-full">{{ processingCount }}</span>
            </button>
          </div>
        </div>

        <!-- List -->
        <div class="max-h-[560px] overflow-y-auto">
          <!-- Loading -->
          <div v-if="loadingNotifications" class="px-4 py-8 text-center">
            <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-orange-600 border-t-transparent"></div>
            <p class="mt-2 text-xs text-gray-500">Loading notifications...</p>
          </div>

          <!-- Empty -->
          <div v-else-if="displayItems.length === 0" class="px-4 py-8 text-center">
            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p class="text-sm text-gray-500">
              {{ activeTab === 'all' ? 'No notifications yet' : activeTab === 'downloads' ? 'No downloads' : `No ${activeTab} notifications` }}
            </p>
          </div>

          <!-- Items -->
          <div v-else>
            <!-- Processing downloads (shown at top for downloads tab or all tab) -->
            <template v-if="activeTab === 'all' || activeTab === 'downloads'">
              <div
                v-for="dl in processingDownloads"
                :key="'dl-' + dl.videoId"
                class="px-4 py-3 bg-orange-50/50 border-b border-gray-50 transition-colors"
              >
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-orange-100 text-orange-600">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-700">Converting "<span class="text-gray-900">{{ dl.videoTitle }}</span>" to MP4...</p>
                    <p class="text-[10px] text-orange-600 mt-1">Processing - we'll notify you when ready</p>
                  </div>
                </div>
              </div>
            </template>

            <!-- Server notifications -->
            <div
              v-for="notification in displayNotifications"
              :key="notification.id"
              @click="handleNotificationClick(notification)"
              class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors"
              :class="{ 'bg-orange-50/50': !notification.read_at }"
            >
              <div class="flex items-start gap-3">
                <!-- Icon -->
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                  :class="getIconClass(notification.type)"
                >
                  <!-- comment -->
                  <svg v-if="notification.type === 'comment'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                  </svg>
                  <!-- viewer -->
                  <svg v-else-if="notification.type === 'viewer'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  <!-- warning -->
                  <svg v-else-if="notification.type === 'warning'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                  <!-- success -->
                  <svg v-else-if="notification.type === 'success'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <!-- feedback -->
                  <svg v-else-if="notification.type === 'feedback'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                  </svg>
                  <!-- download -->
                  <svg v-else-if="notification.type === 'download'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                  <!-- edit_complete -->
                  <svg v-else-if="notification.type === 'edit_complete'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <!-- default -->
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                  </svg>
                </div>

                <div class="flex-1 min-w-0">
                  <p
                    class="text-sm text-gray-700 notification-message"
                    :class="{ 'font-medium': !notification.read_at }"
                    v-html="notification.message"
                  ></p>
                  <p class="text-[10px] text-gray-400 mt-1">{{ formatTime(notification.created_at) }}</p>
                </div>

                <!-- Download button for ready downloads -->
                <button
                  v-if="notification.type === 'download' && notification.link"
                  @click.stop="handleDownloadClick(notification)"
                  class="flex-shrink-0 px-2.5 py-1.5 bg-green-600 hover:bg-green-700 text-white text-[11px] font-semibold rounded-lg transition-colors flex items-center gap-1"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                  Download
                </button>
                <!-- Mark as read -->
                <button
                  v-else-if="!notification.read_at"
                  @click.stop="markAsRead(notification)"
                  class="flex-shrink-0 p-1 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-md transition-colors"
                  title="Mark as read"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                </button>
                <div v-else class="w-6 flex-shrink-0"></div>
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
import { useRouter } from 'vue-router'
import notificationService from '@/services/notificationService'
import videoService from '@/services/videoService'
import { useDownloadTracker } from '@/composables/useDownloadTracker'

export default {
  name: 'NotificationBell',
  setup() {
    const router = useRouter()
    const dropdownRef = ref(null)
    const showDropdown = ref(false)
    const unreadCount = ref(0)
    const notifications = ref([])
    const loadingNotifications = ref(false)
    const activeTab = ref('all')
    let pollInterval = null

    const {
      downloads,
      triggerDownload,
      removeDownload,
      startTracking,
      stopTracking,
      processingCount,
      badgeCount: downloadBadge
    } = useDownloadTracker()

    const notificationTabs = [
      { id: 'all', label: 'All' },
      { id: 'unread', label: 'Unread' },
      { id: 'downloads', label: 'Downloads' },
      { id: 'comment', label: 'Comments' },
      { id: 'viewer', label: 'Views' }
    ]

    const totalBadge = computed(() => unreadCount.value + processingCount.value)

    const processingDownloads = computed(() =>
      downloads.value.filter(d => d.status === 'processing')
    )

    // Filter notifications based on active tab
    const displayNotifications = computed(() => {
      if (activeTab.value === 'all') return notifications.value
      if (activeTab.value === 'unread') return notifications.value.filter(n => !n.read_at)
      if (activeTab.value === 'downloads') return notifications.value.filter(n => n.type === 'download')
      return notifications.value.filter(n => n.type === activeTab.value)
    })

    // Combined list for empty state check
    const displayItems = computed(() => {
      const notifs = displayNotifications.value
      const processing = (activeTab.value === 'all' || activeTab.value === 'downloads') ? processingDownloads.value : []
      return [...processing, ...notifs]
    })

    // Fetch unread count
    const fetchUnreadCount = async () => {
      try {
        unreadCount.value = await notificationService.getUnreadCount()
      } catch {}
    }

    // Fetch notifications
    const fetchNotifications = async () => {
      loadingNotifications.value = true
      try {
        const data = await notificationService.getNotifications(1, 10)
        notifications.value = data.notifications || []

        // Auto-clear processing downloads that now have a ready notification
        for (const dl of processingDownloads.value) {
          const match = notifications.value.find(n =>
            n.type === 'download' &&
            n.link &&
            n.link.includes(`/videos/${dl.videoId}/download-mp4`)
          )
          if (match) {
            removeDownload(dl.videoId)
          }
        }
      } catch {
        notifications.value = []
      } finally {
        loadingNotifications.value = false
      }
    }

    const toggleDropdown = () => {
      showDropdown.value = !showDropdown.value
      if (showDropdown.value) {
        fetchNotifications()
      }
    }

    const handleMarkAllAsRead = async () => {
      try {
        await notificationService.markAllAsRead()
        notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }))
        unreadCount.value = 0
      } catch {}
    }

    const handleNotificationClick = async (notification) => {
      if (!notification.read_at) {
        try {
          await notificationService.markAsRead(notification.id)
          notification.read_at = new Date().toISOString()
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        } catch {}
      }

      // Download notifications handled by the download button, not click
      if (notification.type === 'download') return

      if (notification.link) {
        showDropdown.value = false
        router.push(notification.link)
      }
    }

    const handleDownloadClick = async (notification) => {
      if (!notification.link) return
      try {
        const match = notification.link.match(/\/videos\/(\d+)\/download-mp4/)
        if (match) {
          const blob = await videoService.downloadMp4(match[1])
          if (blob) {
            const blobUrl = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = blobUrl
            link.download = 'video.mp4'
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(blobUrl)
          }
        }
      } catch (err) {
        console.error('Failed to download MP4:', err)
      }

      // Mark as read
      if (!notification.read_at) {
        try {
          await notificationService.markAsRead(notification.id)
          notification.read_at = new Date().toISOString()
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        } catch {}
      }
    }

    const markAsRead = async (notification) => {
      if (notification.read_at) return
      try {
        await notificationService.markAsRead(notification.id)
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      } catch {}
    }

    const formatTime = (dateString) => {
      const date = new Date(dateString)
      const now = new Date()
      const diffMs = now - date
      const diffMins = Math.floor(diffMs / 60000)
      const diffHours = Math.floor(diffMs / 3600000)
      const diffDays = Math.floor(diffMs / 86400000)

      if (diffMins < 1) return 'Just now'
      if (diffMins < 60) return `${diffMins}m ago`
      if (diffHours < 24) return `${diffHours}h ago`
      if (diffDays < 7) return `${diffDays}d ago`
      return date.toLocaleDateString()
    }

    const getIconClass = (type) => {
      switch (type) {
        case 'comment': return 'bg-blue-100 text-blue-600'
        case 'viewer': return 'bg-green-100 text-green-600'
        case 'warning': return 'bg-yellow-100 text-yellow-600'
        case 'success': return 'bg-emerald-100 text-emerald-600'
        case 'edit_complete': return 'bg-emerald-100 text-emerald-600'
        case 'feedback': return 'bg-purple-100 text-purple-600'
        case 'download': return 'bg-indigo-100 text-indigo-600'
        default: return 'bg-gray-100 text-gray-600'
      }
    }

    const handleClickOutside = (event) => {
      if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showDropdown.value = false
      }
    }

    onMounted(() => {
      fetchUnreadCount()
      startTracking()
      pollInterval = setInterval(fetchUnreadCount, 30000)
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      stopTracking()
      if (pollInterval) clearInterval(pollInterval)
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      dropdownRef,
      showDropdown,
      unreadCount,
      notifications,
      loadingNotifications,
      activeTab,
      notificationTabs,
      totalBadge,
      processingCount,
      processingDownloads,
      displayNotifications,
      displayItems,
      toggleDropdown,
      handleMarkAllAsRead,
      handleNotificationClick,
      handleDownloadClick,
      markAsRead,
      formatTime,
      getIconClass
    }
  }
}
</script>

<style scoped>
.notification-message :deep(strong) {
  font-weight: 600;
  color: #1f2937;
}
</style>
