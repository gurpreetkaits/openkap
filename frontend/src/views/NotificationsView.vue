<template>
  <div class="animate-fade-in max-w-2xl mx-auto py-6 px-4">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <!-- Header -->
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
        <button
          @click="markAllRead"
          class="text-xs text-orange-600 font-medium hover:text-orange-700 transition-colors"
          :disabled="!hasUnread"
          :class="{ 'opacity-50 cursor-not-allowed': !hasUnread }"
        >
          Mark all read
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin w-6 h-6 border-2 border-orange-500 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-sm text-gray-500 mt-2">Loading notifications...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="notifications.length === 0" class="p-8 text-center">
        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
          <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        </div>
        <p class="text-sm text-gray-500">No notifications yet</p>
        <p class="text-xs text-gray-400 mt-1">We'll notify you when something happens</p>
      </div>

      <!-- Notifications List -->
      <div v-else class="divide-y divide-gray-100">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="p-4 flex items-start gap-3 hover:bg-gray-50/50 transition-colors cursor-pointer"
          :class="{ 'bg-orange-50/30': !notification.read }"
          @click="markAsRead(notification)"
        >
          <!-- Avatar/Icon -->
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
            :class="getNotificationIconClass(notification.type)"
          >
            <component :is="getNotificationIcon(notification.type)" class="w-4 h-4" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-700" v-html="notification.message"></p>
            <p class="text-xs text-gray-400 mt-1">{{ formatTime(notification.created_at) }}</p>
          </div>

          <!-- Unread Indicator -->
          <div v-if="!notification.read" class="w-2 h-2 bg-orange-500 rounded-full flex-shrink-0 mt-2"></div>
        </div>
      </div>

      <!-- Load More -->
      <div v-if="hasMore && !loading" class="p-4 border-t border-gray-100 text-center">
        <button
          @click="loadMore"
          class="text-sm text-orange-600 font-medium hover:text-orange-700 transition-colors"
        >
          Load more notifications
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue'
import { formatDistanceToNow } from 'date-fns'
import notificationService from '@/services/notificationService'

// State
const loading = ref(true)
const notifications = ref([])
const hasMore = ref(false)
const page = ref(1)
const pagination = ref({})

// Computed
const hasUnread = computed(() => notifications.value.some(n => !n.read))

// Icon Components
const ViewerIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z'
      }),
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'
      })
    ])
  }
}

const CommentIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'
      })
    ])
  }
}

const WarningIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
      })
    ])
  }
}

const SuccessIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
      })
    ])
  }
}

const InfoIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
      })
    ])
  }
}

// Methods
function getNotificationIcon(type) {
  const icons = {
    viewer: ViewerIcon,
    comment: CommentIcon,
    warning: WarningIcon,
    success: SuccessIcon,
    info: InfoIcon
  }
  return icons[type] || InfoIcon
}

function getNotificationIconClass(type) {
  const classes = {
    viewer: 'bg-blue-100 text-blue-600',
    comment: 'bg-purple-100 text-purple-600',
    warning: 'bg-amber-100 text-amber-600',
    success: 'bg-green-100 text-green-600',
    info: 'bg-gray-100 text-gray-600'
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

function formatTime(date) {
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true })
  } catch {
    return date
  }
}

async function fetchNotifications(appendMode = false) {
  loading.value = true
  try {
    const response = await notificationService.getNotifications(page.value)

    if (appendMode) {
      // Append to existing notifications for load more
      notifications.value = [...notifications.value, ...response.notifications]
    } else {
      notifications.value = response.notifications || []
    }

    pagination.value = response.pagination || {}
    hasMore.value = response.pagination?.has_more || false
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  } finally {
    loading.value = false
  }
}

async function markAsRead(notification) {
  if (notification.read) return

  try {
    await notificationService.markAsRead(notification.id)
    notification.read = true
  } catch (error) {
    console.error('Failed to mark notification as read:', error)
  }
}

async function markAllRead() {
  try {
    await notificationService.markAllAsRead()
    notifications.value.forEach(n => n.read = true)
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error)
  }
}

async function loadMore() {
  page.value++
  await fetchNotifications(true)
}

// Lifecycle
onMounted(() => {
  fetchNotifications()
})
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
