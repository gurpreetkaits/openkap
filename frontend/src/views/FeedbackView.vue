<template>
  <div class="animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between mb-5">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Feedback</h1>
        <p class="text-sm text-gray-500 mt-0.5">Help us improve ScreenSense</p>
      </div>
      <button
        @click="openNewModal"
        class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-all duration-200 flex items-center gap-2 shadow-sm hover:shadow-md active:scale-[0.98]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Feedback
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white rounded-xl border border-gray-100 p-12 text-center">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-orange-50 mb-3">
        <div class="animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
      </div>
      <p class="text-sm text-gray-500">Loading your feedback...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="feedbackList.length === 0" class="bg-white rounded-xl border border-gray-100 p-12 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
      </div>
      <h3 class="text-base font-medium text-gray-900 mb-1">No feedback yet</h3>
      <p class="text-sm text-gray-500 mb-4">Share your thoughts, report bugs, or suggest features</p>
      <button @click="openNewModal" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md">
        Send Your First Feedback
      </button>
    </div>

    <!-- Feedback List -->
    <div v-else class="space-y-3">
      <div
        v-for="item in feedbackList"
        :key="item.id"
        @click="viewFeedback(item)"
        class="group bg-white rounded-xl border border-gray-100 p-4 cursor-pointer transition-all duration-200 hover:border-gray-300 hover:shadow-sm"
      >
        <div class="flex items-start gap-3">
          <!-- Type Icon -->
          <div :class="typeIconBg(item.type)" class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center">
            <component :is="typeIcon(item.type)" class="w-5 h-5" :class="typeIconColor(item.type)" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span :class="typeBadgeClass(item.type)" class="px-2 py-0.5 text-xs font-medium rounded-full capitalize">
                    {{ typeLabel(item.type) }}
                  </span>
                  <span v-if="item.reply" class="flex items-center gap-1 text-xs text-green-600 font-medium">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Replied
                  </span>
                </div>
                <h3 class="text-sm font-medium text-gray-900 truncate group-hover:text-orange-600 transition-colors">
                  {{ item.title }}
                </h3>
                <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-500">
                  <span>{{ formatDate(item.created_at) }}</span>
                  <span v-if="hasImages(item.description)" class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Has images
                  </span>
                </div>
              </div>

              <!-- Status Badge -->
              <span :class="statusClass(item.status)" class="flex-shrink-0 px-2.5 py-1 text-xs font-medium rounded-full capitalize flex items-center gap-1">
                <span :class="statusDotClass(item.status)" class="w-1.5 h-1.5 rounded-full"></span>
                {{ item.status }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="flex items-center justify-between pt-4">
        <span class="text-sm text-gray-500">
          Showing {{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }}
        </span>
        <div class="flex gap-2">
          <button
            @click="goToPage(pagination.currentPage - 1)"
            :disabled="pagination.currentPage === 1"
            class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-100 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-1"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Previous
          </button>
          <button
            @click="goToPage(pagination.currentPage + 1)"
            :disabled="pagination.currentPage === pagination.lastPage"
            class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-100 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-1"
          >
            Next
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Rate limit note -->
    <p class="text-xs text-gray-400 mt-4 text-center flex items-center justify-center gap-1">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Limited to 3 submissions per hour
    </p>

    <!-- New Feedback Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @keydown.esc="handleEscape">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="handleBackdropClick"></div>
          <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div v-if="showModal" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl z-10 overflow-hidden">
              <!-- Header -->
              <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">New Feedback</h2>
                <button @click="handleBackdropClick" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <form @submit.prevent="submitFeedback" class="p-5 space-y-4">
                <!-- Feedback Type -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">What type of feedback?</label>
                  <div class="grid grid-cols-3 gap-2">
                    <button
                      v-for="t in feedbackTypes"
                      :key="t.value"
                      type="button"
                      @click="form.type = t.value"
                      :class="[
                        'flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all duration-200',
                        form.type === t.value
                          ? 'border-orange-500 bg-orange-50'
                          : 'border-gray-100 hover:border-gray-300 hover:bg-gray-50'
                      ]"
                      :disabled="submitting"
                    >
                      <component :is="t.icon" :class="['w-5 h-5', form.type === t.value ? 'text-orange-500' : 'text-gray-400']" />
                      <span :class="['text-xs font-medium', form.type === t.value ? 'text-orange-600' : 'text-gray-600']">
                        {{ t.label }}
                      </span>
                    </button>
                  </div>
                </div>

                <!-- Title -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
                  <input
                    ref="titleInput"
                    v-model="form.title"
                    type="text"
                    placeholder="Brief summary of your feedback"
                    maxlength="255"
                    required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-shadow"
                    :disabled="submitting"
                  />
                  <div class="flex justify-end mt-1">
                    <span :class="['text-xs', form.title.length > 200 ? 'text-orange-500' : 'text-gray-400']">
                      {{ form.title.length }}/255
                    </span>
                  </div>
                </div>

                <!-- Description -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Description
                    <span class="text-xs text-gray-400 font-normal ml-1">(You can paste screenshots)</span>
                  </label>
                  <div
                    ref="descriptionEditor"
                    contenteditable="true"
                    @input="onDescriptionInput"
                    @paste="onPaste"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none min-h-[180px] max-h-[300px] overflow-y-auto transition-shadow"
                    :class="{ 'opacity-50 pointer-events-none': submitting }"
                    data-placeholder="Describe your feedback in detail. Screenshots help us understand better..."
                  ></div>
                  <div class="flex justify-end mt-1">
                    <span :class="['text-xs', descriptionLength > 4500 ? 'text-orange-500' : 'text-gray-400']">
                      ~{{ descriptionLength }}/5000
                    </span>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-2">
                  <button
                    type="button"
                    @click="handleBackdropClick"
                    class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
                    :disabled="submitting"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="submitting || !isFormValid"
                    class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl flex items-center gap-2 transition-all duration-200 shadow-sm hover:shadow-md"
                  >
                    <svg v-if="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    {{ submitting ? 'Sending...' : 'Send Feedback' }}
                  </button>
                </div>
              </form>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- View Feedback Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="selectedFeedback" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @keydown.esc="selectedFeedback = null">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="selectedFeedback = null"></div>
          <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div v-if="selectedFeedback" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col z-10 overflow-hidden">
              <!-- Header -->
              <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div class="flex items-start gap-3 flex-1 min-w-0 pr-4">
                  <div :class="typeIconBg(selectedFeedback.type)" class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center">
                    <component :is="typeIcon(selectedFeedback.type)" class="w-5 h-5" :class="typeIconColor(selectedFeedback.type)" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                      <span :class="typeBadgeClass(selectedFeedback.type)" class="px-2 py-0.5 text-xs font-medium rounded-full capitalize">
                        {{ typeLabel(selectedFeedback.type) }}
                      </span>
                      <span :class="statusClass(selectedFeedback.status)" class="px-2 py-0.5 text-xs font-medium rounded-full capitalize flex items-center gap-1">
                        <span :class="statusDotClass(selectedFeedback.status)" class="w-1.5 h-1.5 rounded-full"></span>
                        {{ selectedFeedback.status }}
                      </span>
                    </div>
                    <h2 class="text-base font-semibold text-gray-900 break-words">{{ selectedFeedback.title }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ formatDate(selectedFeedback.created_at) }}</p>
                  </div>
                </div>
                <button @click="selectedFeedback = null" class="p-2 hover:bg-gray-100 rounded-lg transition-colors flex-shrink-0">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <!-- Content -->
              <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <!-- User's Feedback -->
                <div class="bg-gray-50 rounded-xl p-4">
                  <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center">
                      <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                    </div>
                    <span class="text-xs font-medium text-gray-600">Your feedback</span>
                  </div>
                  <div class="text-sm text-gray-700 prose prose-sm max-w-none" v-html="selectedFeedback.description"></div>
                </div>

                <!-- Admin Reply -->
                <div v-if="selectedFeedback.reply" class="bg-green-50 border border-green-100 rounded-xl p-4">
                  <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-green-200 flex items-center justify-center">
                      <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                      </svg>
                    </div>
                    <span class="text-xs font-medium text-green-700">Reply from ScreenSense</span>
                    <span v-if="selectedFeedback.replied_at" class="text-xs text-green-600 ml-auto">
                      {{ formatDate(selectedFeedback.replied_at) }}
                    </span>
                  </div>
                  <p class="text-sm text-green-800 whitespace-pre-wrap">{{ selectedFeedback.reply }}</p>
                </div>

                <!-- Awaiting Response -->
                <div v-else class="flex flex-col items-center justify-center py-6 text-center">
                  <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mb-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  <p class="text-sm text-gray-500">Awaiting response</p>
                  <p class="text-xs text-gray-400 mt-1">We'll get back to you soon</p>
                </div>
              </div>

              <!-- Footer -->
              <div class="p-4 border-t border-gray-100 flex items-center justify-between">
                <button
                  v-if="selectedFeedback.status === 'pending'"
                  @click="confirmDelete"
                  :disabled="deleting"
                  class="px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors flex items-center gap-1.5"
                >
                  <svg v-if="deleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  {{ deleting ? 'Deleting...' : 'Delete' }}
                </button>
                <div v-else></div>
                <button
                  @click="selectedFeedback = null"
                  class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  Close
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- Unsaved Changes Confirmation Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showUnsavedWarning" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm z-10 p-5">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
              </div>
              <div>
                <h3 class="text-base font-semibold text-gray-900">Discard changes?</h3>
                <p class="text-sm text-gray-500">Your feedback hasn't been sent yet.</p>
              </div>
            </div>
            <div class="flex justify-end gap-2">
              <button @click="showUnsavedWarning = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                Keep editing
              </button>
              <button @click="discardAndClose" class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">
                Discard
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm z-10 p-5">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </div>
              <div>
                <h3 class="text-base font-semibold text-gray-900">Delete feedback?</h3>
                <p class="text-sm text-gray-500">This action cannot be undone.</p>
              </div>
            </div>
            <div class="flex justify-end gap-2">
              <button @click="showDeleteConfirm = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                Cancel
              </button>
              <button @click="deleteFeedback" class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">
                Delete
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick, h } from 'vue'
import { useAuth } from '@/stores/auth'
import toast from '@/services/toastService'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''
const auth = useAuth()

// Form state
const form = ref({ title: '', type: 'general', description: '' })
const loading = ref(true)
const submitting = ref(false)
const deleting = ref(false)
const showModal = ref(false)
const showUnsavedWarning = ref(false)
const showDeleteConfirm = ref(false)
const selectedFeedback = ref(null)
const feedbackList = ref([])
const descriptionEditor = ref(null)
const titleInput = ref(null)
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  from: 0,
  to: 0
})

// Feedback types configuration
const feedbackTypes = [
  {
    value: 'bug',
    label: 'Bug Report',
    icon: BugIcon
  },
  {
    value: 'feature',
    label: 'Feature',
    icon: LightbulbIcon
  },
  {
    value: 'general',
    label: 'General',
    icon: ChatIcon
  }
]

// Icon components
function BugIcon(props) {
  return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', ...props }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
  ])
}

function LightbulbIcon(props) {
  return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', ...props }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z' })
  ])
}

function ChatIcon(props) {
  return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', ...props }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' })
  ])
}

// Computed
const isFormValid = computed(() => form.value.title.trim() && form.value.description.trim() && form.value.type)
const hasUnsavedChanges = computed(() => form.value.title.trim() || form.value.description.trim())
const descriptionLength = computed(() => {
  // Rough estimate - actual length includes HTML
  const text = form.value.description.replace(/<[^>]*>/g, '')
  return text.length
})

// Helpers
const hasImages = (html) => html && html.includes('<img')

const typeLabel = (type) => ({
  'bug': 'Bug Report',
  'feature': 'Feature Request',
  'general': 'General'
}[type] || 'General')

const typeBadgeClass = (type) => ({
  'bug': 'bg-red-100 text-red-700',
  'feature': 'bg-purple-100 text-purple-700',
  'general': 'bg-gray-100 text-gray-700'
}[type] || 'bg-gray-100 text-gray-700')

const typeIconBg = (type) => ({
  'bug': 'bg-red-100',
  'feature': 'bg-purple-100',
  'general': 'bg-gray-100'
}[type] || 'bg-gray-100')

const typeIconColor = (type) => ({
  'bug': 'text-red-500',
  'feature': 'text-purple-500',
  'general': 'text-gray-500'
}[type] || 'text-gray-500')

const typeIcon = (type) => ({
  'bug': BugIcon,
  'feature': LightbulbIcon,
  'general': ChatIcon
}[type] || ChatIcon)

const statusClass = (status) => ({
  'pending': 'bg-yellow-100 text-yellow-700',
  'reviewed': 'bg-blue-100 text-blue-700',
  'resolved': 'bg-green-100 text-green-700'
}[status] || 'bg-gray-100 text-gray-700')

const statusDotClass = (status) => ({
  'pending': 'bg-yellow-500',
  'reviewed': 'bg-blue-500',
  'resolved': 'bg-green-500'
}[status] || 'bg-gray-500')

const formatDate = (str) => new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

// Handle description input from contenteditable
const onDescriptionInput = (e) => {
  form.value.description = e.target.innerHTML
}

// Handle paste - convert images to base64
const onPaste = async (e) => {
  const items = e.clipboardData?.items
  if (!items) return

  for (const item of items) {
    if (item.type.startsWith('image/')) {
      e.preventDefault()
      const file = item.getAsFile()
      if (file) {
        const base64 = await fileToBase64(file)
        insertImageAtCursor(base64)
      }
      return
    }
  }
}

const fileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result)
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

const insertImageAtCursor = (base64) => {
  const img = document.createElement('img')
  img.src = base64
  img.style.maxWidth = '100%'
  img.style.borderRadius = '8px'
  img.style.margin = '8px 0'

  const selection = window.getSelection()
  if (selection.rangeCount > 0) {
    const range = selection.getRangeAt(0)
    range.deleteContents()
    range.insertNode(img)
    range.setStartAfter(img)
    range.collapse(true)
    selection.removeAllRanges()
    selection.addRange(range)
  } else {
    descriptionEditor.value?.appendChild(img)
  }

  form.value.description = descriptionEditor.value?.innerHTML || ''
}

// Modal handlers
const openNewModal = () => {
  showModal.value = true
  nextTick(() => {
    titleInput.value?.focus()
  })
}

const handleEscape = (e) => {
  if (e.key === 'Escape') {
    handleBackdropClick()
  }
}

const handleBackdropClick = () => {
  if (submitting.value) return
  if (hasUnsavedChanges.value) {
    showUnsavedWarning.value = true
  } else {
    closeModal()
  }
}

const discardAndClose = () => {
  showUnsavedWarning.value = false
  closeModal()
}

const closeModal = () => {
  showModal.value = false
  form.value = { title: '', type: 'general', description: '' }
  if (descriptionEditor.value) descriptionEditor.value.innerHTML = ''
}

// Reset editor when modal opens
watch(showModal, (val) => {
  if (val) {
    nextTick(() => {
      if (descriptionEditor.value) {
        descriptionEditor.value.innerHTML = ''
        form.value.description = ''
      }
    })
  }
})

// Keyboard listener for escape
const globalKeyHandler = (e) => {
  if (e.key === 'Escape') {
    if (showUnsavedWarning.value) {
      showUnsavedWarning.value = false
    } else if (showDeleteConfirm.value) {
      showDeleteConfirm.value = false
    } else if (showModal.value) {
      handleBackdropClick()
    } else if (selectedFeedback.value) {
      selectedFeedback.value = null
    }
  }
}

onMounted(() => {
  fetchFeedback()
  window.addEventListener('keydown', globalKeyHandler)
})

onUnmounted(() => {
  window.removeEventListener('keydown', globalKeyHandler)
})

// API calls
const fetchFeedback = async (page = 1) => {
  loading.value = true
  try {
    const response = await fetch(`${API_BASE_URL}/api/feedback?page=${page}`, {
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
      },
    })
    if (response.ok) {
      const data = await response.json()
      feedbackList.value = data.data
      pagination.value = {
        currentPage: data.meta.current_page,
        lastPage: data.meta.last_page,
        total: data.meta.total,
        from: data.meta.from || 0,
        to: data.meta.to || 0
      }
    }
  } catch (e) {
    console.error('Failed to fetch feedback:', e)
  } finally {
    loading.value = false
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= pagination.value.lastPage) {
    fetchFeedback(page)
  }
}

const submitFeedback = async () => {
  if (!isFormValid.value || submitting.value) return
  submitting.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/api/feedback`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        title: form.value.title.trim(),
        type: form.value.type,
        description: form.value.description,
      }),
    })

    if (response.ok) {
      closeModal()
      toast.success('Feedback sent successfully!')
      fetchFeedback(1)
    } else {
      const data = await response.json()
      if (response.status === 429) {
        toast.error(data.message || 'Too many submissions. Please try again later.')
      } else if (response.status === 422) {
        toast.error(Object.values(data.errors || {})[0]?.[0] || 'Invalid input.')
      } else {
        toast.error('Failed to submit feedback.')
      }
    }
  } catch (e) {
    toast.error('Failed to submit feedback.')
  } finally {
    submitting.value = false
  }
}

const viewFeedback = (item) => {
  selectedFeedback.value = item
}

const confirmDelete = () => {
  showDeleteConfirm.value = true
}

const deleteFeedback = async () => {
  if (!selectedFeedback.value || deleting.value) return

  showDeleteConfirm.value = false
  deleting.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/api/feedback/${selectedFeedback.value.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
      },
    })

    if (response.ok) {
      selectedFeedback.value = null
      toast.success('Feedback deleted.')
      fetchFeedback(pagination.value.currentPage)
    } else {
      const data = await response.json()
      toast.error(data.message || 'Failed to delete feedback.')
    }
  } catch (e) {
    toast.error('Failed to delete feedback.')
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}

[contenteditable]:empty:before {
  content: attr(data-placeholder);
  color: #9ca3af;
  pointer-events: none;
}

[contenteditable] img {
  max-width: 100%;
  border-radius: 8px;
  margin: 8px 0;
}

.prose img {
  max-width: 100%;
  border-radius: 8px;
  margin: 8px 0;
}
</style>
