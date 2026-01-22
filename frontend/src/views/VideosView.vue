<template>
  <div class="animate-fade-in max-w-7xl mx-auto p-6 lg:p-8">
    <!-- Library Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <!-- Tabs -->
      <div class="flex items-center p-1 bg-gray-100 rounded-lg self-start border border-gray-200/50">
        <button
          @click="activeTab = 'videos'"
          class="px-3.5 py-1 text-[13px] font-medium rounded-[6px] transition-all"
          :class="activeTab === 'videos' ? 'text-gray-900 bg-white shadow-sm' : 'text-gray-500 hover:text-gray-900 hover:bg-white/50'"
        >
          Videos
        </button>
        <button
          @click="activeTab = 'favourites'"
          class="px-3.5 py-1 text-[13px] font-medium rounded-[6px] transition-all"
          :class="activeTab === 'favourites' ? 'text-gray-900 bg-white shadow-sm' : 'text-gray-500 hover:text-gray-900 hover:bg-white/50'"
        >
          <span class="flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            Favourites
          </span>
        </button>
        <button
          class="px-3.5 py-1 text-[13px] font-medium text-gray-500 hover:text-gray-900 hover:bg-white/50 rounded-[6px] transition-all"
        >
          Archived
        </button>
      </div>

      <div class="flex items-center gap-3">
        <!-- Bulk Actions Dropdown (shown when items are selected) -->
        <div v-if="selectedVideos.length > 0" class="flex items-center gap-2">
          <span class="text-[13px] font-medium text-gray-600">
            {{ selectedVideos.length }} selected
          </span>
          <div class="relative" ref="bulkActionsDropdownRef">
            <button
              @click="showBulkActionsDropdown = !showBulkActionsDropdown"
              class="flex items-center gap-2 text-[13px] font-medium text-white bg-orange-600 hover:bg-orange-700 px-3 py-1.5 rounded-lg transition-all shadow-sm"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
              </svg>
              Bulk Actions
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <!-- Bulk Actions Dropdown Menu -->
            <div
              v-show="showBulkActionsDropdown"
              class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
            >
              <button
                @click="bulkAddToFavourites"
                class="w-full px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"
              >
                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                Add to Favourites
              </button>
              <button
                @click="openPlaylistModal"
                class="w-full px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"
              >
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Add to Playlist
              </button>
              <div class="border-t border-gray-100 my-1"></div>
              <button
                @click="showBulkDeleteModal = true; showBulkActionsDropdown = false"
                class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-3"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete Permanently
              </button>
            </div>
          </div>
        </div>

        <!-- Select All (shown when videos are selected) -->
        <div v-if="selectedVideos.length > 0" class="flex items-center gap-2 border-l border-gray-200 pl-3">
          <input
            type="checkbox"
            id="select-all"
            :checked="isAllSelected"
            :indeterminate="isPartiallySelected"
            @change="toggleSelectAll"
            class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 focus:ring-2"
          />
          <label for="select-all" class="text-[13px] text-gray-600 cursor-pointer">All</label>
        </div>

        <!-- Cancel Selection (shown when videos are selected) -->
        <button
          v-if="selectedVideos.length > 0"
          @click="clearSelection"
          class="text-[13px] font-medium text-gray-500 hover:text-gray-700 px-2 py-1"
        >
          Cancel
        </button>

        <!-- Sort Dropdown -->
        <div class="relative" ref="sortDropdownRef">
          <button
            @click="showSortDropdown = !showSortDropdown"
            class="flex items-center gap-2 text-[13px] font-medium text-gray-600 bg-white border border-gray-200 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-all shadow-sm"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
            {{ currentSortLabel }}
          </button>

          <!-- Sort Dropdown Menu -->
          <div
            v-show="showSortDropdown"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
          >
            <button
              v-for="option in sortOptions"
              :key="option.id"
              @click="setSortOption(option.id)"
              class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center justify-between"
              :class="sortBy === option.id ? 'text-orange-600 bg-orange-50' : 'text-gray-700'"
            >
              {{ option.label }}
              <svg v-if="sortBy === option.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Filter Dropdown -->
        <div class="relative" ref="filterDropdownRef">
          <button
            @click="showFilterDropdown = !showFilterDropdown"
            class="flex items-center gap-2 text-[13px] font-medium text-gray-600 bg-white border border-gray-200 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-all shadow-sm"
            :class="activeDateFilter !== 'all' ? 'border-orange-300 bg-orange-50 text-orange-700' : ''"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            Filter
            <span v-if="activeDateFilter !== 'all'" class="w-1.5 h-1.5 bg-orange-500 rounded-full"></span>
          </button>

          <!-- Filter Dropdown Menu -->
          <div
            v-show="showFilterDropdown"
            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50"
          >
            <div class="px-3 pb-2 mb-2 border-b border-gray-100">
              <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Date Range</span>
            </div>
            <button
              v-for="filter in dateFilters"
              :key="filter.id"
              @click="setDateFilter(filter.id); showFilterDropdown = false"
              class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center justify-between"
              :class="activeDateFilter === filter.id ? 'text-orange-600 bg-orange-50' : 'text-gray-700'"
            >
              {{ filter.label }}
              <svg v-if="activeDateFilter === filter.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </button>

            <!-- Custom Date Picker -->
            <div v-if="activeDateFilter === 'custom'" class="px-3 pt-2 mt-2 border-t border-gray-100 space-y-2">
              <div>
                <label class="text-xs text-gray-500 mb-1 block">From</label>
                <input
                  type="date"
                  v-model="customDateFrom"
                  class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                />
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">To</label>
                <input
                  type="date"
                  v-model="customDateTo"
                  class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                />
              </div>
            </div>

            <!-- Clear Filters -->
            <div v-if="activeDateFilter !== 'all'" class="px-3 pt-2 mt-2 border-t border-gray-100">
              <button
                @click="clearFilters(); showFilterDropdown = false"
                class="w-full text-center text-sm text-orange-600 hover:text-orange-700 font-medium py-1"
              >
                Clear Filters
              </button>
            </div>
          </div>
        </div>

        <!-- View Toggle -->
        <div class="flex items-center gap-1 border border-gray-200 rounded-lg p-0.5 bg-white shadow-sm">
          <button
            @click="viewMode = 'grid'"
            class="p-1.5 rounded transition-colors"
            :class="viewMode === 'grid' ? 'bg-gray-100 text-gray-900' : 'text-gray-400 hover:text-gray-600'"
            title="Grid view"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
          </button>
          <button
            @click="viewMode = 'list'"
            class="p-1.5 rounded transition-colors"
            :class="viewMode === 'list' ? 'bg-gray-100 text-gray-900' : 'text-gray-400 hover:text-gray-600'"
            title="List view"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-orange-600 border-t-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">Loading videos...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-24">
      <div class="w-16 h-16 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ error }}</h3>
      <button
        @click="fetchVideos"
        class="inline-flex items-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm shadow-sm transition-colors"
      >
        Try Again
      </button>
    </div>

    <!-- Videos Grid View -->
    <div v-else-if="filteredVideos.length > 0 && viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div
        v-for="video in paginatedVideos"
        :key="video.id"
        class="group relative"
      >
        <!-- Thumbnail -->
        <div
          class="relative aspect-video bg-gray-900 rounded-xl overflow-hidden mb-3 border border-gray-200/50 shadow-sm group-hover:shadow-md transition-all cursor-pointer"
          :class="{ 'ring-2 ring-orange-500 ring-offset-2': selectedVideos.includes(video.id) }"
          style="aspect-ratio: 16 / 9;"
          @click="handleVideoClick(video.id)"
        >
          <img
            v-if="video.thumbnail"
            :src="video.thumbnail"
            :alt="video.title"
            class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 group-hover:opacity-100 transition-all duration-500"
            loading="lazy"
          />
          <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900">
            <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>

          <!-- Duration Badge -->
          <div class="absolute bottom-2 right-2 bg-black/60 backdrop-blur-md text-white text-[10px] font-medium px-1.5 py-0.5 rounded-md border border-white/10 z-10">
            {{ formatDuration(video.duration) }}
          </div>

          <!-- Select Checkbox (always visible when in selection mode or selected, shows on hover otherwise) -->
          <div
            class="absolute top-2 left-2 z-20 transition-opacity"
            :class="selectedVideos.includes(video.id) || isSelectionMode ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
            @click.stop
          >
            <input
              type="checkbox"
              :checked="selectedVideos.includes(video.id)"
              @change="toggleVideoSelection(video.id)"
              class="w-5 h-5 text-orange-600 bg-white/90 border-2 border-gray-300 rounded focus:ring-orange-500 focus:ring-2 cursor-pointer shadow-sm"
            />
          </div>

          <!-- Hover Overlay -->
          <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-200 flex flex-col justify-between p-3">
            <!-- Top Actions -->
            <div class="flex justify-between items-start">
              <!-- Spacer for checkbox position -->
              <div class="w-5 h-5"></div>
              <div class="flex gap-2">
                <button @click.stop="shareVideo(video)" class="p-1.5 bg-white text-gray-700 hover:text-blue-600 rounded-lg shadow-sm transition-colors" title="Copy Link">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                  </svg>
                </button>
                <button @click.stop="downloadVideo(video)" class="p-1.5 bg-white text-gray-700 hover:text-green-600 rounded-lg shadow-sm transition-colors" title="Download">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                </button>
                <!-- More Options Dropdown -->
                <div class="relative" @click.stop>
                  <button
                    @click="toggleVideoMenu(video.id)"
                    class="p-1.5 bg-white text-gray-700 hover:text-gray-900 rounded-lg shadow-sm transition-colors"
                    title="More options"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                  </button>
                  <div
                    v-show="activeVideoMenu === video.id"
                    class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                  >
                    <button
                      @click="toggleFavorite(video); activeVideoMenu = null"
                      class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                    >
                      <svg class="w-4 h-4 text-orange-500" :fill="video.is_favourite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                      </svg>
                      {{ video.is_favourite ? 'Remove from Favourites' : 'Add to Favourites' }}
                    </button>
                    <button
                      @click="openSinglePlaylistModal(video)"
                      class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                    >
                      <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                      </svg>
                      Add to Playlist
                    </button>
                    <div class="border-t border-gray-100 my-1"></div>
                    <button
                      @click="deleteVideo(video); activeVideoMenu = null"
                      class="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Center Play Button (hidden when video is selected) -->
            <div v-if="!selectedVideos.includes(video.id)" class="flex justify-center">
              <div class="w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg text-orange-600 scale-90 hover:scale-110 transition-transform">
                <svg class="w-4 h-4 ml-0.5 fill-current" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
                </svg>
              </div>
            </div>
            <!-- Empty spacer when video is selected -->
            <div v-else class="flex-1"></div>

            <!-- Spacer for bottom -->
            <div class="h-8"></div>
          </div>
        </div>

        <!-- Video Info -->
        <div class="px-1">
          <div class="flex justify-between items-start gap-2 mb-1">
            <h3
              class="font-medium text-gray-900 text-[14px] leading-snug truncate group-hover:text-orange-600 transition-colors cursor-pointer"
              @click="handleVideoClick(video.id)"
            >
              {{ video.title }}
            </h3>
          </div>
          <div class="flex items-center gap-2 text-[12px] text-gray-500">
            <span>{{ formatDate(video.createdAt) }}</span>
            <span class="w-0.5 h-0.5 bg-gray-300 rounded-full"></span>
            <span class="flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              {{ video.views_count || 0 }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Videos List View -->
    <div v-else-if="filteredVideos.length > 0 && viewMode === 'list'" class="space-y-3">
      <div
        v-for="video in paginatedVideos"
        :key="video.id"
        class="group cursor-pointer flex items-center gap-4 p-3 bg-white border rounded-xl hover:shadow-md transition-all duration-200"
        :class="selectedVideos.includes(video.id)
          ? 'border-orange-300 bg-orange-50'
          : 'border-gray-200 hover:border-orange-200'"
        @click="handleVideoClick(video.id)"
      >
        <!-- Selection Checkbox (shown on hover, when selected, or when in selection mode) -->
        <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" :class="{ 'opacity-100': selectedVideos.includes(video.id) || isSelectionMode }">
          <input
            type="checkbox"
            :checked="selectedVideos.includes(video.id)"
            @change="toggleVideoSelection(video.id)"
            @click.stop
            class="w-5 h-5 text-orange-600 bg-white border-2 border-gray-300 rounded focus:ring-orange-500 focus:ring-2 cursor-pointer"
          />
        </div>

        <!-- Thumbnail -->
        <div class="relative w-40 flex-shrink-0 aspect-video rounded-lg overflow-hidden bg-gray-900" style="aspect-ratio: 16 / 9;">
          <img
            v-if="video.thumbnail"
            :src="video.thumbnail"
            :alt="video.title"
            class="absolute inset-0 w-full h-full object-cover"
            loading="lazy"
          />
          <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>
          <div class="absolute bottom-1.5 right-1.5 bg-black/70 text-white text-[10px] px-1.5 py-0.5 rounded font-medium">
            {{ formatDuration(video.duration) }}
          </div>
        </div>

        <!-- Video Info -->
        <div class="flex-1 min-w-0">
          <h3 class="font-medium text-gray-900 group-hover:text-orange-600 transition-colors truncate text-[15px] mb-1">
            {{ video.title }}
          </h3>
          <div class="flex items-center gap-3 text-[12px] text-gray-500">
            <span>{{ formatDate(video.createdAt) }}</span>
            <div class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <span>{{ video.views_count || 0 }}</span>
            </div>
            <div class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
              </svg>
              <span>{{ video.comments_count || 0 }}</span>
            </div>
            <div class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
              </svg>
              <span>{{ video.reactions_count || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button @click.stop="shareVideo(video)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Copy Link">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
          </button>
          <button @click.stop="embedVideo(video)" class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Embed">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
          </button>
          <button @click.stop="downloadVideo(video)" class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Download">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
          </button>
          <!-- More Options Dropdown -->
          <div class="relative" @click.stop>
            <button
              @click="toggleVideoMenu(video.id)"
              class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-50 rounded-lg transition-colors"
              title="More options"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
              </svg>
            </button>
            <div
              v-show="activeVideoMenu === video.id"
              class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
            >
              <button
                @click="toggleFavorite(video); activeVideoMenu = null"
                class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
              >
                <svg class="w-4 h-4 text-orange-500" :fill="video.is_favourite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                {{ video.is_favourite ? 'Remove from Favourites' : 'Add to Favourites' }}
              </button>
              <button
                @click="openSinglePlaylistModal(video)"
                class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
              >
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Add to Playlist
              </button>
              <div class="border-t border-gray-100 my-1"></div>
              <button
                @click="deleteVideo(video); activeVideoMenu = null"
                class="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="showPagination && filteredVideos.length > 0" class="mt-10 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-200 pt-6">
      <div class="text-xs text-gray-500">
        Showing <span class="font-medium text-gray-900">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to
        <span class="font-medium text-gray-900">{{ Math.min(currentPage * itemsPerPage, filteredVideos.length) }}</span> of
        <span class="font-medium text-gray-900">{{ filteredVideos.length }}</span> videos
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="prevPage"
          :disabled="currentPage === 1"
          class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors"
          :class="currentPage === 1
            ? 'border-gray-200 text-gray-300 cursor-not-allowed bg-gray-50'
            : 'border-gray-200 text-gray-700 hover:bg-gray-50 bg-white'"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
          Previous
        </button>

        <div class="flex items-center gap-1">
          <template v-for="page in pageNumbers" :key="page">
            <span v-if="page === '...'" class="px-2 py-1 text-gray-400">...</span>
            <button
              v-else
              @click="goToPage(page)"
              class="w-8 h-8 text-sm font-medium rounded-lg transition-colors"
              :class="page === currentPage
                ? 'bg-gray-900 text-white'
                : 'text-gray-700 hover:bg-gray-100'"
            >
              {{ page }}
            </button>
          </template>
        </div>

        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors"
          :class="currentPage === totalPages
            ? 'border-gray-200 text-gray-300 cursor-not-allowed bg-gray-50'
            : 'border-gray-200 text-gray-700 hover:bg-gray-50 bg-white'"
        >
          Next
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading && filteredVideos.length === 0" class="text-center py-20">
      <div class="w-16 h-16 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
        <svg v-if="activeTab === 'favourites'" class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
        </svg>
        <svg v-else class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
      </div>
      <h3 class="text-gray-900 font-medium mb-1">
        {{ activeTab === 'favourites' ? 'No favourites yet' : (videos.length === 0 ? 'No recordings yet' : 'No videos match your filter') }}
      </h3>
      <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
        {{ activeTab === 'favourites' ? 'Mark videos as favourites to see them here.' : (videos.length === 0 ? 'Start by recording your first screen capture. It only takes a few seconds.' : 'Try adjusting your date filter to see more videos.') }}
      </p>
      <button
        v-if="activeTab === 'favourites'"
        @click="activeTab = 'videos'"
        class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg font-medium text-sm shadow-sm transition-colors"
      >
        Browse Videos
      </button>
      <button
        v-else-if="videos.length === 0"
        @click="goToRecord"
        class="inline-flex items-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm shadow-sm transition-colors"
        :disabled="!canRecord"
        :class="{ 'opacity-50 cursor-not-allowed': !canRecord }"
      >
        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <circle cx="10" cy="10" r="6"/>
        </svg>
        Record Your First Video
      </button>
      <button
        v-else
        @click="clearFilters"
        class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg font-medium text-sm shadow-sm transition-colors"
      >
        Clear Filters
      </button>
    </div>

    <!-- Delete Video Modal -->
    <SBDeleteModal
      v-model="showDeleteModal"
      title="Delete Video Permanently"
      :message="deleteVideoMessage"
      :loading="isDeleting"
      @confirm="confirmDeleteVideo"
      @cancel="showDeleteModal = false"
    />

    <!-- Bulk Delete Modal -->
    <SBDeleteModal
      v-model="showBulkDeleteModal"
      title="Delete Videos Permanently"
      :message="bulkDeleteMessage"
      :loading="isBulkDeleting"
      @confirm="confirmBulkDelete"
      @cancel="showBulkDeleteModal = false"
    />

    <!-- Upgrade Modal -->
    <SBUpgradeModal
      :show="showUpgradeModal"
      @close="showUpgradeModal = false"
      @success="handleUpgradeSuccess"
    />

    <!-- Add to Playlist Modal -->
    <SBModal
      v-model="showPlaylistModal"
      :title="playlistModalTitle"
      size="md"
      @close="closePlaylistModal"
    >
      <div v-if="loadingPlaylists" class="py-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-orange-600 border-t-transparent"></div>
        <p class="mt-3 text-sm text-gray-500">Loading playlists...</p>
      </div>
      <div v-else-if="playlists.length === 0" class="py-8 text-center">
        <div class="w-12 h-12 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
          <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
          </svg>
        </div>
        <p class="text-gray-600 font-medium">No playlists yet</p>
        <p class="text-sm text-gray-500 mt-1">Create a playlist first to add videos.</p>
      </div>
      <div v-else class="space-y-2 max-h-80 overflow-y-auto">
        <button
          v-for="playlist in playlists"
          :key="playlist.id"
          @click="addVideosToPlaylist(playlist)"
          :disabled="addingToPlaylist"
          class="w-full p-3 text-left rounded-lg border border-gray-200 hover:border-orange-300 hover:bg-orange-50 transition-colors flex items-center justify-between group"
        >
          <div>
            <p class="font-medium text-gray-900 group-hover:text-orange-600">{{ playlist.title }}</p>
            <p class="text-sm text-gray-500">{{ playlist.videos_count }} video{{ playlist.videos_count !== 1 ? 's' : '' }}</p>
          </div>
          <svg class="w-5 h-5 text-gray-400 group-hover:text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
        </button>
      </div>
      <template #footer>
        <div class="flex justify-end">
          <button
            @click="closePlaylistModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
        </div>
      </template>
    </SBModal>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useAuth } from '@/stores/auth'
import { useRecording } from '@/composables/useRecording'
import videoService from '@/services/videoService'
import playlistService from '@/services/playlistService'
import toast from '@/services/toastService'
import SBDeleteModal from '@/components/Global/SBDeleteModal.vue'
import SBUpgradeModal from '@/components/Global/SBUpgradeModal.vue'
import SBModal from '@/components/Global/SBModal.vue'

export default {
  name: 'VideosView',
  components: {
    SBDeleteModal,
    SBUpgradeModal,
    SBModal
  },
  setup() {
    const auth = useAuth()
    const recording = useRecording()
    const currentUser = computed(() => auth.user.value)
    const userInitial = computed(() => (currentUser.value?.name || 'U').charAt(0).toUpperCase())

    // Subscription state
    const subscription = computed(() => auth.subscription.value)
    const canRecord = computed(() => {
      if (!subscription.value) return true // Allow if not loaded yet
      return subscription.value.can_record
    })

    const videos = ref([])
    const viewMode = ref('grid')
    const loading = ref(false)
    const error = ref(null)
    const activeTab = ref('videos')

    // Delete modal state
    const showDeleteModal = ref(false)
    const videoToDelete = ref(null)
    const isDeleting = ref(false)

    // Selection state
    const selectedVideos = ref([])
    const showBulkDeleteModal = ref(false)
    const isBulkDeleting = ref(false)

    // Bulk actions dropdown
    const showBulkActionsDropdown = ref(false)
    const bulkActionsDropdownRef = ref(null)

    // Video menu state (for individual video more options)
    const activeVideoMenu = ref(null)

    // Playlist modal state
    const showPlaylistModal = ref(false)
    const playlists = ref([])
    const loadingPlaylists = ref(false)
    const addingToPlaylist = ref(false)
    const videosToAddToPlaylist = ref([])
    const singleVideoForPlaylist = ref(null)

    // Upgrade modal state
    const showUpgradeModal = ref(false)

    // Sort state
    const sortBy = ref('date_desc')
    const showSortDropdown = ref(false)
    const sortDropdownRef = ref(null)

    // Filter dropdown state
    const showFilterDropdown = ref(false)
    const filterDropdownRef = ref(null)

    const sortOptions = [
      { id: 'date_desc', label: 'Newest First' },
      { id: 'date_asc', label: 'Oldest First' },
      { id: 'title_asc', label: 'Title A-Z' },
      { id: 'title_desc', label: 'Title Z-A' },
      { id: 'duration_desc', label: 'Longest First' },
      { id: 'duration_asc', label: 'Shortest First' },
      { id: 'views_desc', label: 'Most Viewed' },
      { id: 'reactions_desc', label: 'Most Reactions' }
    ]

    const currentSortLabel = computed(() => {
      const option = sortOptions.find(o => o.id === sortBy.value)
      return option ? option.label : 'Newest First'
    })

    // Date filter state
    const activeDateFilter = ref('all')
    const customDateFrom = ref('')
    const customDateTo = ref('')

    const dateFilters = [
      { id: 'all', label: 'All Time' },
      { id: 'today', label: 'Today' },
      { id: 'yesterday', label: 'Yesterday' },
      { id: 'week', label: 'This Week' },
      { id: 'month', label: 'This Month' },
      { id: 'custom', label: 'Custom Range' }
    ]

    // Pagination
    const currentPage = ref(1)
    const itemsPerPage = 20

    // Filtered and sorted videos
    const filteredVideos = computed(() => {
      let result = [...videos.value]

      // Apply tab filter (favourites)
      if (activeTab.value === 'favourites') {
        result = result.filter(v => v.is_favourite)
      }

      // Apply date filter
      const now = new Date()
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      const yesterday = new Date(today)
      yesterday.setDate(yesterday.getDate() - 1)

      if (activeDateFilter.value === 'today') {
        result = result.filter(v => v.createdAt >= today)
      } else if (activeDateFilter.value === 'yesterday') {
        result = result.filter(v => v.createdAt >= yesterday && v.createdAt < today)
      } else if (activeDateFilter.value === 'week') {
        const weekAgo = new Date(today)
        weekAgo.setDate(weekAgo.getDate() - 7)
        result = result.filter(v => v.createdAt >= weekAgo)
      } else if (activeDateFilter.value === 'month') {
        const monthAgo = new Date(today)
        monthAgo.setMonth(monthAgo.getMonth() - 1)
        result = result.filter(v => v.createdAt >= monthAgo)
      } else if (activeDateFilter.value === 'custom' && customDateFrom.value && customDateTo.value) {
        const from = new Date(customDateFrom.value)
        const to = new Date(customDateTo.value)
        to.setHours(23, 59, 59, 999)
        result = result.filter(v => v.createdAt >= from && v.createdAt <= to)
      }

      // Apply sorting
      result.sort((a, b) => {
        switch (sortBy.value) {
          case 'date_desc':
            return b.createdAt - a.createdAt
          case 'date_asc':
            return a.createdAt - b.createdAt
          case 'title_asc':
            return a.title.localeCompare(b.title)
          case 'title_desc':
            return b.title.localeCompare(a.title)
          case 'duration_desc':
            return (b.duration || 0) - (a.duration || 0)
          case 'duration_asc':
            return (a.duration || 0) - (b.duration || 0)
          case 'views_desc':
            return (b.views_count || 0) - (a.views_count || 0)
          case 'reactions_desc':
            return (b.reactions_count || 0) - (a.reactions_count || 0)
          default:
            return b.createdAt - a.createdAt
        }
      })

      return result
    })

    // Reset page when filters or tab change
    watch([activeDateFilter, customDateFrom, customDateTo, sortBy, activeTab], () => {
      currentPage.value = 1
    })

    const totalPages = computed(() => Math.ceil(filteredVideos.value.length / itemsPerPage))

    const paginatedVideos = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage
      const end = start + itemsPerPage
      return filteredVideos.value.slice(start, end)
    })

    const showPagination = computed(() => filteredVideos.value.length > itemsPerPage)

    // Selection computed properties
    const isAllSelected = computed(() => {
      return filteredVideos.value.length > 0 &&
        filteredVideos.value.every(v => selectedVideos.value.includes(v.id))
    })

    const isPartiallySelected = computed(() => {
      return selectedVideos.value.length > 0 && !isAllSelected.value
    })

    // Selection mode - when at least one video is selected
    const isSelectionMode = computed(() => selectedVideos.value.length > 0)

    // Delete message computed properties
    const deleteVideoMessage = computed(() => {
      return `Are you sure you want to delete '${videoToDelete.value?.title}'? This action is PERMANENT. The video will be removed from our storage and cannot be recovered.`
    })

    const bulkDeleteMessage = computed(() => {
      const count = selectedVideos.value.length
      return `Are you sure you want to delete ${count} video${count > 1 ? 's' : ''}? This action is PERMANENT. These videos will be removed from our storage and Bunny CDN. They cannot be recovered.`
    })

    const playlistModalTitle = computed(() => {
      if (singleVideoForPlaylist.value) {
        return 'Add to Playlist'
      }
      const count = videosToAddToPlaylist.value.length
      return `Add ${count} Video${count > 1 ? 's' : ''} to Playlist`
    })

    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const nextPage = () => {
      if (currentPage.value < totalPages.value) {
        goToPage(currentPage.value + 1)
      }
    }

    const prevPage = () => {
      if (currentPage.value > 1) {
        goToPage(currentPage.value - 1)
      }
    }

    const pageNumbers = computed(() => {
      const pages = []
      const total = totalPages.value
      const current = currentPage.value

      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        pages.push(1)
        if (current > 3) pages.push('...')
        for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
          pages.push(i)
        }
        if (current < total - 2) pages.push('...')
        pages.push(total)
      }

      return pages
    })

    const setDateFilter = (filterId) => {
      activeDateFilter.value = filterId
      if (filterId !== 'custom') {
        customDateFrom.value = ''
        customDateTo.value = ''
      }
    }

    const setSortOption = (optionId) => {
      sortBy.value = optionId
      showSortDropdown.value = false
    }

    const clearFilters = () => {
      activeDateFilter.value = 'all'
      customDateFrom.value = ''
      customDateTo.value = ''
      sortBy.value = 'date_desc'
    }

    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (sortDropdownRef.value && !sortDropdownRef.value.contains(event.target)) {
        showSortDropdown.value = false
      }
      if (filterDropdownRef.value && !filterDropdownRef.value.contains(event.target)) {
        showFilterDropdown.value = false
      }
      if (bulkActionsDropdownRef.value && !bulkActionsDropdownRef.value.contains(event.target)) {
        showBulkActionsDropdown.value = false
      }
      // Close video menu if click is outside
      if (activeVideoMenu.value !== null) {
        activeVideoMenu.value = null
      }
    }

    const fetchVideos = async () => {
      loading.value = true
      currentPage.value = 1
      error.value = null

      try {
        const fetchedVideos = await videoService.getVideos()
        videos.value = fetchedVideos.map(video => ({
          id: video.id,
          title: video.title,
          duration: video.duration,
          createdAt: new Date(video.created_at),
          thumbnail: video.thumbnail || null,
          views_count: video.views_count || 0,
          comments_count: video.comments_count || 0,
          reactions_count: video.reactions_count || 0,
          url: video.url,
          isPublic: video.is_public,
          is_favourite: video.is_favourite || false,
          shareUrl: video.share_url
        }))
      } catch (err) {
        console.error('Failed to fetch videos:', err)
        error.value = 'Failed to load videos. Please try again.'
        videos.value = []
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      fetchVideos()
      auth.fetchSubscription() // Fetch subscription status
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    const goToRecord = () => {
      // Check if user can record
      if (!canRecord.value) {
        showUpgradeModal.value = true
        return
      }
      // Open the new recording setup panel
      recording.openSetupPanel()
    }

    const openVideo = (id) => {
      window.location.href = `/video/${id}`
    }

    // Handle video click - select if in selection mode, otherwise open video
    const handleVideoClick = (id) => {
      if (isSelectionMode.value) {
        toggleVideoSelection(id)
      } else {
        openVideo(id)
      }
    }

    const shareVideo = async (video) => {
      if (video.shareUrl) {
        try {
          await navigator.clipboard.writeText(video.shareUrl)
          toast.success('Link copied to clipboard!')
        } catch (err) {
          console.error('Failed to copy:', err)
          toast.error('Failed to copy link. Please try again.')
        }
      }
    }

    const embedVideo = async (video) => {
      if (video.shareUrl) {
        try {
          // Derive embed URL from share URL by replacing /share/ with /embed/
          const embedUrl = video.shareUrl.replace('/share/video/', '/embed/video/')
          const embedCode = `<iframe src="${embedUrl}" width="640" height="360" frameborder="0" allowfullscreen></iframe>`
          await navigator.clipboard.writeText(embedCode)
          toast.success('Embed code copied to clipboard!')
        } catch (err) {
          console.error('Failed to copy embed code:', err)
          toast.error('Failed to copy embed code. Please try again.')
        }
      }
    }

    const downloadVideo = async (video) => {
      if (!video.url) return

      try {
        toast.success('Starting download...')

        // Fetch the video as a blob
        const response = await fetch(video.url)
        const blob = await response.blob()

        // Create a blob URL and trigger download
        const blobUrl = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = blobUrl
        link.download = `${video.title || 'video'}.webm`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        // Clean up the blob URL
        window.URL.revokeObjectURL(blobUrl)

        toast.success('Download complete!')
      } catch (err) {
        console.error('Failed to download:', err)
        toast.error('Failed to download video. Please try again.')
      }
    }

    const deleteVideo = (video) => {
      videoToDelete.value = video
      showDeleteModal.value = true
    }

    const confirmDeleteVideo = async () => {
      if (!videoToDelete.value) return

      isDeleting.value = true
      try {
        await videoService.deleteVideo(videoToDelete.value.id)
        videos.value = videos.value.filter(v => v.id !== videoToDelete.value.id)
        toast.success('Video deleted permanently!')
        showDeleteModal.value = false
        videoToDelete.value = null
      } catch (err) {
        console.error('Failed to delete video:', err)
        toast.error('Failed to delete video. Please try again.')
      } finally {
        isDeleting.value = false
      }
    }

    // Selection methods
    const clearSelection = () => {
      selectedVideos.value = []
    }

    const toggleVideoSelection = (videoId) => {
      const index = selectedVideos.value.indexOf(videoId)
      if (index === -1) {
        selectedVideos.value.push(videoId)
      } else {
        selectedVideos.value.splice(index, 1)
      }
    }

    const toggleSelectAll = () => {
      if (isAllSelected.value) {
        selectedVideos.value = []
      } else {
        selectedVideos.value = filteredVideos.value.map(v => v.id)
      }
    }

    const confirmBulkDelete = async () => {
      if (selectedVideos.value.length === 0) return

      isBulkDeleting.value = true
      try {
        const result = await videoService.bulkDeleteVideos(selectedVideos.value)

        // Remove deleted videos from local state
        videos.value = videos.value.filter(v => !selectedVideos.value.includes(v.id))

        // Show appropriate message
        if (result.failed > 0) {
          toast.warning(`${result.deleted} video(s) deleted. ${result.failed} failed.`)
        } else {
          toast.success(result.message)
        }

        // Reset selection
        showBulkDeleteModal.value = false
        selectedVideos.value = []
      } catch (err) {
        console.error('Failed to bulk delete videos:', err)
        toast.error('Failed to delete videos. Please try again.')
      } finally {
        isBulkDeleting.value = false
      }
    }

    // Video menu methods
    const toggleVideoMenu = (videoId) => {
      activeVideoMenu.value = activeVideoMenu.value === videoId ? null : videoId
    }

    // Bulk add to favourites
    const bulkAddToFavourites = async () => {
      if (selectedVideos.value.length === 0) return

      showBulkActionsDropdown.value = false

      try {
        const result = await videoService.bulkAddToFavourites(selectedVideos.value)

        // Update local state
        videos.value.forEach(video => {
          if (selectedVideos.value.includes(video.id)) {
            video.is_favourite = true
          }
        })

        toast.success(result.message)

        // Reset selection
        selectedVideos.value = []
      } catch (err) {
        console.error('Failed to add to favourites:', err)
        toast.error('Failed to add videos to favourites. Please try again.')
      }
    }

    // Playlist methods
    const fetchPlaylists = async () => {
      loadingPlaylists.value = true
      try {
        playlists.value = await playlistService.getPlaylists()
      } catch (err) {
        console.error('Failed to fetch playlists:', err)
        toast.error('Failed to load playlists.')
        playlists.value = []
      } finally {
        loadingPlaylists.value = false
      }
    }

    const openPlaylistModal = () => {
      showBulkActionsDropdown.value = false
      videosToAddToPlaylist.value = [...selectedVideos.value]
      singleVideoForPlaylist.value = null
      showPlaylistModal.value = true
      fetchPlaylists()
    }

    const openSinglePlaylistModal = (video) => {
      activeVideoMenu.value = null
      singleVideoForPlaylist.value = video
      videosToAddToPlaylist.value = [video.id]
      showPlaylistModal.value = true
      fetchPlaylists()
    }

    const closePlaylistModal = () => {
      showPlaylistModal.value = false
      videosToAddToPlaylist.value = []
      singleVideoForPlaylist.value = null
    }

    const addVideosToPlaylist = async (playlist) => {
      if (videosToAddToPlaylist.value.length === 0) return

      addingToPlaylist.value = true
      try {
        const result = await playlistService.bulkAddVideos(playlist.id, videosToAddToPlaylist.value)

        toast.success(result.message)
        closePlaylistModal()

        // Reset selection if adding from bulk selection
        if (videosToAddToPlaylist.value.length > 1 || !singleVideoForPlaylist.value) {
          selectedVideos.value = []
        }
      } catch (err) {
        console.error('Failed to add to playlist:', err)
        toast.error(err.message || 'Failed to add videos to playlist.')
      } finally {
        addingToPlaylist.value = false
      }
    }

    const handleUpgradeSuccess = () => {
      showUpgradeModal.value = false
      auth.fetchSubscription() // Refresh subscription status
    }

    const openUpgradeModal = () => {
      showUpgradeModal.value = true
    }

    const toggleFavorite = async (video) => {
      try {
        const result = await videoService.toggleFavourite(video.id)
        // Update the video in the local array
        const videoIndex = videos.value.findIndex(v => v.id === video.id)
        if (videoIndex !== -1) {
          videos.value[videoIndex].is_favourite = result.is_favourite
        }
        toast.success(result.is_favourite ? 'Added to favourites!' : 'Removed from favourites!')
      } catch (err) {
        console.error('Failed to toggle favourite:', err)
        toast.error('Failed to update favourite. Please try again.')
      }
    }

    const formatDuration = (seconds) => {
      if (!seconds || isNaN(seconds)) return '0:00'
      const mins = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${mins}:${secs.toString().padStart(2, '0')}`
    }

    const formatDate = (date) => {
      const now = new Date()
      const diffTime = Math.abs(now - date)
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))

      if (diffDays === 0) return 'Today'
      if (diffDays === 1) return 'Yesterday'
      if (diffDays < 7) return `${diffDays} days ago`
      if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
      if (diffDays < 365) return `${Math.floor(diffDays / 30)} month${Math.floor(diffDays / 30) > 1 ? 's' : ''} ago`
      return `${Math.floor(diffDays / 365)} year${Math.floor(diffDays / 365) > 1 ? 's' : ''} ago`
    }

    return {
      currentUser,
      userInitial,
      subscription,
      canRecord,
      videos,
      filteredVideos,
      paginatedVideos,
      viewMode,
      loading,
      error,
      activeTab,
      // Sort
      sortBy,
      showSortDropdown,
      sortDropdownRef,
      sortOptions,
      currentSortLabel,
      setSortOption,
      // Filter
      showFilterDropdown,
      filterDropdownRef,
      // Date filter
      activeDateFilter,
      customDateFrom,
      customDateTo,
      dateFilters,
      setDateFilter,
      clearFilters,
      // Pagination
      currentPage,
      itemsPerPage,
      totalPages,
      showPagination,
      pageNumbers,
      goToPage,
      nextPage,
      prevPage,
      // Methods
      fetchVideos,
      goToRecord,
      openVideo,
      handleVideoClick,
      shareVideo,
      embedVideo,
      downloadVideo,
      deleteVideo,
      confirmDeleteVideo,
      toggleFavorite,
      formatDuration,
      formatDate,
      // Delete modal
      showDeleteModal,
      videoToDelete,
      isDeleting,
      // Selection and bulk actions
      selectedVideos,
      showBulkDeleteModal,
      isBulkDeleting,
      isAllSelected,
      isPartiallySelected,
      isSelectionMode,
      deleteVideoMessage,
      bulkDeleteMessage,
      clearSelection,
      toggleVideoSelection,
      toggleSelectAll,
      confirmBulkDelete,
      showBulkActionsDropdown,
      bulkActionsDropdownRef,
      bulkAddToFavourites,
      // Video menu
      activeVideoMenu,
      toggleVideoMenu,
      // Playlist modal
      showPlaylistModal,
      playlists,
      loadingPlaylists,
      addingToPlaylist,
      playlistModalTitle,
      openPlaylistModal,
      openSinglePlaylistModal,
      closePlaylistModal,
      addVideosToPlaylist,
      // Upgrade modal
      showUpgradeModal,
      handleUpgradeSuccess,
      openUpgradeModal
    }
  }
}
</script>

<style scoped>
/* Fade in animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
</style>
