<template>
  <div class="flex h-screen bg-[#F9FAFB] text-gray-600 overflow-hidden selection:bg-orange-100 selection:text-orange-700">
    <!-- Sidebar - Hidden on mobile/tablet, shown on desktop (lg and up) -->
    <aside class="hidden lg:flex w-[260px] bg-white border-r border-gray-200 flex-col flex-shrink-0 h-full z-30 transition-all duration-300 relative">
      <!-- Logo -->
      <div class="h-14 flex items-center px-5 border-b border-gray-100/50 flex-shrink-0">
        <router-link to="/videos" class="flex items-center gap-2.5 group cursor-pointer">
          <img src="/logo.png" alt="ScreenSense" class="w-7 h-7 rounded-lg shadow-sm group-hover:shadow-md transition-all duration-300" />
          <span class="text-gray-900 font-semibold tracking-tight text-[15px]">ScreenSense</span>
        </router-link>
      </div>

      <!-- Navigation Scroll Area -->
      <div class="flex-1 overflow-y-auto px-3 py-4 flex flex-col gap-6">
        <!-- Primary Action -->
        <div>
          <button
            @click="recording.openSetupPanel"
            class="w-full bg-white border border-gray-200 hover:border-orange-200 hover:bg-orange-50 text-gray-700 hover:text-orange-700 font-medium py-2 px-3 rounded-lg shadow-sm transition-all flex items-center justify-center gap-2 text-sm group relative overflow-hidden"
          >
            <span class="relative z-10 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              New Recording
            </span>
          </button>
        </div>

        <!-- Main Menu -->
        <nav class="space-y-0.5">
          <router-link
            to="/videos"
            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
            :class="isActive('/videos') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
          >
            <svg class="w-4 h-4 transition-colors" :class="isActive('/videos') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Library
          </router-link>

          <router-link
            to="/favourites"
            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
            :class="isActive('/favourites') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
          >
            <svg class="w-4 h-4 transition-colors" :class="isActive('/favourites') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            Favourites
          </router-link>

          <router-link
            to="/playlists"
            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
            :class="isActive('/playlists') || route.path.startsWith('/playlist/') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
          >
            <svg class="w-4 h-4 transition-colors" :class="isActive('/playlists') || route.path.startsWith('/playlist/') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Playlists
          </router-link>

          <router-link
            to="/notifications"
            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
            :class="isActive('/notifications') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
          >
            <svg class="w-4 h-4 transition-colors" :class="isActive('/notifications') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="flex-1">Notifications</span>
            <span
              v-if="unreadCount > 0"
              class="min-w-[18px] h-[18px] px-1 flex items-center justify-center text-[10px] font-bold text-white bg-orange-500 rounded-full"
            >
              {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
          </router-link>
        </nav>

        <!-- Separator -->
        <div class="border-t border-gray-100 my-2"></div>

        <!-- Settings Menu -->
        <nav class="space-y-0.5">
          <router-link
            to="/profile"
            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
            :class="isActive('/profile') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
          >
            <svg class="w-4 h-4 transition-colors" :class="isActive('/profile') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Profile
          </router-link>

          <router-link
            to="/subscription"
            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
            :class="isActive('/subscription') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
          >
            <svg class="w-4 h-4 transition-colors" :class="isActive('/subscription') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            Subscription
          </router-link>

          <router-link
            to="/feedback"
            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
            :class="isActive('/feedback') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
          >
            <svg class="w-4 h-4 transition-colors" :class="isActive('/feedback') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            Feedback
          </router-link>

          <!-- Settings hidden for now
          <router-link
            to="/settings"
            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
            :class="isActive('/settings') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
          >
            <svg class="w-4 h-4 transition-colors" :class="isActive('/settings') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Settings
          </router-link>
          -->
        </nav>
      </div>

      <!-- Upgrade Badge (when on free plan) -->
      <div v-if="isAuthenticated && subscription && !subscription.is_active" class="px-3 pb-3">
        <div
          @click="router.push('/subscription')"
          class="bg-gradient-to-r from-orange-50 to-orange-100/80 border border-orange-200/60 rounded-xl p-3.5 relative overflow-hidden group cursor-pointer transition-all hover:shadow-sm hover:border-orange-300"
        >
          <!-- Background Decoration -->
          <div class="absolute -right-2 -top-2 text-orange-200/50 transform rotate-12 group-hover:rotate-0 transition-transform duration-500">
            <svg class="w-12 h-12 fill-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>

          <div class="relative z-10">
            <div class="mb-2">
              <span class="text-[11px] font-bold text-orange-900 uppercase tracking-wide">Free Plan</span>
            </div>
            <div class="w-full bg-orange-200/50 h-1.5 rounded-full overflow-hidden mb-2">
              <div
                class="bg-gradient-to-r from-orange-500 to-orange-600 h-full rounded-full transition-all duration-500"
                :style="{ width: subscriptionUsagePercent + '%' }"
              ></div>
            </div>
            <div class="flex items-center justify-between">
              <p class="text-[10px] text-orange-800 font-medium">{{ subscription?.videos_count || 0 }} / {{ subscription?.max_videos || 1 }} videos</p>
              <svg class="w-3 h-3 text-orange-600 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- User Footer (when authenticated) -->
      <div v-if="isAuthenticated" class="p-3 border-t border-gray-100 bg-gray-50/30 flex-shrink-0">
        <button
          @click="showLogoutModal = true"
          class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-white hover:shadow-sm hover:ring-1 hover:ring-gray-200 transition-all text-left group"
        >
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
            <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-500 rounded-full border-2 border-white" title="Online"></div>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-[13px] font-medium text-gray-900 truncate">{{ userInfo.name }}</p>
            <p class="text-[11px] text-gray-500 truncate">{{ subscription?.is_active ? 'Pro Plan' : 'Free Plan' }}</p>
          </div>
          <svg class="w-3 h-3 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
          </svg>
        </button>
      </div>

      <!-- Sign In Button (when not authenticated) -->
      <div v-else class="px-4 py-4 border-t border-gray-200">
        <button
          @click="handleLogin"
          class="flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
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

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative bg-[#F9FAFB]">
      <!-- Top Bar / Header -->
      <header class="h-14 flex items-center justify-between px-4 lg:px-6 border-b border-gray-200 bg-white/80 backdrop-blur-md sticky top-0 z-20 flex-shrink-0">
        <!-- Mobile Menu Button -->
        <button
          @click="sidebarOpen = true"
          class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>

        <!-- Breadcrumbs / Title (Desktop) -->
        <div class="hidden lg:flex items-center gap-2">
          <span class="text-sm text-gray-500 font-medium">Workspace</span>
          <span class="text-gray-300">/</span>
          <span class="text-sm text-gray-900 font-medium bg-gray-100/50 px-2 py-0.5 rounded-md border border-gray-200/50">{{ currentPageName }}</span>
        </div>

        <!-- Mobile Logo -->
        <router-link to="/videos" class="lg:hidden flex items-center gap-2.5">
          <img src="/logo.png" alt="ScreenSense" class="w-7 h-7 rounded-lg" />
          <span class="text-gray-900 font-semibold tracking-tight text-[15px]">ScreenSense</span>
        </router-link>

        <!-- Global Search & Actions -->
        <div class="flex items-center gap-3">
          <!-- Search (Desktop only) -->
          <div class="relative group hidden md:block">
            <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
              type="text"
              placeholder="Search..."
              class="pl-9 pr-3 py-1.5 text-xs font-medium bg-gray-100 border border-transparent focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 rounded-lg w-56 transition-all outline-none placeholder:text-gray-400"
            />
            <div class="absolute right-2 top-1/2 -translate-y-1/2 hidden group-focus-within:hidden sm:flex items-center gap-0.5">
              <kbd class="px-1.5 py-0.5 text-[10px] font-sans font-medium text-gray-400 bg-white border border-gray-200 rounded">⌘K</kbd>
            </div>
          </div>

          <div class="hidden md:block h-4 w-px bg-gray-200 mx-1"></div>

          <!-- Notifications Button -->
          <router-link
            to="/notifications"
            class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span
              v-if="unreadCount > 0"
              class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 flex items-center justify-center text-[9px] font-bold text-white bg-orange-500 rounded-full border-2 border-white"
            >
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </router-link>
        </div>
      </header>

      <!-- Page Content -->
      <div class="flex-1 overflow-y-auto overflow-x-hidden scroll-smooth">
        <router-view />
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
            <img src="/logo.png" alt="ScreenSense" class="w-7 h-7 rounded-lg" />
            <span class="text-gray-900 font-semibold tracking-tight text-[15px]">ScreenSense</span>
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
        <div class="flex-1 overflow-y-auto px-3 py-4 flex flex-col gap-6">
          <!-- Primary Action -->
          <div>
            <button
              @click="recording.openSetupPanel(); sidebarOpen = false"
              class="w-full bg-white border border-gray-200 hover:border-orange-200 hover:bg-orange-50 text-gray-700 hover:text-orange-700 font-medium py-2 px-3 rounded-lg shadow-sm transition-all flex items-center justify-center gap-2 text-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              New Recording
            </button>
          </div>

          <!-- Main Menu -->
          <nav class="space-y-0.5">
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
              to="/favourites"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/favourites') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/favourites') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="currentColor" viewBox="0 0 24 24">
                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
              </svg>
              Favourites
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
              to="/notifications"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/notifications') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/notifications') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
              </svg>
              <span class="flex-1">Notifications</span>
              <span
                v-if="unreadCount > 0"
                class="min-w-[18px] h-[18px] px-1 flex items-center justify-center text-[10px] font-bold text-white bg-orange-500 rounded-full"
              >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
              </span>
            </router-link>
          </nav>

          <!-- Separator -->
          <div class="border-t border-gray-100 my-2"></div>

          <!-- Settings Menu -->
          <nav class="space-y-0.5">
            <router-link
              to="/profile"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/profile') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/profile') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Profile
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
              Subscription
            </router-link>

            <router-link
              to="/feedback"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/feedback') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/feedback') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
              </svg>
              Feedback
            </router-link>

            <!-- Settings hidden for now
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
            -->
          </nav>
        </div>

        <!-- Mobile User Footer -->
        <div v-if="isAuthenticated" class="p-3 border-t border-gray-100 bg-gray-50/30">
          <button
            @click="showLogoutModal = true"
            class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-white hover:shadow-sm transition-all text-left group"
          >
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
              <p class="text-[11px] text-gray-500 truncate">Tap to logout</p>
            </div>
          </button>
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

    // Subscription from auth store
    const subscription = computed(() => auth.subscription.value)

    // Calculate subscription usage percentage
    const subscriptionUsagePercent = computed(() => {
      if (!subscription.value) return 0
      const used = subscription.value.videos_count || 0
      const max = subscription.value.max_videos || 1
      return Math.min((used / max) * 100, 100)
    })

    // Use auth store for user info
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

    // Current page name for breadcrumb
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
        // Redirect is handled in auth.logout()
      } catch (error) {
        console.error('Logout failed:', error)
        logoutLoading.value = false
      }
    }

    // Fetch unread notification count
    const fetchUnreadCount = async () => {
      if (isAuthenticated.value) {
        try {
          unreadCount.value = await notificationService.getUnreadCount()
        } catch (error) {
          console.error('Failed to fetch unread count:', error)
        }
      }
    }

    // Fetch subscription status on mount
    onMounted(() => {
      if (isAuthenticated.value) {
        auth.fetchSubscription()
        fetchUnreadCount()
        // Poll for new notifications every 30 seconds
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
</style>
