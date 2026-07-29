<template>
  <div class="flex flex-col h-screen bg-white text-gray-600 overflow-hidden selection:bg-orange-100 selection:text-orange-700">
    <!-- Top Navigation Header (replaces left sidebar) -->
    <header
      v-if="!isFullWidthRoute"
      class="h-14 grid grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 lg:px-6 border-b border-gray-200 bg-white/90 backdrop-blur-md sticky top-0 z-30 flex-shrink-0"
    >
      <!-- Left: Mobile menu + Logo -->
      <div class="flex items-center gap-3 min-w-0">
        <!-- Mobile Menu Button -->
        <button
          @click="sidebarOpen = true"
          class="lg:hidden p-2 -ml-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>

        <!-- Logo -->
        <router-link to="/videos" class="flex items-center gap-2.5 group cursor-pointer flex-shrink-0">
          <img :src="branding.logoUrl.value || '/logo.png'" alt="OpenKap" class="w-7 h-7 rounded-lg shadow-sm" />
          <span class="text-gray-900 font-bold text-sm tracking-tight">OpenKap</span>
          <span
            v-if="isAuthenticated"
            class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
            :class="subscription?.is_active ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-500'"
          >
            {{ subscription?.is_active ? 'Pro' : 'Free' }}
          </span>
        </router-link>
      </div>

      <!-- Center: Horizontal Nav (desktop only) -->
      <nav
        v-motion
        :initial="{ opacity: 0, y: -4 }"
        :enter="{ opacity: 1, y: 0, transition: { duration: 350, ease: 'easeOut' } }"
        class="hidden lg:flex items-center gap-1 justify-self-center"
      >
        <router-link
          v-if="isAdmin"
          to="/admin/dashboard"
          class="nav-item group flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/admin/dashboard') ? 'text-orange-700 bg-orange-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
        >
          <svg class="w-4 h-4 nav-icon" :class="isActive('/admin/dashboard') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <rect class="ico-dashboard-cell ico-dashboard-cell-1" x="3" y="3" width="7" height="9" rx="1.5"/>
            <rect class="ico-dashboard-cell ico-dashboard-cell-2" x="14" y="3" width="7" height="5" rx="1.5"/>
            <rect class="ico-dashboard-cell ico-dashboard-cell-3" x="14" y="12" width="7" height="9" rx="1.5"/>
            <rect class="ico-dashboard-cell ico-dashboard-cell-4" x="3" y="16" width="7" height="5" rx="1.5"/>
          </svg>
          Dashboard
        </router-link>

        <router-link
          v-if="isAdmin"
          to="/admin/support"
          class="nav-item group flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/admin/support') ? 'text-orange-700 bg-orange-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
        >
          <svg class="w-4 h-4 nav-icon" :class="isActive('/admin/support') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          Support
        </router-link>

        <router-link
          to="/videos"
          class="nav-item group flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/videos') ? 'text-orange-700 bg-orange-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
        >
          <svg class="w-4 h-4 nav-icon" :class="isActive('/videos') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <rect class="ico-library-tile ico-library-tile-1" x="3" y="3" width="7" height="7" rx="1.5"/>
            <rect class="ico-library-tile ico-library-tile-2" x="14" y="3" width="7" height="7" rx="1.5"/>
            <rect class="ico-library-tile ico-library-tile-3" x="3" y="14" width="7" height="7" rx="1.5"/>
            <rect class="ico-library-tile ico-library-tile-4" x="14" y="14" width="7" height="7" rx="1.5"/>
          </svg>
          Library
        </router-link>

        <router-link
          to="/analytics"
          class="nav-item group flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/analytics') ? 'text-orange-700 bg-orange-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
        >
          <svg class="w-4 h-4 nav-icon" :class="isActive('/analytics') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M3 3v17a1 1 0 0 0 1 1h17"/>
            <line class="ico-chart-bar ico-chart-bar-1" x1="7" y1="18" x2="7" y2="13"/>
            <line class="ico-chart-bar ico-chart-bar-2" x1="12" y1="18" x2="12" y2="9"/>
            <line class="ico-chart-bar ico-chart-bar-3" x1="17" y1="18" x2="17" y2="5"/>
          </svg>
          Analytics
        </router-link>

        <router-link
          to="/playlists"
          class="nav-item group flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/playlists') || route.path.startsWith('/playlist/') ? 'text-orange-700 bg-orange-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
        >
          <svg class="w-4 h-4 nav-icon" :class="isActive('/playlists') || route.path.startsWith('/playlist/') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <line class="ico-playlist-line ico-playlist-line-1" x1="4" y1="6" x2="16" y2="6"/>
            <line class="ico-playlist-line ico-playlist-line-2" x1="4" y1="12" x2="16" y2="12"/>
            <line class="ico-playlist-line ico-playlist-line-3" x1="4" y1="18" x2="11" y2="18"/>
            <polygon class="ico-playlist-play" points="16,15 21,18 16,21"/>
          </svg>
          Playlists
        </router-link>

        <router-link
          to="/subscription"
          class="nav-item group flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/subscription') ? 'text-orange-700 bg-orange-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
        >
          <svg class="w-4 h-4 nav-icon" :class="isActive('/subscription') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <g class="ico-billing-card">
              <rect x="2" y="6" width="20" height="14" rx="2"/>
              <line x1="2" y1="10" x2="22" y2="10"/>
              <line x1="6" y1="15" x2="9" y2="15"/>
            </g>
          </svg>
          Plans & Billing
        </router-link>

        <router-link
          to="/settings"
          class="nav-item group flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/settings') ? 'text-orange-700 bg-orange-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
        >
          <svg class="w-4 h-4 nav-icon" :class="isActive('/settings') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <g class="ico-settings-gear" style="transform-origin: 12px 12px;">
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
              <circle cx="12" cy="12" r="3"/>
            </g>
          </svg>
          Settings
        </router-link>
      </nav>

      <!-- Right: CTA + Bell + User -->
      <div class="flex items-center gap-2 flex-shrink-0 justify-self-end">
        <!-- Free plan: Upgrade pill -->
        <button
          v-if="isAuthenticated && subscription && !subscription.is_active"
          @click="router.push('/subscription')"
          class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[12px] font-medium text-orange-700 bg-orange-50 hover:bg-orange-100 border border-orange-200/60 rounded-lg transition-colors"
          :title="`${minutesUsed} / ${minutesLimit} min this month — click to upgrade`"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
          {{ minutesUsed }}/{{ minutesLimit }} min
        </button>

        <!-- New Recording -->
        <button
          v-if="isAuthenticated"
          @click="handleNewRecording"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg shadow-sm shadow-orange-100 transition-all"
        >
          <div class="w-3.5 h-3.5 rounded-full bg-white/25 flex items-center justify-center">
            <svg class="w-2 h-2" fill="currentColor" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4"/></svg>
          </div>
          <span class="hidden sm:inline">New Recording</span>
        </button>

        <!-- Notifications -->
        <NotificationBell v-if="isAuthenticated" />

        <!-- User avatar / dropdown -->
        <div v-if="isAuthenticated" class="relative" ref="userDropdownRef">
          <button
            @click="showUserDropdown = !showUserDropdown"
            class="flex items-center gap-1.5 p-1 pr-1.5 rounded-full hover:bg-gray-100 transition-colors"
            :title="userInfo.name"
          >
            <div class="relative">
              <img
                v-if="userInfo.avatar"
                :src="userInfo.avatar"
                :alt="userInfo.name"
                class="w-7 h-7 rounded-full bg-gray-200 object-cover"
              />
              <div v-else class="w-7 h-7 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                <span class="text-xs font-bold text-white">{{ userInfo.initial }}</span>
              </div>
            </div>
            <svg class="w-3 h-3 text-gray-400 transition-transform" :class="{ 'rotate-180': showUserDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <Transition name="dropdown">
            <div
              v-show="showUserDropdown"
              class="absolute right-0 top-full mt-1.5 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50"
            >
              <div class="px-3 py-2 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-900 truncate">{{ userInfo.name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ subscription?.is_active ? 'Pro Plan' : 'Free Plan' }}</p>
              </div>
              <router-link
                to="/profile"
                @click="showUserDropdown = false"
                class="flex items-center gap-2.5 px-3 py-2 mx-1 mt-1 text-sm text-gray-700 hover:bg-gray-50 transition-colors rounded-lg"
              >
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile
              </router-link>
              <div class="border-t border-gray-100 my-1 mx-2"></div>
              <button
                @click="showUserDropdown = false; showLogoutModal = true"
                class="w-[calc(100%-8px)] flex items-center gap-2.5 px-3 py-2 mx-1 text-sm text-red-600 hover:bg-red-50 transition-colors rounded-lg"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
              </button>
            </div>
          </Transition>
        </div>

        <!-- Sign in (unauthenticated) -->
        <button
          v-else
          @click="handleLogin"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          Sign in
        </button>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative bg-[#F9FAFB]">
      <!-- Page Content -->
      <div class="flex-1 overflow-hidden scroll-smooth" :class="isFullWidthRoute ? 'bg-[#FAFAFA]' : 'overflow-y-auto overflow-x-hidden bg-white'">
        <div :class="isFullWidthRoute ? 'h-full' : 'p-6 lg:p-8'">
          <router-view />
        </div>
      </div>
    </main>

    <!-- Mobile Sidebar Overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-50 lg:hidden"
      @click="sidebarOpen = false"
    >
      <div class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>

      <aside class="fixed inset-y-0 left-0 w-[260px] bg-white border-r border-gray-200 flex flex-col">
        <!-- Mobile Sidebar Logo -->
        <div class="h-14 flex items-center justify-between px-5 border-b border-gray-100/50">
          <router-link to="/videos" class="flex items-center gap-2.5" @click="sidebarOpen = false">
            <img :src="branding.logoUrl.value || '/logo.png'" alt="OpenKap" class="w-7 h-7 rounded-lg" />
            <span class="text-gray-900 font-semibold tracking-tight text-[15px]">OpenKap</span>
          </router-link>

          <button
            @click="sidebarOpen = false"
            class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Mobile Navigation -->
        <div class="flex-1 overflow-y-auto px-3 py-4">
          <nav class="space-y-0.5">
            <!-- Admin Dashboard (Mobile) -->
            <router-link
              v-if="isAdmin"
              to="/admin/dashboard"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/admin/dashboard') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/admin/dashboard') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
              Dashboard
              <span class="ml-auto text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-orange-100 text-orange-700">Admin</span>
            </router-link>

            <!-- Admin Support (Mobile) -->
            <router-link
              v-if="isAdmin"
              to="/admin/support"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/admin/support') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/admin/support') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
              </svg>
              Support
              <span class="ml-auto text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-orange-100 text-orange-700">Admin</span>
            </router-link>

            <router-link
              to="/videos"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/videos') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/videos') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
              </svg>
              Library
            </router-link>

            <router-link
              to="/playlists"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/playlists') || route.path.startsWith('/playlist/') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/playlists') || route.path.startsWith('/playlist/') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Playlists
            </router-link>

            <router-link
              to="/subscription"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/subscription') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/subscription') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
              </svg>
              Plans & Billing
            </router-link>

            <router-link
              to="/settings"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/settings') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/settings') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Settings
            </router-link>
          </nav>
        </div>

        <!-- Mobile User Footer -->
        <div v-if="isAuthenticated" class="p-3 border-t border-gray-100 bg-gray-50/30">
          <div class="flex items-center gap-3 p-2">
            <div class="relative">
              <img
                v-if="userInfo.avatar"
                :src="userInfo.avatar"
                :alt="userInfo.name"
                class="w-8 h-8 rounded-full bg-gray-200 object-cover ring-2 ring-white"
              />
              <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center ring-2 ring-white">
                <span class="text-xs font-bold text-white">{{ userInfo.initial }}</span>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[13px] font-medium text-gray-900 truncate">{{ userInfo.name }}</p>
              <p class="text-[11px] text-gray-500 truncate">{{ subscription?.is_active ? 'Pro Plan' : 'Free Plan' }}</p>
            </div>
          </div>
          <div class="flex gap-2 mt-2">
            <router-link
              to="/profile"
              @click="sidebarOpen = false"
              class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Profile
            </router-link>
            <button
              @click="sidebarOpen = false; showLogoutModal = true"
              class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
              </svg>
              Logout
            </button>
          </div>
        </div>

        <div v-else class="px-4 py-4 border-t border-gray-200">
          <button
            @click="handleLogin"
            class="flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
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

    <!-- Extension Install Modal -->
    <Transition name="dropdown">
      <div v-if="showExtensionModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showExtensionModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-fade-in">
          <!-- Header with gradient -->
          <div class="bg-gradient-to-br from-orange-500 to-orange-600 px-6 pt-8 pb-10 text-center relative">
            <button @click="showExtensionModal = false" class="absolute top-3 right-3 text-white/70 hover:text-white transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
            <div class="w-16 h-16 bg-white rounded-2xl shadow-lg flex items-center justify-center mx-auto mb-4">
              <img :src="branding.logoUrl.value || '/logo.png'" alt="OpenKap" class="w-10 h-10 rounded-lg" />
            </div>
            <h3 class="text-white text-lg font-bold">OpenKap Extension</h3>
            <p class="text-white/80 text-sm mt-1">Required for screen recording</p>
          </div>

          <!-- Body -->
          <div class="px-6 py-5 -mt-4">
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Record from any tab</p>
                  <p class="text-xs text-gray-500 mt-0.5">Capture your screen, camera, and microphone with one click.</p>
                </div>
              </div>
            </div>

            <a
              :href="extensionStoreUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="flex items-center justify-center gap-2 w-full mt-4 px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all hover:shadow-md"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
              </svg>
              Add to Chrome
            </a>

            <p class="text-center text-[11px] text-gray-400 mt-3">Free &middot; Chrome Web Store</p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Global Recording Components -->
    <RecordingSetupPanel />

    <!-- AI Chat Widget (hidden for now) -->
    <!-- <ChatbotWidget /> -->

    <!-- Support Chat Widget (hidden for admins; they use the admin inbox) -->
    <SupportChatWidget v-if="isAuthenticated && !isAdmin" />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { SBLogoutModal } from '../Global'
import RecordingSetupPanel from '../Global/RecordingSetupPanel.vue'
import ChatbotWidget from '../Global/ChatbotWidget.vue'
import NotificationBell from '../Global/NotificationBell.vue'
import SupportChatWidget from '../SupportChatWidget.vue'
import { useAuth } from '@/stores/auth'
import { useRecording } from '@/composables/useRecording'
import { useBranding } from '@/composables/useBranding'

export default {
  name: 'AppLayout',
  components: {
    SBLogoutModal,
    RecordingSetupPanel,
    ChatbotWidget,
    NotificationBell,
    SupportChatWidget
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const auth = useAuth()
    const recording = useRecording()
    const branding = useBranding()
    const sidebarOpen = ref(false)
    const showLogoutModal = ref(false)
    const logoutLoading = ref(false)
    const showUserDropdown = ref(false)
    const userDropdownRef = ref(null)
    const extensionInstalled = ref(false)
    const showExtensionModal = ref(false)
    const extensionStoreUrl = 'https://chromewebstore.google.com/detail/openkap/nnchnlkilgfemhpcohmgdpcmkjedjkfm'

    // Subscription from auth store
    const subscription = computed(() => auth.subscription.value)

    // Calculate subscription usage percentage
    const subscriptionUsagePercent = computed(() => {
      if (!subscription.value) return 0
      const used = subscription.value.videos_count || 0
      const max = subscription.value.max_videos || 1
      return Math.min((used / max) * 100, 100)
    })

    const minutesUsed = computed(() => subscription.value?.monthly_recording_minutes_used || 0)
    const minutesLimit = computed(() => subscription.value?.monthly_recording_minutes_limit || 0)
    const minutesUsagePercent = computed(() => {
      if (!minutesLimit.value) return 0
      return Math.min((minutesUsed.value / minutesLimit.value) * 100, 100)
    })

    // Use auth store for user info
    const userInfo = computed(() => ({
      name: auth.user.value?.name || 'Guest',
      email: auth.user.value?.email || '',
      avatar: auth.user.value?.avatar || null,
      initial: (auth.user.value?.name || 'U').charAt(0).toUpperCase(),
    }))

    const isAuthenticated = computed(() => auth.isAuthenticated.value)
    const isAdmin = computed(() => auth.isAdmin.value)

    const isActive = (path) => {
      return route.path === path
    }

    const isFullWidthRoute = computed(() => {
      return route.path.startsWith('/video/')
    })

    const handleNewRecording = () => {
      router.push('/record')
    }

    const handleLogin = () => {
      auth.loginWithGoogle()
    }

    const handleLogout = async () => {
      logoutLoading.value = true

      try {
        await auth.logout()
        // Redirect is handled in auth.logout()
      } catch (error) {
        console.error('Logout failed:', error)
        logoutLoading.value = false
      }
    }

    // Handle click outside to close dropdowns
    const handleClickOutside = (event) => {
      if (userDropdownRef.value && !userDropdownRef.value.contains(event.target)) {
        showUserDropdown.value = false
      }
    }

    // Fetch subscription status on mount
    onMounted(() => {
      if (isAuthenticated.value) {
        auth.fetchSubscription()
        branding.loadBranding()
      }
      document.addEventListener('click', handleClickOutside)

      // Detect if OpenKap extension is installed
      window.addEventListener('openkap:extension:ready', () => {
        extensionInstalled.value = true
      })
      if (document.documentElement.hasAttribute('data-openkap-extension')) {
        extensionInstalled.value = true
      }
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      auth,
      route,
      router,
      recording,
      branding,
      sidebarOpen,
      showLogoutModal,
      logoutLoading,
      showUserDropdown,
      userDropdownRef,
      userInfo,
      subscription,
      subscriptionUsagePercent,
      minutesUsed,
      minutesLimit,
      minutesUsagePercent,
      isAuthenticated,
      isAdmin,
      isActive,
      isFullWidthRoute,
      handleNewRecording,
      showExtensionModal,
      extensionStoreUrl,
      handleLogin,
      handleLogout
    }
  }
}
</script>

<style>
/* Custom scrollbar */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #d1d5db; }

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }

/* Dropdown transitions */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

/* Notification message HTML content styling */
.notification-message span.font-medium {
  font-weight: 500;
  color: #111827;
}

.notification-message a {
  color: #ea580c;
  text-decoration: none;
}

.notification-message a:hover {
  text-decoration: underline;
}

/* ============================================================== */
/* Animated nav icons — slim lucide strokes, hover-only animation. */
/* Each .nav-item is a `group`, so hovering anywhere on the menu   */
/* button triggers the keyframes via group-hover.                  */
/* ============================================================== */

.nav-icon { transition: transform 0.2s ease; }
.nav-icon * { transition: transform 0.2s ease, opacity 0.2s ease; }

/* --- Dashboard (4 layout cells) ----------------------------------- */
.ico-dashboard-cell { transform-origin: center; }
.nav-item:hover .ico-dashboard-cell-1 { animation: dashCellPulse 0.6s ease 0s; }
.nav-item:hover .ico-dashboard-cell-2 { animation: dashCellPulse 0.6s ease 0.06s; }
.nav-item:hover .ico-dashboard-cell-3 { animation: dashCellPulse 0.6s ease 0.12s; }
.nav-item:hover .ico-dashboard-cell-4 { animation: dashCellPulse 0.6s ease 0.18s; }
@keyframes dashCellPulse {
  0%, 100% { opacity: 1; }
  40%      { opacity: 0.35; }
}

/* --- Library (2x2 tiles, stagger fade) ---------------------------- */
.ico-library-tile { transform-origin: center; }
.nav-item:hover .ico-library-tile-1 { animation: tileFlip 0.5s ease 0s; }
.nav-item:hover .ico-library-tile-2 { animation: tileFlip 0.5s ease 0.08s; }
.nav-item:hover .ico-library-tile-3 { animation: tileFlip 0.5s ease 0.16s; }
.nav-item:hover .ico-library-tile-4 { animation: tileFlip 0.5s ease 0.24s; }
@keyframes tileFlip {
  0%   { transform: scale(1); }
  50%  { transform: scale(0.6); }
  100% { transform: scale(1); }
}

/* --- Analytics (3 bars rising) ------------------------------------ */
.ico-chart-bar { transform-origin: center bottom; }
.nav-item:hover .ico-chart-bar-1 { animation: barGrow 0.55s ease 0s; }
.nav-item:hover .ico-chart-bar-2 { animation: barGrow 0.55s ease 0.08s; }
.nav-item:hover .ico-chart-bar-3 { animation: barGrow 0.55s ease 0.16s; }
@keyframes barGrow {
  0%   { transform: scaleY(0.1); }
  100% { transform: scaleY(1); }
}

/* --- Playlists (lines slide right, play pulses) ------------------- */
.ico-playlist-line { transform-origin: left center; }
.nav-item:hover .ico-playlist-line-1 { animation: lineSlide 0.45s ease 0s; }
.nav-item:hover .ico-playlist-line-2 { animation: lineSlide 0.45s ease 0.07s; }
.nav-item:hover .ico-playlist-line-3 { animation: lineSlide 0.45s ease 0.14s; }
.nav-item:hover .ico-playlist-play  { animation: playPop  0.45s ease 0.2s;  transform-origin: 18px 18px; }
@keyframes lineSlide {
  0%   { transform: translateX(-3px); opacity: 0.3; }
  100% { transform: translateX(0);    opacity: 1; }
}
@keyframes playPop {
  0%, 100% { transform: scale(1); }
  50%      { transform: scale(1.25); }
}

/* --- Billing (card tilt + swipe) ---------------------------------- */
.ico-billing-card { transform-origin: 12px 13px; }
.nav-item:hover .ico-billing-card { animation: cardTilt 0.6s ease; }
@keyframes cardTilt {
  0%   { transform: rotate(0) translateX(0); }
  40%  { transform: rotate(-6deg) translateX(-1px); }
  100% { transform: rotate(0) translateX(0); }
}

/* --- Settings (gear rotate) --------------------------------------- */
.nav-item:hover .ico-settings-gear { animation: gearSpin 0.7s ease; }
@keyframes gearSpin {
  0%   { transform: rotate(0); }
  100% { transform: rotate(90deg); }
}

/* Slow down all icon animations and disable on reduced-motion users */
@media (prefers-reduced-motion: reduce) {
  .nav-item:hover .nav-icon *,
  .nav-item:hover .nav-icon { animation: none !important; }
}
</style>
