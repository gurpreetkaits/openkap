<template>
  <div class="animate-fade-in flex flex-col h-full -m-6 lg:-m-8">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-orange-600 border-t-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">Loading playlist...</p>
    </div>

    <template v-else-if="playlist">
      <!-- Hero Section -->
      <div class="bg-gray-900 flex-shrink-0 relative overflow-hidden">
        <!-- Background -->
        <div class="absolute inset-0 bg-gradient-to-br from-gray-800 via-gray-900 to-black"></div>
        <div class="absolute inset-0 opacity-[0.06]">
          <svg class="w-full h-full" viewBox="0 0 900 120" fill="none" preserveAspectRatio="xMidYMid slice">
            <circle cx="800" cy="20" r="100" fill="white"/>
            <circle cx="60" cy="100" r="70" fill="white"/>
            <circle cx="450" cy="-20" r="60" fill="white"/>
          </svg>
        </div>

        <div class="relative z-10 px-7 py-5 flex gap-5 items-start">
          <!-- Cover thumbnail -->
          <div class="w-[88px] h-[68px] rounded-lg bg-white/[0.06] border border-white/10 flex-shrink-0 overflow-hidden">
            <div class="w-full h-full grid grid-cols-2 gap-px p-1.5">
              <div class="bg-white/[0.05] rounded-sm"></div>
              <div class="bg-white/[0.08] rounded-sm"></div>
              <div class="bg-white/[0.08] rounded-sm"></div>
              <div class="bg-white/[0.05] rounded-sm"></div>
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="text-[10.5px] font-semibold text-white/35 uppercase tracking-wider mb-1">Playlist</div>
            <h1 class="text-[22px] text-white tracking-tight leading-tight mb-1" style="font-family: Georgia, serif; font-style: italic;">
              {{ playlist.title }}
            </h1>
            <p v-if="playlist.description" class="text-[12.5px] text-white/45 mb-2.5 leading-relaxed">{{ playlist.description }}</p>
            <p v-else class="text-[12.5px] text-white/30 italic mb-2.5">No description</p>
            <div class="flex items-center gap-2.5">
              <div class="flex items-center gap-1 text-xs text-white/40">
                <svg class="w-[11px] h-[11px]" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 11 11">
                  <rect x="1" y="1.5" width="9" height="8" rx="1"/>
                  <polygon points="4,3.5 4,7.5 8,5.5" fill="currentColor" stroke="none"/>
                </svg>
                {{ playlist.videos_count || 0 }} {{ (playlist.videos_count || 0) === 1 ? 'video' : 'videos' }}
              </div>
              <div class="w-[3px] h-[3px] bg-white/20 rounded-full"></div>
              <div class="flex items-center gap-1 text-xs text-white/40">
                <svg class="w-[11px] h-[11px]" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 11 11">
                  <path d="M1 5.5C1 5.5 2.5 2.5 5.5 2.5S10 5.5 10 5.5 8.5 8.5 5.5 8.5 1 5.5 1 5.5z"/>
                  <circle cx="5.5" cy="5.5" r="1.5"/>
                </svg>
                {{ playlist.total_views || 0 }} views
              </div>
              <div class="w-[3px] h-[3px] bg-white/20 rounded-full"></div>
              <div class="flex items-center gap-1 text-xs text-white/40">
                <svg class="w-[11px] h-[11px]" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 11 11">
                  <circle cx="5.5" cy="5.5" r="4"/>
                  <path d="M5.5 3v3l1.5 1.5"/>
                </svg>
                {{ formatDate(playlist.created_at) }}
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1.5 flex-shrink-0">
            <!-- Play all -->
            <button
              v-if="playlist.videos && playlist.videos.length > 0"
              @click="openVideo(playlist.videos[0].id)"
              class="flex items-center gap-1.5 px-3.5 py-[7px] bg-white text-gray-900 rounded-lg text-[12.5px] font-medium hover:bg-gray-100 transition-colors"
            >
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 12 12"><polygon points="3,2 10,6 3,10"/></svg>
              Play all
            </button>

            <!-- Share -->
            <button
              @click="copyShareLink"
              class="flex items-center gap-1.5 px-3.5 py-[7px] bg-white/[0.08] border border-white/[0.12] text-white/80 rounded-lg text-[12.5px] font-medium hover:bg-white/[0.14] hover:text-white transition-colors"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 12 12">
                <circle cx="9" cy="2" r="1.4"/><circle cx="9" cy="10" r="1.4"/><circle cx="2" cy="6" r="1.4"/>
                <line x1="7.7" y1="2.8" x2="3.3" y2="5.2"/><line x1="7.7" y1="9.2" x2="3.3" y2="6.8"/>
              </svg>
              Share
            </button>

            <!-- More dropdown -->
            <div class="relative" data-dropdown-menu>
              <button
                @click="showHeroMenu = !showHeroMenu"
                class="w-8 h-8 bg-white/[0.08] border border-white/10 rounded-lg flex items-center justify-center text-white/60 hover:bg-white/[0.14] hover:text-white transition-colors"
              >
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 14 14">
                  <circle cx="3" cy="7" r="1.2"/><circle cx="7" cy="7" r="1.2"/><circle cx="11" cy="7" r="1.2"/>
                </svg>
              </button>
              <Transition name="dropdown">
                <div
                  v-if="showHeroMenu"
                  class="absolute right-0 mt-1 w-[165px] bg-white border border-gray-200 rounded-lg shadow-xl z-50 py-1"
                >
                  <button @click="editPlaylist(); showHeroMenu = false" class="w-full flex items-center gap-2 px-3 py-[7px] text-[12.5px] text-gray-700 hover:bg-gray-50 transition-colors rounded-md mx-0">
                    Edit details
                  </button>
                  <button @click="toggleVisibility(); showHeroMenu = false" class="w-full flex items-center gap-2 px-3 py-[7px] text-[12.5px] text-gray-700 hover:bg-gray-50 transition-colors rounded-md mx-0">
                    {{ playlist.is_public ? 'Make private' : 'Make public' }}
                  </button>
                  <div class="h-px bg-gray-100 my-0.5"></div>
                  <button @click="confirmDeletePlaylist(); showHeroMenu = false" class="w-full flex items-center gap-2 px-3 py-[7px] text-[12.5px] text-red-600 hover:bg-red-50 transition-colors rounded-md mx-0">
                    Delete playlist
                  </button>
                </div>
              </Transition>
            </div>

            <!-- Back button -->
            <button
              @click="router.push('/playlists')"
              class="w-8 h-8 bg-white/[0.08] border border-white/10 rounded-lg flex items-center justify-center text-white/60 hover:bg-white/[0.14] hover:text-white transition-colors"
              title="Back to playlists"
            >
              <svg class="w-[13px] h-[13px]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 13 13">
                <path d="M8 2L3 6.5 8 11"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Detail Content -->
      <div class="flex-1 flex overflow-hidden">
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto px-6 py-5">
          <!-- Toolbar -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <!-- Search -->
              <div class="flex items-center gap-1.5 bg-white border border-gray-100 rounded-lg px-2.5 py-1.5 focus-within:border-gray-300 transition-colors">
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 12 12">
                  <circle cx="5" cy="5" r="3.5"/><path d="M8 8l2.5 2.5"/>
                </svg>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search in playlist..."
                  class="bg-transparent border-none outline-none text-[12.5px] text-gray-900 placeholder-gray-400 w-40"
                />
              </div>

              <!-- Sort -->
              <div class="relative" data-dropdown-menu>
                <button
                  @click="showSortMenu = !showSortMenu"
                  class="flex items-center gap-1.5 bg-white border border-gray-100 rounded-lg px-2.5 py-1.5 text-[12.5px] text-gray-500 hover:bg-gray-50 hover:text-gray-700 hover:border-gray-200 transition-colors cursor-pointer"
                >
                  <svg class="w-[11px] h-[11px]" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 11 11">
                    <path d="M1.5 3h8M2.5 5.5h6M3.5 8h4"/>
                  </svg>
                  {{ playlist.sort_by === 'manual' ? 'Manual order' : 'Date added' }}
                  <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 10 10"><path d="M2 4l3 3 3-3"/></svg>
                </button>
                <Transition name="dropdown">
                  <div v-if="showSortMenu" class="absolute left-0 mt-1 w-40 bg-white border border-gray-100 rounded-lg shadow-lg z-10 py-1">
                    <button
                      @click="updateSortBy('manual')"
                      class="w-full px-3 py-1.5 text-left text-[12.5px] hover:bg-gray-50 transition-colors flex items-center gap-2"
                      :class="playlist.sort_by === 'manual' ? 'text-orange-600 font-medium' : 'text-gray-700'"
                    >
                      Manual order
                    </button>
                    <button
                      @click="updateSortBy('date_added')"
                      class="w-full px-3 py-1.5 text-left text-[12.5px] hover:bg-gray-50 transition-colors flex items-center gap-2"
                      :class="playlist.sort_by === 'date_added' ? 'text-orange-600 font-medium' : 'text-gray-700'"
                    >
                      Date added
                    </button>
                  </div>
                </Transition>
              </div>
            </div>

            <!-- Add videos -->
            <button
              @click="showAddVideosModal = true"
              class="flex items-center gap-1.5 px-3.5 py-[7px] bg-gray-900 text-white rounded-lg text-[12.5px] font-semibold hover:bg-black hover:shadow-md transition-all"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 12 12">
                <line x1="6" y1="1" x2="6" y2="11"/><line x1="1" y1="6" x2="11" y2="6"/>
              </svg>
              Add videos
            </button>
          </div>

          <!-- Video Rows -->
          <div v-if="filteredVideos.length > 0">
            <div
              v-for="(video, index) in filteredVideos"
              :key="video.id"
              class="group flex items-center gap-3 px-2.5 py-2 rounded-lg cursor-pointer transition-all border border-transparent hover:bg-white hover:border-gray-100"
              :draggable="playlist.sort_by === 'manual'"
              @dragstart="onDragStart($event, index)"
              @dragover.prevent="onDragOver($event, index)"
              @drop="onDrop($event, index)"
              @dragend="onDragEnd"
              @click="openVideo(video.id)"
            >
              <!-- Thumbnail -->
              <div class="relative w-[88px] h-[50px] rounded-md overflow-hidden bg-gray-200 flex-shrink-0">
                <img
                  v-if="video.thumbnail"
                  :src="video.thumbnail"
                  :alt="video.title"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <div v-else class="w-full h-full bg-gradient-to-br from-gray-800 to-gray-900"></div>
                <div class="absolute bottom-1 right-1 bg-black/75 text-white text-[9.5px] font-semibold px-1.5 py-[1.5px] rounded tracking-wide">
                  {{ formatDuration(video.duration) }}
                </div>
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <h3 class="text-[13px] font-medium text-gray-900 truncate tracking-tight mb-0.5">{{ video.title }}</h3>
                <div class="flex items-center gap-1.5 text-[11.5px] text-gray-400">
                  <span v-if="video.added_at">Added {{ formatDate(video.added_at) }}</span>
                  <span v-else>{{ formatDate(video.created_at) }}</span>
                </div>
              </div>

              <!-- Stats -->
              <div class="flex items-center gap-2 flex-shrink-0">
                <div class="flex items-center gap-1 text-[11.5px] text-gray-400">
                  <svg class="w-[11px] h-[11px]" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 11 11">
                    <path d="M1 5.5C1 5.5 2.5 3 5.5 3S10 5.5 10 5.5 8.5 8 5.5 8 1 5.5 1 5.5z"/>
                    <circle cx="5.5" cy="5.5" r="1.5"/>
                  </svg>
                  {{ video.views_count || 0 }}
                </div>
              </div>

              <!-- More button -->
              <div class="relative" @click.stop>
                <button
                  @click="toggleVideoMenu(video.id)"
                  class="w-[26px] h-[26px] rounded-[5px] flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition-colors opacity-0 group-hover:opacity-100"
                >
                  <svg class="w-[13px] h-[13px]" fill="currentColor" viewBox="0 0 13 13">
                    <circle cx="6.5" cy="2" r="1.2"/><circle cx="6.5" cy="6.5" r="1.2"/><circle cx="6.5" cy="11" r="1.2"/>
                  </svg>
                </button>
                <Transition name="dropdown">
                  <div
                    v-if="activeVideoMenu === video.id"
                    class="absolute right-0 mt-1 w-[165px] bg-white border border-gray-200 rounded-lg shadow-xl z-50 py-1"
                  >
                    <button @click="openVideo(video.id); activeVideoMenu = null" class="w-full flex items-center gap-2 px-3 py-[7px] text-[12.5px] text-gray-700 hover:bg-gray-50 transition-colors">
                      <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 12 12"><polygon points="3,2 9,6 3,10"/></svg>
                      Play
                    </button>
                    <button @click="copyVideoLink(video); activeVideoMenu = null" class="w-full flex items-center gap-2 px-3 py-[7px] text-[12.5px] text-gray-700 hover:bg-gray-50 transition-colors">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 12 12">
                        <path d="M5 8.5L8.5 5M7 3l1.5-1.5a2.5 2.5 0 013.5 3.5L10.5 6.5M6 10L4.5 11.5a2.5 2.5 0 01-3.5-3.5L2.5 6.5"/>
                      </svg>
                      Copy link
                    </button>
                    <div class="h-px bg-gray-100 my-0.5"></div>
                    <button @click="removeVideo(video); activeVideoMenu = null" class="w-full flex items-center gap-2 px-3 py-[7px] text-[12.5px] text-red-600 hover:bg-red-50 transition-colors">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 12 12">
                        <path d="M1.5 3.5h9M4.5 3.5V2.5a.5.5 0 01.5-.5h2a.5.5 0 01.5.5v1M3.5 3.5l.8 7h4.4l.8-7"/>
                      </svg>
                      Remove from playlist
                    </button>
                  </div>
                </Transition>
              </div>
            </div>
          </div>

          <!-- Empty Videos -->
          <div v-else-if="!searchQuery" class="flex flex-col items-center text-center py-12">
            <div class="w-[52px] h-[52px] bg-white border border-gray-100 rounded-xl flex items-center justify-center mb-3.5 text-gray-300">
              <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 22 22">
                <rect x="2" y="4" width="18" height="14" rx="2.5"/>
                <polygon points="9,8 9,14 15,11" fill="currentColor" stroke="none"/>
              </svg>
            </div>
            <h3 class="text-sm font-medium text-gray-700 mb-1">No videos yet</h3>
            <p class="text-[12.5px] text-gray-400 max-w-[260px] leading-relaxed">Add videos from your library to this playlist.</p>
          </div>

          <!-- No search results -->
          <div v-else class="flex flex-col items-center text-center py-12">
            <p class="text-sm text-gray-400">No videos match "{{ searchQuery }}"</p>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="w-[280px] flex-shrink-0 border-l border-gray-100 px-[18px] py-[18px] overflow-y-auto">
          <!-- About -->
          <div class="mb-5">
            <div class="text-[10.5px] font-semibold text-gray-400 uppercase tracking-wider mb-2.5">About</div>
            <div class="divide-y divide-gray-100">
              <div class="flex items-center justify-between py-[7px] text-[12.5px]">
                <span class="text-gray-400">Visibility</span>
                <span class="text-gray-900 font-medium flex items-center gap-1">
                  <svg class="w-[11px] h-[11px]" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 11 11">
                    <template v-if="playlist.is_public">
                      <circle cx="5.5" cy="5.5" r="4"/>
                    </template>
                    <template v-else>
                      <rect x="1" y="2.5" width="9" height="7" rx="1"/>
                      <path d="M3.5 2.5V2a2 2 0 014 0v.5"/>
                    </template>
                  </svg>
                  {{ playlist.is_public ? 'Public' : 'Private' }}
                </span>
              </div>
              <div class="flex items-center justify-between py-[7px] text-[12.5px]">
                <span class="text-gray-400">Created</span>
                <span class="text-gray-900 font-medium">{{ formatDate(playlist.created_at) }}</span>
              </div>
              <div class="flex items-center justify-between py-[7px] text-[12.5px]">
                <span class="text-gray-400">Videos</span>
                <span class="text-gray-900 font-medium">{{ playlist.videos_count || 0 }}</span>
              </div>
              <div class="flex items-center justify-between py-[7px] text-[12.5px]">
                <span class="text-gray-400">Total views</span>
                <span class="text-gray-900 font-medium">{{ playlist.total_views || 0 }}</span>
              </div>
            </div>
          </div>

          <!-- Share -->
          <div class="mb-5" v-if="playlist.share_url || playlist.is_public">
            <div class="text-[10.5px] font-semibold text-gray-400 uppercase tracking-wider mb-2.5">Share</div>
            <div class="bg-gray-50 border border-gray-100 rounded-lg px-3 py-2.5 mb-2.5">
              <div class="text-[11.5px] text-gray-400 font-mono truncate mb-2">{{ playlist.share_url || 'No share link' }}</div>
              <div class="flex gap-1.5">
                <button @click="copyShareLink" class="flex-1 bg-white border border-gray-200 rounded-[5px] py-1.5 text-[11.5px] text-gray-500 text-center hover:bg-gray-50 hover:text-gray-900 hover:border-gray-300 transition-all cursor-pointer">
                  Copy
                </button>
                <button @click="copyEmbedCode" class="flex-1 bg-white border border-gray-200 rounded-[5px] py-1.5 text-[11.5px] text-gray-500 text-center hover:bg-gray-50 hover:text-gray-900 hover:border-gray-300 transition-all cursor-pointer">
                  Embed
                </button>
              </div>
            </div>
          </div>

          <!-- Settings -->
          <div class="mb-5">
            <div class="text-[10.5px] font-semibold text-gray-400 uppercase tracking-wider mb-2.5">Settings</div>

            <!-- Visibility toggle -->
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-[12.5px] text-gray-700">Public</span>
              <button
                @click="toggleVisibility"
                class="w-[30px] h-[17px] rounded-full relative cursor-pointer transition-colors"
                :class="playlist.is_public ? 'bg-gray-900' : 'bg-gray-200 border border-gray-300'"
              >
                <div
                  class="absolute top-[2px] w-[13px] h-[13px] rounded-full transition-all"
                  :class="playlist.is_public ? 'right-[2px] bg-white' : 'left-[2px] bg-gray-400'"
                ></div>
              </button>
            </div>

            <!-- Password (when public) -->
            <div v-if="playlist.is_public" class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-[12.5px] text-gray-700">Password lock</span>
              <button
                @click="showPasswordModal = true"
                class="text-[11.5px] font-medium transition-colors"
                :class="playlist.has_password ? 'text-amber-600 hover:text-amber-700' : 'text-gray-400 hover:text-gray-600'"
              >
                {{ playlist.has_password ? 'Change' : 'Set' }}
              </button>
            </div>
          </div>

          <!-- Delete -->
          <button
            @click="confirmDeletePlaylist"
            class="w-full bg-transparent border border-red-200/50 text-red-600 rounded-lg py-[7px] text-xs cursor-pointer hover:bg-red-50 transition-colors"
          >
            Delete playlist
          </button>
        </div>
      </div>
    </template>

    <!-- Edit Playlist Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showEditModal"
          class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/25 backdrop-blur-[5px]"
          @click.self="showEditModal = false"
        >
          <div class="bg-white border border-gray-200 rounded-xl p-6 w-[440px] max-w-[90vw] shadow-2xl animate-modal-up">
            <h2 class="text-xl font-medium text-gray-900 mb-1" style="font-family: Georgia, serif; font-style: italic;">Edit playlist</h2>
            <p class="text-xs text-gray-400 mb-5">Update your playlist details.</p>
            <form @submit.prevent="savePlaylist">
              <div class="mb-3.5">
                <label class="block text-[11.5px] font-medium text-gray-500 mb-1.5">Name</label>
                <input
                  v-model="editForm.title"
                  type="text"
                  required
                  class="w-full bg-gray-50 border border-gray-100 rounded-lg px-3 py-2.5 text-[13.5px] text-gray-900 placeholder-gray-400 outline-none focus:bg-white focus:border-gray-300 transition-all"
                />
              </div>
              <div class="mb-3.5">
                <label class="block text-[11.5px] font-medium text-gray-500 mb-1.5">Description <span class="text-gray-300">(optional)</span></label>
                <input
                  v-model="editForm.description"
                  type="text"
                  class="w-full bg-gray-50 border border-gray-100 rounded-lg px-3 py-2.5 text-[13.5px] text-gray-900 placeholder-gray-400 outline-none focus:bg-white focus:border-gray-300 transition-all"
                />
              </div>
              <div class="flex justify-end gap-2 mt-5">
                <button type="button" @click="showEditModal = false" class="px-4 py-2 text-[13px] font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-all">Cancel</button>
                <button type="submit" :disabled="savingEdit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-[13px] font-semibold hover:bg-black transition-all disabled:bg-gray-300">
                  {{ savingEdit ? 'Saving...' : 'Save changes' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Password Modal -->
    <SBModal v-model="showPasswordModal" :title="playlist?.has_password ? 'Change Password' : 'Set Password'">
      <form @submit.prevent="savePassword" class="space-y-4">
        <p class="text-sm text-gray-500">
          {{ playlist?.has_password ? 'Change or remove the password for this shared playlist.' : 'Add a password to protect this shared playlist.' }}
        </p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            v-model="passwordForm.password"
            type="password"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
            placeholder="Enter password (min 4 characters)"
            :required="!playlist?.has_password"
            minlength="4"
          />
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
          <button
            v-if="playlist?.has_password"
            type="button"
            @click="removePassword"
            :disabled="savingPassword"
            class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors"
          >
            Remove Password
          </button>
          <div v-else></div>
          <div class="flex gap-2">
            <button type="button" @click="showPasswordModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
            <button
              type="submit"
              :disabled="savingPassword || (!passwordForm.password || passwordForm.password.length < 4)"
              class="px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 text-white rounded-lg font-medium text-sm transition-colors"
            >
              {{ savingPassword ? 'Saving...' : 'Set Password' }}
            </button>
          </div>
        </div>
      </form>
    </SBModal>

    <!-- Add Videos Modal -->
    <SBModal v-model="showAddVideosModal" title="Add Videos to Playlist" size="lg">
      <div v-if="loadingVideos" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-orange-600 border-t-transparent"></div>
        <p class="mt-3 text-sm text-gray-500">Loading videos...</p>
      </div>

      <div v-else-if="availableVideos.length === 0" class="text-center py-8">
        <p class="text-gray-500">No videos available to add.</p>
      </div>

      <div v-else class="space-y-2 max-h-96 overflow-y-auto">
        <div
          v-for="video in availableVideos"
          :key="video.id"
          class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer"
          @click="toggleVideoSelection(video)"
        >
          <div class="flex-shrink-0">
            <div
              class="w-5 h-5 border-2 rounded flex items-center justify-center transition-colors"
              :class="selectedVideoIds.includes(video.id) ? 'bg-orange-600 border-orange-600' : 'border-gray-300'"
            >
              <svg v-if="selectedVideoIds.includes(video.id)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </div>
          </div>
          <div class="w-20 aspect-video rounded overflow-hidden bg-gray-200 flex-shrink-0">
            <img v-if="video.thumbnail" :src="video.thumbnail" :alt="video.title" class="w-full h-full object-cover" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ video.title }}</p>
            <p class="text-xs text-gray-500">{{ formatDuration(video.duration) }}</p>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-100">
        <span class="text-sm text-gray-500">{{ selectedVideoIds.length }} selected</span>
        <div class="flex gap-2">
          <button @click="showAddVideosModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
          <button
            @click="addSelectedVideos"
            :disabled="selectedVideoIds.length === 0 || addingVideos"
            class="px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 text-white rounded-lg font-medium text-sm transition-colors"
          >
            {{ addingVideos ? 'Adding...' : 'Add to Playlist' }}
          </button>
        </div>
      </div>
    </SBModal>

    <!-- Delete Confirmation -->
    <SBDeleteModal
      v-model="showDeleteModal"
      title="Delete Playlist"
      :message="`Are you sure you want to delete '${playlist?.title}'? This action cannot be undone.`"
      :loading="deleting"
      @confirm="deletePlaylist"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import playlistService from '@/services/playlistService'
import videoService from '@/services/videoService'
import toast from '@/services/toastService'
import { formatDistanceToNow } from 'date-fns'
import { SBModal, SBDeleteModal } from '@/components/Global'

const route = useRoute()
const router = useRouter()

const playlist = ref(null)
const loading = ref(true)
const searchQuery = ref('')
const showSortMenu = ref(false)
const showHeroMenu = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showPasswordModal = ref(false)
const showAddVideosModal = ref(false)
const savingEdit = ref(false)
const savingPassword = ref(false)
const deleting = ref(false)
const loadingVideos = ref(false)
const addingVideos = ref(false)
const availableVideos = ref([])
const selectedVideoIds = ref([])
const activeVideoMenu = ref(null)
const passwordForm = ref({ password: '' })
const editForm = ref({ title: '', description: '' })
const draggedIndex = ref(null)

const filteredVideos = computed(() => {
  if (!playlist.value?.videos) return []
  if (!searchQuery.value) return playlist.value.videos
  const q = searchQuery.value.toLowerCase()
  return playlist.value.videos.filter(v => v.title.toLowerCase().includes(q))
})

async function fetchPlaylist() {
  loading.value = true
  try {
    playlist.value = await playlistService.getPlaylist(route.params.id)
  } catch (error) {
    console.error('Failed to fetch playlist:', error)
    toast.error('Failed to load playlist')
    router.push('/playlists')
  } finally {
    loading.value = false
  }
}

function editPlaylist() {
  editForm.value = {
    title: playlist.value.title,
    description: playlist.value.description || ''
  }
  showEditModal.value = true
}

async function savePlaylist() {
  if (!editForm.value.title.trim()) return
  savingEdit.value = true
  try {
    await playlistService.updatePlaylist(playlist.value.id, editForm.value)
    playlist.value.title = editForm.value.title
    playlist.value.description = editForm.value.description
    showEditModal.value = false
    toast.success('Playlist updated')
  } catch (error) {
    toast.error(error.message || 'Failed to update playlist')
  } finally {
    savingEdit.value = false
  }
}

function confirmDeletePlaylist() {
  showDeleteModal.value = true
}

async function deletePlaylist() {
  deleting.value = true
  try {
    await playlistService.deletePlaylist(playlist.value.id)
    toast.success('Playlist deleted')
    router.push('/playlists')
  } catch (error) {
    toast.error('Failed to delete playlist')
  } finally {
    deleting.value = false
  }
}

async function toggleVisibility() {
  try {
    const result = await playlistService.toggleSharing(playlist.value.id)
    playlist.value.is_public = result.is_public
    playlist.value.share_url = result.share_url
    toast.success(result.is_public ? 'Playlist is now public' : 'Playlist is now private')
  } catch (error) {
    toast.error('Failed to update visibility')
  }
}

async function copyShareLink() {
  if (!playlist.value.is_public) {
    await toggleVisibility()
    return
  }
  if (!playlist.value.share_url) return
  try {
    await navigator.clipboard.writeText(playlist.value.share_url)
    toast.success('Link copied to clipboard!')
  } catch (error) {
    toast.error('Failed to copy link')
  }
}

async function copyEmbedCode() {
  if (!playlist.value.share_url) return
  try {
    const embedCode = `<iframe src="${playlist.value.share_url}" width="640" height="480" frameborder="0" allowfullscreen></iframe>`
    await navigator.clipboard.writeText(embedCode)
    toast.success('Embed code copied!')
  } catch (error) {
    toast.error('Failed to copy embed code')
  }
}

async function copyVideoLink(video) {
  if (video.shareUrl) {
    try {
      await navigator.clipboard.writeText(video.shareUrl)
      toast.success('Link copied!')
    } catch { toast.error('Failed to copy link') }
  }
}

async function savePassword() {
  if (!passwordForm.value.password || passwordForm.value.password.length < 4) return
  savingPassword.value = true
  try {
    const result = await playlistService.setPassword(playlist.value.id, passwordForm.value.password)
    playlist.value.has_password = result.has_password
    showPasswordModal.value = false
    passwordForm.value.password = ''
    toast.success('Password set successfully')
  } catch (error) {
    toast.error(error.message || 'Failed to set password')
  } finally {
    savingPassword.value = false
  }
}

async function removePassword() {
  savingPassword.value = true
  try {
    const result = await playlistService.removePassword(playlist.value.id)
    playlist.value.has_password = result.has_password
    showPasswordModal.value = false
    passwordForm.value.password = ''
    toast.success('Password removed')
  } catch (error) {
    toast.error(error.message || 'Failed to remove password')
  } finally {
    savingPassword.value = false
  }
}

async function updateSortBy(sortBy) {
  showSortMenu.value = false
  if (playlist.value.sort_by === sortBy) return
  try {
    await playlistService.updateSortBy(playlist.value.id, sortBy)
    playlist.value.sort_by = sortBy
    await fetchPlaylist()
    toast.success('Sort order updated')
  } catch (error) {
    toast.error('Failed to update sort order')
  }
}

async function fetchAvailableVideos() {
  loadingVideos.value = true
  try {
    const allVideos = await videoService.getVideos()
    const playlistVideoIds = (playlist.value?.videos || []).map(v => v.id)
    availableVideos.value = allVideos.filter(v => !playlistVideoIds.includes(v.id))
  } catch (error) {
    toast.error('Failed to load videos')
  } finally {
    loadingVideos.value = false
  }
}

function toggleVideoSelection(video) {
  const index = selectedVideoIds.value.indexOf(video.id)
  if (index === -1) selectedVideoIds.value.push(video.id)
  else selectedVideoIds.value.splice(index, 1)
}

async function addSelectedVideos() {
  if (selectedVideoIds.value.length === 0) return
  addingVideos.value = true
  try {
    for (const videoId of selectedVideoIds.value) {
      await playlistService.addVideo(playlist.value.id, videoId)
    }
    toast.success(`Added ${selectedVideoIds.value.length} video(s) to playlist`)
    showAddVideosModal.value = false
    selectedVideoIds.value = []
    await fetchPlaylist()
  } catch (error) {
    toast.error(error.message || 'Failed to add videos')
  } finally {
    addingVideos.value = false
  }
}

async function removeVideo(video) {
  try {
    await playlistService.removeVideo(playlist.value.id, video.id)
    toast.success('Video removed from playlist')
    await fetchPlaylist()
  } catch (error) {
    toast.error('Failed to remove video')
  }
}

function toggleVideoMenu(videoId) {
  activeVideoMenu.value = activeVideoMenu.value === videoId ? null : videoId
}

// Drag and drop
function onDragStart(event, index) {
  if (playlist.value.sort_by !== 'manual') return
  draggedIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
}

function onDragOver(event, index) {
  if (playlist.value.sort_by !== 'manual') return
  event.dataTransfer.dropEffect = 'move'
}

async function onDrop(event, index) {
  if (playlist.value.sort_by !== 'manual' || draggedIndex.value === null) return
  const fromIndex = draggedIndex.value
  if (fromIndex === index) return

  const videos = [...playlist.value.videos]
  const [removed] = videos.splice(fromIndex, 1)
  videos.splice(index, 0, removed)
  playlist.value.videos = videos

  const videoIds = videos.map(v => v.id)
  try {
    await playlistService.reorderVideos(playlist.value.id, videoIds)
  } catch (error) {
    toast.error('Failed to save new order')
    await fetchPlaylist()
  }
}

function onDragEnd() {
  draggedIndex.value = null
}

function openVideo(id) {
  router.push(`/video/${id}`)
}

function formatDuration(seconds) {
  if (!seconds || isNaN(seconds)) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

function formatDate(date) {
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true })
  } catch {
    return date
  }
}

function handleClickOutside(event) {
  if (!event.target.closest('[data-dropdown-menu]')) {
    showSortMenu.value = false
    showHeroMenu.value = false
  }
  if (!event.target.closest('.vr-more') && !event.target.closest('[data-video-menu]')) {
    activeVideoMenu.value = null
  }
}

watch(showAddVideosModal, (newValue) => {
  if (newValue) {
    fetchAvailableVideos()
    selectedVideoIds.value = []
  }
})

onMounted(() => {
  fetchPlaylist()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }

@keyframes modalUp {
  from { opacity: 0; transform: translateY(7px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-modal-up { animation: modalUp 0.2s cubic-bezier(0.16, 1, 0.3, 1); }

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.dropdown-enter-active {
  transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
}
.dropdown-leave-active {
  transition: all 0.1s ease-in;
}
.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-4px);
}
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-2px);
}
</style>
