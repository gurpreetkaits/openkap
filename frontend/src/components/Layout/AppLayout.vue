<template>
  <div class="flex h-screen bg-white text-gray-600 overflow-hidden selection:bg-orange-100 selection:text-orange-700">
    <!-- Sidebar - Hidden on mobile/tablet, shown on desktop (lg and up) -->
    <aside
  class="hidden lg:flex flex-col flex-shrink-0 h-full z-30 relative bg-white border-r border-gray-100 transition-all duration-300 ease-in-out"
  :class="sidebarCollapsed ? 'w-16' : 'w-56'"
>
  <!-- Collapse Toggle -->
  <button
    @click="toggleSidebarCollapsed"
    class="absolute -right-3 top-[52px] z-50 w-6 h-6 bg-white border border-gray-200 rounded-full shadow-sm flex items-center justify-center text-gray-400 hover:text-gray-600 hover:shadow-md transition-all"
    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
  >
    <svg class="w-3 h-3 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
    </svg>
  </button>

  <!-- Logo -->
  <div class="h-14 flex items-center border-b border-gray-100 flex-shrink-0 overflow-hidden" :class="sidebarCollapsed ? 'justify-center px-3' : 'px-4'">
    <router-link to="/videos" class="flex items-center gap-2.5 group cursor-pointer min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
      <img :src="branding.logoUrl.value || '/logo.png'" alt="OpenKap" class="w-7 h-7 rounded-lg shadow-sm flex-shrink-0" />
      <span v-show="!sidebarCollapsed" class="text-gray-900 font-bold text-sm tracking-tight whitespace-nowrap">OpenKap</span>
      <span
        v-if="isAuthenticated && !sidebarCollapsed"
        class="ml-auto text-[10px] font-semibold px-1.5 py-0.5 rounded-full flex-shrink-0"
        :class="subscription?.is_active ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-500'"
      >
        {{ subscription?.is_active ? 'Pro' : 'Free' }}
      </span>
    </router-link>
  </div>

  <!-- Navigation Scroll Area -->
  <div class="flex-1 overflow-y-auto py-4 overflow-x-hidden" :class="sidebarCollapsed ? 'px-2' : 'px-3'">
    <!-- Record CTA Button -->
    <button
      v-if="!sidebarCollapsed"
      @click="handleNewRecording"
      class="flex items-center gap-2 w-full px-3 py-2.5 mb-5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg shadow-sm shadow-orange-100 transition-all cursor-pointer"
    >
      <div class="w-4 h-4 rounded-full bg-white/25 flex items-center justify-center flex-shrink-0">
        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4"/></svg>
      </div>
      New Recording
    </button>
    <button
      v-else
      @click="handleNewRecording"
      class="w-full p-2.5 mb-5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg flex items-center justify-center transition-all cursor-pointer shadow-sm shadow-orange-100"
      title="New Recording"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="6"/></svg>
    </button>

    <nav class="space-y-0.5">
      <!-- Admin Dashboard -->
      <router-link
        v-if="isAdmin"
        to="/admin/dashboard"
        class="group flex items-center rounded-lg transition-all font-medium text-sm"
        :class="[
          sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2',
          isActive('/admin/dashboard') ? 'text-orange-700 bg-orange-50 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
        ]"
        :title="sidebarCollapsed ? 'Dashboard' : ''"
      >
        <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="isActive('/admin/dashboard') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <span v-show="!sidebarCollapsed" class="whitespace-nowrap overflow-hidden flex-1">Dashboard</span>
        <span v-show="!sidebarCollapsed" class="ml-auto text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 flex-shrink-0">Admin</span>
      </router-link>

      <router-link
        to="/videos"
        class="group flex items-center rounded-lg transition-all font-medium text-sm"
        :class="[
          sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2',
          isActive('/videos') ? 'text-orange-700 bg-orange-50 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
        ]"
        :title="sidebarCollapsed ? 'Library' : ''"
      >
        <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="isActive('/videos') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
        </svg>
        <span v-show="!sidebarCollapsed" class="whitespace-nowrap overflow-hidden">Library</span>
      </router-link>

      <router-link
        to="/analytics"
        class="group flex items-center rounded-lg transition-all font-medium text-sm"
        :class="[
          sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2',
          isActive('/analytics') ? 'text-orange-700 bg-orange-50 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
        ]"
        :title="sidebarCollapsed ? 'Analytics' : ''"
      >
        <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="isActive('/analytics') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <span v-show="!sidebarCollapsed" class="whitespace-nowrap overflow-hidden">Analytics</span>
      </router-link>

      <router-link
        to="/playlists"
        class="group flex items-center rounded-lg transition-all font-medium text-sm"
        :class="[
          sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2',
          isActive('/playlists') || route.path.startsWith('/playlist/') ? 'text-orange-700 bg-orange-50 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
        ]"
        :title="sidebarCollapsed ? 'Playlists' : ''"
      >
        <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="isActive('/playlists') || route.path.startsWith('/playlist/') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        <span v-show="!sidebarCollapsed" class="whitespace-nowrap overflow-hidden">Playlists</span>
      </router-link>

      <router-link
        to="/feedback"
        class="group flex items-center rounded-lg transition-all font-medium text-sm"
        :class="[
          sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2',
          isActive('/feedback') ? 'text-orange-700 bg-orange-50 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
        ]"
        :title="sidebarCollapsed ? 'Feedback' : ''"
      >
        <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="isActive('/feedback') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
        <span v-show="!sidebarCollapsed" class="whitespace-nowrap overflow-hidden">Feedback</span>
      </router-link>

      <router-link
        to="/subscription"
        class="group flex items-center rounded-lg transition-all font-medium text-sm"
        :class="[
          sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2',
          isActive('/subscription') ? 'text-orange-700 bg-orange-50 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
        ]"
        :title="sidebarCollapsed ? 'Billing' : ''"
      >
        <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="isActive('/subscription') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        <span v-show="!sidebarCollapsed" class="whitespace-nowrap overflow-hidden">Billing</span>
      </router-link>

      <router-link
        to="/integrations"
        class="group flex items-center rounded-lg transition-all font-medium text-sm"
        :class="[
          sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2',
          isActive('/integrations') ? 'text-orange-700 bg-orange-50 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
        ]"
        :title="sidebarCollapsed ? 'Integrations' : ''"
      >
        <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="isActive('/integrations') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
        </svg>
        <span v-show="!sidebarCollapsed" class="whitespace-nowrap overflow-hidden">Integrations</span>
      </router-link>

      <router-link
        to="/settings"
        class="group flex items-center rounded-lg transition-all font-medium text-sm"
        :class="[
          sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2',
          isActive('/settings') ? 'text-orange-700 bg-orange-50 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
        ]"
        :title="sidebarCollapsed ? 'Settings' : ''"
      >
        <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="isActive('/settings') ? 'text-orange-500' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span v-show="!sidebarCollapsed" class="whitespace-nowrap overflow-hidden">Settings</span>
      </router-link>
    </nav>
  </div>

  <!-- Upgrade Badge (free plan) -->
  <div v-if="isAuthenticated && subscription && !subscription.is_active && !sidebarCollapsed" class="px-3 pb-3">
    <div
      @click="router.push('/subscription')"
      class="bg-gradient-to-r from-orange-50 to-orange-100/80 border border-orange-200/60 rounded-lg p-2.5 relative overflow-hidden group cursor-pointer transition-all hover:shadow-sm hover:border-orange-300"
    >
      <div class="relative z-10">
        <div class="mb-1.5">
          <span class="text-[10px] font-bold text-orange-900 uppercase tracking-wide">Free Plan</span>
        </div>
        <div class="w-full bg-orange-200/50 h-1 rounded-full overflow-hidden mb-1.5">
          <div
            class="bg-gradient-to-r from-orange-500 to-orange-600 h-full rounded-full transition-all duration-500"
            :style="{ width: subscriptionUsagePercent + '%' }"
          ></div>
        </div>
        <div class="flex items-center justify-between">
          <p class="text-[9px] text-orange-800 font-medium">{{ subscription?.videos_count || 0 }} / {{ subscription?.max_videos || 1 }} videos</p>
          <svg class="w-2.5 h-2.5 text-orange-600 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </div>
      </div>
    </div>
  </div>

  <!-- User Footer -->
  <div v-if="isAuthenticated" class="p-3 border-t border-gray-100 flex-shrink-0 relative" ref="userDropdownRef">
    <button
      @click="showUserDropdown = !showUserDropdown"
      class="w-full flex items-center rounded-lg hover:bg-gray-50 transition-all text-left group"
      :class="sidebarCollapsed ? 'justify-center p-2' : 'gap-2.5 p-2'"
      :title="sidebarCollapsed ? userInfo.name : ''"
    >
      <div class="relative flex-shrink-0">
        <img
          v-if="userInfo.avatar"
          :src="userInfo.avatar"
          :alt="userInfo.name"
          class="w-8 h-8 rounded-full bg-gray-200 object-cover ring-2 ring-white ring-offset-1"
        />
        <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center ring-2 ring-white">
          <span class="text-xs font-bold text-white">{{ userInfo.initial }}</span>
        </div>
        <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-400 rounded-full border-2 border-white"></div>
      </div>
      <div v-show="!sidebarCollapsed" class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-900 truncate leading-tight">{{ userInfo.name }}</p>
        <p class="text-xs text-gray-500 truncate leading-tight">{{ subscription?.is_active ? 'Pro Plan' : 'Free Plan' }}</p>
      </div>
      <svg v-show="!sidebarCollapsed" class="w-3 h-3 text-gray-400 group-hover:text-gray-600 flex-shrink-0 transition-transform" :class="{ 'rotate-180': showUserDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
      </svg>
    </button>

    <!-- User Dropdown Menu -->
    <Transition name="dropdown">
      <div
        v-show="showUserDropdown"
        class="absolute bottom-full left-2 right-2 mb-1.5 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50"
      >
        <router-link
          to="/profile"
          @click="showUserDropdown = false"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors rounded-lg mx-1"
        >
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Profile
        </router-link>
        <div class="border-t border-gray-100 my-1 mx-2"></div>
        <button
          @click="showUserDropdown = false; showLogoutModal = true"
          class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors rounded-lg mx-1"
          style="width: calc(100% - 8px);"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          Logout
        </button>
      </div>
    </Transition>
  </div>

  <!-- Sign In Button (when not authenticated) -->
  <div v-else class="px-3 py-3 border-t border-gray-100">
    <button
      v-if="!sidebarCollapsed"
      @click="handleLogin"
      class="flex items-center justify-center w-full px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
    >
      <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24">
        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
      </svg>
      Sign in with Google
    </button>
    <button
      v-else
      @click="handleLogin"
      class="w-full p-2 text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center"
      title="Sign in with Google"
    >
      <svg class="w-4 h-4" viewBox="0 0 24 24">
        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
      </svg>
    </button>
  </div>
</aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative bg-[#F9FAFB]">
      <!-- Top Bar / Header -->
      <header v-if="!isFullWidthRoute" class="h-14 flex items-center justify-between px-4 lg:px-6 border-b border-gray-200 bg-white/80 backdrop-blur-md sticky top-0 z-20 flex-shrink-0">
        <!-- Mobile Menu Button -->
        <button
          @click="sidebarOpen = true"
          class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>

        <!-- Page Title (Desktop) -->
        <div class="hidden lg:flex items-center gap-2">
          <span class="text-sm text-gray-900 font-semibold">{{ currentPageName }}</span>
        </div>

        <!-- Mobile Logo -->
        <router-link to="/videos" class="lg:hidden flex items-center gap-2.5">
          <img :src="branding.logoUrl.value || '/logo.png'" alt="OpenKap" class="w-7 h-7 rounded-lg" />
          <span class="text-gray-900 font-semibold tracking-tight text-[15px]">OpenKap</span>
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

          <!-- Download Bell -->
          <DownloadBell />

          <!-- Notifications Dropdown -->
          <div class="relative" ref="notificationsDropdownRef">
            <button
              @click="toggleNotificationsDropdown"
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
            </button>

            <!-- Notifications Dropdown Panel -->
            <Transition name="dropdown">
              <div
                v-show="showNotificationsDropdown"
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

                <!-- Notification Tabs -->
                <div class="px-2 py-2 border-b border-gray-100">
                  <div class="flex gap-1">
                    <button
                      v-for="tab in notificationTabs"
                      :key="tab.id"
                      @click="activeNotificationTab = tab.id"
                      class="px-2.5 py-1 text-[11px] font-medium rounded-lg transition-all"
                      :class="activeNotificationTab === tab.id
                        ? 'bg-orange-100 text-orange-700'
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                    >
                      {{ tab.label }}
                    </button>
                  </div>
                </div>

                <!-- Notifications List -->
                <div class="max-h-[560px] overflow-y-auto">
                  <!-- Loading State -->
                  <div v-if="loadingNotifications" class="px-4 py-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-orange-600 border-t-transparent"></div>
                    <p class="mt-2 text-xs text-gray-500">Loading notifications...</p>
                  </div>

                  <!-- Empty State -->
                  <div v-else-if="filteredNotifications.length === 0" class="px-4 py-8 text-center">
                    <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-sm text-gray-500">
                      {{ activeNotificationTab === 'all' ? 'No notifications yet' : `No ${activeNotificationTab} notifications` }}
                    </p>
                  </div>

                  <!-- Notifications -->
                  <div v-else>
                    <div
                      v-for="notification in filteredNotifications"
                      :key="notification.id"
                      @click="handleNotificationClick(notification)"
                      class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors"
                      :class="{ 'bg-orange-50/50': !notification.read_at }"
                    >
                      <div class="flex items-start gap-3">
                        <!-- Icon based on type -->
                        <div
                          class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                          :class="getNotificationIconClass(notification.type)"
                        >
                          <svg v-if="notification.type === 'comment'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                          </svg>
                          <svg v-else-if="notification.type === 'viewer'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                          </svg>
                          <svg v-else-if="notification.type === 'warning'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                          </svg>
                          <svg v-else-if="notification.type === 'success'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                          </svg>
                          <svg v-else-if="notification.type === 'feedback'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                          </svg>
                          <svg v-else-if="notification.type === 'download'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                          </svg>
                          <svg v-else-if="notification.type === 'edit_complete'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                          </svg>
                          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                          </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                          <!-- Render message as HTML to support formatted content -->
                          <p
                            class="text-sm text-gray-700 notification-message"
                            :class="{ 'font-medium': !notification.read_at }"
                            v-html="notification.message"
                          ></p>
                          <p class="text-[10px] text-gray-400 mt-1">{{ formatNotificationTime(notification.created_at) }}</p>
                        </div>

                        <!-- Mark as read button -->
                        <button
                          v-if="!notification.read_at"
                          @click.stop="markSingleAsRead(notification)"
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
        </div>
      </header>

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
              to="/integrations"
              @click="sidebarOpen = false"
              class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all group"
              :class="isActive('/integrations') ? 'text-gray-900 bg-gray-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4 transition-colors" :class="isActive('/integrations') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
              </svg>
              Integrations
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

    <!-- AI Chat Widget -->
    <ChatbotWidget />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { SBLogoutModal } from '../Global'
import RecordingSetupPanel from '../Global/RecordingSetupPanel.vue'
import ChatbotWidget from '../Global/ChatbotWidget.vue'
import DownloadBell from '../Global/DownloadBell.vue'
import { useAuth } from '@/stores/auth'
import { useRecording } from '@/composables/useRecording'
import { useBranding } from '@/composables/useBranding'
import notificationService from '@/services/notificationService'
import videoService from '@/services/videoService'

export default {
  name: 'AppLayout',
  components: {
    SBLogoutModal,
    RecordingSetupPanel,
    ChatbotWidget,
    DownloadBell
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const auth = useAuth()
    const recording = useRecording()
    const branding = useBranding()
    const sidebarOpen = ref(false)
    const sidebarCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')
    const toggleSidebarCollapsed = () => {
      sidebarCollapsed.value = !sidebarCollapsed.value
      localStorage.setItem('sidebar_collapsed', String(sidebarCollapsed.value))
    }
    const showLogoutModal = ref(false)
    const logoutLoading = ref(false)
    const unreadCount = ref(0)
    const showUserDropdown = ref(false)
    const userDropdownRef = ref(null)
    const showNotificationsDropdown = ref(false)
    const notificationsDropdownRef = ref(null)
    const notifications = ref([])
    const loadingNotifications = ref(false)
    const activeNotificationTab = ref('all')
    const extensionInstalled = ref(false)
    const showExtensionModal = ref(false)
    const extensionStoreUrl = 'https://chromewebstore.google.com/detail/openkap/nnchnlkilgfemhpcohmgdpcmkjedjkfm'
    let pollInterval = null

    // Notification tabs (matching backend types)
    const notificationTabs = [
      { id: 'all', label: 'All' },
      { id: 'unread', label: 'Unread' },
      { id: 'comment', label: 'Comments' },
      { id: 'viewer', label: 'Views' }
    ]

    // Filtered notifications based on active tab
    const filteredNotifications = computed(() => {
      if (activeNotificationTab.value === 'all') {
        return notifications.value
      }
      if (activeNotificationTab.value === 'unread') {
        return notifications.value.filter(n => !n.read_at)
      }
      return notifications.value.filter(n => n.type === activeNotificationTab.value)
    })

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
    const isAdmin = computed(() => auth.isAdmin.value)

    const isActive = (path) => {
      return route.path === path
    }

    const isFullWidthRoute = computed(() => {
      return route.path.startsWith('/video/')
    })

    // Current page name for header
    const currentPageName = computed(() => {
      const pathMap = {
        '/videos': 'Library',
        '/workspaces': 'Workspaces',
        '/playlists': 'Playlists',
        '/profile': 'Profile',
        '/subscription': 'Plans & Billing',
        '/integrations': 'Integrations',
        '/feedback': 'Feedback',
        '/settings': 'Settings',
        '/admin/dashboard': 'Admin Dashboard'
      }
      if (route.path.startsWith('/workspace/')) {
        return 'Workspace'
      }
      if (route.path.startsWith('/folder/')) {
        return 'Folder'
      }
      return pathMap[route.path] || 'Library'
    })

    const handleNewRecording = () => {
      if (document.documentElement.hasAttribute('data-openkap-extension')) {
        window.dispatchEvent(new CustomEvent('openkap:new-recording'))
      } else {
        window.open(extensionStoreUrl, '_blank')
      }
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

    // Fetch notifications
    const fetchNotifications = async () => {
      loadingNotifications.value = true
      try {
        const data = await notificationService.getNotifications(1, 10)
        notifications.value = data.notifications || []
      } catch (error) {
        console.error('Failed to fetch notifications:', error)
        notifications.value = []
      } finally {
        loadingNotifications.value = false
      }
    }

    // Toggle notifications dropdown
    const toggleNotificationsDropdown = () => {
      showNotificationsDropdown.value = !showNotificationsDropdown.value
      if (showNotificationsDropdown.value) {
        showUserDropdown.value = false
        fetchNotifications()
      }
    }

    // Mark all notifications as read
    const handleMarkAllAsRead = async () => {
      try {
        await notificationService.markAllAsRead()
        notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }))
        unreadCount.value = 0
      } catch (error) {
        console.error('Failed to mark all as read:', error)
      }
    }

    // Handle notification click
    const handleNotificationClick = async (notification) => {
      // Mark as read if unread
      if (!notification.read_at) {
        try {
          await notificationService.markAsRead(notification.id)
          notification.read_at = new Date().toISOString()
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        } catch (error) {
          console.error('Failed to mark notification as read:', error)
        }
      }

      // Download notifications: fetch the MP4 file via API and trigger blob download
      if (notification.type === 'download' && notification.link) {
        showNotificationsDropdown.value = false
        try {
          // Extract video ID from link like "/api/videos/123/download-mp4"
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

      // Navigate to the related content if there's a link
      if (notification.link) {
        showNotificationsDropdown.value = false
        router.push(notification.link)
      }
    }

    // Mark a single notification as read (without navigating)
    const markSingleAsRead = async (notification) => {
      if (notification.read_at) return
      try {
        await notificationService.markAsRead(notification.id)
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      } catch (error) {
        console.error('Failed to mark notification as read:', error)
      }
    }

    // Format notification time
    const formatNotificationTime = (dateString) => {
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

    // Get notification icon class based on type
    const getNotificationIconClass = (type) => {
      switch (type) {
        case 'comment':
          return 'bg-blue-100 text-blue-600'
        case 'viewer':
          return 'bg-green-100 text-green-600'
        case 'warning':
          return 'bg-yellow-100 text-yellow-600'
        case 'success':
          return 'bg-emerald-100 text-emerald-600'
        case 'edit_complete':
          return 'bg-emerald-100 text-emerald-600'
        case 'feedback':
          return 'bg-purple-100 text-purple-600'
        case 'download':
          return 'bg-indigo-100 text-indigo-600'
        default:
          return 'bg-gray-100 text-gray-600'
      }
    }

    // Handle click outside to close dropdowns
    const handleClickOutside = (event) => {
      if (userDropdownRef.value && !userDropdownRef.value.contains(event.target)) {
        showUserDropdown.value = false
      }
      if (notificationsDropdownRef.value && !notificationsDropdownRef.value.contains(event.target)) {
        showNotificationsDropdown.value = false
      }
    }

    // Fetch subscription status on mount
    onMounted(() => {
      if (isAuthenticated.value) {
        auth.fetchSubscription()
        fetchUnreadCount()
        branding.loadBranding()
        // Poll for new notifications every 30 seconds
        pollInterval = setInterval(fetchUnreadCount, 30000)
      }
      document.addEventListener('click', handleClickOutside)

      // Detect if OpenKap extension is installed
      window.addEventListener('openkap:extension:ready', () => {
        extensionInstalled.value = true
      })
      // Extension may have already loaded before mount — check DOM attribute
      if (document.documentElement.hasAttribute('data-openkap-extension')) {
        extensionInstalled.value = true
      }
    })

    onUnmounted(() => {
      if (pollInterval) {
        clearInterval(pollInterval)
      }
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
      showNotificationsDropdown,
      notificationsDropdownRef,
      notifications,
      loadingNotifications,
      activeNotificationTab,
      notificationTabs,
      filteredNotifications,
      userInfo,
      subscription,
      subscriptionUsagePercent,
      isAuthenticated,
      isAdmin,
      isActive,
      isFullWidthRoute,
      currentPageName,
      unreadCount,
      handleNewRecording,
      showExtensionModal,
      extensionStoreUrl,
      handleLogin,
      handleLogout,
      toggleNotificationsDropdown,
      handleMarkAllAsRead,
      handleNotificationClick,
      markSingleAsRead,
      formatNotificationTime,
      getNotificationIconClass,
      sidebarCollapsed,
      toggleSidebarCollapsed
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
</style>
