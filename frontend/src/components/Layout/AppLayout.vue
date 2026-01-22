<template>
  <div class="drawer lg:drawer-open min-h-screen bg-base-200">
    <input id="main-drawer" type="checkbox" class="drawer-toggle" v-model="sidebarOpen" />

    <!-- Page content -->
    <div class="drawer-content flex flex-col">
      <!-- Top Navigation Bar -->
      <header class="navbar bg-base-100 border-b border-base-200 sticky top-0 z-20">
        <!-- Mobile Menu Button -->
        <div class="flex-none lg:hidden">
          <label for="main-drawer" class="btn btn-square btn-ghost">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </label>
        </div>

        <!-- Breadcrumbs (Desktop) -->
        <div class="flex-1 px-2 hidden lg:flex">
          <div class="breadcrumbs text-sm">
            <ul>
              <li class="text-base-content/60">Workspace</li>
              <li><span class="badge badge-ghost">{{ currentPageName }}</span></li>
            </ul>
          </div>
        </div>

        <!-- Mobile Logo -->
        <div class="flex-1 lg:hidden">
          <router-link to="/videos" class="flex items-center gap-2">
            <img src="/logo.png" alt="ScreenSense" class="w-7 h-7 rounded-lg" />
            <span class="font-semibold text-sm">ScreenSense</span>
          </router-link>
        </div>

        <!-- Search & Actions -->
        <div class="flex-none gap-2">
          <!-- Search (Desktop) -->
          <div class="form-control hidden md:block">
            <div class="input-group input-group-sm">
              <input type="text" placeholder="Search..." class="input input-bordered input-sm w-56" />
              <kbd class="kbd kbd-sm">⌘K</kbd>
            </div>
          </div>

          <!-- Notifications -->
          <router-link to="/notifications" class="btn btn-ghost btn-circle btn-sm indicator">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span v-if="unreadCount > 0" class="badge badge-xs badge-primary indicator-item">
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </router-link>
        </div>
      </header>

      <!-- Main Content -->
      <main class="flex-1 overflow-y-auto overflow-x-hidden p-0">
        <router-view />
      </main>
    </div>

    <!-- Sidebar -->
    <div class="drawer-side z-30">
      <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

      <aside class="menu bg-base-100 w-64 min-h-full border-r border-base-200 flex flex-col">
        <!-- Logo -->
        <div class="p-4 border-b border-base-200">
          <router-link to="/videos" class="flex items-center gap-2.5 group" @click="sidebarOpen = false">
            <img src="/logo.png" alt="ScreenSense" class="w-8 h-8 rounded-lg shadow-sm group-hover:shadow-md transition-shadow" />
            <span class="font-semibold">ScreenSense</span>
          </router-link>
        </div>

        <!-- New Recording Button -->
        <div class="px-3 py-4">
          <button
            @click="recording.openSetupPanel(); sidebarOpen = false"
            class="btn btn-outline btn-primary btn-block gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            New Recording
          </button>
        </div>

        <!-- Main Navigation -->
        <ul class="px-3 flex-1">
          <li>
            <router-link
              to="/videos"
              @click="sidebarOpen = false"
              :class="{ 'active': isActive('/videos') }"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
              </svg>
              Library
            </router-link>
          </li>
          <li>
            <router-link
              to="/favourites"
              @click="sidebarOpen = false"
              :class="{ 'active': isActive('/favourites') }"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
              </svg>
              Favourites
            </router-link>
          </li>
          <li>
            <router-link
              to="/playlists"
              @click="sidebarOpen = false"
              :class="{ 'active': isActive('/playlists') || route.path.startsWith('/playlist/') }"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Playlists
            </router-link>
          </li>
          <li>
            <router-link
              to="/notifications"
              @click="sidebarOpen = false"
              :class="{ 'active': isActive('/notifications') }"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
              </svg>
              <span class="flex-1">Notifications</span>
              <span v-if="unreadCount > 0" class="badge badge-primary badge-sm">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
              </span>
            </router-link>
          </li>

          <div class="divider my-2"></div>

          <li>
            <router-link
              to="/profile"
              @click="sidebarOpen = false"
              :class="{ 'active': isActive('/profile') }"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Profile
            </router-link>
          </li>
          <li>
            <router-link
              to="/subscription"
              @click="sidebarOpen = false"
              :class="{ 'active': isActive('/subscription') }"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
              </svg>
              Subscription
            </router-link>
          </li>
          <li>
            <router-link
              to="/feedback"
              @click="sidebarOpen = false"
              :class="{ 'active': isActive('/feedback') }"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
              </svg>
              Feedback
            </router-link>
          </li>
        </ul>

        <!-- Upgrade Banner (Free users) -->
        <div v-if="isAuthenticated && subscription && !subscription.is_active" class="px-3 pb-3">
          <div
            @click="router.push('/subscription')"
            class="card bg-gradient-to-r from-primary/10 to-secondary/10 border border-primary/20 cursor-pointer hover:shadow-md transition-shadow"
          >
            <div class="card-body p-4">
              <div class="badge badge-primary badge-sm mb-2">Free Plan</div>
              <progress
                class="progress progress-primary w-full"
                :value="subscription?.videos_count || 0"
                :max="subscription?.max_videos || 1"
              ></progress>
              <p class="text-xs mt-2">{{ subscription?.videos_count || 0 }} / {{ subscription?.max_videos || 1 }} videos</p>
            </div>
          </div>
        </div>

        <!-- User Footer -->
        <div v-if="isAuthenticated" class="p-3 border-t border-base-200">
          <button
            @click="showLogoutModal = true"
            class="btn btn-ghost btn-block justify-start gap-3 normal-case"
          >
            <div class="avatar">
              <div class="w-8 rounded-full ring ring-primary ring-offset-base-100 ring-offset-1">
                <img v-if="userInfo.avatar" :src="userInfo.avatar" :alt="userInfo.name" />
                <div v-else class="bg-primary text-primary-content flex items-center justify-center w-full h-full text-sm font-bold">
                  {{ userInfo.initial }}
                </div>
              </div>
            </div>
            <div class="flex-1 text-left min-w-0">
              <p class="text-sm font-medium truncate">{{ userInfo.name }}</p>
              <p class="text-xs text-base-content/60">{{ subscription?.is_active ? 'Pro Plan' : 'Free Plan' }}</p>
            </div>
          </button>
        </div>

        <!-- Login Button (Unauthenticated) -->
        <div v-else class="p-3 border-t border-base-200">
          <button
            @click="handleLogin"
            class="btn btn-outline btn-block gap-2"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Sign in with Google
          </button>
        </div>
      </aside>
    </div>

    <!-- Logout Modal -->
    <SBLogoutModal
      v-model="showLogoutModal"
      message="Are you sure you want to logout? Any unsaved work will be lost."
      :loading="logoutLoading"
      @confirm="handleLogout"
    />

    <!-- Global Recording Components -->
    <RecordingSetupPanel />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { SBLogoutModal } from '../Global'
import RecordingSetupPanel from '../Global/RecordingSetupPanel.vue'
import { useAuth } from '@/stores/auth'
import { useRecording } from '@/composables/useRecording'
import notificationService from '@/services/notificationService'

export default {
  name: 'AppLayout',
  components: {
    SBLogoutModal,
    RecordingSetupPanel
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const auth = useAuth()
    const recording = useRecording()
    const sidebarOpen = ref(false)
    const showLogoutModal = ref(false)
    const logoutLoading = ref(false)
    const unreadCount = ref(0)
    let pollInterval = null

    const subscription = computed(() => auth.subscription.value)

    const subscriptionUsagePercent = computed(() => {
      if (!subscription.value) return 0
      const used = subscription.value.videos_count || 0
      const max = subscription.value.max_videos || 1
      return Math.min((used / max) * 100, 100)
    })

    const userInfo = computed(() => ({
      name: auth.user.value?.name || 'Guest',
      email: auth.user.value?.email || '',
      avatar: auth.user.value?.avatar || null,
      initial: (auth.user.value?.name || 'U').charAt(0).toUpperCase(),
    }))

    const isAuthenticated = computed(() => auth.isAuthenticated.value)

    const isActive = (path) => {
      return route.path === path
    }

    const currentPageName = computed(() => {
      const pathMap = {
        '/videos': 'Library',
        '/favourites': 'Favourites',
        '/playlists': 'Playlists',
        '/notifications': 'Notifications',
        '/profile': 'Profile',
        '/subscription': 'Subscription',
        '/record': 'Record',
        '/feedback': 'Feedback',
        '/settings': 'Settings'
      }
      if (route.path.startsWith('/playlist/')) {
        return 'Playlist'
      }
      return pathMap[route.path] || 'Library'
    })

    const handleLogin = () => {
      auth.loginWithGoogle()
    }

    const handleLogout = async () => {
      logoutLoading.value = true

      try {
        await auth.logout()
      } catch (error) {
        console.error('Logout failed:', error)
        logoutLoading.value = false
      }
    }

    const fetchUnreadCount = async () => {
      if (isAuthenticated.value) {
        try {
          unreadCount.value = await notificationService.getUnreadCount()
        } catch (error) {
          console.error('Failed to fetch unread count:', error)
        }
      }
    }

    onMounted(() => {
      if (isAuthenticated.value) {
        auth.fetchSubscription()
        fetchUnreadCount()
        pollInterval = setInterval(fetchUnreadCount, 30000)
      }
    })

    onUnmounted(() => {
      if (pollInterval) {
        clearInterval(pollInterval)
      }
    })

    return {
      auth,
      route,
      router,
      recording,
      sidebarOpen,
      showLogoutModal,
      logoutLoading,
      userInfo,
      subscription,
      subscriptionUsagePercent,
      isAuthenticated,
      isActive,
      currentPageName,
      unreadCount,
      handleLogin,
      handleLogout
    }
  }
}
</script>
