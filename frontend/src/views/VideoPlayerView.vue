<template>
  <div class="bg-[#FAFAFA] text-slate-900 h-screen flex flex-col overflow-hidden selection:bg-orange-100 selection:text-orange-700">

    <!-- Subtle Background Grid -->
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 32px 32px; opacity: 0.4;"></div>

    <!-- Loading State (Skeleton) -->
    <div v-if="loading" class="flex flex-col lg:flex-row h-full animate-pulse">
      <div class="flex-1 p-4 lg:p-8 flex flex-col items-center">
        <div class="w-full max-w-4xl">
          <div class="h-7 w-2/3 bg-gray-200 rounded-lg mb-3"></div>
          <div class="h-4 w-1/3 bg-gray-100 rounded mb-6"></div>
          <div class="w-full rounded-xl bg-gray-200" style="aspect-ratio: 16/9;"></div>
        </div>
      </div>
      <div class="w-full lg:w-[400px] bg-white border-l border-gray-200 p-4 space-y-4">
        <div class="flex gap-2">
          <div class="h-9 w-24 bg-gray-100 rounded-lg"></div>
          <div class="h-9 w-24 bg-gray-100 rounded-lg"></div>
        </div>
        <div class="space-y-3">
          <div class="flex gap-3 items-start" v-for="n in 3" :key="n">
            <div class="w-8 h-8 rounded-full bg-gray-200 flex-shrink-0"></div>
            <div class="flex-1 space-y-2">
              <div class="h-3 w-20 bg-gray-200 rounded"></div>
              <div class="h-3 w-full bg-gray-100 rounded"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ error }}</h3>
        <button @click="goBack" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors">
          Go Back
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <template v-else>
      <!-- Navigation -->
      <nav class="h-14 border-b border-gray-200/60 bg-white/90 backdrop-blur-md flex items-center justify-between px-5 z-50 sticky top-0 flex-shrink-0">
        <div class="flex items-center gap-2">
          <router-link to="/videos" class="flex items-center gap-1.5 hover:opacity-80 transition-opacity">
            <img :src="branding.logoUrl.value || '/logo.png'" alt="OpenKap" class="w-6 h-6 rounded-md" />
            <span class="text-xs font-semibold text-gray-900">OpenKap</span>
          </router-link>
          <!-- Zoom Status Badge -->
          <div v-if="video.zoom_enabled">
            <span
              v-if="video.zoom_status === 'processing'"
              class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-orange-100 text-orange-700"
            >
              <svg class="w-2.5 h-2.5 mr-0.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              Zoom {{ video.zoom_progress || 0 }}%
            </span>
            <span
              v-else-if="video.is_zoom_ready"
              class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700"
            >
              <svg class="w-2.5 h-2.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
              </svg>
              Zoom ({{ video.zoom_event_count }})
            </span>
            <span
              v-else-if="video.zoom_status === 'failed'"
              class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700"
            >
              Zoom Failed
            </span>
            <span
              v-else-if="video.zoom_status === 'pending' && video.zoom_event_count > 0"
              class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600"
            >
              Zoom Pending
            </span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Editor Button (paid users only) -->
          <router-link
            v-if="auth.hasActiveSubscription.value"
            :to="`/video/${video.id}/edit`"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-1.5"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit
          </router-link>

          <!-- Share Dropdown -->
          <div class="relative" ref="shareDropdownRef">
            <button @click="showShareDropdown = !showShareDropdown" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
              </svg>
              Share
              <svg class="w-2.5 h-2.5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div v-show="showShareDropdown" class="absolute right-0 top-full mt-1.5 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-1.5 z-50">
              <button @click="copyShareLink; showShareDropdown = false" class="w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Copy link
              </button>
              <button @click="copyEmbedCode; showShareDropdown = false" class="w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                Copy embed code
              </button>
            </div>
          </div>

          <!-- Options Menu (three-dot) -->
          <div class="relative" ref="optionsMenuRef">
            <button @click="showOptionsMenu = !showOptionsMenu" class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="5" r="1.5"/>
                <circle cx="12" cy="12" r="1.5"/>
                <circle cx="12" cy="19" r="1.5"/>
              </svg>
            </button>
            <div v-show="showOptionsMenu" class="absolute right-0 top-full mt-1.5 w-52 bg-white rounded-lg shadow-xl border border-gray-200 py-1.5 z-50">
              <button @click="handleDownload; showOptionsMenu = false" class="w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download
              </button>
              <button @click="handleDuplicate; showOptionsMenu = false" class="w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Duplicate
              </button>
              <button @click="startEditingTitle; showOptionsMenu = false" class="w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Rename
              </button>
              <button @click="handleDownloadCaptions; showOptionsMenu = false" class="w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <rect x="2" y="4" width="20" height="16" rx="2" stroke-width="2"/>
                  <text x="12" y="15" text-anchor="middle" fill="currentColor" stroke="none" font-size="8" font-weight="bold">CC</text>
                </svg>
                Download Captions
              </button>
              <div class="my-1.5 border-t border-gray-100"></div>
              <button @click="showPrivateConfirm = true; showOptionsMenu = false" class="w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Make it private
              </button>
              <button @click="showArchiveConfirm = true; showOptionsMenu = false" class="w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Archive
              </button>
              <button @click="showDeleteConfirm = true; showOptionsMenu = false" class="w-full px-4 py-2 text-left text-xs text-red-600 hover:bg-red-50 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete
              </button>
            </div>
          </div>

          <router-link to="/profile" class="w-8 h-8 rounded-full bg-gray-100 border border-gray-200 ml-1.5 overflow-hidden flex-shrink-0">
            <img v-if="currentUser?.avatar" :src="currentUser.avatar" alt="Profile" class="w-full h-full object-cover opacity-90 hover:opacity-100 transition-opacity">
            <div v-else class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-[10px] font-semibold">
              {{ userInitial }}
            </div>
          </router-link>
        </div>
      </nav>

      <!-- Confirmation Dialogs -->
      <!-- Archive Confirm -->
      <div v-if="showArchiveConfirm" class="fixed inset-0 z-[70] flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm" @click="showArchiveConfirm = false"></div>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm relative z-10 p-5">
          <h3 class="text-sm font-semibold text-gray-900 mb-2">Archive Video</h3>
          <p class="text-xs text-gray-500 mb-4">Are you sure you want to archive this video? It will be moved to your archive.</p>
          <div class="flex justify-end gap-2">
            <button @click="showArchiveConfirm = false" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
            <button @click="handleArchive; showArchiveConfirm = false" class="px-3 py-1.5 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-md transition-colors">Archive</button>
          </div>
        </div>
      </div>
      <!-- Private Confirm -->
      <div v-if="showPrivateConfirm" class="fixed inset-0 z-[70] flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm" @click="showPrivateConfirm = false"></div>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm relative z-10 p-5">
          <h3 class="text-sm font-semibold text-gray-900 mb-2">Make Private</h3>
          <p class="text-xs text-gray-500 mb-4">Are you sure you want to make this video private? The share link will stop working.</p>
          <div class="flex justify-end gap-2">
            <button @click="showPrivateConfirm = false" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
            <button @click="handleMakePrivate; showPrivateConfirm = false" class="px-3 py-1.5 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-md transition-colors">Make Private</button>
          </div>
        </div>
      </div>
      <!-- Delete Confirm -->
      <div v-if="showDeleteConfirm" class="fixed inset-0 z-[70] flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm relative z-10 p-5">
          <h3 class="text-sm font-semibold text-gray-900 mb-2">Delete Video</h3>
          <p class="text-xs text-gray-500 mb-4">Are you sure you want to delete this video? This cannot be undone.</p>
          <div class="flex justify-end gap-2">
            <button @click="showDeleteConfirm = false" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
            <button @click="confirmDeleteVideo; showDeleteConfirm = false" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">Delete</button>
          </div>
        </div>
      </div>

      <!-- Share Modal -->
      <div v-if="showShareModal" class="fixed inset-0 z-[60] flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm transition-opacity" @click="showShareModal = false"></div>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md relative z-10 border border-gray-100 overflow-hidden transform transition-all duration-200">
          <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-sm font-semibold text-gray-900">Share Recording</h3>
            <button @click="showShareModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <div class="p-5">
            <div class="flex gap-4 mb-6">
              <a :href="`mailto:?subject=${encodeURIComponent(video.title || 'Check out this video')}&body=${encodeURIComponent(video.shareUrl || '')}`" class="flex-1 flex flex-col items-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-orange-500 hover:bg-orange-50/50 transition-all group no-underline">
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
              <a :href="`https://twitter.com/intent/tweet?url=${encodeURIComponent(video.shareUrl || '')}&text=${encodeURIComponent(video.title || 'Check out this video')}`" target="_blank" rel="noopener noreferrer" class="flex-1 flex flex-col items-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-orange-500 hover:bg-orange-50/50 transition-all group no-underline">
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
                <input type="text" :value="video.shareUrl" class="w-full pl-9 pr-3 py-2 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-orange-500 text-gray-600" readonly>
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
              </div>
              <button @click="copyShareLink" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded-lg text-xs font-medium transition-colors">
                {{ copied ? 'Copied!' : 'Copy' }}
              </button>
            </div>

            <!-- Integration Sharing -->
            <VideoShareIntegrations v-if="video.id" :videoId="video.id" />
          </div>
        </div>
      </div>


      <!-- Main Layout -->
      <main class="flex-1 flex flex-col lg:flex-row h-full z-10 relative overflow-hidden">

        <!-- Center Stage: Video Player -->
        <div class="flex-1 flex flex-col items-center justify-center p-4 lg:p-8 relative bg-[#FAFAFA]/50 overflow-hidden overflow-y-auto">

          <div class="w-full max-w-[min(calc((100vh-280px)*16/9),100%)] flex flex-col">

            <!-- Video Title Above Container -->
            <div class="w-full mb-4">
              <div class="flex items-center gap-3 mb-1">
                <h1
                  v-if="!isEditingTitle"
                  @click="startEditingTitle"
                  class="text-2xl font-bold text-gray-900 tracking-tight leading-tight cursor-pointer hover:text-orange-600 transition-colors"
                  title="Click to edit title"
                >
                  {{ video.title }}
                </h1>
                <input
                  v-else
                  ref="titleInput"
                  v-model="editedTitle"
                  @keydown.enter="saveTitle"
                  @keydown.escape="cancelEditingTitle"
                  @blur="saveTitle"
                  class="text-2xl font-bold text-gray-900 bg-white border-2 border-orange-500 rounded-lg px-3 py-1 focus:outline-none focus:ring-2 focus:ring-orange-500 flex-1"
                  placeholder="Enter video title..."
                />
              </div>
              <div class="flex items-center gap-3 text-sm text-gray-500">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  {{ formatTimeAgo(video.createdAt) }}
                </span>
                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  {{ video.views_count || 0 }} views
                </span>
              </div>
            </div>

            <!-- Video Container - Responsive with 16:9 aspect ratio -->
            <div
              class="relative w-full bg-black rounded-xl shadow-2xl ring-1 ring-black/10 overflow-hidden z-20"
              :class="isFullscreen ? 'rounded-none !aspect-auto h-full' : ''"
              :style="{
                aspectRatio: isFullscreen ? 'auto' : '16 / 9',
                maxHeight: isFullscreen ? 'none' : 'calc(100vh - 280px)',
                maxWidth: isFullscreen ? 'none' : 'calc((100vh - 280px) * 16 / 9)',
                ...(video.thumbnail && !isFullscreen ? { backgroundImage: `url(${video.thumbnail})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {})
              }"
              ref="playerContainer"
              @mousemove="showControls"
              @mouseleave="hideControlsDelayed"
            >

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
                :key="video.id"
                class="w-full h-full object-contain"
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

              <!-- Custom Floating Controls -->
              <div class="absolute bottom-4 left-4 right-4 z-30">
                <div class="flex flex-col gap-3 px-2">
                  <!-- Captions Bar -->
                  <div v-if="captionsEnabled && activeCaptionCue" class="flex justify-center pointer-events-none">
                    <div class="caption-bar">
                      <span v-for="(word, wi) in activeCaptionCue.words" :key="wi" class="caption-word" :class="word.active ? 'caption-active' : 'caption-inactive'">{{ word.text }}</span>
                    </div>
                  </div>
                  <!-- Progress -->
                    <div
                      class="relative h-2.5 w-full group/seek cursor-pointer flex items-center"
                      @click.stop="seek"
                      @mousedown.stop="startSeeking"
                      @mousemove="updateHoverTime"
                      @mouseleave="hoverTime = null"
                      ref="progressBar"
                    >
                      <div class="absolute inset-0 bg-white/10 rounded-full"></div>
                      <div class="absolute left-0 h-full bg-white/30 rounded-full" :style="{ width: bufferedPercent + '%' }"></div>
                      <div class="absolute left-0 h-full bg-orange-500 rounded-full shadow-[0_0_10px_rgba(249,115,22,0.5)]" :style="{ width: progressPercent + '%' }"></div>
                      <div
                        class="absolute w-4 h-4 bg-white rounded-full shadow-lg scale-0 group-hover/seek:scale-100 transition-transform flex items-center justify-center"
                        :style="{ left: `calc(${progressPercent}% - 8px)` }"
                      >
                        <div class="w-1.5 h-1.5 bg-orange-600 rounded-full"></div>
                      </div>
                      <!-- Hover time tooltip -->
                      <div
                        v-if="hoverTime !== null"
                        class="absolute -top-10 px-2 py-1 bg-black text-white text-xs rounded transform -translate-x-1/2 pointer-events-none"
                        :style="{ left: hoverPercent + '%' }"
                      >
                        {{ formatTime(hoverTime) }}
                      </div>
                    </div>

                    <!-- Control Buttons -->
                    <transition name="fade">
                    <div v-show="controlsVisible" class="flex items-center justify-between text-white/90 pt-1 bg-black/40 backdrop-blur-sm rounded-lg px-3 py-2">
                      <div class="flex items-center gap-5">
                        <button @click.stop="togglePlay" class="hover:text-orange-400 hover:scale-110 transition-all">
                          <svg v-if="isPlaying" class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                            <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                          </svg>
                          <svg v-else class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                          </svg>
                        </button>
                        <span class="text-[12px] font-mono opacity-60 tracking-wider">
                          {{ formatTime(currentTime) }} <span class="opacity-50">/</span> {{ formatTime(duration) }}
                        </span>
                        <div class="w-px h-5 bg-white/10"></div>
                        <div class="flex items-center gap-4">
                          <button @click.stop="skip(-5)" class="hover:text-white opacity-70 hover:opacity-100 transition-opacity" title="Rewind 5s">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                            </svg>
                          </button>
                          <button @click.stop="skip(5)" class="hover:text-white opacity-70 hover:opacity-100 transition-opacity" title="Forward 5s">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/>
                            </svg>
                          </button>
                        </div>
                      </div>
                      <div class="flex items-center gap-4">
                        <!-- Volume -->
                        <div class="flex items-center gap-1 group/vol">
                          <button @click.stop="toggleMute" class="hover:text-white opacity-70 hover:opacity-100 transition-opacity">
                            <svg v-if="isMuted || volume === 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                              <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                              <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                            </svg>
                          </button>
                          <input
                            type="range" min="0" max="1" step="0.05" v-model="volume" @input="updateVolume"
                            class="w-0 group-hover/vol:w-20 opacity-0 group-hover/vol:opacity-100 transition-all"
                          />
                        </div>
                        <!-- Quality Selector -->
                        <div v-if="availableQualities.length > 0" class="relative" ref="qualityMenuRef">
                          <button
                            @click.stop.prevent="toggleQualityMenu"
                            class="text-[11px] font-bold bg-white/10 px-2 py-1 rounded hover:bg-orange-600 transition-colors"
                          >
                            {{ getCurrentQualityLabel() }}
                          </button>
                          <div
                            v-show="showQualityMenu"
                            class="absolute bottom-full right-0 mb-2 py-2 bg-gray-900 rounded-xl shadow-2xl border border-white/20 min-w-[100px] z-50"
                          >
                            <button
                              v-for="quality in availableQualities"
                              :key="quality.index"
                              @click.stop.prevent="setQuality(quality.index)"
                              class="w-full px-3 py-2 text-left text-xs hover:bg-white/10 transition-colors"
                              :class="currentQuality === quality.index ? 'text-orange-400' : 'text-white'"
                            >
                              {{ quality.label }}
                            </button>
                          </div>
                        </div>
                        <!-- Speed -->
                        <div class="relative" ref="speedMenuRef">
                          <button
                            @click.stop.prevent="toggleSpeedMenu"
                            class="text-[11px] font-bold bg-white/10 px-2 py-1 rounded hover:bg-orange-600 transition-colors"
                          >
                            {{ playbackSpeed }}x
                          </button>
                          <div
                            v-show="showSpeedMenu"
                            class="absolute bottom-full right-0 mb-2 py-2 bg-gray-900 rounded-xl shadow-2xl border border-white/20 min-w-[80px] z-50"
                          >
                            <button
                              v-for="speed in speedOptions"
                              :key="speed"
                              @click.stop.prevent="setPlaybackSpeed(speed)"
                              class="w-full px-3 py-2 text-left text-xs hover:bg-white/10 transition-colors"
                              :class="playbackSpeed === speed ? 'text-orange-400' : 'text-white'"
                            >
                              {{ speed }}x
                            </button>
                          </div>
                        </div>
                        <!-- Captions Toggle -->
                        <button
                          v-if="captionsUrl"
                          @click.stop="toggleCaptions"
                          class="hover:text-white hover:scale-110 transition-transform relative"
                          :class="captionsEnabled ? 'text-orange-400' : 'opacity-70 hover:opacity-100'"
                          title="Toggle captions"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <text x="12" y="15" text-anchor="middle" fill="currentColor" stroke="none" font-size="8" font-weight="bold">CC</text>
                          </svg>
                          <div v-if="captionsEnabled" class="absolute -bottom-0.5 left-1/2 -translate-x-1/2 w-3 h-0.5 bg-orange-400 rounded-full"></div>
                        </button>
                        <!-- Fullscreen -->
                        <button @click.stop="toggleFullscreen" class="hover:text-white hover:scale-110 transition-transform">
                          <svg v-if="!isFullscreen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                          </svg>
                          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/>
                          </svg>
                        </button>
                      </div>
                    </div>
                    </transition>
                  </div>
                </div>
            </div>

            <!-- Reaction Bar - Horizontal, Bottom Center of Video -->
            <div class="mt-4 flex justify-center z-30 relative">
              <div class="flex items-center gap-1 bg-white border border-gray-200/60 rounded-full shadow-sm px-2 py-1.5">
                <button
                  v-for="emoji in reactions"
                  :key="emoji.icon"
                  @click="addReaction(emoji.icon)"
                  class="w-9 h-9 rounded-full hover:bg-gray-100 flex items-center justify-center text-lg transition-transform hover:scale-110 active:scale-95"
                  :class="emoji.selected ? 'bg-orange-100' : ''"
                  :title="emoji.icon"
                >
                  {{ emoji.icon }}
                </button>
                <div class="w-px h-5 bg-gray-200 mx-0.5"></div>
                <button @click="copyShareLink" class="w-9 h-9 rounded-full hover:bg-gray-50 hover:text-orange-600 flex items-center justify-center text-gray-400 transition-colors" title="Copy Link">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <aside class="relative flex flex-col z-40 bg-white border-l border-gray-200 transition-all duration-300 ease-in-out" :class="sidebarVisible ? 'w-full lg:w-[400px]' : 'w-0 lg:w-10'">
          <!-- Sidebar Toggle Button -->
          <button
            @click="toggleSidebar"
            class="hidden lg:flex absolute -left-3 top-1/2 -translate-y-1/2 z-50 items-center justify-center w-6 h-12 bg-white border border-gray-200 rounded-l-lg shadow-sm hover:bg-gray-50 transition-all"
            :title="sidebarVisible ? 'Collapse sidebar' : 'Expand sidebar'"
          >
            <svg class="w-3.5 h-3.5 text-gray-500 transition-transform" :class="sidebarVisible ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>

          <template v-if="sidebarVisible">

          <!-- Functional Tabs -->
          <div class="grid grid-cols-4 gap-0 px-4 py-3 border-b border-gray-100 sticky top-0 bg-white z-10" :class="{ 'grid-cols-3': !jiraConnected }">
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
            <button
              v-if="jiraConnected"
              @click="activeTab = 'bugs'; loadBugTabData()"
              class="px-3 py-2 text-xs rounded-lg transition-all text-center truncate flex items-center justify-center gap-1"
              :class="activeTab === 'bugs' ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
            >
              Bugs
              <span v-if="detectedBugs.length" class="text-[10px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full font-medium">{{ detectedBugs.length }}</span>
            </button>
          </div>

          <!-- Content Area -->
          <div class="flex-1 overflow-y-auto relative">

            <!-- TAB: COMMENTS -->
            <div v-show="activeTab === 'comments'" class="flex flex-col min-h-full">
              <div v-if="comments.length === 0" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <svg class="w-16 h-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="text-base font-semibold text-gray-900">No comments yet</p>
                <p class="text-sm text-gray-500 mt-1">Be the first to share your thoughts!</p>
              </div>

              <div v-else>
                <div
                  v-for="comment in comments"
                  :key="comment.id"
                  class="group p-5 hover:bg-gray-50/80 transition-colors border-b border-gray-50 flex gap-3 items-start relative"
                >
                  <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-orange-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                  <div class="relative">
                    <img v-if="comment.avatar" :src="comment.avatar" class="w-8 h-8 rounded-full object-cover border border-gray-100 shadow-sm">
                    <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-100 to-amber-100 flex items-center justify-center border border-orange-200/50 text-orange-600 text-xs font-bold shadow-sm">
                      {{ comment.author.charAt(0) }}
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                      <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-gray-900">{{ comment.author }}</span>
                        <button
                          v-if="comment.timestamp_seconds != null"
                          @click="seekToTime(comment.timestamp_seconds)"
                          class="text-[10px] font-mono bg-orange-50 text-orange-600 px-1.5 py-0.5 rounded hover:bg-orange-100 transition-colors cursor-pointer"
                        >
                          {{ formatTime(comment.timestamp_seconds) }}
                        </button>
                      </div>
                      <span class="text-[10px] text-gray-400">{{ comment.time }}</span>
                    </div>
                    <p class="text-[13px] text-gray-600 leading-relaxed">{{ comment.text }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- TAB: TRANSCRIPT -->
            <div v-show="activeTab === 'transcript'" class="flex flex-col min-h-full">
              <!-- Loading state -->
              <div v-if="transcriptionStatus === 'processing'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center mb-4 animate-pulse">
                  <svg class="w-8 h-8 text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">Generating Transcript...</p>
                <p class="text-sm text-gray-500 mt-1">{{ transcriptionProgress }}% complete</p>
                <div class="w-48 h-1.5 bg-gray-200 rounded-full mt-3 overflow-hidden">
                  <div class="h-full bg-orange-500 rounded-full transition-all duration-300" :style="{ width: transcriptionProgress + '%' }"></div>
                </div>
              </div>

              <!-- Empty state - not started -->
              <div v-else-if="transcriptionStatus === 'pending'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">No Transcript Yet</p>
                <p class="text-sm text-gray-500 mt-1 mb-4">Generate an AI transcript of your video</p>
                <button
                  @click="requestTranscription"
                  :disabled="isRequestingTranscription || !video.conversion_status || video.conversion_status !== 'completed'"
                  class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                  </svg>
                  {{ isRequestingTranscription ? 'Starting...' : 'Generate Transcript' }}
                </button>
              </div>

              <!-- Skipped - no audio -->
              <div v-else-if="transcriptionStatus === 'skipped'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">No Audio Detected</p>
                <p class="text-sm text-gray-500 mt-1">This video has no audio track, so transcription is not available</p>
              </div>

              <!-- Error state -->
              <div v-else-if="transcriptionStatus === 'failed'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">Transcription Failed</p>
                <p class="text-sm text-gray-500 mt-1 mb-4">{{ transcriptionError || 'Something went wrong' }}</p>
                <button
                  @click="requestTranscription"
                  :disabled="isRequestingTranscription"
                  class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors disabled:bg-gray-300"
                >
                  Try Again
                </button>
              </div>

              <!-- Transcript content with timestamps -->
              <div v-else-if="transcriptionSegments && transcriptionSegments.length > 0" class="flex flex-col h-full">
                <!-- Enhanced header with search & actions -->
                <div class="sticky top-0 bg-white/95 backdrop-blur-sm z-10 border-b border-gray-100">
                  <!-- Search bar -->
                  <div class="px-4 pt-3 pb-2">
                    <div class="relative">
                      <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                      </svg>
                      <input
                        v-model="transcriptSearch"
                        type="text"
                        placeholder="Search transcript..."
                        class="w-full pl-10 pr-8 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 focus:ring-1 focus:ring-orange-400 transition-colors"
                      />
                      <button
                        v-if="transcriptSearch"
                        @click="transcriptSearch = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                  <!-- Stats & Actions row -->
                  <div class="flex items-center justify-between px-4 py-2">
                    <div class="flex items-center gap-3 text-[11px] text-gray-400">
                      <span>{{ transcriptWordCount }} words</span>
                      <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                      <span>{{ transcriptReadTime }} min read</span>
                      <span v-if="transcriptSearch && filteredSegments.length !== transcriptionSegments.length" class="text-orange-500 font-medium">
                        {{ filteredSegments.length }} matches
                      </span>
                    </div>
                    <div class="flex items-center gap-1">
                      <!-- Export dropdown -->
                      <div class="relative export-menu-container">
                        <button
                          @click="showExportMenu = !showExportMenu"
                          class="flex items-center gap-1 px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
                        >
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                          </svg>
                          Export
                        </button>
                        <div v-if="showExportMenu" class="absolute right-0 mt-1 w-36 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-20">
                          <button @click="exportTranscript('txt')" class="w-full px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Plain Text (.txt)
                          </button>
                          <button @click="exportTranscript('srt')" class="w-full px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                            </svg>
                            Subtitles (.srt)
                          </button>
                        </div>
                      </div>
                      <!-- Copy all -->
                      <button
                        @click="copyTranscript"
                        class="flex items-center gap-1 px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-md transition-colors"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        {{ copiedTranscript ? 'Copied!' : 'Copy' }}
                      </button>
                    </div>
                  </div>
                </div>
                <!-- Segments list -->
                <div class="flex-1 overflow-y-auto" ref="transcriptContainer">
                  <div
                    v-for="(segment, index) in filteredSegments"
                    :key="segment.originalIndex"
                    :ref="el => { if (el) segmentRefs[segment.originalIndex] = el }"
                    @click="seekToTime(segment.start)"
                    class="group relative px-4 py-3 cursor-pointer transition-all duration-200 border-l-2"
                    :class="activeSegmentIndex === segment.originalIndex
                      ? 'bg-orange-50 border-l-orange-500'
                      : 'hover:bg-gray-50 border-l-transparent'"
                  >
                    <div class="flex gap-3">
                      <span
                        class="text-[11px] font-medium tabular-nums tracking-wide flex-shrink-0 transition-colors min-w-[36px] pt-0.5"
                        :class="activeSegmentIndex === segment.originalIndex ? 'text-orange-500' : 'text-gray-400'"
                      >
                        {{ formatTime(segment.start) }}
                      </span>
                      <p
                        class="text-[13px] leading-relaxed transition-colors flex-1"
                        :class="activeSegmentIndex === segment.originalIndex ? 'text-gray-900' : 'text-gray-600'"
                        v-html="highlightSearch(segment.text)"
                      ></p>
                    </div>
                    <!-- Hover actions -->
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                      <button
                        @click.stop="copySegment(segment)"
                        class="p-1.5 rounded-md bg-white shadow-sm border border-gray-200 text-gray-400 hover:text-orange-500 hover:border-orange-200 transition-colors"
                        title="Copy this segment"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                  <!-- No results -->
                  <div v-if="transcriptSearch && filteredSegments.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-sm text-gray-500">No matches found for "{{ transcriptSearch }}"</p>
                  </div>
                </div>
              </div>

              <!-- Fallback: show full transcript if no segments -->
              <div v-else-if="transcription" class="p-5">
                <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-medium text-gray-500">Full transcript</span>
                  <button
                    @click="copyTranscript"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    {{ copiedTranscript ? 'Copied!' : 'Copy' }}
                  </button>
                </div>
                <p class="text-[13px] text-gray-700 leading-relaxed whitespace-pre-wrap">{{ transcription }}</p>
              </div>
            </div>

            <!-- TAB: SUMMARY -->
            <div v-show="activeTab === 'summary'" class="flex flex-col min-h-full">
              <!-- Loading state -->
              <div v-if="summaryStatus === 'processing'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-orange-100 to-amber-100 flex items-center justify-center mb-4 animate-pulse">
                  <svg class="w-8 h-8 text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">Generating Summary...</p>
                <p class="text-sm text-gray-500 mt-1">AI is analyzing your video</p>
              </div>

              <!-- Skipped - no audio -->
              <div v-else-if="summaryStatus === 'skipped'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">No Audio Detected</p>
                <p class="text-sm text-gray-500 mt-1">Summary is not available for videos without audio</p>
              </div>

              <!-- Empty state - waiting for transcript -->
              <div v-else-if="summaryStatus === 'pending' && transcriptionStatus !== 'completed'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">Summary Requires Transcript</p>
                <p class="text-sm text-gray-500 mt-1">Generate a transcript first to get an AI summary</p>
              </div>

              <!-- Error state -->
              <div v-else-if="summaryStatus === 'failed'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">Summary Failed</p>
                <p class="text-sm text-gray-500 mt-1">{{ summaryError || 'Something went wrong' }}</p>
              </div>

              <!-- Summary content -->
              <div v-else-if="summary" class="p-5">
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-100 to-amber-100 flex items-center justify-center">
                      <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                      </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">AI Summary</span>
                  </div>
                  <button
                    @click="copySummary"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    {{ copiedSummary ? 'Copied!' : 'Copy' }}
                  </button>
                </div>
                <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed" v-html="formattedSummary"></div>
              </div>

              <!-- Pending state - transcript complete but no summary yet -->
              <div v-else-if="summaryStatus === 'pending' && transcriptionStatus === 'completed'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">Summary Available Soon</p>
                <p class="text-sm text-gray-500 mt-1">AI summary is being generated...</p>
              </div>
            </div>

            <!-- TAB: BUGS -->
            <div v-show="activeTab === 'bugs'" class="flex flex-col min-h-full">
              <!-- Processing state -->
              <div v-if="bugDetectionStatus === 'processing'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4 animate-pulse">
                  <svg class="w-8 h-8 text-red-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">Detecting Bugs...</p>
                <p class="text-sm text-gray-500 mt-1">AI is analyzing your transcript for bugs</p>
              </div>

              <!-- Skipped - no audio -->
              <div v-else-if="bugDetectionStatus === 'skipped'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">No Audio Detected</p>
                <p class="text-sm text-gray-500 mt-1">Bug detection is not available for videos without audio</p>
              </div>

              <!-- Pending state -->
              <div v-else-if="bugDetectionStatus === 'pending'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">Bug Detection Pending</p>
                <p class="text-sm text-gray-500 mt-1">Bug detection will start after transcription and summary complete</p>
              </div>

              <!-- Failed state -->
              <div v-else-if="bugDetectionStatus === 'failed'" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">Bug Detection Failed</p>
                <p class="text-sm text-gray-500 mt-1">{{ bugDetectionError || 'Something went wrong' }}</p>
              </div>

              <!-- No bugs found -->
              <div v-else-if="bugDetectionStatus === 'completed' && detectedBugs.length === 0" class="flex flex-col items-center justify-center h-64 text-center px-5">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <p class="text-base font-semibold text-gray-900">No Bugs Detected</p>
                <p class="text-sm text-gray-500 mt-1">No issues were found in the video transcript</p>
              </div>

              <!-- Bugs found -->
              <div v-else-if="detectedBugs.length > 0" class="p-4 space-y-3">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-xs text-gray-500">
                    Based on the video transcript, <span class="font-semibold text-gray-700">{{ detectedBugs.length }}</span> potential bug(s) detected
                  </p>
                </div>

                <!-- Jira project selector -->
                <div v-if="jiraProjects.length > 0" class="mb-3">
                  <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1 block">Jira Project</label>
                  <select
                    v-model="selectedBugProject"
                    class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-orange-500"
                  >
                    <option value="">Select a project...</option>
                    <option v-for="project in jiraProjects" :key="project.id" :value="project.id">
                      {{ project.name }}
                    </option>
                  </select>
                </div>

                <!-- Bug cards -->
                <div
                  v-for="bug in detectedBugs"
                  :key="bug.id"
                  class="border border-gray-200 rounded-lg overflow-hidden transition-all hover:border-gray-300"
                >
                  <!-- Bug card header -->
                  <button
                    @click="expandedBugId = expandedBugId === bug.id ? null : bug.id"
                    class="w-full px-4 py-3 flex items-center gap-3 text-left hover:bg-gray-50 transition-colors"
                  >
                    <!-- Severity dot -->
                    <span
                      class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                      :class="{
                        'bg-red-500': bug.severity === 'critical',
                        'bg-orange-500': bug.severity === 'high',
                        'bg-yellow-500': bug.severity === 'medium',
                        'bg-blue-500': bug.severity === 'low'
                      }"
                    ></span>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 truncate">{{ bug.title }}</p>
                      <span
                        class="text-[10px] font-medium uppercase tracking-wider px-1.5 py-0.5 rounded"
                        :class="{
                          'bg-red-100 text-red-700': bug.severity === 'critical',
                          'bg-orange-100 text-orange-700': bug.severity === 'high',
                          'bg-yellow-100 text-yellow-700': bug.severity === 'medium',
                          'bg-blue-100 text-blue-700': bug.severity === 'low'
                        }"
                      >
                        {{ bug.severity }}
                      </span>
                    </div>
                    <svg
                      class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0"
                      :class="{ 'rotate-180': expandedBugId === bug.id }"
                      fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </button>

                  <!-- Bug card details (expanded) -->
                  <div v-if="expandedBugId === bug.id" class="px-4 pb-4 border-t border-gray-100 space-y-3">
                    <!-- Description -->
                    <div v-if="bug.description" class="pt-3">
                      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Description</p>
                      <p class="text-sm text-gray-700 leading-relaxed">{{ bug.description }}</p>
                    </div>

                    <!-- Steps to reproduce -->
                    <div v-if="bug.steps_to_reproduce && bug.steps_to_reproduce.length > 0">
                      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Steps to Reproduce</p>
                      <ol class="list-decimal list-inside text-sm text-gray-700 space-y-1">
                        <li v-for="(step, i) in bug.steps_to_reproduce" :key="i">{{ step }}</li>
                      </ol>
                    </div>

                    <!-- Mentioned at timestamp -->
                    <div v-if="bug.mentioned_at_seconds != null">
                      <button
                        @click="seekToTime(bug.mentioned_at_seconds)"
                        class="text-xs font-mono bg-orange-50 text-orange-600 px-2 py-1 rounded hover:bg-orange-100 transition-colors"
                      >
                        Mentioned at {{ formatTime(bug.mentioned_at_seconds) }}
                      </button>
                    </div>

                    <!-- Create in Jira / View in Jira -->
                    <div class="pt-2">
                      <a
                        v-if="bug.jira_url"
                        :href="bug.jira_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                      >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M11.571 11.513H0a5.218 5.218 0 0 0 5.232 5.215h2.13v2.057A5.215 5.215 0 0 0 12.575 24V12.518a1.005 1.005 0 0 0-1.005-1.005zm5.723-5.756H5.736a5.215 5.215 0 0 0 5.215 5.214h2.129v2.058a5.218 5.218 0 0 0 5.215 5.214V6.758a1.001 1.001 0 0 0-1.001-1.001zM23.013 0H11.455a5.215 5.215 0 0 0 5.215 5.215h2.129v2.057A5.215 5.215 0 0 0 24.013 12.487V1.005A1.005 1.005 0 0 0 23.013 0z"/>
                        </svg>
                        {{ bug.jira_key }} - View in Jira
                      </a>
                      <span
                        v-else-if="bug.jira_queued"
                        class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-green-700 bg-green-50 rounded-lg"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Creating in Jira...
                      </span>
                      <button
                        v-else
                        @click="createBugInJira(bug)"
                        :disabled="creatingBugId === bug.id || !selectedBugProject"
                        class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                      >
                        <svg v-if="creatingBugId === bug.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M11.571 11.513H0a5.218 5.218 0 0 0 5.232 5.215h2.13v2.057A5.215 5.215 0 0 0 12.575 24V12.518a1.005 1.005 0 0 0-1.005-1.005zm5.723-5.756H5.736a5.215 5.215 0 0 0 5.215 5.214h2.129v2.058a5.218 5.218 0 0 0 5.215 5.214V6.758a1.001 1.001 0 0 0-1.001-1.001zM23.013 0H11.455a5.215 5.215 0 0 0 5.215 5.215h2.129v2.057A5.215 5.215 0 0 0 24.013 12.487V1.005A1.005 1.005 0 0 0 23.013 0z"/>
                        </svg>
                        Create Bug in Jira
                      </button>
                      <p v-if="!selectedBugProject && !bug.jira_url && !bug.jira_queued" class="text-[11px] text-gray-400 mt-1">Select a Jira project above first</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Comment Input -->
          <div v-show="activeTab === 'comments'" class="p-4 bg-white border-t border-gray-100 z-20">
            <div class="relative shadow-sm ring-1 ring-gray-200 rounded-xl bg-white focus-within:ring-2 focus-within:ring-orange-500/20 focus-within:border-orange-500 transition-all">
              <textarea
                v-model="newComment"
                rows="1"
                placeholder="Add a comment..."
                @keydown.enter.exact.prevent="addComment"
                class="w-full bg-transparent border-none text-[13px] text-gray-900 placeholder:text-gray-400 focus:ring-0 p-3 resize-none min-h-[44px]"
              ></textarea>

              <div class="flex items-center justify-between px-2 pb-2">
                <span v-if="currentTime > 0" class="text-[11px] text-gray-400 font-mono bg-gray-50 px-1.5 py-0.5 rounded">
                  @ {{ formatTime(currentTime) }}
                </span>
                <span v-else></span>
                <button
                  @click="addComment"
                  :disabled="!newComment.trim() || isSavingComment"
                  class="bg-orange-600 text-white p-1.5 rounded-lg hover:bg-orange-700 transition-colors shadow-sm shadow-orange-200 disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          </template>
        </aside>

      </main>
    </template>

    <!-- Delete Video Modal (legacy - kept for compatibility) -->
    <SBDeleteModal
      v-model="showDeleteModal"
      title="Delete Video"
      message="Are you sure you want to delete this video? This cannot be undone."
      :loading="isDeleting"
      @confirm="confirmDeleteVideo"
      @cancel="showDeleteModal = false"
    />

    <!-- Toast -->
    <transition name="toast">
      <div
        v-if="toast"
        class="fixed bottom-8 left-1/2 -translate-x-1/2 px-5 py-3 bg-white text-gray-900 rounded-xl text-sm font-medium shadow-2xl border border-gray-200 flex items-center gap-2 z-[100]"
      >
        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ toast }}
      </div>
    </transition>

  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/stores/auth'
import { useBranding } from '@/composables/useBranding'
import videoService from '@/services/videoService'
import integrationService from '@/services/integrationService'
import SBDeleteModal from '@/components/Global/SBDeleteModal.vue'
import VideoShareIntegrations from '@/components/VideoShareIntegrations.vue'
import Hls from 'hls.js'
import { marked } from 'marked'
import { sanitizeHtml } from '@/utils/sanitize'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

export default {
  name: 'VideoPlayerView',
  components: {
    SBDeleteModal,
    VideoShareIntegrations
  },
  setup() {
    const route = useRoute()
    const auth = useAuth()
    const branding = useBranding()
    const isAuthenticated = computed(() => auth.isAuthenticated.value)
    const currentUser = computed(() => auth.user.value)
    const userInitial = computed(() => (currentUser.value?.name || 'U').charAt(0).toUpperCase())

    const video = ref({})
    const loading = ref(true)
    const error = ref(null)

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
    const controlsVisible = ref(false)
    const hoverTime = ref(null)
    const hoverPercent = ref(0)
    const showSpeedMenu = ref(false)
    const speedOptions = [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]
    const showBigPlayButton = ref(true)
    const showPrePlaySpeedMenu = ref(false)
    const copied = ref(false)
    const copiedEmbed = ref(false)
    const toast = ref(null)

    const newComment = ref('')
    const comments = ref([])
    const isLoadingComments = ref(false)
    const isSavingComment = ref(false)

    const showDeleteModal = ref(false)
    const isDeleting = ref(false)

    const isEditingTitle = ref(false)
    const editedTitle = ref('')
    const isSavingTitle = ref(false)
    const titleInput = ref(null)

    const showShareModal = ref(false)
    const showShareDropdown = ref(false)
    const showOptionsMenu = ref(false)
    const showArchiveConfirm = ref(false)
    const showDeleteConfirm = ref(false)
    const showPrivateConfirm = ref(false)
    const shareDropdownRef = ref(null)
    const optionsMenuRef = ref(null)
    const activeTab = ref('transcript')
    const sidebarVisible = ref(localStorage.getItem('sidebar_visible') !== 'false')

    const toggleSidebar = () => {
      sidebarVisible.value = !sidebarVisible.value
      localStorage.setItem('sidebar_visible', sidebarVisible.value)
    }

    // Transcription state
    const transcription = ref(null)
    const transcriptionSegments = ref([])
    const transcriptionStatus = ref('pending')
    const transcriptionProgress = ref(0)
    const transcriptionError = ref(null)
    const isRequestingTranscription = ref(false)

    // Summary state
    const summary = ref(null)
    const summaryStatus = ref('pending')
    const summaryError = ref(null)

    // Bug detection state
    const detectedBugs = ref([])
    const bugDetectionStatus = ref('pending')
    const bugDetectionError = ref(null)
    const expandedBugId = ref(null)
    const creatingBugId = ref(null)
    const jiraConnected = ref(false)
    const jiraProjects = ref([])
    const selectedBugProject = ref('')
    const bugProjectsLoaded = ref(false)

    // Copy states
    const copiedTranscript = ref(false)
    const copiedSummary = ref(false)

    // Transcript sync state
    const transcriptContainer = ref(null)
    const segmentRefs = ref({})
    const autoScrollEnabled = ref(true)

    // Captions state
    const captionsEnabled = ref(true)
    const captionsUrl = computed(() => {
      if (transcriptionStatus.value !== 'completed') return null
      if (!transcriptionSegments.value || transcriptionSegments.value.length === 0) return null
      return true // just indicates captions are available
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

    // Enhanced transcript features
    const transcriptSearch = ref('')
    const showExportMenu = ref(false)

    // Close export menu on click outside
    const closeExportMenu = (e) => {
      if (showExportMenu.value && !e.target.closest('.export-menu-container')) {
        showExportMenu.value = false
      }
    }

    let transcriptionPollInterval = null

    const reactions = ref([
      { icon: '👍', count: 0, selected: false },
      { icon: '❤️', count: 0, selected: false },
      { icon: '😂', count: 0, selected: false },
    ])

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
    const bunnyPlaybackData = ref(null)

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

    // captionsUrl watch removed — custom caption overlay used instead

    // Filtered segments based on search
    const filteredSegments = computed(() => {
      if (!transcriptionSegments.value) return []
      const segments = transcriptionSegments.value.map((seg, idx) => ({ ...seg, originalIndex: idx }))
      if (!transcriptSearch.value.trim()) return segments
      const search = transcriptSearch.value.toLowerCase()
      return segments.filter(seg => seg.text.toLowerCase().includes(search))
    })

    // Word count
    const transcriptWordCount = computed(() => {
      if (!transcriptionSegments.value) return 0
      const allText = transcriptionSegments.value.map(s => s.text).join(' ')
      return allText.split(/\s+/).filter(w => w.length > 0).length
    })

    // Estimated read time (avg 200 words per minute)
    const transcriptReadTime = computed(() => {
      return Math.max(1, Math.ceil(transcriptWordCount.value / 200))
    })

    // Highlight search matches in text
    const highlightSearch = (text) => {
      if (!transcriptSearch.value.trim()) return sanitizeHtml(text)
      const escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      const search = transcriptSearch.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
      const regex = new RegExp(`(${search})`, 'gi')
      return escaped.replace(regex, '<mark class="bg-yellow-200 text-yellow-900 rounded px-0.5">$1</mark>')
    }

    // Copy individual segment
    const copySegment = async (segment) => {
      try {
        await navigator.clipboard.writeText(`[${formatTime(segment.start)}] ${segment.text}`)
        showToast('Segment copied!')
      } catch (err) {
        console.error('Failed to copy segment:', err)
      }
    }

    // Export transcript
    const exportTranscript = (format) => {
      showExportMenu.value = false
      if (!transcriptionSegments.value) return

      let content = ''
      const filename = `${video.value?.title || 'transcript'}`

      if (format === 'txt') {
        content = transcriptionSegments.value
          .map(seg => `[${formatTime(seg.start)}] ${seg.text}`)
          .join('\n\n')
        downloadFile(content, `${filename}.txt`, 'text/plain')
      } else if (format === 'srt') {
        content = transcriptionSegments.value
          .map((seg, idx) => {
            const startSrt = formatSrtTime(seg.start)
            const endSrt = formatSrtTime(seg.end || seg.start + 5)
            return `${idx + 1}\n${startSrt} --> ${endSrt}\n${seg.text}\n`
          })
          .join('\n')
        downloadFile(content, `${filename}.srt`, 'text/plain')
      }
    }

    const formatSrtTime = (seconds) => {
      const hrs = Math.floor(seconds / 3600)
      const mins = Math.floor((seconds % 3600) / 60)
      const secs = Math.floor(seconds % 60)
      const ms = Math.floor((seconds % 1) * 1000)
      return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')},${String(ms).padStart(3, '0')}`
    }

    const downloadFile = (content, filename, mimeType) => {
      const blob = new Blob([content], { type: mimeType })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
      showToast(`Downloaded ${filename}`)
    }

    const fetchVideo = async () => {
      loading.value = true
      error.value = null

      try {
        const fetchedVideo = await videoService.getVideo(route.params.id)

        video.value = {
          id: fetchedVideo.id,
          title: fetchedVideo.title,
          description: fetchedVideo.description,
          duration: fetchedVideo.duration,
          url: fetchedVideo.url,
          thumbnail: fetchedVideo.thumbnail,
          hls_url: fetchedVideo.hls_url,
          is_hls_ready: fetchedVideo.is_hls_ready,
          shareUrl: fetchedVideo.share_url,
          views_count: fetchedVideo.views_count,
          createdAt: new Date(fetchedVideo.created_at),
          conversion_status: fetchedVideo.conversion_status,
          storage_type: fetchedVideo.storage_type,
          bunny_status: fetchedVideo.bunny_status,
          // Zoom fields
          zoom_enabled: fetchedVideo.zoom_enabled,
          zoom_status: fetchedVideo.zoom_status,
          zoom_progress: fetchedVideo.zoom_progress,
          is_zoom_ready: fetchedVideo.is_zoom_ready,
          zoom_event_count: fetchedVideo.zoom_event_count,
        }

        // Bunny playback disabled - encoding costs too high, using local storage only
        // if (fetchedVideo.storage_type === 'bunny') {
        //   isBunnyVideo.value = true
        //   bunnyStatus.value = fetchedVideo.bunny_status
        //   if (['ready', 'transcoding'].includes(fetchedVideo.bunny_status)) {
        //     try {
        //       const bunnyData = await videoService.getBunnyPlayback(fetchedVideo.id)
        //       bunnyPlaybackData.value = bunnyData
        //       if (bunnyData.playback) {
        //         video.value.hls_url = bunnyData.playback.hlsUrl
        //         video.value.is_hls_ready = true
        //       }
        //       if (bunnyData.video) {
        //         bunnyStatus.value = bunnyData.video.status
        //         bunnyEncodeProgress.value = bunnyData.video.encode_progress || 0
        //         bunnyAvailableResolutions.value = bunnyData.video.available_resolutions || []
        //         if (bunnyData.video.duration && bunnyData.video.duration > 0) {
        //           video.value.duration = bunnyData.video.duration
        //           duration.value = bunnyData.video.duration
        //         }
        //       }
        //     } catch (bunnyErr) {
        //       console.warn('Failed to fetch Bunny playback, falling back to local file:', bunnyErr)
        //     }
        //   }
        // }

        setTimeout(() => initHls(), 100)

        if (fetchedVideo.duration && fetchedVideo.duration > 0) {
          duration.value = fetchedVideo.duration
        }

      } catch (err) {
        console.error('Failed to load video:', err)
        error.value = 'Failed to load video. Please try again.'
      } finally {
        loading.value = false
      }
    }

    const initHls = () => {
      const videoElement = videoRef.value
      if (!videoElement) return

      const hlsUrl = video.value.hls_url
      const isHlsReady = video.value.is_hls_ready

      if (!isHlsReady || !hlsUrl) {
        videoElement.src = video.value.url
        return
      }

      if (Hls.isSupported()) {
        if (hlsInstance.value) {
          hlsInstance.value.destroy()
        }

        const hls = new Hls({
          enableWorker: true,
          lowLatencyMode: false,
          backBufferLength: 90,
          xhrSetup: function (xhr, url) {
            xhr.onreadystatechange = function () {
              if (xhr.readyState === 4) {
                if (xhr.status === 404 || xhr.status === 403) {
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
          availableQualities.value = data.levels.map((level, index) => ({
            index,
            height: level.height,
            width: level.width,
            bitrate: level.bitrate,
            label: level.height >= 2160 ? '4K' : `${level.height}p`
          }))

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
        videoElement.src = hlsUrl
        videoElement.onerror = () => {
          // Native HLS failed, falling back to MP4
          videoElement.src = video.value.url
        }
      } else {
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
      const video = videoRef.value
      const bar = progressBar.value
      if (!video || !bar) return

      const videoDuration = isFinite(video.duration) && video.duration > 0 ? video.duration : duration.value
      if (!videoDuration || !isFinite(videoDuration) || videoDuration <= 0) return

      const rect = bar.getBoundingClientRect()
      const percent = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width))
      const newTime = percent * videoDuration
      video.currentTime = newTime
      currentTime.value = newTime
    }

    const seek = (e) => {
      seekToPosition(e.clientX)
    }

    const startSeeking = (e) => {
      e.preventDefault()
      const video = videoRef.value
      if (!video) return

      const wasPlaying = !video.paused
      if (wasPlaying) video.pause()

      const onMouseMove = (moveEvent) => {
        seekToPosition(moveEvent.clientX)
      }

      const onMouseUp = () => {
        document.removeEventListener('mousemove', onMouseMove)
        document.removeEventListener('mouseup', onMouseUp)
        if (wasPlaying && video) video.play()
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
      const video = videoRef.value
      if (!video) return

      const videoDuration = isFinite(video.duration) && video.duration > 0 ? video.duration : duration.value
      if (!videoDuration || !isFinite(videoDuration) || videoDuration <= 0) return

      const currentVideoTime = video.currentTime || 0
      const newTime = Math.max(0, Math.min(videoDuration, currentVideoTime + seconds))
      video.currentTime = newTime
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

    const showToast = (msg) => {
      toast.value = msg
      if (toastTimeout) clearTimeout(toastTimeout)
      toastTimeout = setTimeout(() => { toast.value = null }, 2000)
    }

    const formatTime = (seconds) => {
      if (!seconds || isNaN(seconds) || !isFinite(seconds)) return '0:00'
      const mins = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${mins}:${secs.toString().padStart(2, '0')}`
    }

    const formatTimeAgo = (date) => {
      if (!date) return ''
      const seconds = Math.floor((new Date() - date) / 1000)
      if (seconds < 60) return 'Just now'
      if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`
      if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
      if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`
      return date.toLocaleDateString()
    }

    const copyShareLink = async () => {
      if (video.value.shareUrl) {
        try {
          await navigator.clipboard.writeText(video.value.shareUrl)
          copied.value = true
          showToast('Share link copied!')
          setTimeout(() => { copied.value = false }, 3000)
        } catch (err) {}
      }
    }

    const copyEmbedCode = async () => {
      if (video.value.shareUrl) {
        try {
          const embedUrl = video.value.shareUrl.replace('/share/video/', '/embed/video/')
          const embedCode = `<iframe src="${embedUrl}" width="640" height="360" frameborder="0" allowfullscreen></iframe>`
          await navigator.clipboard.writeText(embedCode)
          copiedEmbed.value = true
          showToast('Embed code copied!')
          setTimeout(() => { copiedEmbed.value = false }, 3000)
        } catch (err) {
          console.error('Failed to copy embed code:', err)
        }
      }
    }

    const downloadVideo = async () => {
      if (!video.value.id) return

      try {
        showToast('Preparing download...')

        const result = await videoService.requestDownloadMp4(video.value.id)

        if (result.mode === 'async') {
          showToast('Video is being prepared for download. This may take a moment.')
          return
        }

        // Sync mode - we have the blob directly
        const blobUrl = window.URL.createObjectURL(result.blob)
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

    const deleteVideo = () => {
      showDeleteModal.value = true
    }

    const confirmDeleteVideo = async () => {
      isDeleting.value = true
      try {
        await videoService.deleteVideo(video.value.id)
        showDeleteModal.value = false
        window.location.href = import.meta.env.BASE_URL + 'videos'
      } catch (err) {
        showToast('Failed to delete')
      } finally {
        isDeleting.value = false
      }
    }

    const goBack = () => {
      window.location.href = import.meta.env.BASE_URL + 'videos'
    }

    const startEditingTitle = () => {
      editedTitle.value = video.value.title
      isEditingTitle.value = true
      setTimeout(() => {
        if (titleInput.value) {
          titleInput.value.focus()
          titleInput.value.select()
        }
      }, 50)
    }

    const saveTitle = async () => {
      if (isSavingTitle.value) return

      const newTitle = editedTitle.value.trim()
      if (!newTitle) {
        cancelEditingTitle()
        return
      }

      if (newTitle === video.value.title) {
        isEditingTitle.value = false
        return
      }

      isSavingTitle.value = true
      try {
        await videoService.updateVideo(video.value.id, { title: newTitle })
        video.value.title = newTitle
        isEditingTitle.value = false
        showToast('Title updated!')
      } catch (err) {
        console.error('Failed to update title:', err)
        showToast('Failed to update title')
      } finally {
        isSavingTitle.value = false
      }
    }

    const cancelEditingTitle = () => {
      isEditingTitle.value = false
      editedTitle.value = video.value.title
    }

    const addReaction = (icon) => {
      const reaction = reactions.value.find(r => r.icon === icon)
      if (reaction) {
        reaction.selected = !reaction.selected
        reaction.count += reaction.selected ? 1 : -1
      }
    }

    // Action handlers for options menu
    const handleDownload = async () => {
      await downloadVideo()
    }

    const handleDuplicate = async () => {
      try {
        showToast('Duplicating video...')
        const result = await videoService.updateVideo(video.value.id, { duplicate: true })
        if (result) {
          showToast('Video duplicated!')
        }
      } catch (err) {
        console.error('Failed to duplicate:', err)
        showToast('Failed to duplicate video')
      }
    }

    const handleDownloadCaptions = () => {
      if (transcriptionSegments.value && transcriptionSegments.value.length > 0) {
        exportTranscript('srt')
      } else {
        showToast('No captions available')
      }
    }

    const handleMakePrivate = async () => {
      try {
        await videoService.updateVideo(video.value.id, { is_public: false })
        video.value.is_public = false
        showToast('Video is now private')
      } catch (err) {
        console.error('Failed to make private:', err)
        showToast('Failed to update privacy')
      }
    }

    const handleArchive = async () => {
      try {
        await videoService.updateVideo(video.value.id, { archived: true })
        showToast('Video archived')
        setTimeout(() => {
          window.location.href = import.meta.env.BASE_URL + 'videos'
        }, 500)
      } catch (err) {
        console.error('Failed to archive:', err)
        showToast('Failed to archive video')
      }
    }

    const loadComments = async () => {
      if (!video.value.id) return
      isLoadingComments.value = true
      try {
        const fetchedComments = await videoService.getComments(video.value.id)
        comments.value = fetchedComments.map(comment => ({
          id: comment.id,
          author: comment.author_name,
          avatar: comment.author_avatar,
          text: comment.content,
          time: formatTimeAgo(new Date(comment.created_at)),
          timestamp_seconds: comment.timestamp_seconds
        }))
      } catch (err) {
        console.error('Failed to load comments:', err)
      } finally {
        isLoadingComments.value = false
      }
    }

    const addComment = async () => {
      if (!newComment.value.trim() || isSavingComment.value) return

      isSavingComment.value = true
      const commentText = newComment.value.trim()
      const timestampSeconds = videoRef.value ? Math.floor(videoRef.value.currentTime) : null
      newComment.value = ''

      try {
        const savedComment = await videoService.addComment(video.value.id, commentText, 'You', timestampSeconds)

        comments.value.unshift({
          id: savedComment.id,
          author: savedComment.author_name,
          avatar: savedComment.author_avatar,
          text: savedComment.content,
          time: 'Just now',
          timestamp_seconds: savedComment.timestamp_seconds
        })
      } catch (err) {
        console.error('Failed to save comment:', err)
        newComment.value = commentText
        showToast('Failed to save comment')
      } finally {
        isSavingComment.value = false
      }
    }

    // Transcription methods
    const loadTranscriptionData = async () => {
      if (!video.value.id) return

      try {
        const data = await videoService.getTranscription(video.value.id)
        if (data) {
          transcription.value = data.transcription?.transcription || null
          transcriptionSegments.value = data.transcription?.segments || []
          summary.value = data.summary?.summary || null

          // Load bug detection data
          if (data.bugs) {
            detectedBugs.value = data.bugs.bugs || []
          }

          // Update status from response
          if (data.status) {
            transcriptionStatus.value = data.status.transcription_status || 'pending'
            transcriptionProgress.value = data.status.transcription_progress || 0
            transcriptionError.value = data.status.transcription_error || null
            summaryStatus.value = data.status.summary_status || 'pending'
            summaryError.value = data.status.summary_error || null
            bugDetectionStatus.value = data.status.bug_detection_status || 'pending'
            bugDetectionError.value = data.status.bug_detection_error || null
          }

          // If still processing, start polling
          if (transcriptionStatus.value === 'processing' || summaryStatus.value === 'processing' || bugDetectionStatus.value === 'processing') {
            startTranscriptionPolling()
          }
        }
      } catch (err) {
        console.error('Failed to load transcription:', err)
      }
    }

    const requestTranscription = async () => {
      if (isRequestingTranscription.value) return

      isRequestingTranscription.value = true
      try {
        const result = await videoService.requestTranscription(video.value.id, true, true)
        if (result.success) {
          transcriptionStatus.value = 'processing'
          transcriptionProgress.value = 0
          summaryStatus.value = 'pending'
          showToast('Transcription started')
          startTranscriptionPolling()
        } else {
          showToast(result.message || 'Failed to start transcription')
        }
      } catch (err) {
        console.error('Failed to request transcription:', err)
        showToast('Failed to start transcription')
      } finally {
        isRequestingTranscription.value = false
      }
    }

    const startTranscriptionPolling = () => {
      if (transcriptionPollInterval) return

      transcriptionPollInterval = setInterval(async () => {
        try {
          const status = await videoService.getTranscriptionStatus(video.value.id)
          if (status) {
            transcriptionStatus.value = status.transcription_status || 'pending'
            transcriptionProgress.value = status.transcription_progress || 0
            transcriptionError.value = status.transcription_error || null
            summaryStatus.value = status.summary_status || 'pending'
            summaryError.value = status.summary_error || null
            bugDetectionStatus.value = status.bug_detection_status || 'pending'
            bugDetectionError.value = status.bug_detection_error || null

            // If transcription completed, fetch the full data
            if (status.is_transcription_ready) {
              await loadTranscriptionData()

              // Also update the video title if it changed
              const updatedVideo = await videoService.getVideo(video.value.id)
              if (updatedVideo && updatedVideo.title !== video.value.title) {
                video.value.title = updatedVideo.title
              }
            }

            // If bug detection just completed, reload data
            if (status.is_bug_detection_ready && detectedBugs.value.length === 0) {
              await loadTranscriptionData()
            }

            // Stop polling if all are done
            if (!status.is_transcribing && !status.is_summarizing && !status.is_bug_detecting) {
              stopTranscriptionPolling()
            }
          }
        } catch (err) {
          console.error('Failed to poll transcription status:', err)
        }
      }, 3000) // Poll every 3 seconds
    }

    const stopTranscriptionPolling = () => {
      if (transcriptionPollInterval) {
        clearInterval(transcriptionPollInterval)
        transcriptionPollInterval = null
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
          .map(segment => `[${formatTime(segment.start)}] ${segment.text}`)
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

    // Bug detection methods
    const checkJiraConnectivity = async () => {
      try {
        const providers = await integrationService.getAvailableProviders()
        if (providers) {
          const jira = providers.find(p => p.id === 'jira')
          jiraConnected.value = jira?.connected === true
        }
      } catch (err) {
        console.error('Failed to check Jira connectivity:', err)
      }
    }

    const loadBugTabData = async () => {
      if (bugProjectsLoaded.value) return
      bugProjectsLoaded.value = true
      try {
        const targets = await integrationService.getTargets('jira')
        if (targets) {
          jiraProjects.value = targets
          if (targets.length > 0 && !selectedBugProject.value) {
            selectedBugProject.value = targets[0].id
          }
        }
      } catch (err) {
        console.error('Failed to load Jira projects:', err)
      }
    }

    const createBugInJira = async (bug) => {
      if (!selectedBugProject.value || creatingBugId.value) return
      creatingBugId.value = bug.id

      try {
        const result = await integrationService.createBug('jira', video.value.id, {
          target_id: selectedBugProject.value,
          bug_id: bug.id,
          bug_title: bug.title,
          bug_description: bug.description || '',
          bug_severity: bug.severity || 'medium',
          steps_to_reproduce: bug.steps_to_reproduce || []
        })

        if (result && result.success) {
          // Mark as sent — the job will create it in the background
          const idx = detectedBugs.value.findIndex(b => b.id === bug.id)
          if (idx !== -1) {
            detectedBugs.value[idx] = {
              ...detectedBugs.value[idx],
              jira_queued: true
            }
          }
          showToast('Bug will be created in Jira shortly')
        } else {
          showToast(result?.error || 'Failed to create bug in Jira')
        }
      } catch (err) {
        console.error('Failed to create bug in Jira:', err)
        showToast('Failed to create bug in Jira')
      } finally {
        creatingBugId.value = null
      }
    }

    const handleKeydown = (e) => {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return

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
      }
    }

    const handleClickOutside = (e) => {
      if (speedMenuRef.value && !speedMenuRef.value.contains(e.target)) {
        showSpeedMenu.value = false
      }
      if (qualityMenuRef.value && !qualityMenuRef.value.contains(e.target)) {
        showQualityMenu.value = false
      }
      // Close export menu
      if (showExportMenu.value && !e.target.closest('.export-menu-container')) {
        showExportMenu.value = false
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
      if (!branding.loaded.value) {
        branding.loadBranding()
      }
      await fetchVideo()
      await loadComments()
      await loadTranscriptionData()

      // Check Jira connectivity (non-blocking)
      checkJiraConnectivity()

      // Record view (non-blocking)
      if (video.value.id) {
        videoService.recordView(video.value.id).catch(() => {})
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
      stopTranscriptionPolling()
      destroyHls()
    })

    return {
      branding, isAuthenticated, currentUser, userInitial,
      video, loading, error, videoRef, progressBar, speedMenuRef, playerContainer,
      isPlaying, isBuffering, videoLoading, isMuted, isFullscreen, volume, currentTime, duration,
      bufferedPercent, progressPercent, playbackSpeed, controlsVisible, hoverTime,
      hoverPercent, showSpeedMenu, showBigPlayButton, showPrePlaySpeedMenu, copied, toast, newComment, comments, reactions,
      isLoadingComments, isSavingComment, copiedEmbed, copyEmbedCode,
      speedOptions, toggleSpeedMenu,
      availableQualities, currentQuality, showQualityMenu, qualityMenuRef,
      setQuality, toggleQualityMenu, getCurrentQualityLabel,
      togglePlay, updateProgress, onVideoLoaded, onVideoError, onVideoEnded, seek, startSeeking, updateHoverTime,
      skip, toggleMute, updateVolume, setPlaybackSpeed, toggleFullscreen,
      showControls, hideControlsDelayed, formatTime, formatTimeAgo, copyShareLink,
      downloadVideo, deleteVideo, confirmDeleteVideo, goBack, addReaction, addComment, loadComments,
      showDeleteModal, isDeleting,
      isEditingTitle, editedTitle, isSavingTitle, titleInput,
      startEditingTitle, saveTitle, cancelEditingTitle,
      showShareModal, activeTab, sidebarVisible, toggleSidebar,
      // Dropdowns
      showShareDropdown, showOptionsMenu, shareDropdownRef, optionsMenuRef,
      showArchiveConfirm, showDeleteConfirm, showPrivateConfirm,
      // Action handlers
      handleDownload, handleDuplicate, handleDownloadCaptions, handleMakePrivate, handleArchive,
      // Transcription
      transcription, transcriptionSegments, transcriptionStatus, transcriptionProgress, transcriptionError,
      isRequestingTranscription, requestTranscription, seekToTime,
      // Transcript sync & search
      transcriptContainer, segmentRefs, activeSegmentIndex,
      transcriptSearch, showExportMenu, filteredSegments,
      transcriptWordCount, transcriptReadTime,
      highlightSearch, copySegment, exportTranscript,
      // Summary
      summary, summaryStatus, summaryError, formattedSummary,
      // Bug detection
      detectedBugs, bugDetectionStatus, bugDetectionError,
      expandedBugId, creatingBugId, jiraConnected, jiraProjects, selectedBugProject,
      loadBugTabData, createBugInJira,
      // Captions
      captionsEnabled, captionsUrl, toggleCaptions, activeCaptionCue,
      // Copy
      copiedTranscript, copiedSummary, copyTranscript, copySummary,
      // Bunny
      isBunnyVideo, bunnyStatus, bunnyEncodeProgress, bunnyAvailableResolutions,
      // Auth
      auth,
    }
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

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
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-radius: 8px;
  padding: 6px 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(229, 231, 235, 0.5);
  max-width: 80%;
}
.caption-word {
  font-size: 14px;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-weight: 500;
  letter-spacing: -0.01em;
  line-height: 1.5;
  transition: color 0.2s ease;
}
.caption-active { color: #111827; }
.caption-inactive { color: #d1d5db; }
</style>

