<template>
  <div class="animate-fade-in">
    <!-- Action Bar -->
    <div class="flex items-center gap-2 mb-5">
      <!-- Upload Video Button -->
      <button
        v-if="activeTab !== 'screenshots'"
        @click="handleUpload"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-lg shadow-sm shadow-orange-200 transition-all"
        :disabled="uploading"
      >
        <svg v-if="!uploading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ uploading ? 'Uploading...' : 'Upload Video' }}
      </button>

      <!-- Upload Screenshot Button -->
      <button
        v-if="activeTab === 'screenshots'"
        @click="handleScreenshotUpload"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-lg shadow-sm shadow-orange-200 transition-all"
        :disabled="uploadingScreenshot"
      >
        <svg v-if="!uploadingScreenshot" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ uploadingScreenshot ? 'Uploading...' : 'Upload Screenshot' }}
      </button>

      <!-- Hidden file input for video upload -->
      <input ref="fileInput" type="file" accept="video/*" class="hidden" @change="onFileSelected" />
      <!-- Hidden file input for screenshot upload -->
      <input ref="screenshotFileInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onScreenshotFileSelected" />
    </div>

    <!-- Stats context line -->
    <div v-if="!loading && videos.length > 0 && activeTab === 'videos'" class="flex items-center gap-2 mb-4 text-sm">
      <span class="font-semibold text-gray-800">{{ videos.length }}</span>
      <span class="text-gray-400">recording{{ videos.length !== 1 ? 's' : '' }}</span>
      <template v-if="filteredVideos.length !== videos.length">
        <span class="text-gray-200">·</span>
        <span class="text-gray-500">{{ filteredVideos.length }} shown</span>
      </template>
    </div>

    <!-- Folders Section -->
    <div v-if="activeTab !== 'screenshots'" class="mb-5">
      <div class="flex flex-wrap gap-2 items-center">
        <div
          v-for="folder in folders"
          :key="folder.id"
          class="group relative flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-xl shadow-sm transition-all cursor-pointer select-none"
          :class="dragOverFolderId === folder.id
            ? 'border-orange-400 bg-orange-50 shadow-orange-100 scale-[1.02]'
            : 'hover:border-gray-300 hover:shadow-md hover:-translate-y-px'"
          @click="openFolder(folder)"
          @contextmenu.prevent="handleFolderContextMenu($event, folder)"
          @dragover.prevent="handleFolderDragOver($event, folder.id)"
          @dragleave="handleFolderDragLeave"
          @drop.prevent="handleDropOnFolder($event, folder)"
        >
          <!-- Folder icon -->
          <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="dragOverFolderId === folder.id ? 'text-orange-500' : 'text-amber-400'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
          </svg>
          <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 truncate max-w-[140px] transition-colors">{{ folder.name }}</span>
          <span class="text-[11px] font-medium text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded-full leading-none">{{ folder.videos_count }}</span>
          <!-- Three-dot menu -->
          <button
            @click.stop="handleFolderMenuClick($event, folder)"
            class="opacity-0 group-hover:opacity-100 transition-opacity p-0.5 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100"
            title="Folder options"
          >
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 16 16">
              <circle cx="8" cy="3" r="1.5"/>
              <circle cx="8" cy="8" r="1.5"/>
              <circle cx="8" cy="13" r="1.5"/>
            </svg>
          </button>
        </div>

        <!-- New Folder inline chip -->
        <button
          v-if="activeTab !== 'screenshots'"
          @click="showNewFolderModal = true"
          class="group flex items-center gap-1.5 px-3 py-2 bg-white border border-dashed border-gray-300 rounded-xl text-sm text-gray-400 hover:border-orange-300 hover:text-orange-500 hover:bg-orange-50 transition-all"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          <span class="font-medium">New folder</span>
        </button>
      </div>
    </div>

    <!-- Library Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
      <!-- Modern Segmented Tab Switch -->
      <div class="relative grid grid-cols-3 bg-gray-100 rounded-xl p-1 self-start" style="min-width: 280px;">
        <!-- Animated sliding pill -->
        <div
          class="absolute top-1 bottom-1 bg-white rounded-[10px] shadow-sm pointer-events-none transition-all duration-200 ease-out"
          :style="{ width: 'calc(33.33% - 3px)', left: `calc(${tabIndexMap[activeTab]} * 33.33% + 1px)` }"
        ></div>
        <!-- Videos tab -->
        <button
          @click="activeTab = 'videos'"
          class="relative z-10 flex items-center justify-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors duration-150 whitespace-nowrap"
          :class="activeTab === 'videos' ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700'"
        >
          Videos
          <span v-if="videos.length > 0" class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full leading-none" :class="activeTab === 'videos' ? 'bg-orange-100 text-orange-600' : 'bg-gray-200/80 text-gray-500'">{{ videos.length }}</span>
        </button>
        <!-- Starred/Favourites tab -->
        <button
          @click="activeTab = 'favourites'"
          class="relative z-10 flex items-center justify-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors duration-150 whitespace-nowrap"
          :class="activeTab === 'favourites' ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700'"
        >
          <svg class="w-3.5 h-3.5 flex-shrink-0" :fill="activeTab === 'favourites' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
          </svg>
          Starred
          <span v-if="favouriteCount > 0" class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full leading-none" :class="activeTab === 'favourites' ? 'bg-orange-100 text-orange-600' : 'bg-gray-200/80 text-gray-500'">{{ favouriteCount }}</span>
        </button>
        <!-- Screenshots tab -->
        <button
          @click="activeTab = 'screenshots'"
          class="relative z-10 flex items-center justify-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors duration-150 whitespace-nowrap"
          :class="activeTab === 'screenshots' ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700'"
        >
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          Shots
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
            <Transition name="dropdown">
              <div
                v-show="showBulkActionsDropdown"
                class="absolute right-0 mt-1.5 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1.5 z-50"
              >
                <button
                  @click="bulkAddToFavourites"
                  class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2.5 transition-colors"
                >
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                  </svg>
                  Add to Favourites
                </button>
                <button
                  v-if="folders.length > 0"
                  @click="openBulkMoveToFolderModal"
                  class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2.5 transition-colors"
                >
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                  </svg>
                  Move to Folder
                </button>
                <div class="h-px bg-gray-100 my-1"></div>
                <button
                  @click="showBulkDeleteModal = true; showBulkActionsDropdown = false"
                  class="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2.5 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  Delete
                </button>
              </div>
            </Transition>
          </div>
        </div>

        <!-- Select All (shown when videos are selected) -->
        <div v-if="selectedVideos.length > 0" class="flex items-center gap-2 border-l border-gray-100 pl-3">
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

        <!-- Search -->
        <div class="relative group">
          <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search..."
            class="pl-9 pr-8 py-1.5 text-sm font-medium bg-white border border-gray-200 focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 rounded-lg w-48 transition-all outline-none placeholder:text-gray-400 shadow-sm"
          />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Sort Dropdown -->
        <div class="relative" ref="sortDropdownRef">
          <button
            @click="showSortDropdown = !showSortDropdown"
            class="flex items-center gap-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-all shadow-sm"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
            {{ currentSortLabel }}
          </button>

          <!-- Sort Dropdown Menu -->
          <Transition name="dropdown">
            <div
              v-show="showSortDropdown"
              class="absolute right-0 mt-1.5 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1.5 z-50"
            >
              <button
                v-for="option in sortOptions"
                :key="option.id"
                @click="setSortOption(option.id)"
                class="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex items-center justify-between transition-colors"
                :class="sortBy === option.id ? 'text-orange-600 font-medium' : 'text-gray-700'"
              >
                {{ option.label }}
                <svg v-if="sortBy === option.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              </button>
            </div>
          </Transition>
        </div>

        <!-- Filter Dropdown -->
        <div class="relative" ref="filterDropdownRef">
          <button
            @click="showFilterDropdown = !showFilterDropdown"
            class="flex items-center gap-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-all shadow-sm"
            :class="activeDateFilter !== 'all' ? 'border-orange-300 bg-orange-50 text-orange-700' : ''"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            Filter
            <span v-if="activeDateFilter !== 'all'" class="w-1.5 h-1.5 bg-orange-500 rounded-full"></span>
          </button>

          <!-- Filter Dropdown Menu -->
          <Transition name="dropdown">
            <div
              v-show="showFilterDropdown"
              class="absolute right-0 mt-1.5 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-1.5 z-50"
            >
              <div class="px-3 py-1.5 mb-0.5">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Date Range</span>
              </div>
              <button
                v-for="filter in dateFilters"
                :key="filter.id"
                @click="setDateFilter(filter.id); showFilterDropdown = false"
                class="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex items-center justify-between transition-colors"
                :class="activeDateFilter === filter.id ? 'text-orange-600 font-medium' : 'text-gray-700'"
              >
                {{ filter.label }}
                <svg v-if="activeDateFilter === filter.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              </button>

              <!-- Custom Date Picker -->
              <div v-if="activeDateFilter === 'custom'" class="px-3 pt-3 mt-1.5 border-t border-gray-100 space-y-3 pb-2">
                <div>
                  <label class="text-xs font-medium text-gray-500 mb-1 block">From</label>
                  <input
                    type="date"
                    v-model="customDateFrom"
                    class="w-full px-2.5 py-1.5 text-sm border border-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                  />
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 mb-1 block">To</label>
                  <input
                    type="date"
                    v-model="customDateTo"
                    class="w-full px-2.5 py-1.5 text-sm border border-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                  />
                </div>
              </div>

              <!-- Clear Filters -->
              <div v-if="activeDateFilter !== 'all'" class="px-3 pt-2 mt-1.5 border-t border-gray-100">
                <button
                  @click="clearFilters(); showFilterDropdown = false"
                  class="w-full text-center text-sm text-orange-600 hover:text-orange-700 font-medium py-1.5 hover:bg-orange-50 rounded-lg transition-colors"
                >
                  Clear Filters
                </button>
              </div>
            </div>
          </Transition>
        </div>

        <!-- View Toggle -->
        <div class="flex border border-gray-200 rounded-lg overflow-hidden">
          <button
            @click="viewMode = 'grid'"
            class="p-2 transition-colors"
            :class="viewMode === 'grid' ? 'bg-gray-100 text-gray-900' : 'text-gray-400 hover:text-gray-600'"
            title="Grid view"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
          </button>
          <button
            @click="viewMode = 'list'"
            class="p-2 transition-colors"
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

    <!-- Loading State (Skeleton Grid) -->
    <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-5 animate-pulse">
      <div v-for="n in 8" :key="n" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="w-full bg-gray-200" style="aspect-ratio: 16/9;"></div>
        <div class="p-3 space-y-2">
          <div class="h-4 w-3/4 bg-gray-200 rounded"></div>
          <div class="h-3 w-1/2 bg-gray-100 rounded"></div>
        </div>
      </div>
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
    <div v-else-if="activeTab !== 'screenshots' && filteredVideos.length > 0 && viewMode === 'grid'" :key="activeTab + '-grid'" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-5 animate-fade-in">
      <div
        v-for="video in paginatedVideos"
        :key="video.id"
        :data-video-id="video.id"
        class="group"
        :class="selectedVideos.includes(video.id) ? 'ring-2 ring-orange-500 ring-offset-2' : ''"
        :draggable="folders.length > 0"
        @dragstart="handleVideoDragStart($event, video)"
        @dragend="handleVideoDragEnd"
        @contextmenu.prevent="handleVideoContextMenu($event, video)"
      >
        <!-- Thumbnail Container -->
        <div
          class="relative w-full cursor-pointer overflow-hidden rounded-xl"
          style="aspect-ratio: 16/9;"
          @click="handleVideoClick(video.id)"
        >
          <!-- Thumbnail Image -->
          <img
            v-if="video.thumbnail"
            :src="video.thumbnail"
            :alt="video.title"
            class="w-full h-full object-cover"
            loading="lazy"
            @error="$event.target.style.display='none'"
          />
          <!-- Placeholder when no thumbnail -->
          <div
            v-if="!video.thumbnail"
            class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center"
          >
            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>

          <!-- Processing Badge -->
          <div
            v-if="video.conversion_status === 'processing'"
            class="absolute top-2 left-2 z-10 bg-black/70 backdrop-blur-sm text-white text-[10px] font-medium px-2 py-1 rounded-md flex items-center gap-1.5"
          >
            <div class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
            Processing
          </div>

          <!-- Bunny Encoding Badge -->
          <div
            v-else-if="video.storage_type === 'bunny' && video.bunny_status && video.bunny_status !== 'ready'"
            class="absolute top-2 left-2 z-10 bg-black/70 backdrop-blur-sm text-white text-[10px] font-medium px-2 py-1 rounded-md flex items-center gap-1.5"
          >
            <div class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
            Encoding
          </div>

          <!-- Duration Badge -->
          <div class="absolute bottom-2 right-2 z-10 bg-black/80 backdrop-blur-sm text-white text-[11px] font-medium px-2 py-0.5 rounded-md pointer-events-none">
            {{ formatDuration(video.duration) }}
          </div>

          <!-- Favourite Badge -->
          <div v-if="video.is_favourite" class="absolute top-2 right-2 z-10 pointer-events-none">
            <div class="bg-white/90 backdrop-blur-sm rounded-full p-1 shadow-sm">
              <svg class="w-3.5 h-3.5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
          </div>

          <!-- Hover Overlay -->
          <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none"></div>

          <!-- Select Checkbox -->
          <div
            class="absolute top-2 left-2 z-10 transition-opacity"
            :class="selectedVideos.includes(video.id) || isSelectionMode ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
            @click.stop
          >
            <input
              type="checkbox"
              :checked="selectedVideos.includes(video.id)"
              @change="toggleVideoSelection(video.id)"
              class="w-4 h-4 text-orange-500 bg-white border-2 border-white rounded shadow-sm focus:ring-0 cursor-pointer"
            />
          </div>
        </div>

        <!-- Video Info -->
        <div class="p-3">
          <!-- Title Row -->
          <div class="flex items-start gap-1.5">
            <h3
              class="flex-1 font-medium text-sm text-gray-900 leading-snug line-clamp-2 cursor-pointer"
              @click="handleVideoClick(video.id)"
            >
              {{ video.title }}
            </h3>
            <!-- Hover Actions: Star + Copy Link + More -->
            <div class="flex items-center gap-0.5 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity z-20" @click.stop>
              <!-- Star / Favourite toggle -->
              <button
                @click.stop="toggleFavorite(video)"
                class="p-1 rounded-lg transition-colors"
                :class="video.is_favourite ? 'text-orange-500 hover:text-orange-600 hover:bg-orange-50' : 'text-gray-400 hover:text-orange-500 hover:bg-orange-50'"
                :title="video.is_favourite ? 'Remove from starred' : 'Add to starred'"
              >
                <svg class="w-3.5 h-3.5" :fill="video.is_favourite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
              </button>
              <!-- Copy Link -->
              <button
                @click.stop="shareVideo(video)"
                class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                title="Copy link"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
              </button>
              <!-- More Options (opens context menu at body level - no z-index clipping) -->
              <button
                @click.stop="handleVideoMenuClick($event, video)"
                class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                title="More options"
              >
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                </svg>
              </button>
            </div>
          </div>
          <!-- Meta Info -->
          <div class="flex items-center gap-1 mt-1.5 text-[11px] text-gray-400">
            <span>{{ formatDate(video.createdAt) }}</span>
          </div>
          <!-- Stats Row -->
          <div class="flex items-center gap-2.5 mt-1.5 text-[11px] text-gray-400">
            <span class="flex items-center gap-1" title="Views">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              {{ video.views_count || 0 }}
            </span>
            <span class="flex items-center gap-1" title="Comments">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
              </svg>
              {{ video.comments_count || 0 }}
            </span>
            <span class="flex items-center gap-1" title="Reactions">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
              </svg>
              {{ video.reactions_count || 0 }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Videos List View -->
    <div v-else-if="activeTab !== 'screenshots' && filteredVideos.length > 0 && viewMode === 'list'" :key="activeTab + '-list'" class="space-y-1.5 animate-fade-in">
      <div
        v-for="video in paginatedVideos"
        :key="video.id"
        :data-video-id="video.id"
        class="group flex items-center gap-4 p-3.5 bg-white border border-gray-100 rounded-xl hover:shadow-sm hover:border-gray-200 transition-all cursor-pointer"
        :class="selectedVideos.includes(video.id) ? 'ring-2 ring-orange-500 ring-offset-1 border-orange-200' : ''"
        @click="handleVideoClick(video.id)"
        @contextmenu.prevent="handleVideoContextMenu($event, video)"
      >
        <!-- Selection Checkbox -->
        <div
          class="flex-shrink-0 transition-opacity duration-150"
          :class="selectedVideos.includes(video.id) || isSelectionMode ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
          @click.stop
        >
          <input
            type="checkbox"
            :checked="selectedVideos.includes(video.id)"
            @change="toggleVideoSelection(video.id)"
            class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500 focus:ring-offset-0 cursor-pointer"
          />
        </div>

        <!-- Thumbnail -->
        <div class="relative flex-shrink-0 w-44 rounded-lg overflow-hidden group/thumb" style="aspect-ratio: 16/9;">
          <img
            v-if="video.thumbnail"
            :src="video.thumbnail"
            :alt="video.title"
            class="w-full h-full object-cover"
            loading="lazy"
            @error="$event.target.style.display='none'"
          />
          <div
            v-if="!video.thumbnail"
            class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center"
          >
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>
          <!-- Bunny Encoding Badge (list view) -->
          <div
            v-if="isBunnyEncoding(video)"
            class="absolute top-1.5 left-1.5 z-10 bg-black/70 backdrop-blur-sm text-white text-[9px] font-medium px-1.5 py-0.5 rounded flex items-center gap-1"
          >
            <div class="w-1.5 h-1.5 bg-blue-400 rounded-full animate-pulse"></div>
            Encoding
          </div>
          <!-- Duration -->
          <div class="absolute bottom-1.5 right-1.5 bg-black/75 backdrop-blur-sm text-white text-[10px] font-medium px-1.5 py-0.5 rounded">
            {{ formatDuration(video.duration) }}
          </div>
          <!-- Play overlay -->
          <div class="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 group-hover/thumb:opacity-100 transition-opacity duration-200">
            <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center shadow-lg shadow-orange-500/25">
              <svg class="w-4 h-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Video Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start gap-2">
            <h3 class="flex-1 font-medium text-gray-900 text-sm leading-snug line-clamp-2 hover:text-orange-600 transition-colors">
              {{ video.title }}
            </h3>
            <!-- Favourite Star -->
            <div v-if="video.is_favourite" class="flex-shrink-0 bg-orange-50 rounded-full p-1">
              <svg class="w-3.5 h-3.5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
          </div>
          <div class="flex items-center gap-2 mt-1.5 text-xs text-gray-500">
            <span>{{ formatDate(video.createdAt) }}</span>
            <span class="text-gray-300">·</span>
            <span class="flex items-center gap-1">
              <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              {{ video.views_count || 0 }}
            </span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150" @click.stop>
          <!-- Star / Favourite toggle -->
          <button @click.stop="toggleFavorite(video)" class="p-2 rounded-lg transition-colors" :class="video.is_favourite ? 'text-orange-500 hover:bg-orange-50' : 'text-gray-400 hover:text-orange-500 hover:bg-orange-50'" :title="video.is_favourite ? 'Remove from starred' : 'Star'">
            <svg class="w-3.5 h-3.5" :fill="video.is_favourite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
          </button>
          <!-- Copy Link -->
          <button @click="shareVideo(video)" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Copy link">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
          </button>
          <!-- More Options (context menu - teleported to body) -->
          <div class="relative">
            <button
              @click.stop="handleVideoMenuClick($event, video)"
              class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
              title="More options"
            >
              <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="activeTab !== 'screenshots' && showPagination && filteredVideos.length > 0" class="mt-10 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-100 pt-6">
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
            ? 'border-gray-100 text-gray-300 cursor-not-allowed bg-gray-50'
            : 'border-gray-100 text-gray-700 hover:bg-gray-50 bg-white'"
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
                ? 'bg-orange-600 text-white'
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
            ? 'border-gray-100 text-gray-300 cursor-not-allowed bg-gray-50'
            : 'border-gray-100 text-gray-700 hover:bg-gray-50 bg-white'"
        >
          Next
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Screenshots Section -->
    <template v-if="activeTab === 'screenshots'">
      <!-- Screenshots Loading -->
      <div v-if="screenshotsLoading" class="flex items-center justify-center py-20">
        <svg class="animate-spin h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>

      <!-- Screenshots Grid -->
      <div v-else-if="screenshots.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-5">
        <div
          v-for="screenshot in screenshots"
          :key="screenshot.id"
          class="group relative bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-sm hover:-translate-y-0.5 transition-all cursor-pointer"
          @click="openScreenshotPreview(screenshot)"
        >
          <!-- Screenshot Image -->
          <div class="aspect-video bg-gray-100 relative overflow-hidden">
            <img
              :src="screenshot.thumbnailUrl"
              :alt="screenshot.title"
              class="w-full h-full object-cover"
              loading="lazy"
            />
            <!-- Hover Overlay -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
              <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-12 h-12 text-white drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                </svg>
              </div>
            </div>
            <!-- Share Badge -->
            <div v-if="screenshot.isPublic" class="absolute top-2 right-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                Shared
              </span>
            </div>
          </div>
          <!-- Screenshot Info -->
          <div class="p-3">
            <h3 class="text-sm font-medium text-gray-900 truncate mb-1">{{ screenshot.title }}</h3>
            <div class="flex items-center justify-between text-xs text-gray-500">
              <span>{{ formatDate(screenshot.createdAt) }}</span>
              <span v-if="screenshot.fileSize">{{ screenshot.fileSize }}</span>
            </div>
          </div>
          <!-- Actions (visible on hover) -->
          <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1" @click.stop>
            <button
              @click="copyScreenshotLink(screenshot)"
              class="p-1.5 bg-white/90 hover:bg-white rounded-lg shadow-sm transition-colors"
              title="Copy share link"
            >
              <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
              </svg>
            </button>
            <button
              @click="deleteScreenshot(screenshot)"
              class="p-1.5 bg-white/90 hover:bg-red-50 rounded-lg shadow-sm transition-colors"
              title="Delete screenshot"
            >
              <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Screenshots Empty State -->
      <div v-else class="text-center py-20">
        <div class="w-20 h-20 mx-auto mb-5 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl flex items-center justify-center shadow-sm border border-orange-100">
          <svg class="w-9 h-9 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        <h3 class="text-gray-900 font-medium mb-1">No screenshots yet</h3>
        <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
          Take screenshots using the browser extension or upload images directly.
        </p>
        <button
          @click="handleScreenshotUpload"
          class="inline-flex items-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm shadow-sm transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
          </svg>
          Upload Screenshot
        </button>
      </div>
    </template>

    <!-- Videos Empty State -->
    <div v-else-if="activeTab !== 'screenshots' && !loading && filteredVideos.length === 0" class="text-center py-24">
      <div class="w-20 h-20 mx-auto mb-5 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl flex items-center justify-center shadow-sm border border-orange-100">
        <svg v-if="activeTab === 'favourites'" class="w-8 h-8 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
        </svg>
        <svg v-else class="w-8 h-8 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        class="inline-flex items-center px-4 py-2 bg-white border border-gray-100 hover:bg-gray-50 text-gray-700 rounded-lg font-medium text-sm shadow-sm transition-colors"
      >
        Browse Videos
      </button>
      <button
        v-else-if="videos.length === 0"
        @click="goToRecord"
        class="inline-flex items-center px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium text-sm shadow-sm shadow-orange-200 transition-colors"
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
        class="inline-flex items-center px-4 py-2 bg-white border border-gray-100 hover:bg-gray-50 text-gray-700 rounded-lg font-medium text-sm shadow-sm transition-colors"
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

    <!-- Delete Screenshot Modal -->
    <SBDeleteModal
      v-model="showDeleteScreenshotModal"
      title="Delete Screenshot"
      :message="`Are you sure you want to delete '${screenshotToDelete?.title}'? This action cannot be undone.`"
      :loading="isDeletingScreenshot"
      @confirm="confirmDeleteScreenshot"
      @cancel="showDeleteScreenshotModal = false"
    />

    <!-- Screenshot Preview Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showScreenshotPreview && selectedScreenshot"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
          @click.self="closeScreenshotPreview"
        >
          <div class="relative max-w-5xl max-h-[90vh] w-full">
            <!-- Close Button -->
            <button
              @click="closeScreenshotPreview"
              class="absolute -top-12 right-0 p-2 text-white/80 hover:text-white transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
            <!-- Image -->
            <img
              :src="selectedScreenshot.imageUrl"
              :alt="selectedScreenshot.title"
              class="w-full h-auto max-h-[85vh] object-contain rounded-lg shadow-2xl"
            />
            <!-- Info Bar -->
            <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/70 to-transparent rounded-b-lg">
              <div class="flex items-center justify-between text-white">
                <div>
                  <h3 class="font-medium">{{ selectedScreenshot.title }}</h3>
                  <p class="text-sm text-white/70">{{ formatDate(selectedScreenshot.createdAt) }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <button
                    @click="copyScreenshotLink(selectedScreenshot)"
                    class="px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition-colors"
                  >
                    Copy Link
                  </button>
                  <button
                    @click="downloadScreenshot(selectedScreenshot)"
                    class="px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition-colors"
                  >
                    Download
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

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

    <!-- New Folder Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showNewFolderModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
          @click.self="closeNewFolderModal"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
              <!-- Header -->
              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Create New Folder</h2>
                <button
                  @click="closeNewFolderModal"
                  class="p-1.5 -mr-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <!-- Content -->
              <div class="px-6 py-5">
                <p class="text-sm text-gray-500 mb-4">Organize your videos into folders for easier access.</p>
                <div>
                  <label for="folder-name" class="block text-sm font-medium text-gray-700 mb-1.5">Folder Name</label>
                  <input
                    id="folder-name"
                    v-model="newFolderName"
                    type="text"
                    placeholder="e.g., Marketing Videos"
                    class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
                    @keyup.enter="createFolder"
                  />
                  <p v-if="folderError" class="mt-2 text-sm text-red-600">{{ folderError }}</p>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                <button
                  @click="closeNewFolderModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="createFolder"
                  :disabled="!newFolderName.trim() || creatingFolder"
                  class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ creatingFolder ? 'Creating...' : 'Create Folder' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- Move to Folder Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showMoveToFolderModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
          @click.self="closeMoveToFolderModal"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-sm w-full overflow-hidden">
              <!-- Header -->
              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Move to Folder</h2>
                <button
                  @click="closeMoveToFolderModal"
                  class="p-1.5 -mr-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <!-- Content -->
              <div class="px-6 py-4">
                <p class="text-sm text-gray-500 mb-4">
                  {{ videosToMove.length > 1 ? `Select a folder for ${videosToMove.length} videos` : 'Select a folder for this video' }}
                </p>

                <!-- Folder List -->
                <div class="space-y-2 max-h-64 overflow-y-auto">
                  <button
                    v-for="folder in folders"
                    :key="folder.id"
                    @click="moveToFolder(folder)"
                    class="w-full flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-orange-300 hover:bg-orange-50 transition-all text-left"
                    :disabled="movingToFolder"
                  >
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                      <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 truncate">{{ folder.name }}</p>
                      <p class="text-xs text-gray-500">{{ folder.videos_count }} videos</p>
                    </div>
                  </button>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex justify-end px-6 py-4 bg-gray-50 border-t border-gray-100">
                <button
                  @click="closeMoveToFolderModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- Rename Video Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showRenameVideoModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
          @click.self="closeRenameVideoModal"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-sm w-full overflow-hidden">
              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Rename Video</h2>
                <button
                  @click="closeRenameVideoModal"
                  class="p-1.5 -mr-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
              <div class="px-6 py-4">
                <input
                  v-model="renameVideoTitle"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                  placeholder="Video title"
                  @keydown.enter="confirmRenameVideo"
                />
                <p v-if="renameVideoError" class="mt-2 text-sm text-red-600">{{ renameVideoError }}</p>
              </div>
              <div class="flex justify-end gap-2 px-6 py-4 bg-gray-50 border-t border-gray-100">
                <button
                  @click="closeRenameVideoModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="confirmRenameVideo"
                  :disabled="renamingVideo"
                  class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50"
                >
                  {{ renamingVideo ? 'Saving...' : 'Save' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- Edit Folder Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showEditFolderModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
          @click.self="closeEditFolderModal"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
              <!-- Header -->
              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Rename Folder</h2>
                <button
                  @click="closeEditFolderModal"
                  class="p-1.5 -mr-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <!-- Content -->
              <div class="px-6 py-5">
                <div>
                  <label for="edit-folder-name" class="block text-sm font-medium text-gray-700 mb-1.5">Folder Name</label>
                  <input
                    id="edit-folder-name"
                    v-model="editFolderName"
                    type="text"
                    placeholder="Enter folder name"
                    class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
                    @keyup.enter="updateFolder"
                  />
                  <p v-if="editFolderError" class="mt-2 text-sm text-red-600">{{ editFolderError }}</p>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                <button
                  @click="closeEditFolderModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="updateFolder"
                  :disabled="!editFolderName.trim() || updatingFolder"
                  class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ updatingFolder ? 'Saving...' : 'Save' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete Folder Modal -->
    <SBDeleteModal
      v-model="showDeleteFolderModal"
      title="Delete Folder"
      :message="deleteFolderMessage"
      :loading="deletingFolder"
      @confirm="confirmDeleteFolder"
      @cancel="showDeleteFolderModal = false"
    />

    <!-- Custom Drag Preview -->
    <div
      ref="dragPreviewRef"
      class="fixed pointer-events-none z-[-1] opacity-0"
      style="top: -1000px; left: -1000px;"
    >
      <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg shadow-lg border border-gray-100 max-w-[200px]">
        <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-900 truncate">{{ draggedVideo?.title || 'Video' }}</span>
      </div>
    </div>

    <!-- Context Menu (always teleported to body — never clipped by card overflow) -->
    <Teleport to="body">
      <Transition name="dropdown">
        <div
          v-if="contextMenu.show"
          class="fixed z-[500] bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 overflow-hidden"
          :style="{ left: contextMenu.x + 'px', top: contextMenu.y + 'px', minWidth: '176px' }"
          @click.stop
        >
          <!-- Folder Context Menu -->
          <template v-if="contextMenu.type === 'folder'">
            <button @click="contextMenuAction('rename')" class="w-full px-3.5 py-2 text-left text-[13px] text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              Rename
            </button>
            <div class="h-px bg-gray-100 my-1 mx-2"></div>
            <button @click="contextMenuAction('delete')" class="w-full px-3.5 py-2 text-left text-[13px] text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2.5">
              <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              Delete
            </button>
          </template>

          <!-- Video Context Menu -->
          <template v-else-if="contextMenu.type === 'video'">
            <button @click="contextMenuAction('share')" class="w-full px-3.5 py-2 text-left text-[13px] text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
              Copy Link
            </button>
            <button @click="contextMenuAction('rename')" class="w-full px-3.5 py-2 text-left text-[13px] text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              Rename
            </button>
            <button @click="contextMenuAction('move')" class="w-full px-3.5 py-2 text-left text-[13px] text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
              Move to Folder
            </button>
            <button
              @click="contextMenuAction('download')"
              :disabled="isBunnyEncoding(contextMenu.target)"
              :title="isBunnyEncoding(contextMenu.target) ? 'Available when video encoding completes' : 'Download'"
              class="w-full px-3.5 py-2 text-left text-[13px] transition-colors flex items-center gap-2.5"
              :class="isBunnyEncoding(contextMenu.target) ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'"
            >
              <svg class="w-3.5 h-3.5 flex-shrink-0" :class="isBunnyEncoding(contextMenu.target) ? 'text-gray-300' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Download
            </button>
            <button @click="contextMenuAction('archive')" class="w-full px-3.5 py-2 text-left text-[13px] text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
              Archive
            </button>
            <div class="h-px bg-gray-100 my-1 mx-2"></div>
            <button @click="contextMenuAction('delete')" class="w-full px-3.5 py-2 text-left text-[13px] text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2.5">
              <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              Delete
            </button>
          </template>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/stores/auth'
import { useRecording } from '@/composables/useRecording'
import videoService from '@/services/videoService'
import playlistService from '@/services/playlistService'
import folderService from '@/services/folderService'
import screenshotService from '@/services/screenshotService'
import toast from '@/services/toastService'
const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'
import SBDeleteModal from '@/components/Global/SBDeleteModal.vue'
import SBUpgradeModal from '@/components/Global/SBUpgradeModal.vue'
import SBModal from '@/components/Global/SBModal.vue'
import { useDownloadTracker } from '@/composables/useDownloadTracker'

export default {
  name: 'VideosView',
  components: {
    SBDeleteModal,
    SBUpgradeModal,
    SBModal
  },
  setup() {
    const auth = useAuth()
    const router = useRouter()
    const recording = useRecording()
    const { trackDownload, removeDownload } = useDownloadTracker()
    const currentUser = computed(() => auth.user.value)
    const userInitial = computed(() => (currentUser.value?.name || 'U').charAt(0).toUpperCase())

    // Subscription state
    const subscription = computed(() => auth.subscription.value)
    const canRecord = computed(() => {
      if (!subscription.value) return true // Allow if not loaded yet
      return subscription.value.can_record
    })

    const videos = ref([])
    const screenshots = ref([])
    const screenshotsLoading = ref(false)
    const screenshotsFetched = ref(false)
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

    // Action bar state
    const showNewFolderModal = ref(false)
    const uploading = ref(false)
    const fileInput = ref(null)

    // Folder state
    const folders = ref([])
    const newFolderName = ref('')
    const creatingFolder = ref(false)
    const folderError = ref('')

    // Move to folder state
    const showMoveToFolderModal = ref(false)
    const videosToMove = ref([])
    const movingToFolder = ref(false)
    const dragOverFolderId = ref(null)
    const draggedVideo = ref(null)
    const dragPreviewRef = ref(null)

    // Folder actions state
    const activeFolderMenu = ref(null)
    const showEditFolderModal = ref(false)
    const showDeleteFolderModal = ref(false)
    const folderToEdit = ref(null)
    const folderToDelete = ref(null)
    const editFolderName = ref('')
    const editFolderError = ref('')
    const updatingFolder = ref(false)
    const deletingFolder = ref(false)

    // Context menu state
    const contextMenu = ref({
      show: false,
      x: 0,
      y: 0,
      type: null, // 'folder' or 'video'
      target: null
    })

    // Search state
    const searchQuery = ref('')

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
    const itemsPerPage = 24

    // Filtered and sorted videos
    const filteredVideos = computed(() => {
      let result = [...videos.value]

      // Apply tab filter (favourites)
      if (activeTab.value === 'favourites') {
        result = result.filter(v => v.is_favourite)
      }

      // Apply search filter
      if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase()
        result = result.filter(v => v.title.toLowerCase().includes(q))
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
    watch([activeDateFilter, customDateFrom, customDateTo, sortBy, activeTab, searchQuery], () => {
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
      // Close folder menu if click is outside
      if (activeFolderMenu.value !== null) {
        activeFolderMenu.value = null
      }
      // Close context menu
      if (contextMenu.value.show) {
        closeContextMenu()
      }
    }

    // Action bar methods
    const openRecording = () => {
      recording.openSetupPanel()
    }

    const handleUpload = () => {
      // Check if user can still upload (quota not exceeded)
      if (subscription.value?.can_record === false) {
        showUpgradeModal.value = true
        return
      }
      fileInput.value?.click()
    }

    const onFileSelected = async (event) => {
      const file = event.target.files?.[0]
      if (!file) return

      uploading.value = true
      try {
        // Generate title from filename (remove extension)
        const title = file.name.replace(/\.[^/.]+$/, '') || `Upload ${new Date().toLocaleString()}`

        const formData = new FormData()
        formData.append('video', file)
        formData.append('title', title)

        const response = await fetch(`${API_BASE_URL}/api/videos`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
          },
          body: formData
        })

        if (!response.ok) {
          const errorData = await response.json().catch(() => ({}))
          throw new Error(errorData.message || 'Upload failed')
        }

        toast.success('Video uploaded successfully!')
        // Refresh videos list
        await fetchVideos()
      } catch (err) {
        console.error('Upload failed:', err)
        toast.error(err.message || 'Failed to upload video. Please try again.')
      } finally {
        uploading.value = false
        // Reset file input
        if (fileInput.value) {
          fileInput.value.value = ''
        }
      }
    }

    // Folder methods
    const fetchFolders = async () => {
      try {
        folders.value = await folderService.getFolders()
      } catch (err) {
        console.error('Failed to fetch folders:', err)
        folders.value = []
      }
    }

    const createFolder = async () => {
      if (!newFolderName.value.trim()) return

      folderError.value = ''
      creatingFolder.value = true
      try {
        const result = await folderService.createFolder(newFolderName.value.trim())
        folders.value.unshift(result.folder)
        toast.success('Folder created successfully!')
        closeNewFolderModal()
      } catch (err) {
        console.error('Failed to create folder:', err)
        folderError.value = err.message || 'Failed to create folder. Please try again.'
      } finally {
        creatingFolder.value = false
      }
    }

    const closeNewFolderModal = () => {
      showNewFolderModal.value = false
      newFolderName.value = ''
      folderError.value = ''
    }

    const openFolder = (folder) => {
      router.push({ name: 'Folder', params: { id: folder.id } })
    }

    // Folder menu and actions
    const toggleFolderMenu = (folderId) => {
      activeFolderMenu.value = activeFolderMenu.value === folderId ? null : folderId
    }

    const openEditFolderModal = (folder) => {
      folderToEdit.value = folder
      editFolderName.value = folder.name
      editFolderError.value = ''
      showEditFolderModal.value = true
      activeFolderMenu.value = null
    }

    const closeEditFolderModal = () => {
      showEditFolderModal.value = false
      folderToEdit.value = null
      editFolderName.value = ''
      editFolderError.value = ''
    }

    const updateFolder = async () => {
      if (!editFolderName.value.trim() || !folderToEdit.value) return

      editFolderError.value = ''
      updatingFolder.value = true
      try {
        const result = await folderService.updateFolder(folderToEdit.value.id, {
          name: editFolderName.value.trim()
        })
        // Update folder in list
        const index = folders.value.findIndex(f => f.id === folderToEdit.value.id)
        if (index !== -1) {
          folders.value[index] = { ...folders.value[index], name: editFolderName.value.trim() }
        }
        toast.success('Folder renamed successfully!')
        closeEditFolderModal()
      } catch (err) {
        console.error('Failed to update folder:', err)
        editFolderError.value = err.message || 'Failed to rename folder. Please try again.'
      } finally {
        updatingFolder.value = false
      }
    }

    const openDeleteFolderModal = (folder) => {
      folderToDelete.value = folder
      showDeleteFolderModal.value = true
      activeFolderMenu.value = null
    }

    const deleteFolderMessage = computed(() => {
      if (!folderToDelete.value) return ''
      const count = folderToDelete.value.videos_count || 0
      if (count > 0) {
        return `Are you sure you want to delete "${folderToDelete.value.name}"? The ${count} video${count > 1 ? 's' : ''} in this folder will not be deleted, but will be moved out of the folder.`
      }
      return `Are you sure you want to delete "${folderToDelete.value.name}"?`
    })

    const confirmDeleteFolder = async () => {
      if (!folderToDelete.value) return

      deletingFolder.value = true
      try {
        await folderService.deleteFolder(folderToDelete.value.id)
        folders.value = folders.value.filter(f => f.id !== folderToDelete.value.id)
        toast.success('Folder deleted successfully!')
        showDeleteFolderModal.value = false
        folderToDelete.value = null
      } catch (err) {
        console.error('Failed to delete folder:', err)
        toast.error(err.message || 'Failed to delete folder. Please try again.')
      } finally {
        deletingFolder.value = false
      }
    }

    // Context menu methods
    const showContextMenu = (event, type, target) => {
      event.preventDefault()
      // Close any open menus
      activeVideoMenu.value = null
      activeFolderMenu.value = null

      // Calculate position with edge detection
      const menuWidth = 128 // w-32 = 8rem = 128px
      const menuHeight = type === 'folder' ? 72 : 180 // Approximate heights
      let x = event.clientX
      let y = event.clientY

      // Prevent menu from going off right edge
      if (x + menuWidth > window.innerWidth) {
        x = window.innerWidth - menuWidth - 8
      }

      // Prevent menu from going off bottom edge
      if (y + menuHeight > window.innerHeight) {
        y = window.innerHeight - menuHeight - 8
      }

      contextMenu.value = {
        show: true,
        x,
        y,
        type,
        target
      }
    }

    const closeContextMenu = () => {
      contextMenu.value.show = false
    }

    // Close context menu on Escape key
    const handleKeyDown = (event) => {
      if (event.key === 'Escape' && contextMenu.value.show) {
        closeContextMenu()
      }
    }

    const handleFolderContextMenu = (event, folder) => {
      showContextMenu(event, 'folder', folder)
    }

    const handleVideoContextMenu = (event, video) => {
      showContextMenu(event, 'video', video)
    }

    const contextMenuAction = (action) => {
      const { type, target } = contextMenu.value
      closeContextMenu()

      if (type === 'folder') {
        if (action === 'rename') {
          openEditFolderModal(target)
        } else if (action === 'delete') {
          openDeleteFolderModal(target)
        }
      } else if (type === 'video') {
        if (action === 'share') {
          shareVideo(target)
        } else if (action === 'rename') {
          openRenameVideoModal(target)
        } else if (action === 'move') {
          openMoveToFolderModal(target)
        } else if (action === 'download') {
          downloadVideoWithAnimation(target)
        } else if (action === 'archive') {
          archiveVideo(target)
        } else if (action === 'delete') {
          deleteVideo(target)
        }
      }
    }

    // Move to folder methods
    const openMoveToFolderModal = (video) => {
      videosToMove.value = [video.id]
      showMoveToFolderModal.value = true
    }

    const openBulkMoveToFolderModal = () => {
      showBulkActionsDropdown.value = false
      videosToMove.value = [...selectedVideos.value]
      showMoveToFolderModal.value = true
    }

    const closeMoveToFolderModal = () => {
      showMoveToFolderModal.value = false
      videosToMove.value = []
    }

    const moveToFolder = async (folder) => {
      if (videosToMove.value.length === 0) return

      movingToFolder.value = true
      try {
        await folderService.addVideosToFolder(folder.id, videosToMove.value)

        const count = videosToMove.value.length
        toast.success(`${count > 1 ? count + ' videos' : 'Video'} moved to "${folder.name}"`)

        closeMoveToFolderModal()
        selectedVideos.value = []
        fetchFolders() // Refresh folder counts
      } catch (err) {
        console.error('Failed to move videos to folder:', err)
        toast.error('Failed to move videos. Please try again.')
      } finally {
        movingToFolder.value = false
      }
    }

    // Drag and drop handlers
    const handleVideoDragStart = (event, video) => {
      draggedVideo.value = video
      event.dataTransfer.effectAllowed = 'move'
      event.dataTransfer.setData('text/plain', video.id)

      // Create custom drag image
      if (dragPreviewRef.value) {
        // Make the preview temporarily visible for setDragImage
        const preview = dragPreviewRef.value
        preview.style.opacity = '1'
        preview.style.zIndex = '9999'
        preview.style.top = '0'
        preview.style.left = '0'

        // Set the custom drag image
        event.dataTransfer.setDragImage(preview, 100, 20)

        // Hide it again after a brief moment
        requestAnimationFrame(() => {
          preview.style.opacity = '0'
          preview.style.zIndex = '-1'
          preview.style.top = '-1000px'
          preview.style.left = '-1000px'
        })
      }
    }

    const handleVideoDragEnd = () => {
      draggedVideo.value = null
      dragOverFolderId.value = null
    }

    const handleFolderDragOver = (event, folderId) => {
      event.dataTransfer.dropEffect = 'move'
      dragOverFolderId.value = folderId
    }

    const handleFolderDragLeave = () => {
      dragOverFolderId.value = null
    }

    const handleDropOnFolder = async (event, folder) => {
      dragOverFolderId.value = null

      if (!draggedVideo.value) return

      try {
        await folderService.addVideosToFolder(folder.id, [draggedVideo.value.id])
        toast.success(`Video moved to "${folder.name}"`)
        fetchFolders() // Refresh folder counts
      } catch (err) {
        console.error('Failed to move video to folder:', err)
        toast.error('Failed to move video. Please try again.')
      } finally {
        draggedVideo.value = null
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
          shareUrl: video.share_url,
          conversion_status: video.conversion_status,
          storage_type: video.storage_type,
          bunny_status: video.bunny_status
        }))
      } catch (err) {
        console.error('Failed to fetch videos:', err)
        error.value = 'Failed to load videos. Please try again.'
        videos.value = []
      } finally {
        loading.value = false
      }
    }

    const fetchScreenshots = async () => {
      if (screenshotsFetched.value) return // Don't refetch if already loaded

      screenshotsLoading.value = true
      try {
        const fetchedScreenshots = await screenshotService.getScreenshots()
        screenshots.value = fetchedScreenshots.map(screenshot => ({
          id: screenshot.id,
          title: screenshot.title,
          imageUrl: screenshot.image_url,
          thumbnailUrl: screenshot.thumbnail_url || screenshot.image_url,
          shareUrl: screenshot.share_url,
          shareToken: screenshot.share_token,
          isPublic: screenshot.is_public,
          fileSize: screenshot.file_size_formatted,
          createdAt: new Date(screenshot.created_at)
        }))
        screenshotsFetched.value = true
      } catch (err) {
        console.error('Failed to fetch screenshots:', err)
        toast.error('Failed to load screenshots')
        screenshots.value = []
      } finally {
        screenshotsLoading.value = false
      }
    }

    // Watch for tab changes to fetch screenshots when needed
    watch(activeTab, (newTab) => {
      if (newTab === 'screenshots' && !screenshotsFetched.value) {
        fetchScreenshots()
      }
    })

    // Poll for encoding status updates on Bunny videos
    let encodingPollInterval = null

    const pollEncodingStatus = async () => {
      const encodingVideos = videos.value.filter(v => isBunnyEncoding(v))
      if (encodingVideos.length === 0) {
        if (encodingPollInterval) {
          clearInterval(encodingPollInterval)
          encodingPollInterval = null
        }
        return
      }
      // Silently refresh video list without resetting page or showing loading
      try {
        const fetchedVideos = await videoService.getVideos()
        const page = currentPage.value
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
          shareUrl: video.share_url,
          conversion_status: video.conversion_status,
          storage_type: video.storage_type,
          bunny_status: video.bunny_status
        }))
        currentPage.value = page
      } catch (e) {
        // Silently fail — polling shouldn't disrupt the UI
      }
    }

    const startEncodingPoll = () => {
      if (encodingPollInterval) return
      const encodingVideos = videos.value.filter(v => isBunnyEncoding(v))
      if (encodingVideos.length > 0) {
        encodingPollInterval = setInterval(pollEncodingStatus, 15000)
      }
    }

    watch(videos, () => {
      startEncodingPoll()
    }, { deep: true })

    onMounted(() => {
      fetchVideos()
      fetchFolders()
      auth.fetchSubscription() // Fetch subscription status
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('keydown', handleKeyDown)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
      document.removeEventListener('keydown', handleKeyDown)
      if (encodingPollInterval) {
        clearInterval(encodingPollInterval)
        encodingPollInterval = null
      }
    })

    const goToRecord = () => {
      // Check if user can record
      if (!canRecord.value) {
        showUpgradeModal.value = true
        return
      }
      // Trigger extension recording popup
      if (document.documentElement.hasAttribute('data-openkap-extension')) {
        window.dispatchEvent(new CustomEvent('openkap:new-recording'))
      } else {
        window.open('https://chromewebstore.google.com/detail/openkap/nnchnlkilgfemhpcohmgdpcmkjedjkfm', '_blank')
      }
    }

    const openVideo = (id) => {
      window.location.href = import.meta.env.BASE_URL + `video/${id}`
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

    // Rename video
    const showRenameVideoModal = ref(false)
    const videoToRename = ref(null)
    const renameVideoTitle = ref('')
    const renameVideoError = ref('')
    const renamingVideo = ref(false)

    const openRenameVideoModal = (video) => {
      videoToRename.value = video
      renameVideoTitle.value = video.title
      renameVideoError.value = ''
      showRenameVideoModal.value = true
    }

    const closeRenameVideoModal = () => {
      showRenameVideoModal.value = false
      videoToRename.value = null
      renameVideoTitle.value = ''
      renameVideoError.value = ''
    }

    const confirmRenameVideo = async () => {
      if (!renameVideoTitle.value.trim()) {
        renameVideoError.value = 'Title cannot be empty'
        return
      }
      renamingVideo.value = true
      try {
        await videoService.updateVideo(videoToRename.value.id, { title: renameVideoTitle.value.trim() })
        const video = videos.value.find(v => v.id === videoToRename.value.id)
        if (video) video.title = renameVideoTitle.value.trim()
        toast.success('Video renamed successfully!')
        closeRenameVideoModal()
      } catch (err) {
        renameVideoError.value = err.message || 'Failed to rename video'
      } finally {
        renamingVideo.value = false
      }
    }

    // Archive video
    const archiveVideo = async (video) => {
      try {
        await videoService.updateVideo(video.id, { is_archived: true })
        videos.value = videos.value.filter(v => v.id !== video.id)
        toast.success('Video archived!')
      } catch (err) {
        console.error('Failed to archive:', err)
        toast.error('Failed to archive video. Please try again.')
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
      try {
        toast.success('Preparing your download...')

        const result = await videoService.requestDownloadMp4(video.id)
        if (!result) return

        if (result.mode === 'redirect') {
          const link = document.createElement('a')
          link.href = result.url
          link.download = result.fileName || `${video.title || 'video'}.mp4`
          link.target = '_blank'
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
          toast.success('Download started!')
        } else if (result.mode === 'sync') {
          const blobUrl = window.URL.createObjectURL(result.blob)
          const link = document.createElement('a')
          link.href = blobUrl
          link.download = `${video.title || 'video'}.mp4`
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
          window.URL.revokeObjectURL(blobUrl)
          toast.success('Download complete!')
        } else {
          trackDownload(video.id, video.title || 'Untitled Video')
          toast.success('Your video is being converted to MP4. Check notifications for progress!')
        }
      } catch (err) {
        console.error('Failed to download:', err)
        toast.error('Failed to download video. Please try again.')
      }
    }

    const flyThumbnailToNotificationBell = (video) => {
      // Find the video card's thumbnail in the DOM
      const videoCards = document.querySelectorAll('[data-video-id]')
      let sourceEl = null
      for (const card of videoCards) {
        if (card.dataset.videoId === String(video.id)) {
          sourceEl = card.querySelector('img') || card.querySelector('[style*="aspect-ratio"]')
          break
        }
      }

      // Find the notification bell button in the header
      const bellEl = document.querySelector('[data-notification-bell]')

      if (!sourceEl || !bellEl) return

      const sourceRect = sourceEl.getBoundingClientRect()
      const bellRect = bellEl.getBoundingClientRect()

      // Create a floating clone
      const clone = document.createElement('div')
      clone.style.cssText = `
        position: fixed;
        z-index: 9999;
        left: ${sourceRect.left}px;
        top: ${sourceRect.top}px;
        width: ${sourceRect.width}px;
        height: ${sourceRect.height}px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        pointer-events: none;
        transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
      `

      // Add thumbnail image or gradient placeholder
      if (video.thumbnail) {
        const img = document.createElement('img')
        img.src = video.thumbnail
        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;'
        clone.appendChild(img)
      } else {
        clone.style.background = 'linear-gradient(135deg, #f3f4f6, #e5e7eb)'
      }

      // Add a play icon overlay
      const overlay = document.createElement('div')
      overlay.style.cssText = `
        position: absolute; inset: 0;
        background: rgba(0,0,0,0.2);
        display: flex; align-items: center; justify-content: center;
      `
      overlay.innerHTML = `<svg width="24" height="24" fill="white" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/></svg>`
      clone.appendChild(overlay)

      document.body.appendChild(clone)

      // Trigger the fly animation on next frame
      requestAnimationFrame(() => {
        const targetX = bellRect.left + bellRect.width / 2 - 16
        const targetY = bellRect.top + bellRect.height / 2 - 10
        clone.style.left = `${targetX}px`
        clone.style.top = `${targetY}px`
        clone.style.width = '32px'
        clone.style.height = '20px'
        clone.style.opacity = '0.3'
        clone.style.borderRadius = '50%'
        clone.style.boxShadow = '0 4px 12px rgba(249, 115, 22, 0.4)'
      })

      // Pulse the bell after animation lands
      setTimeout(() => {
        clone.remove()
        if (bellEl) {
          bellEl.classList.add('animate-bounce')
          setTimeout(() => bellEl.classList.remove('animate-bounce'), 600)
        }
      }, 750)
    }

    const downloadVideoWithAnimation = async (video) => {
      try {
        const result = await videoService.requestDownloadMp4(video.id)
        if (!result) return

        if (result.mode === 'redirect') {
          const link = document.createElement('a')
          link.href = result.url
          link.download = result.fileName || `${video.title || 'video'}.mp4`
          link.target = '_blank'
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
          toast.success('Download started!')
        } else if (result.mode === 'sync') {
          const blobUrl = window.URL.createObjectURL(result.blob)
          const link = document.createElement('a')
          link.href = blobUrl
          link.download = `${video.title || 'video'}.mp4`
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
          window.URL.revokeObjectURL(blobUrl)
          toast.success('Download complete!')
        } else if (result.mode === 'processing') {
          // Bunny is still encoding — show in bell with live progress
          flyThumbnailToNotificationBell(video)
          trackDownload(video.id, video.title || 'Untitled Video')
          toast.info('Video is still processing — check the bell for progress!')
        } else {
          // Async local conversion
          flyThumbnailToNotificationBell(video)
          trackDownload(video.id, video.title || 'Untitled Video')
          toast.success('Converting to MP4 — check the bell for progress!')
        }
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

    // Screenshot methods
    const screenshotToDelete = ref(null)
    const showDeleteScreenshotModal = ref(false)
    const isDeletingScreenshot = ref(false)
    const screenshotFileInput = ref(null)
    const uploadingScreenshot = ref(false)
    const selectedScreenshot = ref(null)
    const showScreenshotPreview = ref(false)

    const openScreenshotPreview = (screenshot) => {
      selectedScreenshot.value = screenshot
      showScreenshotPreview.value = true
    }

    const closeScreenshotPreview = () => {
      selectedScreenshot.value = null
      showScreenshotPreview.value = false
    }

    const copyScreenshotLink = async (screenshot) => {
      const shareUrl = screenshot.shareUrl || screenshot.share_url
      if (shareUrl) {
        try {
          await navigator.clipboard.writeText(shareUrl)
          toast.success('Link copied to clipboard!')
        } catch (err) {
          toast.error('Failed to copy link')
        }
      } else {
        toast.error('No share link available')
      }
    }

    const downloadScreenshot = async (screenshot) => {
      try {
        const imageUrl = screenshot.imageUrl || screenshot.image_url
        if (!imageUrl) {
          toast.error('No image URL available')
          return
        }

        // Fetch the image as a blob
        const response = await fetch(imageUrl)
        if (!response.ok) {
          throw new Error('Failed to fetch image')
        }

        const blob = await response.blob()

        // Create a download link
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url

        // Generate filename from title or use default
        const title = screenshot.title || 'screenshot'
        const extension = blob.type.split('/')[1] || 'png'
        link.download = `${title}.${extension}`

        // Trigger download
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        // Cleanup
        window.URL.revokeObjectURL(url)
        toast.success('Screenshot downloaded!')
      } catch (err) {
        console.error('Failed to download screenshot:', err)
        toast.error('Failed to download screenshot')
      }
    }

    const deleteScreenshot = (screenshot) => {
      screenshotToDelete.value = screenshot
      showDeleteScreenshotModal.value = true
    }

    const confirmDeleteScreenshot = async () => {
      if (!screenshotToDelete.value) return

      isDeletingScreenshot.value = true
      try {
        await screenshotService.deleteScreenshot(screenshotToDelete.value.id)
        screenshots.value = screenshots.value.filter(s => s.id !== screenshotToDelete.value.id)
        toast.success('Screenshot deleted!')
        showDeleteScreenshotModal.value = false
        screenshotToDelete.value = null
      } catch (err) {
        console.error('Failed to delete screenshot:', err)
        toast.error('Failed to delete screenshot. Please try again.')
      } finally {
        isDeletingScreenshot.value = false
      }
    }

    const handleScreenshotUpload = () => {
      screenshotFileInput.value?.click()
    }

    const onScreenshotFileSelected = async (event) => {
      const file = event.target.files?.[0]
      if (!file) return

      // Validate file type
      const validTypes = ['image/png', 'image/jpeg', 'image/webp']
      if (!validTypes.includes(file.type)) {
        toast.error('Please select a valid image file (PNG, JPEG, or WebP)')
        return
      }

      // Validate file size (10MB max)
      if (file.size > 10 * 1024 * 1024) {
        toast.error('File size must be less than 10MB')
        return
      }

      uploadingScreenshot.value = true
      try {
        const screenshot = await screenshotService.uploadScreenshot(file)
        screenshots.value.unshift({
          id: screenshot.id,
          title: screenshot.title,
          imageUrl: screenshot.image_url,
          thumbnailUrl: screenshot.thumbnail_url || screenshot.image_url,
          shareUrl: screenshot.share_url,
          shareToken: screenshot.share_token,
          isPublic: screenshot.is_public,
          fileSize: screenshot.file_size_formatted,
          createdAt: new Date(screenshot.created_at)
        })
        toast.success('Screenshot uploaded successfully!')
      } catch (err) {
        console.error('Failed to upload screenshot:', err)
        toast.error(err.message || 'Failed to upload screenshot')
      } finally {
        uploadingScreenshot.value = false
        event.target.value = '' // Reset file input
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

    // Tab index mapping for sliding pill indicator
    const tabIndexMap = { videos: 0, favourites: 1, screenshots: 2 }

    // Favourite count for tab badge
    const favouriteCount = computed(() => videos.value.filter(v => v.is_favourite).length)

    // Open context menu at a specific position (used by button clicks)
    const openContextMenuAt = (clientX, clientY, type, target) => {
      activeVideoMenu.value = null
      activeFolderMenu.value = null
      const menuWidth = 180
      const menuHeight = type === 'folder' ? 100 : 220
      let x = clientX
      let y = clientY
      if (x + menuWidth > window.innerWidth) x = window.innerWidth - menuWidth - 8
      if (y + menuHeight > window.innerHeight) y = clientY - menuHeight - 4
      contextMenu.value = { show: true, x, y, type, target }
    }

    // Called when "..." button is clicked on a video card
    const handleVideoMenuClick = (event, video) => {
      event.stopPropagation()
      const rect = event.currentTarget.getBoundingClientRect()
      openContextMenuAt(rect.right, rect.bottom + 4, 'video', video)
    }

    // Called when "..." button is clicked on a folder chip
    const handleFolderMenuClick = (event, folder) => {
      event.stopPropagation()
      const rect = event.currentTarget.getBoundingClientRect()
      openContextMenuAt(rect.right, rect.bottom + 4, 'folder', folder)
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

    const isBunnyEncoding = (video) => {
      return video?.storage_type === 'bunny' && video?.bunny_status && video.bunny_status !== 'ready'
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
      // Action bar
      showNewFolderModal,
      uploading,
      fileInput,
      openRecording,
      handleUpload,
      onFileSelected,
      // Folders
      folders,
      fetchFolders,
      newFolderName,
      creatingFolder,
      folderError,
      createFolder,
      closeNewFolderModal,
      openFolder,
      // Folder actions
      activeFolderMenu,
      toggleFolderMenu,
      showEditFolderModal,
      showDeleteFolderModal,
      folderToEdit,
      folderToDelete,
      editFolderName,
      editFolderError,
      updatingFolder,
      deletingFolder,
      openEditFolderModal,
      closeEditFolderModal,
      updateFolder,
      openDeleteFolderModal,
      deleteFolderMessage,
      confirmDeleteFolder,
      // Context menu
      contextMenu,
      handleFolderContextMenu,
      handleVideoContextMenu,
      contextMenuAction,
      closeContextMenu,
      // Move to folder
      showMoveToFolderModal,
      videosToMove,
      movingToFolder,
      openMoveToFolderModal,
      openBulkMoveToFolderModal,
      closeMoveToFolderModal,
      moveToFolder,
      // Drag and drop
      dragOverFolderId,
      draggedVideo,
      dragPreviewRef,
      handleVideoDragStart,
      handleVideoDragEnd,
      handleFolderDragOver,
      handleFolderDragLeave,
      handleDropOnFolder,
      videos,
      filteredVideos,
      paginatedVideos,
      viewMode,
      loading,
      error,
      activeTab,
      // Search
      searchQuery,
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
      // Rename video
      showRenameVideoModal,
      renameVideoTitle,
      renameVideoError,
      renamingVideo,
      openRenameVideoModal,
      closeRenameVideoModal,
      confirmRenameVideo,
      // Archive video
      archiveVideo,
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
      // Tab control
      tabIndexMap,
      favouriteCount,
      // Video menu
      activeVideoMenu,
      toggleVideoMenu,
      handleVideoMenuClick,
      handleFolderMenuClick,
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
      openUpgradeModal,
      // Screenshots
      screenshots,
      screenshotsLoading,
      screenshotsFetched,
      fetchScreenshots,
      screenshotToDelete,
      showDeleteScreenshotModal,
      isDeletingScreenshot,
      screenshotFileInput,
      uploadingScreenshot,
      selectedScreenshot,
      showScreenshotPreview,
      openScreenshotPreview,
      closeScreenshotPreview,
      copyScreenshotLink,
      downloadScreenshot,
      deleteScreenshot,
      confirmDeleteScreenshot,
      handleScreenshotUpload,
      onScreenshotFileSelected,
      isBunnyEncoding
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

/* Tab content transition - triggers on v-if remount when switching tabs */
@keyframes tabFadeUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.tab-content-enter { animation: tabFadeUp 0.18s ease-out forwards; }

/* Dropdown transitions */
.dropdown-enter-active {
  transition: all 0.2s ease-out;
}

.dropdown-leave-active {
  transition: all 0.15s ease-in;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-4px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-2px);
}

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-content-enter-active {
  transition: all 0.25s ease-out;
}

.modal-content-leave-active {
  transition: all 0.15s ease-in;
}

.modal-content-enter-from {
  opacity: 0;
  transform: scale(0.95);
}

.modal-content-leave-to {
  opacity: 0;
  transform: scale(0.98);
}

/* Smooth hover transitions */
button, a {
  transition: all 0.15s ease;
}
</style>
