<template>
  <div class="animate-fade-in py-4 px-4 lg:px-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-900">Notifications</h1>
        <p class="text-xs text-gray-500">Stay updated on your videos and activity</p>
      </div>
      <button
        v-if="hasUnread"
        @click="markAllRead"
        class="px-3 py-1.5 text-xs font-medium text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
      >
        Mark all read
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading && notifications.length === 0" class="bg-white rounded-lg border border-gray-200 p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="notifications.length === 0" class="bg-white rounded-lg border border-gray-200 p-8 text-center">
      <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <p class="mt-2 text-sm text-gray-500">No notifications yet</p>
      <p class="text-xs text-gray-400 mt-1">We'll notify you when something happens</p>
    </div>

    <!-- Notifications List -->
    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="divide-y divide-gray-100">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="flex items-start gap-3 p-3 hover:bg-gray-50 transition-colors group"
          :class="{ 'bg-orange-50/40': !notification.read }"
        >
          <!-- Actor Avatar or Type Icon -->
          <div v-if="notification.actor?.avatar_url" class="flex-shrink-0">
            <img :src="notification.actor.avatar_url" :alt="notification.actor.name" class="w-8 h-8 rounded-full">
          </div>
          <div
            v-else
            class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
            :class="getIconClass(notification.type)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="notification.type === 'viewer'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              <path v-else-if="notification.type === 'comment'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
              <path v-else-if="notification.type === 'warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              <path v-else-if="notification.type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              <path v-else-if="notification.type === 'feedback'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
              <path v-else-if="notification.type === 'download'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              <path v-else-if="notification.type === 'edit_complete'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0" @click="handleClick(notification)">
            <p class="text-sm text-gray-700 leading-snug" v-html="notification.message"></p>
            <p class="text-xs text-gray-400 mt-0.5">{{ formatTime(notification.created_at) }}</p>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              v-if="!notification.read"
              @click.stop="markAsRead(notification)"
              class="p-1.5 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded transition-colors"
              title="Mark as read"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </button>
            <button
              @click.stop="deleteNotification(notification)"
              class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
              title="Delete"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>

          <!-- Unread dot -->
          <div v-if="!notification.read" class="w-2 h-2 bg-orange-500 rounded-full flex-shrink-0 mt-1.5"></div>
        </div>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="p-3 border-t border-gray-100 text-center">
        <button
          @click="loadMore"
          :disabled="loadingMore"
          class="text-xs font-medium text-orange-600 hover:text-orange-700 disabled:opacity-50"
        >
          {{ loadingMore ? 'Loading...' : 'Load more' }}
        </button>
      </div>
    </div>

    <!-- Pagination info -->
    <p v-if="pagination.total > 0" class="text-xs text-gray-400 mt-3 text-center">
      Showing {{ notifications.length }} of {{ pagination.total }} notifications
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { formatDistanceToNow } from 'date-fns'
import notificationService from '@/services/notificationService'
import videoService from '@/services/videoService'

const router = useRouter()

const loading = ref(true)
const loadingMore = ref(false)
const notifications = ref([])
const hasMore = ref(false)
const page = ref(1)
const pagination = ref({ total: 0 })

const hasUnread = computed(() => notifications.value.some(n => !n.read))

const getIconClass = (type) => ({
  'viewer': 'bg-blue-100 text-blue-600',
  'comment': 'bg-purple-100 text-purple-600',
  'warning': 'bg-amber-100 text-amber-600',
  'success': 'bg-green-100 text-green-600',
  'feedback': 'bg-green-100 text-green-600',
  'download': 'bg-indigo-100 text-indigo-600',
  'edit_complete': 'bg-emerald-100 text-emerald-600',
  'info': 'bg-gray-100 text-gray-600'
}[type] || 'bg-gray-100 text-gray-600')

const formatTime = (date) => {
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true })
  } catch {
    return date
  }
}

const fetchNotifications = async (append = false) => {
  if (append) {
    loadingMore.value = true
  } else {
    loading.value = true
  }

  try {
    const response = await notificationService.getNotifications(page.value)

    if (append) {
      notifications.value = [...notifications.value, ...(response.notifications || [])]
    } else {
      notifications.value = response.notifications || []
    }

    pagination.value = response.pagination || {}
    hasMore.value = response.pagination?.has_more || false
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const handleClick = async (notification) => {
  if (!notification.read) {
    await markAsRead(notification)
  }

  // Download notifications: fetch the MP4 file via API and trigger blob download
  if (notification.type === 'download' && notification.link) {
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
    } catch (error) {
      console.error('Failed to download MP4:', error)
    }
    return
  }

  // Navigate based on notification type
  if (notification.type === 'edit_complete' && notification.link) {
    router.push(notification.link)
    return
  }

  if (notification.type === 'feedback') {
    router.push('/feedback')
  }
}

const markAsRead = async (notification) => {
  if (notification.read) return
  try {
    await notificationService.markAsRead(notification.id)
    notification.read = true
  } catch (error) {
    console.error('Failed to mark as read:', error)
  }
}

const markAllRead = async () => {
  try {
    await notificationService.markAllAsRead()
    notifications.value.forEach(n => n.read = true)
  } catch (error) {
    console.error('Failed to mark all as read:', error)
  }
}

const deleteNotification = async (notification) => {
  try {
    await notificationService.deleteNotification(notification.id)
    notifications.value = notifications.value.filter(n => n.id !== notification.id)
  } catch (error) {
    console.error('Failed to delete notification:', error)
  }
}

const loadMore = async () => {
  page.value++
  await fetchNotifications(true)
}

onMounted(() => fetchNotifications())
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
