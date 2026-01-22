<template>
  <div class="animate-fade-in py-4 px-4 lg:px-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-lg font-semibold text-base-content">Notifications</h1>
        <p class="text-xs text-base-content/60">Stay updated on your videos and activity</p>
      </div>
      <button
        v-if="hasUnread"
        @click="markAllRead"
        class="btn btn-ghost btn-sm text-primary"
      >
        Mark all read
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading && notifications.length === 0" class="card bg-base-100 border border-base-300 p-8 text-center">
      <span class="loading loading-spinner loading-md text-primary mx-auto"></span>
    </div>

    <!-- Empty -->
    <div v-else-if="notifications.length === 0" class="card bg-base-100 border border-base-300 p-8 text-center">
      <svg class="w-10 h-10 mx-auto text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <p class="mt-2 text-sm text-base-content/60">No notifications yet</p>
      <p class="text-xs text-base-content/40 mt-1">We'll notify you when something happens</p>
    </div>

    <!-- Notifications List -->
    <div v-else class="card bg-base-100 border border-base-300 overflow-hidden">
      <div class="divide-y divide-base-200">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="flex items-start gap-3 p-3 hover:bg-base-200/50 transition-colors group"
          :class="{ 'bg-primary/5': !notification.read }"
        >
          <!-- Actor Avatar or Type Icon -->
          <div v-if="notification.actor?.avatar_url" class="flex-shrink-0">
            <div class="avatar">
              <div class="w-8 rounded-full">
                <img :src="notification.actor.avatar_url" :alt="notification.actor.name">
              </div>
            </div>
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
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0 cursor-pointer" @click="handleClick(notification)">
            <p class="text-sm text-base-content leading-snug" v-html="notification.message"></p>
            <p class="text-xs text-base-content/40 mt-0.5">{{ formatTime(notification.created_at) }}</p>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              v-if="!notification.read"
              @click.stop="markAsRead(notification)"
              class="btn btn-ghost btn-xs btn-circle hover:text-primary"
              title="Mark as read"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </button>
            <button
              @click.stop="deleteNotification(notification)"
              class="btn btn-ghost btn-xs btn-circle hover:text-error"
              title="Delete"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>

          <!-- Unread indicator -->
          <div v-if="!notification.read" class="badge badge-primary badge-xs flex-shrink-0 mt-1.5"></div>
        </div>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="p-3 border-t border-base-200 text-center">
        <button
          @click="loadMore"
          :disabled="loadingMore"
          class="btn btn-ghost btn-sm text-primary"
        >
          <span v-if="loadingMore" class="loading loading-spinner loading-xs mr-1"></span>
          {{ loadingMore ? 'Loading...' : 'Load more' }}
        </button>
      </div>
    </div>

    <!-- Pagination info -->
    <p v-if="pagination.total > 0" class="text-xs text-base-content/40 mt-3 text-center">
      Showing {{ notifications.length }} of {{ pagination.total }} notifications
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { formatDistanceToNow } from 'date-fns'
import notificationService from '@/services/notificationService'

const router = useRouter()

const loading = ref(true)
const loadingMore = ref(false)
const notifications = ref([])
const hasMore = ref(false)
const page = ref(1)
const pagination = ref({ total: 0 })

const hasUnread = computed(() => notifications.value.some(n => !n.read))

const getIconClass = (type) => ({
  'viewer': 'bg-info/20 text-info',
  'comment': 'bg-secondary/20 text-secondary',
  'warning': 'bg-warning/20 text-warning',
  'success': 'bg-success/20 text-success',
  'feedback': 'bg-success/20 text-success',
  'info': 'bg-base-200 text-base-content/60'
}[type] || 'bg-base-200 text-base-content/60')

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

  // Navigate based on notification type
  if (notification.type === 'feedback') {
    router.push('/feedback')
  }
  // Add more navigation logic for other types if needed
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
