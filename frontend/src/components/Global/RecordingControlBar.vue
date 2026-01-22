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
        <div class="card bg-base-100 shadow-2xl">
          <div class="card-body p-4 flex-row items-center gap-6">
            <!-- Recording Indicator -->
            <div class="flex items-center gap-3">
              <div class="relative">
                <div class="w-3 h-3 bg-error rounded-full animate-pulse"></div>
                <div class="absolute inset-0 w-3 h-3 bg-error rounded-full animate-ping opacity-75"></div>
              </div>
              <span class="text-lg font-mono font-semibold">{{ formatDuration }}</span>
            </div>

            <!-- Divider -->
            <div class="divider divider-horizontal m-0"></div>

            <!-- Controls -->
            <div class="flex items-center gap-2">
              <!-- Pause/Resume Button -->
              <button
                v-if="!isPaused"
                @click="pauseRecording"
                class="btn btn-circle btn-ghost"
                title="Pause Recording"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                </svg>
              </button>

              <button
                v-else
                @click="resumeRecording"
                class="btn btn-circle btn-primary"
                title="Resume Recording"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              </button>

              <!-- Stop Button -->
              <button
                @click="handleStopRecording"
                :disabled="saving"
                class="btn btn-circle btn-primary"
                title="Stop & Save Recording"
              >
                <span v-if="saving" class="loading loading-spinner loading-sm"></span>
                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M6 6h12v12H6z" />
                </svg>
              </button>

              <!-- Delete Button -->
              <button
                @click="handleDeleteRecording"
                class="btn btn-circle btn-error"
                title="Delete Recording"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
              <div v-if="isPaused" class="badge badge-info gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                </svg>
                Paused
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Confirmation Modal for Delete -->
    <dialog
      ref="deleteDialogRef"
      class="modal"
      :class="{ 'modal-open': showDeleteConfirm }"
    >
      <div class="modal-box">
        <h3 class="font-bold text-lg">Delete Recording?</h3>
        <p class="py-4 text-base-content/70">
          Are you sure you want to delete this recording? This action cannot be undone.
        </p>
        <div class="modal-action">
          <button @click="showDeleteConfirm = false" class="btn btn-ghost">Cancel</button>
          <button @click="confirmDelete" class="btn btn-error">Delete</button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showDeleteConfirm = false">close</button>
      </form>
    </dialog>
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
const deleteDialogRef = ref(null);

const handleStopRecording = async () => {
  saving.value = true;
  try {
    const blob = await stopRecording();

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
