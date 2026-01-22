<template>
  <div class="animate-fade-in max-w-7xl mx-auto p-6 lg:p-8">
    <!-- Library Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <!-- Tabs -->
      <div class="tabs tabs-boxed bg-base-200 self-start">
        <button
          @click="activeTab = 'videos'"
          class="tab tab-sm"
          :class="activeTab === 'videos' ? 'tab-active' : ''"
        >
          Videos
        </button>
        <button
          @click="activeTab = 'favourites'"
          class="tab tab-sm"
          :class="activeTab === 'favourites' ? 'tab-active' : ''"
        >
          <span class="flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            Favourites
          </span>
        </button>
        <button
          class="tab tab-sm"
        >
          Archived
        </button>
      </div>

      <div class="flex items-center gap-3">
        <!-- Sort Dropdown -->
        <div class="dropdown dropdown-end" ref="sortDropdownRef">
          <label
            tabindex="0"
            @click="showSortDropdown = !showSortDropdown"
            class="btn btn-sm btn-ghost gap-2"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
            {{ currentSortLabel }}
          </label>

          <!-- Sort Dropdown Menu -->
          <ul
            v-show="showSortDropdown"
            tabindex="0"
            class="dropdown-content menu menu-sm bg-base-100 rounded-box z-50 w-48 p-2 shadow-lg border border-base-300"
          >
            <li v-for="option in sortOptions" :key="option.id">
              <a
                @click="setSortOption(option.id)"
                :class="sortBy === option.id ? 'active' : ''"
              >
                {{ option.label }}
                <svg v-if="sortBy === option.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              </a>
            </li>
          </ul>
        </div>

        <!-- Filter Dropdown -->
        <div class="dropdown dropdown-end" ref="filterDropdownRef">
          <label
            tabindex="0"
            @click="showFilterDropdown = !showFilterDropdown"
            class="btn btn-sm gap-2"
            :class="activeDateFilter !== 'all' ? 'btn-primary' : 'btn-ghost'"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            Filter
            <span v-if="activeDateFilter !== 'all'" class="badge badge-xs badge-secondary"></span>
          </label>

          <!-- Filter Dropdown Menu -->
          <div
            v-show="showFilterDropdown"
            tabindex="0"
            class="dropdown-content bg-base-100 rounded-box z-50 w-56 p-2 shadow-lg border border-base-300"
          >
            <div class="px-3 pb-2 mb-2 border-b border-base-200">
              <span class="text-xs font-semibold text-base-content/60 uppercase tracking-wide">Date Range</span>
            </div>
            <ul class="menu menu-sm p-0">
              <li v-for="filter in dateFilters" :key="filter.id">
                <a
                  @click="setDateFilter(filter.id); showFilterDropdown = false"
                  :class="activeDateFilter === filter.id ? 'active' : ''"
                >
                  {{ filter.label }}
                  <svg v-if="activeDateFilter === filter.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </a>
              </li>
            </ul>

            <!-- Custom Date Picker -->
            <div v-if="activeDateFilter === 'custom'" class="px-3 pt-2 mt-2 border-t border-base-200 space-y-2">
              <div class="form-control">
                <label class="label py-1">
                  <span class="label-text text-xs">From</span>
                </label>
                <input
                  type="date"
                  v-model="customDateFrom"
                  class="input input-bordered input-sm w-full"
                />
              </div>
              <div class="form-control">
                <label class="label py-1">
                  <span class="label-text text-xs">To</span>
                </label>
                <input
                  type="date"
                  v-model="customDateTo"
                  class="input input-bordered input-sm w-full"
                />
              </div>
            </div>

            <!-- Clear Filters -->
            <div v-if="activeDateFilter !== 'all'" class="px-3 pt-2 mt-2 border-t border-base-200">
              <button
                @click="clearFilters(); showFilterDropdown = false"
                class="btn btn-ghost btn-sm w-full text-primary"
              >
                Clear Filters
              </button>
            </div>
          </div>
        </div>

        <!-- View Toggle -->
        <div class="join border border-base-300 bg-base-100 shadow-sm">
          <button
            @click="viewMode = 'grid'"
            class="join-item btn btn-sm btn-ghost"
            :class="viewMode === 'grid' ? 'btn-active' : ''"
            title="Grid view"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
          </button>
          <button
            @click="viewMode = 'list'"
            class="join-item btn btn-sm btn-ghost"
            :class="viewMode === 'list' ? 'btn-active' : ''"
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
      <span class="loading loading-spinner loading-lg text-primary"></span>
      <p class="mt-4 text-sm text-base-content/60">Loading videos...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-24">
      <div class="w-16 h-16 mx-auto mb-6 bg-error/10 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-base-content mb-2">{{ error }}</h3>
      <button
        @click="fetchVideos"
        class="btn btn-neutral"
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
          class="relative aspect-video bg-neutral rounded-xl overflow-hidden mb-3 border border-base-300 shadow-sm group-hover:shadow-md transition-all cursor-pointer"
          style="aspect-ratio: 16 / 9;"
          @click="openVideo(video.id)"
        >
          <img
            v-if="video.thumbnail"
            :src="video.thumbnail"
            :alt="video.title"
            class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 group-hover:opacity-100 transition-all duration-500"
            loading="lazy"
          />
          <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-neutral to-neutral-focus">
            <svg class="w-10 h-10 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>

          <!-- Duration Badge -->
          <div class="badge badge-neutral badge-sm absolute bottom-2 right-2 z-10">
            {{ formatDuration(video.duration) }}
          </div>

          <!-- Hover Overlay -->
          <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-200 flex flex-col justify-between p-3">
            <!-- Top Actions -->
            <div class="flex justify-between items-start">
              <button
                @click.stop="toggleFavorite(video)"
                class="btn btn-circle btn-sm"
                :class="video.is_favourite ? 'btn-primary' : 'btn-ghost bg-black/50 hover:bg-primary'"
                :title="video.is_favourite ? 'Remove from favourites' : 'Add to favourites'"
              >
                <svg class="w-3.5 h-3.5" :fill="video.is_favourite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
              </button>
              <div class="flex gap-2">
                <button @click.stop="shareVideo(video)" class="btn btn-circle btn-sm bg-base-100 hover:text-info" title="Copy Link">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                  </svg>
                </button>
                <button @click.stop="downloadVideo(video)" class="btn btn-circle btn-sm bg-base-100 hover:text-success" title="Download">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                </button>
                <button @click.stop="deleteVideo(video)" class="btn btn-circle btn-sm bg-base-100 hover:text-error" title="Delete">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Center Play Button -->
            <div class="flex justify-center">
              <div class="w-10 h-10 bg-base-100/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg text-primary scale-90 hover:scale-110 transition-transform">
                <svg class="w-4 h-4 ml-0.5 fill-current" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
                </svg>
              </div>
            </div>

            <!-- Spacer for bottom -->
            <div class="h-8"></div>
          </div>
        </div>

        <!-- Video Info -->
        <div class="px-1">
          <div class="flex justify-between items-start gap-2 mb-1">
            <h3
              class="font-medium text-base-content text-[14px] leading-snug truncate group-hover:text-primary transition-colors cursor-pointer"
              @click="openVideo(video.id)"
            >
              {{ video.title }}
            </h3>
          </div>
          <div class="flex items-center gap-2 text-[12px] text-base-content/60">
            <span>{{ formatDate(video.createdAt) }}</span>
            <span class="w-0.5 h-0.5 bg-base-content/30 rounded-full"></span>
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
        class="group cursor-pointer flex items-center gap-4 p-3 card bg-base-100 border border-base-300 hover:border-primary/30 hover:shadow-md transition-all duration-200"
        @click="openVideo(video.id)"
      >
        <!-- Thumbnail -->
        <div class="relative w-40 flex-shrink-0 aspect-video rounded-lg overflow-hidden bg-neutral" style="aspect-ratio: 16 / 9;">
          <img
            v-if="video.thumbnail"
            :src="video.thumbnail"
            :alt="video.title"
            class="absolute inset-0 w-full h-full object-cover"
            loading="lazy"
          />
          <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-neutral to-neutral-focus">
            <svg class="w-6 h-6 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>
          <div class="badge badge-neutral badge-xs absolute bottom-1.5 right-1.5">
            {{ formatDuration(video.duration) }}
          </div>
        </div>

        <!-- Video Info -->
        <div class="flex-1 min-w-0">
          <h3 class="font-medium text-base-content group-hover:text-primary transition-colors truncate text-[15px] mb-1">
            {{ video.title }}
          </h3>
          <div class="flex items-center gap-3 text-[12px] text-base-content/60">
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
          <button @click.stop="shareVideo(video)" class="btn btn-ghost btn-sm btn-circle hover:text-info" title="Copy Link">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
          </button>
          <button @click.stop="embedVideo(video)" class="btn btn-ghost btn-sm btn-circle hover:text-secondary" title="Embed">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
          </button>
          <button @click.stop="downloadVideo(video)" class="btn btn-ghost btn-sm btn-circle hover:text-success" title="Download">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
          </button>
          <button @click.stop="deleteVideo(video)" class="btn btn-ghost btn-sm btn-circle hover:text-error" title="Delete">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="showPagination && filteredVideos.length > 0" class="mt-10 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-base-300 pt-6">
      <div class="text-xs text-base-content/60">
        Showing <span class="font-medium text-base-content">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to
        <span class="font-medium text-base-content">{{ Math.min(currentPage * itemsPerPage, filteredVideos.length) }}</span> of
        <span class="font-medium text-base-content">{{ filteredVideos.length }}</span> videos
      </div>

      <div class="join">
        <button
          @click="prevPage"
          :disabled="currentPage === 1"
          class="join-item btn btn-sm"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
          Previous
        </button>

        <template v-for="page in pageNumbers" :key="page">
          <span v-if="page === '...'" class="join-item btn btn-sm btn-disabled">...</span>
          <button
            v-else
            @click="goToPage(page)"
            class="join-item btn btn-sm"
            :class="page === currentPage ? 'btn-active' : ''"
          >
            {{ page }}
          </button>
        </template>

        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="join-item btn btn-sm"
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
      <div class="w-16 h-16 mx-auto mb-6 bg-base-200 rounded-full flex items-center justify-center">
        <svg v-if="activeTab === 'favourites'" class="w-8 h-8 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
        </svg>
        <svg v-else class="w-8 h-8 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
      </div>
      <h3 class="text-base-content font-medium mb-1">
        {{ activeTab === 'favourites' ? 'No favourites yet' : (videos.length === 0 ? 'No recordings yet' : 'No videos match your filter') }}
      </h3>
      <p class="text-sm text-base-content/60 max-w-md mx-auto mb-6">
        {{ activeTab === 'favourites' ? 'Mark videos as favourites to see them here.' : (videos.length === 0 ? 'Start by recording your first screen capture. It only takes a few seconds.' : 'Try adjusting your date filter to see more videos.') }}
      </p>
      <button
        v-if="activeTab === 'favourites'"
        @click="activeTab = 'videos'"
        class="btn btn-outline"
      >
        Browse Videos
      </button>
      <button
        v-else-if="videos.length === 0"
        @click="goToRecord"
        class="btn btn-neutral"
        :disabled="!canRecord"
        :class="{ 'btn-disabled': !canRecord }"
      >
        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <circle cx="10" cy="10" r="6"/>
        </svg>
        Record Your First Video
      </button>
      <button
        v-else
        @click="clearFilters"
        class="btn btn-outline"
      >
        Clear Filters
      </button>
    </div>

    <!-- Delete Video Modal -->
    <SBDeleteModal
      v-model="showDeleteModal"
      title="Delete Video"
      :message="`Are you sure you want to delete '${videoToDelete?.title}'? This cannot be undone.`"
      :loading="isDeleting"
      @confirm="confirmDeleteVideo"
      @cancel="showDeleteModal = false"
    />

    <!-- Upgrade Modal -->
    <SBUpgradeModal
      :show="showUpgradeModal"
      @close="showUpgradeModal = false"
      @success="handleUpgradeSuccess"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useAuth } from '@/stores/auth'
import { useRecording } from '@/composables/useRecording'
import videoService from '@/services/videoService'
import toast from '@/services/toastService'
import SBDeleteModal from '@/components/Global/SBDeleteModal.vue'
import SBUpgradeModal from '@/components/Global/SBUpgradeModal.vue'

export default {
  name: 'VideosView',
  components: {
    SBDeleteModal,
    SBUpgradeModal
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
        toast.success('Video deleted successfully!')
        showDeleteModal.value = false
        videoToDelete.value = null
      } catch (err) {
        console.error('Failed to delete video:', err)
        toast.error('Failed to delete video. Please try again.')
      } finally {
        isDeleting.value = false
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
