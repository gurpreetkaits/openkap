<template>
  <div class="bg-[#FAFAFA] text-slate-900 h-screen flex overflow-hidden selection:bg-orange-100 selection:text-orange-700">

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">

    <!-- Subtle Background Grid -->
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 32px 32px; opacity: 0.25;"></div>

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
        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ error }}</h3>
        <a href="/" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors inline-block">
          Go Home
        </a>
      </div>
    </div>

    <!-- Main Content -->
    <template v-else>
      <!-- Navigation -->
      <nav class="border-b border-gray-200/60 bg-white/90 backdrop-blur-md z-50 sticky top-0 flex-shrink-0 px-6" style="height: 80px;">
        <div class="flex items-center gap-4 h-full">

          <!-- Logo -->
          <a href="/" class="flex items-center gap-2 hover:opacity-80 transition-opacity flex-shrink-0">
            <img :src="branding.logoUrl.value || '/logo.png'" alt="OpenKap" class="w-7 h-7 rounded-md" />
            <span class="text-sm font-bold text-gray-900">OpenKap</span>
          </a>

          <!-- Divider -->
          <div v-if="video.title" class="h-10 w-px bg-gray-200 flex-shrink-0"></div>

          <!-- Title + Meta -->
          <div v-if="video.title" class="flex-1 min-w-0 flex flex-col justify-center gap-1">
            <h1 class="text-xl font-bold text-gray-900 truncate leading-tight">{{ video.title }}</h1>
            <div class="flex items-center gap-3 text-xs text-gray-400">
              <template v-if="video.user_name">
                <div class="flex items-center gap-1.5">
                  <img v-if="video.user_avatar" :src="video.user_avatar" :alt="video.user_name" class="w-4 h-4 rounded-full object-cover" />
                  <div v-else class="w-4 h-4 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-[9px] font-bold">{{ (video.user_name || 'U').charAt(0).toUpperCase() }}</div>
                  <span class="font-medium text-gray-500">{{ video.user_name }}</span>
                </div>
                <span>·</span>
              </template>
              <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ formatTimeAgo(video.created_at) }}
              </span>
              <span>·</span>
              <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                {{ video.views_count || 0 }} views
              </span>
            </div>
          </div>

          <!-- Right Actions -->
          <div class="flex items-center gap-2 flex-shrink-0 ml-auto">

          <!-- Notifications (authenticated users) -->
          <NotificationBell v-if="isAuthenticated" />

          </div><!-- end Right Actions -->
        </div><!-- end nav inner flex -->
      </nav>

      <!-- Confirmation Dialogs (owner only) -->
      <template v-if="isOwner">
        <!-- Archive Confirm -->
        <div v-if="showArchiveConfirm" class="fixed inset-0 z-[70] flex items-center justify-center">
          <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm" @click="showArchiveConfirm = false"></div>
          <div class="bg-white rounded-lg shadow-xl w-full max-w-sm relative z-10 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Archive Video</h3>
            <p class="text-xs text-gray-500 mb-4">Are you sure you want to archive this video?</p>
            <div class="flex justify-end gap-2">
              <button @click="showArchiveConfirm = false" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
              <button @click="handleSharedArchive; showArchiveConfirm = false" class="px-3 py-1.5 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-md transition-colors">Archive</button>
            </div>
          </div>
        </div>
        <!-- Private Confirm -->
        <div v-if="showPrivateConfirm" class="fixed inset-0 z-[70] flex items-center justify-center">
          <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm" @click="showPrivateConfirm = false"></div>
          <div class="bg-white rounded-lg shadow-xl w-full max-w-sm relative z-10 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Make Private</h3>
            <p class="text-xs text-gray-500 mb-4">Are you sure? The share link will stop working.</p>
            <div class="flex justify-end gap-2">
              <button @click="showPrivateConfirm = false" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
              <button @click="handleSharedMakePrivate; showPrivateConfirm = false" class="px-3 py-1.5 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-md transition-colors">Make Private</button>
            </div>
          </div>
        </div>
        <!-- Delete Confirm -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-[70] flex items-center justify-center">
          <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
          <div class="bg-white rounded-lg shadow-xl w-full max-w-sm relative z-10 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Delete Video</h3>
            <p class="text-xs text-gray-500 mb-4">Are you sure? This cannot be undone.</p>
            <div class="flex justify-end gap-2">
              <button @click="showDeleteConfirm = false" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
              <button @click="handleSharedDelete; showDeleteConfirm = false" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">Delete</button>
            </div>
          </div>
        </div>
      </template>

      <!-- Share Modal -->
      <div v-if="showShareModal" class="fixed inset-0 z-[60] flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm transition-opacity" @click="showShareModal = false"></div>
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-sm relative z-10 border border-gray-100 overflow-hidden transform transition-all duration-200">
          <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-xs font-semibold text-gray-900">Share Recording</h3>
            <button @click="showShareModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <div class="p-5">
            <div class="flex gap-4 mb-6">
              <a :href="`mailto:?subject=${encodeURIComponent(video.title || 'Check out this video')}&body=${encodeURIComponent(shareUrl || '')}`" class="flex-1 flex flex-col items-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-orange-500 hover:bg-orange-50/50 transition-all group no-underline">
                <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <span class="text-xs font-medium text-gray-600 group-hover:text-orange-700">Email</span>
              </a>
              <button @click="copyEmbedCode" class="flex-1 flex flex-col items-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-orange-500 hover:bg-orange-50/50 transition-all group">
                <div class="w-8 h-8 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                  </svg>
                </div>
                <span class="text-xs font-medium text-gray-600 group-hover:text-pink-700">{{ copiedEmbed ? 'Copied!' : 'Embed' }}</span>
              </button>
              <a :href="`https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl || '')}&text=${encodeURIComponent(video.title || 'Check out this video')}`" target="_blank" rel="noopener noreferrer" class="flex-1 flex flex-col items-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-orange-500 hover:bg-orange-50/50 transition-all group no-underline">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                  </svg>
                </div>
                <span class="text-xs font-medium text-gray-600 group-hover:text-blue-700">Twitter</span>
              </a>
            </div>

            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2 block">Public Link</label>
            <div class="flex gap-2">
              <div class="flex-1 relative">
                <input type="text" :value="shareUrl" class="w-full pl-9 pr-3 py-2 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-orange-500 text-gray-600" readonly>
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
              </div>
              <button @click="copyShareLink" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded-lg text-xs font-medium transition-colors">
                {{ copied ? 'Copied!' : 'Copy' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Layout -->
      <main class="flex z-10" style="height: calc(100vh - 80px)">

        <!-- Video Player — expands/shrinks with sidebar -->
        <div class="video-stage flex flex-col items-center justify-center p-6 bg-[#FAFAFA]/50 overflow-y-auto" :class="sidebarVisible ? 'sidebar-is-open' : 'sidebar-is-closed'">

          <div class="w-full flex flex-col" style="max-width: min(calc((100vh - 200px) * 16 / 9), 100%)">

            <!-- Video Container - Responsive with 16:9 aspect ratio -->
            <div
              class="relative w-full bg-black rounded-xl shadow-2xl ring-1 ring-black/10 overflow-hidden z-20"
              :class="isFullscreen ? 'rounded-none !aspect-auto h-full' : ''"
              :style="{
                aspectRatio: isFullscreen ? 'auto' : '16 / 9',
                maxHeight: isFullscreen ? 'none' : 'calc(100vh - 200px)',
                maxWidth: isFullscreen ? 'none' : 'calc((100vh - 200px) * 16 / 9)',
              }"
              ref="playerContainer"
              @mousemove="showControls"
              @mouseleave="hideControlsDelayed"
            >

              <!-- Sidebar Toggle — top right of video -->
              <button
                @click.stop="sidebarVisible = !sidebarVisible"
                class="absolute top-3 right-3 z-40 w-8 h-8 flex items-center justify-center rounded-lg bg-black/50 backdrop-blur-sm text-white/80 hover:text-white hover:bg-black/70 transition-all"
                :title="sidebarVisible ? 'Hide sidebar' : 'Show sidebar'"
              >
                <svg v-if="!sidebarVisible" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18M3 12h18M3 19h18"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>

              <!-- Blurred thumbnail backdrop (behind video, visible only in aspect-ratio gaps) -->
              <div v-if="video.thumbnail && !isFullscreen" class="absolute inset-0 z-0 scale-110 blur-2xl" :style="{ backgroundImage: `url(${video.thumbnail})`, backgroundSize: 'cover', backgroundPosition: 'center' }"></div>
              <div v-if="video.thumbnail && !isFullscreen" class="absolute inset-0 z-0 bg-black/50"></div>

              <!-- Video Loading Text -->
              <div v-if="videoLoading" class="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
                <p class="text-white/70 text-sm font-medium">Loading video...</p>
              </div>

              <!-- Bunny Encoding Progress (disabled - Bunny costs too high) -->
              <!-- <div
                v-if="isBunnyVideo && bunnyStatus === 'transcoding' && !isPlaying"
                class="absolute top-4 left-4 z-20 bg-black/70 backdrop-blur-sm rounded-lg px-3 py-2 flex items-center gap-2"
              >
                <div class="w-4 h-4 border-2 border-orange-500/30 border-t-orange-500 rounded-full animate-spin"></div>
                <div class="text-white text-xs">
                  <span class="font-medium">Encoding: {{ bunnyEncodeProgress }}%</span>
                  <span v-if="bunnyAvailableResolutions.length > 0" class="text-white/60 ml-2">
                    ({{ bunnyAvailableResolutions.join(', ') }} ready)
                  </span>
                </div>
              </div> -->

              <video
                ref="videoRef"
                class="w-full h-full object-contain relative z-[1]"
                :poster="video.thumbnail"
                preload="metadata"
                crossorigin="anonymous"
                @click="togglePlay"
                @dblclick="toggleFullscreen"
                @timeupdate="updateProgress"
                @loadedmetadata="onVideoLoaded"
                @loadeddata="onVideoLoaded"
                @durationchange="onVideoLoaded"
                @canplay="onVideoLoaded"
                @ended="onVideoEnded"
                @seeking="isBuffering = true"
                @seeked="isBuffering = false"
                @waiting="isBuffering = true"
                @play="isPlaying = true"
                @pause="isPlaying = false"
                @error="onVideoError"
                playsinline
              >
              </video>

              <!-- Buffering -->
              <div v-if="isBuffering" class="absolute inset-0 flex items-center justify-center bg-black/40 pointer-events-none">
                <div class="w-14 h-14 border-4 border-white/20 border-t-orange-500 rounded-full animate-spin"></div>
              </div>

              <!-- Big Play Button -->
              <transition name="fade">
                <div
                  v-if="!isPlaying && !isBuffering && showBigPlayButton"
                  class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center"
                  @click="togglePlay"
                >
                  <button class="group/btn relative flex items-center justify-center w-24 h-24 bg-white/5 backdrop-blur-sm rounded-full border border-white/10 hover:scale-105 hover:bg-orange-600 hover:border-orange-500 transition-all duration-300 shadow-2xl cursor-pointer">
                    <svg class="w-10 h-10 text-white fill-white ml-1" viewBox="0 0 24 24">
                      <path d="M8 5v14l11-7z"/>
                    </svg>
                    <div class="absolute inset-0 rounded-full border border-white/10 animate-ping opacity-20 group-hover/btn:opacity-0"></div>
                  </button>
                  <!-- Pre-play controls -->
                  <div class="flex items-center gap-3 mt-4" @click.stop>
                    <span class="text-white/80 text-xs font-mono bg-black/50 backdrop-blur-sm px-2.5 py-1.5 rounded-lg">
                      {{ formatTime(duration) }}
                    </span>
                    <div class="relative">
                      <button
                        @click.stop="showPrePlaySpeedMenu = !showPrePlaySpeedMenu"
                        class="text-white/80 text-xs font-bold bg-black/50 backdrop-blur-sm px-2.5 py-1.5 rounded-lg hover:bg-white/20 transition-colors cursor-pointer"
                      >
                        {{ playbackSpeed }}x
                      </button>
                      <div
                        v-show="showPrePlaySpeedMenu"
                        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 py-2 bg-gray-900 rounded-xl shadow-2xl border border-white/20 min-w-[80px] z-50"
                      >
                        <button
                          v-for="speed in speedOptions"
                          :key="speed"
                          @click.stop="setPlaybackSpeed(speed); showPrePlaySpeedMenu = false"
                          class="w-full px-3 py-2 text-left text-xs hover:bg-white/10 transition-colors cursor-pointer"
                          :class="playbackSpeed === speed ? 'text-orange-400' : 'text-white'"
                        >
                          {{ speed }}x
                        </button>
                      </div>
                    </div>
                    <button
                      @click.stop="toggleMute"
                      class="text-white/80 bg-black/50 backdrop-blur-sm p-1.5 rounded-lg hover:bg-white/20 transition-colors cursor-pointer"
                    >
                      <svg v-if="isMuted || volume === 0" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                      </svg>
                      <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </transition>

              <!-- Gradient shadow behind controls -->
              <div
                class="absolute bottom-0 left-0 right-0 h-44 bg-gradient-to-t from-black/70 via-black/30 to-transparent z-20 pointer-events-none transition-opacity duration-500"
                :class="controlsVisible ? 'opacity-100' : 'opacity-0'"
              ></div>

              <!-- Custom Floating Controls -->
              <div class="absolute bottom-0 left-0 right-0 z-30 px-5 pb-4">
                <div class="flex flex-col gap-2">
                  <!-- Captions Bar -->
                  <div v-if="captionsEnabled && activeCaptionCue" class="flex justify-center pointer-events-none mb-1">
                    <div class="caption-bar">
                      <span v-for="(word, wi) in activeCaptionCue.words" :key="wi" class="caption-word" :class="word.active ? 'caption-active' : 'caption-inactive'">{{ word.text }}</span>
                    </div>
                  </div>

                  <!-- Progress Bar -->
                  <div
                    class="relative h-7 w-full group/seek cursor-pointer flex items-center"
                    @click.stop="seek"
                    @mousedown.stop="startSeeking"
                    @mousemove="updateHoverTime"
                    @mouseleave="hoverTime = null"
                    ref="progressBar"
                  >
                    <div class="absolute left-0 right-0 bottom-2 h-1 group-hover/seek:h-3.5 transition-all duration-200 ease-out rounded-sm overflow-hidden bg-gray-500/70">
                      <div class="absolute left-0 h-full bg-gray-300/40" :style="{ width: bufferedPercent + '%' }"></div>
                      <div class="absolute left-0 h-full bg-orange-500 shadow-[0_0_8px_rgba(249,115,22,0.4)]" :style="{ width: progressPercent + '%' }"></div>
                    </div>
                    <div
                      v-if="hoverTime !== null"
                      class="absolute -top-8 px-2 py-1 bg-black/80 text-white text-xs rounded transform -translate-x-1/2 pointer-events-none whitespace-nowrap"
                      :style="{ left: hoverPercent + '%' }"
                    >
                      {{ formatTime(hoverTime) }}
                    </div>
                    <div class="absolute right-0 -top-7 opacity-0 group-hover/seek:opacity-100 transition-opacity duration-200 text-white/70 text-[11px] font-mono pointer-events-none whitespace-nowrap">
                      {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
                    </div>
                  </div>

                  <!-- Control Buttons -->
                  <transition name="fade">
                  <div v-show="controlsVisible" class="flex items-center justify-between text-white pt-0.5">
                    <div class="flex items-center gap-3">
                      <!-- Play/Pause -->
                      <button @click.stop="togglePlay" class="ctrl-tip player-btn w-7 h-7 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all" :data-tip="isPlaying ? 'Pause' : 'Play'">
                        <svg v-if="isPlaying" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                          <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                        </svg>
                        <svg v-else class="w-5 h-5 fill-current ml-0.5" viewBox="0 0 24 24">
                          <path d="M8 5v14l11-7z"/>
                        </svg>
                      </button>
                      <!-- Skip back -->
                      <button @click.stop="skip(-5)" class="ctrl-tip player-btn w-7 h-7 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all opacity-80 hover:opacity-100" data-tip="Rewind 5s">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                        </svg>
                      </button>
                      <!-- Skip forward -->
                      <button @click.stop="skip(5)" class="ctrl-tip player-btn w-7 h-7 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all opacity-80 hover:opacity-100" data-tip="Forward 5s">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/>
                        </svg>
                      </button>
                      <!-- Volume -->
                      <div class="ctrl-tip flex items-center group/vol bg-white/10 hover:bg-white/20 rounded-full h-7 px-1.5 transition-all" :data-tip="isMuted ? 'Unmute' : 'Volume'">
                        <button @click.stop="toggleMute" class="flex items-center justify-center w-5 h-5 opacity-80 hover:opacity-100 transition-all">
                          <svg v-if="isMuted || volume === 0" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                          </svg>
                          <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                          </svg>
                        </button>
                        <input
                          type="range" min="0" max="1" step="0.05" v-model="volume" @input="updateVolume"
                          class="volume-slider w-0 group-hover/vol:w-16 opacity-0 group-hover/vol:opacity-100 transition-all duration-200 ml-1"
                        />
                      </div>
                      <!-- Time -->
                      <span class="text-[11px] font-mono text-white/50 tracking-wide ml-1 select-none">
                        {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
                      </span>
                    </div>
                    <div class="flex items-center gap-2">
                      <!-- Quality -->
                      <div v-if="availableQualities.length > 0" class="relative ctrl-tip" ref="qualityMenuRef" data-tip="Quality">
                        <button
                          @click.stop.prevent="toggleQualityMenu"
                          class="text-[10px] font-bold text-white/70 hover:text-white h-7 px-2.5 flex items-center rounded-full bg-white/10 hover:bg-white/20 transition-all"
                        >
                          {{ getCurrentQualityLabel() }}
                        </button>
                        <div
                          v-show="showQualityMenu"
                          class="absolute bottom-full right-0 mb-2 py-1.5 bg-black/80 backdrop-blur-xl rounded-lg shadow-2xl border border-white/10 min-w-[100px] z-50"
                        >
                          <button
                            @click.stop.prevent="setQuality(-1)"
                            class="w-full px-3 py-1.5 text-left text-[11px] hover:bg-white/10 transition-colors"
                            :class="currentQuality === -1 ? 'text-orange-400 font-semibold' : 'text-white/80'"
                          >
                            Auto
                          </button>
                          <button
                            v-for="quality in [...availableQualities].reverse()"
                            :key="quality.index"
                            @click.stop.prevent="setQuality(quality.index)"
                            class="w-full px-3 py-1.5 text-left text-[11px] hover:bg-white/10 transition-colors"
                            :class="currentQuality === quality.index ? 'text-orange-400 font-semibold' : 'text-white/80'"
                          >
                            {{ quality.label }}
                          </button>
                        </div>
                      </div>
                      <!-- Speed -->
                      <div class="relative ctrl-tip" ref="speedMenuRef" data-tip="Speed">
                        <button
                          @click.stop.prevent="toggleSpeedMenu"
                          class="text-[10px] font-bold text-white/70 hover:text-white h-7 px-2.5 flex items-center rounded-full bg-white/10 hover:bg-white/20 transition-all"
                        >
                          {{ playbackSpeed }}x
                        </button>
                        <div
                          v-show="showSpeedMenu"
                          class="absolute bottom-full right-0 mb-2 py-1.5 bg-black/80 backdrop-blur-xl rounded-lg shadow-2xl border border-white/10 min-w-[80px] z-50"
                        >
                          <button
                            v-for="speed in speedOptions"
                            :key="speed"
                            @click.stop.prevent="setPlaybackSpeed(speed)"
                            class="w-full px-3 py-1.5 text-left text-[11px] hover:bg-white/10 transition-colors"
                            :class="playbackSpeed === speed ? 'text-orange-400 font-semibold' : 'text-white/80'"
                          >
                            {{ speed }}x
                          </button>
                        </div>
                      </div>
                      <!-- Captions -->
                      <button
                        v-if="captionsUrl"
                        @click.stop="toggleCaptions"
                        class="ctrl-tip player-btn w-7 h-7 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all relative"
                        :class="captionsEnabled ? 'text-orange-400' : 'text-white/60 hover:text-white'"
                        :data-tip="captionsEnabled ? 'Captions on' : 'Captions'"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                          <rect x="2" y="4" width="20" height="16" rx="2"/>
                          <text x="12" y="15" text-anchor="middle" fill="currentColor" stroke="none" font-size="8" font-weight="bold">CC</text>
                        </svg>
                        <div v-if="captionsEnabled" class="absolute -bottom-0.5 left-1/2 -translate-x-1/2 w-3 h-0.5 bg-orange-400 rounded-full"></div>
                      </button>
                      <!-- PiP -->
                      <button @click.stop="togglePiP" class="ctrl-tip player-btn w-7 h-7 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all text-white/60 hover:text-white" data-tip="Picture in Picture">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                          <rect x="2" y="3" width="20" height="14" rx="2"/>
                          <rect x="11" y="10" width="9" height="6" rx="1" fill="currentColor"/>
                        </svg>
                      </button>
                      <!-- Fullscreen -->
                      <button @click.stop="toggleFullscreen" class="ctrl-tip player-btn w-7 h-7 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all text-white/60 hover:text-white" :data-tip="isFullscreen ? 'Exit fullscreen' : 'Fullscreen'">
                        <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                  </transition>
                </div>
              </div>
            </div>

            <!-- Action Bar Below Video -->
            <div class="mt-3 z-30 relative flex items-center gap-2">

              <!-- Comment input bar (slides open from left) -->
              <transition name="comment-slide">
              <div v-if="showCommentBox" class="flex-1 min-w-0 flex items-center bg-white border border-gray-200 rounded-full px-1.5 py-1 gap-1.5 transition-colors">
                <!-- Avatar -->
                <div v-if="currentUser" class="flex-shrink-0">
                  <img v-if="currentUser.avatar" :src="currentUser.avatar" class="w-7 h-7 rounded-full object-cover" />
                  <div v-else class="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-[10px] font-bold">
                    {{ (currentUser.name || 'U').substring(0, 2).toUpperCase() }}
                  </div>
                </div>
                <!-- Timestamp badge -->
                <span class="text-[10px] text-gray-500 font-mono font-medium bg-gray-100 px-1.5 py-0.5 rounded flex-shrink-0">
                  {{ formatTime(currentTime) }}
                </span>
                <!-- Input -->
                <div class="flex-1 min-w-0 relative">
                  <input
                    ref="commentBoxRef"
                    v-model="newComment"
                    type="text"
                    placeholder="Add new comment..."
                    @keydown.enter.prevent="addComment"
                    @keydown.escape="showCommentBox = false; newComment = ''"
                    @input="onCommentInput"
                    class="w-full text-xs text-gray-900 placeholder:text-gray-400 outline-none border-0 ring-0 focus:outline-none focus:ring-0 focus:border-0 bg-transparent py-1"
                  />
                  <!-- @ Mention Dropdown -->
                  <div
                    v-if="showMentionDropdown && mentionUsers.length > 0"
                    class="absolute bottom-full left-0 mb-2 w-56 bg-white border border-gray-200 rounded-lg shadow-xl py-1 z-50 max-h-40 overflow-y-auto"
                  >
                    <button
                      v-for="user in mentionUsers"
                      :key="user.id"
                      @mousedown.prevent="insertMention(user)"
                      class="w-full px-3 py-1.5 text-left text-xs text-gray-700 hover:bg-orange-50 flex items-center gap-2"
                    >
                      <img v-if="user.avatar_url" :src="user.avatar_url" class="w-5 h-5 rounded-full object-cover" />
                      <div v-else class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-[9px] font-bold">
                        {{ (user.name || 'U').charAt(0).toUpperCase() }}
                      </div>
                      <span class="font-medium">{{ user.name }}</span>
                    </button>
                  </div>
                </div>
                <!-- Quick emoji reactions -->
                <div class="flex items-center gap-0.5 flex-shrink-0">
                  <button v-for="emoji in quickEmojis" :key="emoji" @click="newComment += emoji" class="w-6 h-6 flex items-center justify-center text-sm hover:bg-gray-100 rounded-full transition-colors">{{ emoji }}</button>
                </div>
                <!-- @ mention button -->
                <button
                  @click="triggerMention"
                  class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-full transition-colors flex-shrink-0"
                  title="Mention someone"
                >
                  <span class="text-sm font-bold">@</span>
                </button>
                <!-- Send button -->
                <button
                  @click="addComment"
                  :disabled="!newComment.trim() || isSavingComment"
                  class="flex-shrink-0 bg-orange-600 text-white px-3 py-1 rounded-full text-[11px] font-semibold hover:bg-orange-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                  Send
                </button>
              </div>
              </transition>

              <!-- Spacer when comment box is closed -->
              <div v-if="!showCommentBox" class="flex-1"></div>

              <!-- Comment button (hidden when box is open) -->
              <button
                v-if="!showCommentBox"
                @click="isAuthenticated ? openCommentBox() : loginToComment()"
                class="flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 text-gray-600 text-xs font-medium rounded-lg border border-gray-200 transition-colors flex-shrink-0"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Comment
              </button>
              <!-- Copy Link -->
              <button
                @click="copyShareLink"
                class="flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 text-gray-600 text-xs font-medium rounded-lg border border-gray-200 transition-colors flex-shrink-0"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                {{ copied ? 'Copied!' : 'Copy Link' }}
              </button>
              <!-- Download -->
              <button
                @click="handleSharedDownload"
                class="flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 text-gray-600 text-xs font-medium rounded-lg border border-gray-200 transition-colors flex-shrink-0"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download
              </button>
            </div>
          </div>
        </div>

        <!-- Sidebar — slides in/out from right -->
        <aside
          class="sidebar-panel flex flex-col bg-white border-l border-gray-200 overflow-hidden flex-shrink-0"
          :class="sidebarVisible ? 'sidebar-open' : 'sidebar-closed'"
        >

          <!-- Functional Tabs -->
          <div class="grid grid-cols-3 gap-0 px-4 py-3 border-b border-gray-100 sticky top-0 bg-white z-10 flex-shrink-0">
            <button
              @click="activeTab = 'transcript'"
              class="px-3 py-2 text-xs rounded-lg transition-all text-center truncate"
              :class="activeTab === 'transcript' ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
            >
              Transcript
            </button>
            <button
              @click="activeTab = 'summary'"
              class="px-3 py-2 text-xs rounded-lg transition-all text-center truncate"
              :class="activeTab === 'summary' ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
            >
              Summary
            </button>
            <button
              @click="activeTab = 'comments'"
              class="px-3 py-2 text-xs rounded-lg transition-all text-center truncate flex items-center justify-center gap-1"
              :class="activeTab === 'comments' ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
            >
              Comments
              <span v-if="comments.length" class="text-[10px] text-gray-400 font-normal">{{ comments.length }}</span>
            </button>
          </div>

          <!-- Content Area -->
          <div class="flex-1 overflow-y-auto relative">

            <!-- TAB: COMMENTS -->
            <div v-show="activeTab === 'comments'" class="flex flex-col min-h-full">
              <div v-if="comments.length === 0" class="flex flex-col items-center justify-center h-48 text-center px-4">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mb-2">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                  </svg>
                </div>
                <p class="text-sm font-medium text-gray-900">No comments yet</p>
                <p class="text-xs text-gray-400 mt-0.5">Start the conversation</p>
              </div>

              <div v-else class="flex flex-col gap-1 p-3">
                <div
                  v-for="comment in comments"
                  :key="comment.id"
                  class="group flex gap-2 items-start"
                >
                  <div class="flex-shrink-0 pt-0.5">
                    <img v-if="comment.author_avatar" :src="comment.author_avatar" class="w-6 h-6 rounded-full object-cover ring-1 ring-gray-100">
                    <div v-else class="w-6 h-6 rounded-full bg-gradient-to-br from-orange-100 to-amber-100 flex items-center justify-center text-orange-600 text-[9px] font-bold ring-1 ring-orange-200/40">
                      {{ (comment.author_name || 'U').charAt(0).toUpperCase() }}
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="bg-gray-50 rounded-xl rounded-tl-sm px-2.5 py-1.5">
                      <div class="flex items-center gap-1.5 mb-0.5">
                        <span class="text-[10px] font-semibold text-gray-900">{{ comment.author_name }}</span>
                        <span class="text-[9px] text-gray-400">{{ formatCommentTime(comment.created_at) }}</span>
                      </div>
                      <p class="text-[11px] text-gray-700 leading-relaxed comment-content" v-html="renderCommentContent(comment.content)"></p>
                    </div>
                    <button
                      v-if="comment.timestamp_seconds != null"
                      @click="seekToTime(comment.timestamp_seconds)"
                      class="text-[9px] font-mono text-orange-500 hover:text-orange-600 mt-0.5 ml-1 transition-colors"
                    >
                      {{ formatTime(comment.timestamp_seconds) }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- TAB: TRANSCRIPT -->
            <div v-show="activeTab === 'transcript'" class="flex flex-col min-h-full">
              <!-- Transcript content with timestamps -->
              <div v-if="transcriptionSegments && transcriptionSegments.length > 0" class="flex flex-col h-full">
                <!-- Toolbar -->
                <div class="flex items-center gap-2 px-5 py-2.5 border-b border-gray-100 sticky top-0 bg-white z-10 flex-shrink-0">
                  <button
                    @click="copyTranscript"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors border border-gray-200"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    {{ copiedTranscript ? 'Copied!' : 'Copy' }}
                  </button>
                </div>
                <!-- Segment rows: timestamp | text -->
                <div class="flex-1 overflow-y-auto" ref="transcriptContainer">
                  <div
                    v-for="(segment, index) in transcriptionSegments"
                    :key="index"
                    :ref="el => { if (el) segmentRefs[index] = el }"
                    @click="seekToTime(segment.start)"
                    class="flex gap-4 px-5 py-3 cursor-pointer transition-colors duration-150 border-b border-gray-50"
                    :class="activeSegmentIndex === index
                      ? 'bg-orange-50/60'
                      : 'hover:bg-gray-50'"
                  >
                    <button
                      class="text-[13px] font-medium tabular-nums tracking-wide flex-shrink-0 transition-colors pt-0.5"
                      :class="activeSegmentIndex === index ? 'text-orange-500' : 'text-gray-400'"
                    >
                      {{ formatTime(segment.start) }}
                    </button>
                    <p
                      class="text-[13px] leading-relaxed flex-1"
                      :class="activeSegmentIndex === index ? 'text-gray-900' : 'text-gray-600'"
                    >
                      {{ getSegmentText(segment) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Fallback: show full transcript if no segments -->
              <div v-else-if="transcription" class="p-3">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-[10px] font-medium text-gray-400">Full transcript</span>
                  <button
                    @click="copyTranscript"
                    class="flex items-center gap-1 px-2 py-1 text-[10px] font-medium text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-md transition-colors"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    {{ copiedTranscript ? 'Copied!' : 'Copy' }}
                  </button>
                </div>
                <p class="text-[11px] text-gray-600 leading-relaxed whitespace-pre-wrap">{{ transcription }}</p>
              </div>

              <!-- Empty state -->
              <div v-else class="flex flex-col items-center justify-center h-48 text-center px-4">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <p class="text-sm font-medium text-gray-900">No Transcript</p>
                <p class="text-xs text-gray-500 mt-0.5">Not yet generated</p>
              </div>

              <!-- AI Chat about transcript (authenticated users only) -->
              <div v-if="isAuthenticated && isOwner && transcriptionSegments && transcriptionSegments.length > 0" class="border-t border-gray-100">
                <button
                  @click="showTranscriptChat = !showTranscriptChat"
                  class="w-full px-5 py-2.5 flex items-center justify-between text-left hover:bg-gray-50 transition-colors"
                >
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span class="text-xs font-semibold text-gray-900">Ask AI about transcript</span>
                    <span class="text-[10px] text-gray-400">({{ transcriptChatRemaining }} left)</span>
                  </div>
                  <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="showTranscriptChat ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </button>

                <div v-show="showTranscriptChat" class="px-5 pb-3">
                  <!-- Chat messages -->
                  <div v-if="transcriptChatMessages.length > 0" class="space-y-2 mb-3 max-h-48 overflow-y-auto">
                    <div
                      v-for="(msg, i) in transcriptChatMessages"
                      :key="i"
                      class="text-[11px] leading-relaxed"
                    >
                      <div v-if="msg.role === 'user'" class="flex justify-end">
                        <div class="bg-orange-600 text-white rounded-xl rounded-br-sm px-3 py-1.5 max-w-[85%]">
                          {{ msg.content }}
                        </div>
                      </div>
                      <div v-else class="flex justify-start">
                        <div class="bg-gray-100 text-gray-700 rounded-xl rounded-bl-sm px-3 py-1.5 max-w-[85%]">
                          {{ msg.content }}
                        </div>
                      </div>
                    </div>
                    <div v-if="transcriptChatLoading" class="flex justify-start">
                      <div class="bg-gray-100 text-gray-400 rounded-xl rounded-bl-sm px-3 py-1.5 text-[11px]">
                        <span class="inline-flex gap-1">
                          <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                          <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                          <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Input -->
                  <div v-if="transcriptChatRemaining > 0" class="flex gap-2">
                    <input
                      v-model="transcriptChatInput"
                      type="text"
                      placeholder="Ask about the transcript..."
                      @keydown.enter.prevent="askTranscriptQuestion"
                      :disabled="transcriptChatLoading"
                      class="flex-1 bg-gray-50 border border-gray-200 text-[11px] text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-orange-400 focus:ring-1 focus:ring-orange-400 rounded-lg px-3 py-1.5 transition-colors disabled:opacity-50"
                    />
                    <button
                      @click="askTranscriptQuestion"
                      :disabled="!transcriptChatInput.trim() || transcriptChatLoading"
                      class="bg-orange-600 text-white px-2.5 py-1.5 rounded-lg hover:bg-orange-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed flex-shrink-0 text-[11px] font-medium"
                    >
                      Ask
                    </button>
                  </div>
                  <p v-else class="text-[10px] text-gray-400 text-center py-2">Question limit reached for today</p>
                </div>
              </div>
            </div>

            <!-- TAB: SUMMARY -->
            <div v-show="activeTab === 'summary'" class="flex flex-col min-h-full">
              <!-- Summary content -->
              <div v-if="summary" class="p-3">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span class="text-xs font-semibold text-gray-900">AI Summary</span>
                  </div>
                  <button
                    @click="copySummary"
                    class="flex items-center gap-1 px-2 py-1 text-[10px] font-medium text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-md transition-colors"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    {{ copiedSummary ? 'Copied!' : 'Copy' }}
                  </button>
                </div>
                <div class="prose prose-sm max-w-none text-gray-600 leading-relaxed text-[12px]" v-html="formattedSummary"></div>
              </div>

              <!-- Empty state - no summary available -->
              <div v-else class="flex flex-col items-center justify-center h-48 text-center px-4">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-100 to-amber-100 flex items-center justify-center mb-3">
                  <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                  </svg>
                </div>
                <p class="text-sm font-medium text-gray-900">No Summary</p>
                <p class="text-xs text-gray-500 mt-0.5">Not yet generated</p>
              </div>
            </div>

          </div>

          <!-- Comment Input -->
          <div v-show="activeTab === 'comments'" class="px-3 py-2 bg-white border-t border-gray-100 z-20 flex-shrink-0">
            <!-- Authenticated users -->
            <div v-if="isAuthenticated">
              <div class="flex items-end gap-2">
                <div class="flex-1 relative">
                  <textarea
                    v-model="newComment"
                    rows="1"
                    placeholder="Write a message..."
                    @keydown.enter.exact.prevent="addComment"
                    class="w-full bg-gray-50 border border-gray-200 text-[11px] text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-orange-400 focus:ring-1 focus:ring-orange-400 rounded-xl px-3 py-2 resize-none min-h-[34px] max-h-[72px] transition-colors"
                  ></textarea>
                  <span v-if="currentTime > 0" class="absolute right-2 bottom-1.5 text-[8px] text-gray-400 font-mono pointer-events-none">
                    @ {{ formatTime(currentTime) }}
                  </span>
                </div>
                <button
                  @click="addComment"
                  :disabled="!newComment.trim() || isSavingComment"
                  class="bg-orange-600 text-white p-1.5 rounded-xl hover:bg-orange-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed flex-shrink-0"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Sign In Prompt (Non-authenticated users) -->
            <div v-else class="p-2.5 bg-gray-50 rounded-xl border border-gray-200">
              <p class="text-gray-500 text-[10px] mb-1.5 text-center">Sign in to comment</p>
              <button
                @click="loginToComment"
                class="flex items-center justify-center w-full px-3 py-1.5 text-[11px] font-medium text-gray-700 bg-white rounded-lg hover:bg-gray-100 transition-colors border border-gray-200"
              >
                <svg class="w-3.5 h-3.5 mr-1.5" viewBox="0 0 24 24">
                  <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                  <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                  <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                  <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Sign in with Google
              </button>
            </div>
          </div>
        </aside>

      </main>
    </template>

    <!-- Toast -->
    <transition name="toast">
      <div
        v-if="toast"
        class="fixed bottom-20 left-1/2 -translate-x-1/2 px-5 py-3 bg-white text-gray-900 rounded-xl text-sm font-medium shadow-2xl border border-gray-200 flex items-center gap-2 z-[100]"
      >
        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ toast }}
      </div>
    </transition>


    </div><!-- end Main Content Area -->
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/stores/auth'
import { useBranding } from '@/composables/useBranding'
import videoService from '@/services/videoService'
import NotificationBell from '@/components/Global/NotificationBell.vue'
import Hls from 'hls.js'
import { marked } from 'marked'
import { sanitizeHtml } from '@/utils/sanitize'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

export default {
  name: 'SharedVideoView',
  components: { NotificationBell },
  setup() {
    const route = useRoute()
    const auth = useAuth()
    const branding = useBranding()
    const token = computed(() => route.params.token)
    const isAuthenticated = computed(() => auth.isAuthenticated.value)
    const currentUser = computed(() => auth.user.value)
    const userInitial = computed(() => (currentUser.value?.name || 'U').charAt(0).toUpperCase())

    const video = ref({})
    const loading = ref(true)
    const error = ref(null)
    const comments = ref([])
    const reactions = ref({})
    const userReactions = ref([])

    const videoRef = ref(null)
    const progressBar = ref(null)
    const speedMenuRef = ref(null)
    const playerContainer = ref(null)

    const isPlaying = ref(false)
    const isBuffering = ref(false)
    const videoLoading = ref(true)
    const isMuted = ref(false)
    const isFullscreen = ref(false)
    const volume = ref(1)
    const currentTime = ref(0)
    const duration = ref(0)
    const bufferedPercent = ref(0)
    const playbackSpeed = ref(1)
    const speedOptions = [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]
    const controlsVisible = ref(false)
    const hoverTime = ref(null)
    const hoverPercent = ref(0)
    const showSpeedMenu = ref(false)
    const showBigPlayButton = ref(true)
    const showPrePlaySpeedMenu = ref(false)
    const copied = ref(false)
    const copiedEmbed = ref(false)
    const toast = ref(null)

    const newComment = ref('')
    const showCommentBox = ref(false)
    const commentBoxRef = ref(null)
    const isSavingComment = ref(false)

    const showShareModal = ref(false)
    const showShareDropdown = ref(false)
    const showOptionsMenu = ref(false)
    const showArchiveConfirm = ref(false)
    const showDeleteConfirm = ref(false)
    const showPrivateConfirm = ref(false)
    const shareDropdownRef = ref(null)
    const optionsMenuRef = ref(null)

    // Ownership check for restricting actions
    const isOwner = computed(() => currentUser.value?.id && video.value?.user_id && currentUser.value.id === video.value.user_id)

    const activeTab = ref('transcript')
    const sidebarVisible = ref(localStorage.getItem('sidebar_visible') !== 'false')
    const toggleSidebar = () => {
      sidebarVisible.value = !sidebarVisible.value
      localStorage.setItem('sidebar_visible', sidebarVisible.value)
    }

    // Transcription state (read-only for shared videos)
    const transcription = ref(null)
    const transcriptionSegments = ref([])
    const summary = ref(null)

    // Reconstruct properly spaced text from words array if available
    const getSegmentText = (segment) => {
      if (segment.words && segment.words.length > 0) {
        return segment.words.map(w => (w.text || '').trim()).filter(Boolean).join(' ')
      }
      return segment.text || ''
    }

    // Copy states
    const copiedTranscript = ref(false)
    const copiedSummary = ref(false)

    // Transcript AI chat
    const showTranscriptChat = ref(false)
    const transcriptChatInput = ref('')
    const transcriptChatMessages = ref([])
    const transcriptChatLoading = ref(false)
    const transcriptChatRemaining = ref(5)

    const askTranscriptQuestion = async () => {
      const question = transcriptChatInput.value.trim()
      if (!question || transcriptChatLoading.value || transcriptChatRemaining.value <= 0) return
      if (!video.value.id) return

      transcriptChatMessages.value.push({ role: 'user', content: question })
      transcriptChatInput.value = ''
      transcriptChatLoading.value = true

      try {
        const result = await videoService.askTranscriptQuestion(video.value.id, question)
        transcriptChatMessages.value.push({ role: 'assistant', content: result.answer })
        transcriptChatRemaining.value = result.questions_remaining
      } catch (err) {
        transcriptChatMessages.value.push({ role: 'assistant', content: err.message || 'Failed to get answer' })
      } finally {
        transcriptChatLoading.value = false
      }
    }

    // Captions state
    const captionsEnabled = ref(false)
    const captionsUrl = computed(() => {
      if (!transcriptionSegments.value || transcriptionSegments.value.length === 0) return null
      return true // indicates captions are available
    })

    // Build caption cues with word-level timing from segments
    const captionCues = computed(() => {
      if (!transcriptionSegments.value || transcriptionSegments.value.length === 0) return []
      const cues = []
      const MAX_CUE = 3.0

      for (const seg of transcriptionSegments.value) {
        const text = (seg.text || '').trim()
        if (!text) continue

        // Use real word timestamps when available
        if (seg.words && seg.words.length > 0) {
          let cueWords = []
          let cueStart = null

          for (const w of seg.words) {
            const wText = (w.text || '').trim()
            if (!wText) continue
            if (cueStart === null) cueStart = w.start

            cueWords.push({ text: wText, start: w.start, end: w.end })
            const cueDur = w.end - cueStart

            if (cueDur >= MAX_CUE) {
              cues.push({ start: cueStart, end: w.end, words: [...cueWords] })
              cueWords = []
              cueStart = null
            }
          }
          if (cueWords.length > 0 && cueStart !== null) {
            const last = cueWords[cueWords.length - 1]
            cues.push({ start: cueStart, end: last.end, words: [...cueWords] })
          }
          continue
        }

        // Fallback: no word timestamps — split by duration
        const dur = seg.end - seg.start
        if (dur <= MAX_CUE) {
          const splitWords = text.split(/\s+/)
          const wordDur = dur / splitWords.length
          cues.push({
            start: seg.start, end: seg.end,
            words: splitWords.map((w, i) => ({
              text: w,
              start: +(seg.start + i * wordDur).toFixed(2),
              end: +(seg.start + (i + 1) * wordDur).toFixed(2)
            }))
          })
          continue
        }
        const splitWords = text.split(/\s+/)
        const chunks = Math.ceil(dur / MAX_CUE)
        const perChunk = Math.max(1, Math.ceil(splitWords.length / chunks))
        const groups = []
        for (let i = 0; i < splitWords.length; i += perChunk) {
          groups.push(splitWords.slice(i, i + perChunk))
        }
        const chunkDur = dur / groups.length
        groups.forEach((g, gi) => {
          const gStart = +(seg.start + gi * chunkDur).toFixed(2)
          const gEnd = gi === groups.length - 1 ? seg.end : +(seg.start + (gi + 1) * chunkDur).toFixed(2)
          const wDur = (gEnd - gStart) / g.length
          cues.push({
            start: gStart, end: gEnd,
            words: g.map((w, wi) => ({
              text: w,
              start: +(gStart + wi * wDur).toFixed(2),
              end: +(gStart + (wi + 1) * wDur).toFixed(2)
            }))
          })
        })
      }
      return cues
    })

    // Find active cue and highlight words by their actual timestamps
    const activeCaptionCue = computed(() => {
      const t = currentTime.value
      const cue = captionCues.value.find(c => t >= c.start && t < c.end)
      if (!cue) return null
      return {
        words: cue.words.map(w => ({ text: w.text, active: t >= w.start }))
      }
    })

    const toggleCaptions = () => {
      captionsEnabled.value = !captionsEnabled.value
    }

    // Transcript sync state
    const transcriptContainer = ref(null)
    const segmentRefs = ref({})
    const autoScrollEnabled = ref(true)

    // HLS related
    const hlsInstance = ref(null)
    const availableQualities = ref([])
    const currentQuality = ref(-1)
    const showQualityMenu = ref(false)
    const qualityMenuRef = ref(null)

    // Bunny-specific state
    const isBunnyVideo = ref(false)
    const bunnyStatus = ref(null)
    const bunnyEncodeProgress = ref(0)
    const bunnyAvailableResolutions = ref([])

    let controlsTimeout = null
    let toastTimeout = null

    const progressPercent = computed(() => {
      if (!duration.value) return 0
      return (currentTime.value / duration.value) * 100
    })

    // Find the active transcript segment based on current video time
    const activeSegmentIndex = computed(() => {
      if (!transcriptionSegments.value || transcriptionSegments.value.length === 0) return -1

      const time = currentTime.value
      for (let i = transcriptionSegments.value.length - 1; i >= 0; i--) {
        const segment = transcriptionSegments.value[i]
        if (time >= segment.start) {
          return i
        }
      }
      return -1
    })

    // Auto-scroll to active segment when it changes
    watch(activeSegmentIndex, (newIndex) => {
      if (newIndex >= 0 && autoScrollEnabled.value && activeTab.value === 'transcript') {
        nextTick(() => {
          const segmentEl = segmentRefs.value[newIndex]
          if (segmentEl && transcriptContainer.value) {
            segmentEl.scrollIntoView({
              behavior: 'smooth',
              block: 'center'
            })
          }
        })
      }
    })

    const totalReactions = computed(() => {
      return Object.values(reactions.value).reduce((sum, r) => sum + (r.count || 0), 0)
    })

    const shareUrl = computed(() => window.location.href)

    const sessionId = ref(localStorage.getItem('openkap_session') || Math.random().toString(36).substring(2))
    if (!localStorage.getItem('openkap_session')) {
      localStorage.setItem('openkap_session', sessionId.value)
    }

    const fetchVideo = async () => {
      loading.value = true
      try {
        const response = await fetch(`${API_BASE_URL}/api/share/video/${token.value}`)
        if (!response.ok) throw new Error('Video not available')
        const data = await response.json()
        video.value = data.video
        comments.value = data.video.comments || []
        reactions.value = data.video.reactions || {}
        duration.value = data.video.duration || 0

        // Apply owner's branding
        if (data.video.branding) {
          if (data.video.branding.brand_color) {
            branding.setBrandColor(data.video.branding.brand_color)
          }
          if (data.video.branding.logo_url) {
            branding.setLogoUrl(data.video.branding.logo_url)
          }
        }

        // Load transcription/summary if available
        transcription.value = data.video.transcription || null
        transcriptionSegments.value = data.video.transcription_segments || []
        summary.value = data.video.summary || null

        if (data.video.storage_type === 'bunny') {
          isBunnyVideo.value = true
          bunnyStatus.value = data.video.bunny_status
          if (['ready', 'transcoding'].includes(data.video.bunny_status)) {
            try {
              const bunnyData = await videoService.getBunnySharedPlayback(token.value)
              if (bunnyData.playback) {
                video.value.hls_url = bunnyData.playback.hlsUrl
                video.value.is_hls_ready = true
              }
              if (bunnyData.video) {
                bunnyStatus.value = bunnyData.video.status
                bunnyEncodeProgress.value = bunnyData.video.encode_progress || 0
                bunnyAvailableResolutions.value = bunnyData.video.available_resolutions || []
                if (bunnyData.video.duration && bunnyData.video.duration > 0) {
                  video.value.duration = bunnyData.video.duration
                  duration.value = bunnyData.video.duration
                }
              }
            } catch (bunnyErr) {
              console.warn('Failed to fetch Bunny playback, falling back to local file:', bunnyErr)
            }
          }
        }

        // Initialize HLS after video data is loaded
        setTimeout(() => initHls(), 100)
      } catch (err) {
        error.value = err.message || 'Failed to load video'
      } finally {
        loading.value = false
      }
    }

    const seekToTime = (seconds) => {
      if (videoRef.value) {
        videoRef.value.currentTime = seconds
        if (!isPlaying.value) {
          togglePlay()
        }
      }
    }

    const copyTranscript = async () => {
      if (!transcription.value && (!transcriptionSegments.value || transcriptionSegments.value.length === 0)) return

      let textToCopy = transcription.value || ''
      if (transcriptionSegments.value && transcriptionSegments.value.length > 0) {
        textToCopy = transcriptionSegments.value
          .map(segment => `[${formatTime(segment.start)}] ${getSegmentText(segment)}`)
          .join('\n')
      }

      try {
        await navigator.clipboard.writeText(textToCopy)
        copiedTranscript.value = true
        showToast('Transcript copied!')
        setTimeout(() => { copiedTranscript.value = false }, 3000)
      } catch (err) {
        console.error('Failed to copy transcript:', err)
      }
    }

    const copySummary = async () => {
      if (!summary.value) return

      try {
        await navigator.clipboard.writeText(summary.value)
        copiedSummary.value = true
        showToast('Summary copied!')
        setTimeout(() => { copiedSummary.value = false }, 3000)
      } catch (err) {
        console.error('Failed to copy summary:', err)
      }
    }

    const formattedSummary = computed(() => {
      if (!summary.value) return ''
      return sanitizeHtml(marked.parse(summary.value, { breaks: true }))
    })

    const initHls = () => {
      const videoElement = videoRef.value
      if (!videoElement) return

      const hlsUrl = video.value.hls_url
      const isHlsReady = video.value.is_hls_ready

      // Check backend's is_hls_ready flag - if not ready, use raw URL
      if (!isHlsReady || !hlsUrl) {
        videoElement.src = video.value.url
        return
      }

      // If HLS URL available and HLS.js is supported
      if (Hls.isSupported()) {
        // Destroy existing instance
        if (hlsInstance.value) {
          hlsInstance.value.destroy()
        }

        const hls = new Hls({
          enableWorker: true,
          lowLatencyMode: false,
          backBufferLength: 90,
          // Handle 404/403 errors gracefully
          xhrSetup: function (xhr, url) {
            xhr.onreadystatechange = function () {
              if (xhr.readyState === 4) {
                if (xhr.status === 404 || xhr.status === 403) {
                  // Manifest missing or forbidden, fallback to raw
                  // HLS manifest missing/forbidden, falling back to MP4
                  hls.destroy()
                  videoElement.src = video.value.url
                }
              }
            }
          }
        })

        hls.loadSource(hlsUrl)
        hls.attachMedia(videoElement)

        hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
          // Show all available qualities
          availableQualities.value = data.levels.map((level, index) => ({
            index,
            height: level.height,
            width: level.width,
            bitrate: level.bitrate,
            label: level.height >= 2160 ? '4K' : `${level.height}p`
          }))

          // Set highest quality as default
          if (availableQualities.value.length > 0) {
            const highest = availableQualities.value[availableQualities.value.length - 1]
            currentQuality.value = highest.index
            hls.currentLevel = highest.index
          }
        })

        hls.on(Hls.Events.ERROR, (event, data) => {
          if (data.fatal) {
            switch (data.type) {
              case Hls.ErrorTypes.NETWORK_ERROR:
                if (data.response && (data.response.code === 404 || data.response.code === 403)) {
                  hls.destroy()
                  videoElement.src = video.value.url
                } else {
                  hls.startLoad()
                }
                break
              case Hls.ErrorTypes.MEDIA_ERROR:
                hls.recoverMediaError()
                break
              default:
                hls.destroy()
                videoElement.src = video.value.url
                break
            }
          }
        })

        hlsInstance.value = hls

      } else if (hlsUrl && videoElement.canPlayType('application/vnd.apple.mpegurl')) {
        // Native HLS support (Safari)
        videoElement.src = hlsUrl
        videoElement.onerror = () => {
          // Native HLS failed, falling back to MP4
          videoElement.src = video.value.url
        }
      } else {
        // Fallback to MP4
        videoElement.src = video.value.url
      }
    }

    const setQuality = (qualityIndex) => {
      if (!hlsInstance.value) return
      currentQuality.value = qualityIndex
      showQualityMenu.value = false
      hlsInstance.value.currentLevel = qualityIndex
      const quality = availableQualities.value.find(q => q.index === qualityIndex)
      showToast(`Quality: ${quality?.label || 'Auto'}`)
    }

    const toggleQualityMenu = () => {
      showQualityMenu.value = !showQualityMenu.value
      showSpeedMenu.value = false
    }

    const getCurrentQualityLabel = () => {
      if (currentQuality.value === -1) return 'Auto'
      const quality = availableQualities.value.find(q => q.index === currentQuality.value)
      return quality?.label || 'Auto'
    }

    const destroyHls = () => {
      if (hlsInstance.value) {
        hlsInstance.value.destroy()
        hlsInstance.value = null
      }
    }

    const togglePlay = () => {
      if (!videoRef.value) return
      if (isPlaying.value) {
        videoRef.value.pause()
      } else {
        videoRef.value.play()
        showBigPlayButton.value = false
      }
    }

    const updateProgress = () => {
      if (!videoRef.value) return
      currentTime.value = videoRef.value.currentTime
      if (videoRef.value.buffered.length > 0) {
        bufferedPercent.value = (videoRef.value.buffered.end(videoRef.value.buffered.length - 1) / duration.value) * 100
      }
    }

    const onVideoLoaded = () => {
      if (!videoRef.value) return
      const videoDuration = videoRef.value.duration
      const apiDuration = video.value.duration

      if (isFinite(videoDuration) && videoDuration > 0) {
        duration.value = videoDuration
        isBuffering.value = false
        videoLoading.value = false
      } else if (apiDuration && apiDuration > 0) {
        duration.value = apiDuration
        isBuffering.value = false
        videoLoading.value = false
      }

      if (videoRef.value) {
        videoRef.value.playbackRate = playbackSpeed.value
      }
    }

    const onVideoError = (event) => {
      console.error('Video error:', event)
      isBuffering.value = false
      videoLoading.value = false
    }

    const onVideoEnded = () => {
      isPlaying.value = false
      showBigPlayButton.value = true
    }

    const seekToPosition = (clientX) => {
      const vid = videoRef.value
      const bar = progressBar.value
      if (!vid || !bar) return

      const videoDuration = isFinite(vid.duration) && vid.duration > 0 ? vid.duration : duration.value
      if (!videoDuration || !isFinite(videoDuration) || videoDuration <= 0) return

      const rect = bar.getBoundingClientRect()
      const percent = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width))
      const newTime = percent * videoDuration
      vid.currentTime = newTime
      currentTime.value = newTime
    }

    const seek = (e) => {
      seekToPosition(e.clientX)
    }

    const startSeeking = (e) => {
      e.preventDefault()
      const vid = videoRef.value
      if (!vid) return

      const wasPlaying = !vid.paused
      if (wasPlaying) vid.pause()

      const onMouseMove = (moveEvent) => {
        seekToPosition(moveEvent.clientX)
      }

      const onMouseUp = () => {
        document.removeEventListener('mousemove', onMouseMove)
        document.removeEventListener('mouseup', onMouseUp)
        if (wasPlaying && vid) vid.play()
      }

      document.addEventListener('mousemove', onMouseMove)
      document.addEventListener('mouseup', onMouseUp)
      seekToPosition(e.clientX)
    }

    const updateHoverTime = (e) => {
      if (!progressBar.value) return
      const rect = progressBar.value.getBoundingClientRect()
      const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
      hoverPercent.value = percent * 100
      hoverTime.value = percent * duration.value
    }

    const skip = (seconds) => {
      const vid = videoRef.value
      if (!vid) return

      const videoDuration = isFinite(vid.duration) && vid.duration > 0 ? vid.duration : duration.value
      if (!videoDuration || !isFinite(videoDuration) || videoDuration <= 0) return

      const currentVideoTime = vid.currentTime || 0
      const newTime = Math.max(0, Math.min(videoDuration, currentVideoTime + seconds))
      vid.currentTime = newTime
      currentTime.value = newTime
    }

    const toggleMute = () => {
      if (!videoRef.value) return
      isMuted.value = !isMuted.value
      videoRef.value.muted = isMuted.value
    }

    const updateVolume = () => {
      if (!videoRef.value) return
      videoRef.value.volume = volume.value
      isMuted.value = volume.value === 0
    }

    const toggleSpeedMenu = () => {
      showSpeedMenu.value = !showSpeedMenu.value
      showQualityMenu.value = false
    }

    const setPlaybackSpeed = (speed) => {
      playbackSpeed.value = speed
      showSpeedMenu.value = false
      if (videoRef.value) {
        videoRef.value.playbackRate = speed
      }
    }

    const toggleFullscreen = async () => {
      try {
        if (!document.fullscreenElement) {
          await playerContainer.value.requestFullscreen()
          isFullscreen.value = true
        } else {
          await document.exitFullscreen()
          isFullscreen.value = false
        }
      } catch (err) {}
    }

    const togglePiP = async () => {
      try {
        if (document.pictureInPictureElement) {
          await document.exitPictureInPicture()
        } else if (videoRef.value) {
          await videoRef.value.requestPictureInPicture()
        }
      } catch (err) {}
    }

    const showControls = () => {
      controlsVisible.value = true
      if (controlsTimeout) clearTimeout(controlsTimeout)
    }

    const hideControlsDelayed = () => {
      if (controlsTimeout) clearTimeout(controlsTimeout)
      controlsTimeout = setTimeout(() => {
        if (isPlaying.value && !showSpeedMenu.value && !showQualityMenu.value) controlsVisible.value = false
      }, 3000)
    }

    const formatTime = (seconds) => {
      if (!seconds || isNaN(seconds) || !isFinite(seconds)) return '0:00'
      const mins = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${mins}:${secs.toString().padStart(2, '0')}`
    }

    const formatTimeAgo = (dateStr) => {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      const seconds = Math.floor((new Date() - date) / 1000)
      if (seconds < 60) return 'Just now'
      if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`
      if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
      if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`
      return date.toLocaleDateString()
    }

    const formatCommentTime = (dateStr) => {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      const now = new Date()
      const diff = now - date
      const minutes = Math.floor(diff / 60000)
      if (minutes < 1) return 'Just now'
      if (minutes < 60) return `${minutes}m ago`
      const hours = Math.floor(minutes / 60)
      if (hours < 24) return `${hours}h ago`
      const days = Math.floor(hours / 24)
      return `${days}d ago`
    }

    const showToast = (msg) => {
      toast.value = msg
      if (toastTimeout) clearTimeout(toastTimeout)
      toastTimeout = setTimeout(() => { toast.value = null }, 2000)
    }

    const copyShareLink = async () => {
      try {
        await navigator.clipboard.writeText(window.location.href)
        copied.value = true
        showToast('Link copied!')
        setTimeout(() => { copied.value = false }, 3000)
      } catch (err) {}
    }

    const copyEmbedCode = async () => {
      try {
        const embedUrl = `${API_BASE_URL}/embed/video/${token.value}`
        const embedCode = `<iframe src="${embedUrl}" width="640" height="360" frameborder="0" allowfullscreen></iframe>`
        await navigator.clipboard.writeText(embedCode)
        copiedEmbed.value = true
        showToast('Embed code copied!')
        setTimeout(() => { copiedEmbed.value = false }, 3000)
      } catch (err) {}
    }

    // Owner action handlers for shared view
    const handleSharedDownload = async () => {
      try {
        // Bunny video ready — download MP4 from CDN
        if (isBunnyVideo.value && bunnyStatus.value === 'ready') {
          try {
            const downloadData = await videoService.getBunnySharedDownload(token.value)
            if (downloadData.url) {
              const link = document.createElement('a')
              link.href = downloadData.url
              link.download = downloadData.file_name || `${video.value.title || 'video'}.mp4`
              link.target = '_blank'
              document.body.appendChild(link)
              link.click()
              document.body.removeChild(link)
              showToast('Download started!')
              return
            }
          } catch (bunnyErr) {
            console.warn('Bunny download failed:', bunnyErr)
          }
        }

        // Bunny video still processing
        if (isBunnyVideo.value) {
          showToast('Video is still processing — download will be available shortly.')
          return
        }

        // Local video fallback
        if (!video.value.url) return
        showToast('Starting download...')
        const response = await fetch(video.value.url)
        const blob = await response.blob()
        const blobUrl = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = blobUrl
        link.download = `${video.value.title || 'video'}.mp4`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(blobUrl)
        showToast('Download complete!')
      } catch (err) {
        console.error('Failed to download:', err)
        showToast('Failed to download video')
      }
    }

    const handleSharedDuplicate = async () => {
      if (!isOwner.value) return
      try {
        showToast('Duplicating video...')
        const result = await videoService.duplicateVideo(video.value.id)
        if (result) showToast('Video duplicated!')
      } catch (err) {
        console.error('Failed to duplicate:', err)
        showToast('Failed to duplicate video')
      }
    }

    const handleSharedRename = () => {
      if (!isOwner.value) return
      const newTitle = prompt('Enter new title:', video.value.title)
      if (newTitle && newTitle.trim()) {
        videoService.updateVideo(video.value.id, { title: newTitle.trim() })
          .then(() => {
            video.value.title = newTitle.trim()
            showToast('Title updated!')
          })
          .catch(() => showToast('Failed to update title'))
      }
    }

    const handleSharedMakePrivate = async () => {
      if (!isOwner.value) return
      try {
        await videoService.updateVideo(video.value.id, { is_private: true })
        showToast('Video is now private')
      } catch (err) {
        console.error('Failed to make private:', err)
        showToast('Failed to update privacy')
      }
    }

    const handleSharedArchive = async () => {
      if (!isOwner.value) return
      try {
        await videoService.updateVideo(video.value.id, { archived: true })
        showToast('Video archived')
      } catch (err) {
        console.error('Failed to archive:', err)
        showToast('Failed to archive video')
      }
    }

    const handleSharedDelete = async () => {
      if (!isOwner.value) return
      try {
        await videoService.deleteVideo(video.value.id)
        showToast('Video deleted')
        window.location.href = '/'
      } catch (err) {
        console.error('Failed to delete:', err)
        showToast('Failed to delete video')
      }
    }

    const toggleReaction = async (type) => {
      try {
        const response = await fetch(`${API_BASE_URL}/api/share/video/${token.value}/reactions`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ type, session_id: sessionId.value })
        })
        const data = await response.json()

        if (data.action === 'added') {
          userReactions.value.push(type)
          if (reactions.value[type]) reactions.value[type].count++
        } else {
          userReactions.value = userReactions.value.filter(r => r !== type)
          if (reactions.value[type]) reactions.value[type].count--
        }
      } catch (err) {
        console.error('Failed to toggle reaction:', err)
      }
    }

    const addComment = async () => {
      if (!newComment.value.trim() || !isAuthenticated.value || isSavingComment.value) return

      isSavingComment.value = true
      const commentText = newComment.value.trim()
      const timestampSeconds = videoRef.value ? Math.floor(videoRef.value.currentTime) : null
      newComment.value = ''

      try {
        const response = await fetch(`${API_BASE_URL}/api/share/video/${token.value}/comments`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${auth.token.value}`
          },
          body: JSON.stringify({
            content: commentText,
            author_name: currentUser.value?.name || 'Anonymous',
            timestamp_seconds: timestampSeconds
          })
        })
        const data = await response.json()
        comments.value.unshift(data.comment)
        showToast('Comment added!')
        showCommentBox.value = false
      } catch (err) {
        console.error('Failed to add comment:', err)
        newComment.value = commentText
        showToast('Failed to save comment')
      } finally {
        isSavingComment.value = false
      }
    }

    const openCommentBox = () => {
      showCommentBox.value = true
      nextTick(() => {
        commentBoxRef.value?.focus()
      })
    }

    // --- Mention & Emoji ---
    const quickEmojis = ['👍', '🤩', '😮', '👎', '😊']
    const showMentionDropdown = ref(false)
    const mentionUsers = ref([])
    const mentionQuery = ref('')
    let allCommenters = []

    const loadCommenters = async () => {
      if (allCommenters.length > 0) return
      try {
        const res = await fetch(`${API_BASE_URL}/api/share/video/${token.value}/commenters`, {
          headers: { 'Authorization': `Bearer ${auth.token.value}` }
        })
        const data = await res.json()
        allCommenters = data.commenters || []
      } catch { allCommenters = [] }
    }

    const onCommentInput = () => {
      const val = newComment.value
      const atIdx = val.lastIndexOf('@')
      if (atIdx >= 0 && (atIdx === 0 || val[atIdx - 1] === ' ')) {
        const query = val.substring(atIdx + 1).toLowerCase()
        if (!query.includes(' ') || query.length < 20) {
          mentionQuery.value = query
          loadCommenters().then(() => {
            mentionUsers.value = allCommenters.filter(u =>
              u.name.toLowerCase().includes(query)
            ).slice(0, 6)
            showMentionDropdown.value = mentionUsers.value.length > 0
          })
          return
        }
      }
      showMentionDropdown.value = false
    }

    const triggerMention = () => {
      const val = newComment.value
      newComment.value = val + (val.length > 0 && val[val.length - 1] !== ' ' ? ' @' : '@')
      commentBoxRef.value?.focus()
      onCommentInput()
    }

    const insertMention = (user) => {
      const val = newComment.value
      const atIdx = val.lastIndexOf('@')
      newComment.value = val.substring(0, atIdx) + `@[${user.name}](${user.id}) `
      showMentionDropdown.value = false
      commentBoxRef.value?.focus()
    }

    const renderCommentContent = (content) => {
      if (!content) return ''
      let safe = content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      safe = safe.replace(/@\[([^\]]+)\]\(\d+\)/g, '<span class="text-orange-600 font-semibold cursor-pointer hover:underline">@$1</span>')
      safe = safe.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline break-all">$1</a>')
      return safe
    }

    const loginToComment = () => {
      localStorage.setItem('auth_redirect', window.location.pathname)
      auth.loginWithGoogle()
    }

    const handleKeydown = (e) => {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return

      switch (e.key.toLowerCase()) {
        case ' ':
        case 'k':
          e.preventDefault()
          togglePlay()
          break
        case 'f':
          e.preventDefault()
          toggleFullscreen()
          break
        case 'm':
          e.preventDefault()
          toggleMute()
          showToast(isMuted.value ? 'Muted' : 'Unmuted')
          break
        case 'arrowleft':
          e.preventDefault()
          skip(-5)
          break
        case 'arrowright':
          e.preventDefault()
          skip(5)
          break
        case 'j':
          e.preventDefault()
          skip(-10)
          break
        case 'l':
          e.preventDefault()
          skip(10)
          break
        case 'i':
          e.preventDefault()
          togglePiP()
          break
        case 'arrowup':
          e.preventDefault()
          volume.value = Math.min(1, Number(volume.value) + 0.1)
          updateVolume()
          showToast(`Volume: ${Math.round(volume.value * 100)}%`)
          break
        case 'arrowdown':
          e.preventDefault()
          volume.value = Math.max(0, Number(volume.value) - 0.1)
          updateVolume()
          showToast(`Volume: ${Math.round(volume.value * 100)}%`)
          break
      }

      if (e.key >= '0' && e.key <= '9') {
        e.preventDefault()
        const percent = parseInt(e.key) * 10
        const vid = videoRef.value
        if (vid && duration.value) {
          const newTime = (percent / 100) * duration.value
          vid.currentTime = newTime
          currentTime.value = newTime
          showToast(`Jumped to ${percent}%`)
        }
      }
    }

    const handleClickOutside = (e) => {
      if (speedMenuRef.value && !speedMenuRef.value.contains(e.target)) {
        showSpeedMenu.value = false
      }
      if (qualityMenuRef.value && !qualityMenuRef.value.contains(e.target)) {
        showQualityMenu.value = false
      }
      // Close share dropdown
      if (shareDropdownRef.value && !shareDropdownRef.value.contains(e.target)) {
        showShareDropdown.value = false
      }
      // Close options menu
      if (optionsMenuRef.value && !optionsMenuRef.value.contains(e.target)) {
        showOptionsMenu.value = false
      }
    }

    const handleFullscreenChange = () => {
      isFullscreen.value = !!document.fullscreenElement
    }

    onMounted(async () => {
      await fetchVideo()

      // Record view (non-blocking)
      if (token.value) {
        videoService.recordSharedView(token.value).catch(() => {})
      }

      document.addEventListener('keydown', handleKeydown)
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('fullscreenchange', handleFullscreenChange)
    })

    onUnmounted(() => {
      document.removeEventListener('keydown', handleKeydown)
      document.removeEventListener('click', handleClickOutside)
      document.removeEventListener('fullscreenchange', handleFullscreenChange)
      if (controlsTimeout) clearTimeout(controlsTimeout)
      if (toastTimeout) clearTimeout(toastTimeout)
      destroyHls()
    })

    return {
      branding,
      video, loading, error, comments, reactions, userReactions, totalReactions,
      videoRef, progressBar, speedMenuRef, playerContainer, qualityMenuRef,
      isPlaying, isBuffering, videoLoading, isMuted, isFullscreen, volume, currentTime, duration,
      bufferedPercent, progressPercent, playbackSpeed, speedOptions, controlsVisible,
      hoverTime, hoverPercent, showSpeedMenu, showBigPlayButton, showPrePlaySpeedMenu,
      copied, copiedEmbed, toast, shareUrl,
      newComment, isSavingComment, isAuthenticated, currentUser, userInitial,
      showShareModal, activeTab, sidebarVisible, toggleSidebar,
      // Dropdowns & confirmations
      showShareDropdown, showOptionsMenu, shareDropdownRef, optionsMenuRef,
      showArchiveConfirm, showDeleteConfirm, showPrivateConfirm,
      isOwner,
      // Owner action handlers
      handleSharedDownload, handleSharedDuplicate, handleSharedRename,
      handleSharedMakePrivate, handleSharedArchive, handleSharedDelete,
      // Transcription (read-only for shared view)
      transcription, transcriptionSegments, summary, formattedSummary, seekToTime, getSegmentText,
      // Captions
      captionsEnabled, captionsUrl, toggleCaptions, activeCaptionCue,
      // Transcript sync
      transcriptContainer, segmentRefs, activeSegmentIndex,
      // Transcript AI chat
      showTranscriptChat, transcriptChatInput, transcriptChatMessages,
      transcriptChatLoading, transcriptChatRemaining, askTranscriptQuestion,
      // Copy
      copiedTranscript, copiedSummary, copyTranscript, copySummary,
      // HLS
      availableQualities, currentQuality, showQualityMenu,
      togglePlay, updateProgress, onVideoLoaded, onVideoError, onVideoEnded, seek, startSeeking,
      updateHoverTime, skip, toggleMute, updateVolume, toggleSpeedMenu, setPlaybackSpeed,
      toggleFullscreen, togglePiP, showControls, hideControlsDelayed,
      formatTime, formatTimeAgo, formatCommentTime, copyShareLink, copyEmbedCode, toggleReaction, addComment, loginToComment, openCommentBox, showCommentBox, commentBoxRef,
      quickEmojis, showMentionDropdown, mentionUsers, onCommentInput, triggerMention, insertMention, renderCommentContent,
      // HLS functions
      setQuality, toggleQualityMenu, getCurrentQualityLabel,
      // Bunny
      isBunnyVideo, bunnyStatus, bunnyEncodeProgress, bunnyAvailableResolutions,
    }
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Comment bar slide in from left */
.comment-slide-enter-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.comment-slide-leave-active { transition: all 0.2s ease-in; }
.comment-slide-enter-from { opacity: 0; transform: translateX(-20px); max-width: 0; }
.comment-slide-leave-to { opacity: 0; transform: translateX(-20px); max-width: 0; }

/* Control tooltips */
.ctrl-tip { position: relative; }
.ctrl-tip::before {
  content: attr(data-tip);
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  padding: 3px 8px;
  background: rgba(0,0,0,0.85);
  color: white;
  font-size: 11px;
  font-weight: 500;
  border-radius: 5px;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.15s ease;
  z-index: 60;
}
.ctrl-tip:hover::before { opacity: 1; }

/* Volume slider styling */
.volume-slider { -webkit-appearance: none; appearance: none; background: transparent; cursor: pointer; height: 6px; }
.volume-slider::-webkit-slider-runnable-track { height: 4px; background: rgba(255,255,255,0.3); border-radius: 3px; }
.volume-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 12px; height: 12px; background: white; border-radius: 50%; margin-top: -4px; box-shadow: 0 0 4px rgba(0,0,0,0.3); }
.volume-slider::-moz-range-track { height: 4px; background: rgba(255,255,255,0.3); border-radius: 3px; border: none; }
.volume-slider::-moz-range-thumb { width: 12px; height: 12px; background: white; border-radius: 50%; border: none; box-shadow: 0 0 4px rgba(0,0,0,0.3); }

/* Video stage transitions */
.video-stage {
  flex: 1;
  min-width: 0;
  transition: flex 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Sidebar slide from right */
.sidebar-panel {
  transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.sidebar-panel.sidebar-open { width: 380px; }
.sidebar-panel.sidebar-closed { width: 0; border-left: none; }

.toast-enter-active { transition: all 0.3s ease; }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 20px); }

@keyframes pulse-glow {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.01); }
}
.ambient-glow { animation: pulse-glow 8s infinite ease-in-out; }

input[type=range] { -webkit-appearance: none; background: transparent; }
input[type=range]::-webkit-slider-thumb {
  -webkit-appearance: none;
  height: 12px; width: 12px;
  border-radius: 50%; background: white;
  cursor: pointer; margin-top: -5px;
  box-shadow: 0 0 0 1px rgba(0,0,0,0.1), 0 2px 4px rgba(0,0,0,0.1);
}
input[type=range]::-webkit-slider-runnable-track {
  width: 100%; height: 2px;
  cursor: pointer; background: rgba(255,255,255,0.3);
  border-radius: 10px;
}
.caption-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 4px;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(8px);
  border-radius: 6px;
  padding: 6px 14px;
  max-width: 80%;
}
.caption-word {
  font-size: 14px;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-weight: 500;
  letter-spacing: -0.01em;
  line-height: 1.5;
  transition: color 0.15s ease;
}
.caption-active { color: #ffffff; }
.caption-inactive { color: rgba(255, 255, 255, 0.45); }
</style>
