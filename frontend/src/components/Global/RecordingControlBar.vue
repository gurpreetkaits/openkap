<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300"
      leave-active-class="transition-all duration-300"
      enter-from-class="opacity-0 translate-y-4"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div
        v-if="isRecording"
        class="fixed top-8 left-1/2 -translate-x-1/2 z-50"
      >
        <div class="bg-white rounded-full shadow-2xl px-6 py-4 flex items-center gap-6 border border-gray-200">
          <!-- Recording Indicator -->
          <div class="flex items-center gap-3">
            <div class="relative">
              <div class="w-3 h-3 bg-red-600 rounded-full animate-pulse"></div>
              <div class="absolute inset-0 w-3 h-3 bg-red-600 rounded-full animate-ping opacity-75"></div>
            </div>
            <span class="text-lg font-mono font-semibold text-gray-900">{{ formatDuration }}</span>
          </div>

          <!-- Divider -->
          <div class="w-px h-8 bg-gray-300"></div>

          <!-- Controls -->
          <div class="flex items-center gap-2">
            <!-- Pause/Resume Button -->
            <button
              v-if="!isPaused"
              @click="pauseRecording"
              class="p-2.5 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors group"
              title="Pause Recording"
            >
              <svg class="w-5 h-5 text-gray-700 group-hover:text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
              </svg>
            </button>

            <button
              v-else
              @click="resumeRecording"
              class="p-2.5 rounded-full bg-blue-600 hover:bg-blue-700 transition-colors group"
              title="Resume Recording"
            >
              <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </button>

            <!-- Stop Button -->
            <button
              @click="handleStopRecording"
              :disabled="saving"
              class="p-2.5 rounded-full bg-orange-600 hover:bg-orange-700 transition-colors group disabled:opacity-50 disabled:cursor-not-allowed"
              title="Stop & Save Recording"
            >
              <svg v-if="!saving" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 6h12v12H6z" />
              </svg>
              <svg v-else class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </button>

            <!-- Delete Button -->
            <button
              @click="handleDeleteRecording"
              class="p-2.5 rounded-full bg-red-600 hover:bg-red-700 transition-colors group"
              title="Delete Recording"
            >
              <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

          <!-- Paused Indicator -->
          <Transition
            enter-active-class="transition-all duration-200"
            leave-active-class="transition-all duration-200"
            enter-from-class="opacity-0 scale-90"
            leave-to-class="opacity-0 scale-90"
          >
            <div v-if="isPaused" class="flex items-center gap-2 text-sm font-medium text-blue-600">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
              </svg>
              Paused
            </div>
          </Transition>
        </div>
      </div>
    </Transition>

    <!-- Confirmation Modal for Delete -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showDeleteConfirm"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="showDeleteConfirm = false"
      >
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md mx-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Recording?</h3>
          <p class="text-gray-600 mb-6">
            Are you sure you want to delete this recording? This action cannot be undone.
          </p>
          <div class="flex gap-3">
            <button
              @click="confirmDelete"
              class="flex-1 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors"
            >
              Delete
            </button>
            <button
              @click="showDeleteConfirm = false"
              class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import { useRecording } from '../../composables/useRecording';
import { useRouter } from 'vue-router';

const router = useRouter();

const {
  isRecording,
  isPaused,
  formatDuration,
  pauseRecording,
  resumeRecording,
  stopRecording,
  deleteRecording
} = useRecording();

const showDeleteConfirm = ref(false);
const saving = ref(false);

const handleStopRecording = async () => {
  saving.value = true;
  try {
    const blob = await stopRecording();

    // Upload the recording
    const formData = new FormData();
    formData.append('video', blob, 'recording.webm');
    formData.append('title', `Recording ${new Date().toLocaleString()}`);

    const token = localStorage.getItem('token');
    const response = await fetch('/api/videos', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      },
      body: formData
    });

    if (!response.ok) {
      throw new Error('Upload failed');
    }

    const result = await response.json();

    // Navigate to the video page
    router.push(`/video/${result.video.id}`);
  } catch (error) {
    console.error('Failed to save recording:', error);
    alert('Failed to save recording. Please try again.');
  } finally {
    saving.value = false;
  }
};

const handleDeleteRecording = () => {
  showDeleteConfirm.value = true;
};

const confirmDelete = () => {
  deleteRecording();
  showDeleteConfirm.value = false;
};
</script>
