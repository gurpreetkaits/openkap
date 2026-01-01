<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300"
      leave-active-class="transition-all duration-300"
      enter-from-class="opacity-0 translate-x-8"
      leave-to-class="opacity-0 translate-x-8"
    >
      <div
        v-if="showSetupPanel || isRecording"
        ref="cardElement"
        :style="{
          position: 'fixed',
          left: `${cardPosition.x}px`,
          top: `${cardPosition.y}px`,
          zIndex: 50
        }"
        class="w-[340px] max-w-[calc(100vw-2rem)]"
      >
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden"
        >
          <!-- Recording Status View -->
          <div v-if="isRecording" class="p-5">
            <div
              @mousedown="startDrag"
              class="flex items-center justify-between mb-3 cursor-move select-none"
            >
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                </svg>
                <h3 class="text-base font-semibold text-gray-900">Recording</h3>
              </div>
              <div class="flex items-center gap-1">
                <span v-if="recordingInterrupted" class="text-xs text-orange-600 font-medium mr-2">
                  Interrupted
                </span>
              </div>
            </div>

            <!-- Interrupted Warning -->
            <div v-if="recordingInterrupted" class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
              <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-orange-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-xs text-orange-800">
                  <strong>Recording Interrupted</strong>
                  <p class="mt-1">Your recording was interrupted by a page reload. The video data cannot be recovered. Please delete this session and start a new recording.</p>
                </div>
              </div>
            </div>

            <!-- Recording Indicator -->
            <div class="flex items-center gap-3 mb-4 p-3 bg-gradient-to-r from-red-50 to-orange-50 rounded-lg">
              <div class="relative">
                <div v-if="!recordingInterrupted" class="w-2.5 h-2.5 bg-red-600 rounded-full animate-pulse"></div>
                <div v-if="!recordingInterrupted" class="absolute inset-0 w-2.5 h-2.5 bg-red-600 rounded-full animate-ping opacity-75"></div>
                <div v-else class="w-2.5 h-2.5 bg-orange-600 rounded-full"></div>
              </div>
              <div class="flex-1">
                <div class="text-xl font-mono font-bold text-gray-900">{{ formatDuration }}</div>
                <div class="text-xs text-gray-600">
                  {{ recordingInterrupted ? 'Interrupted' : (isPaused ? 'Paused' : 'Recording...') }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm font-medium text-gray-700">{{ formattedUploadedBytes }}</div>
                <div class="text-xs text-gray-500">uploaded</div>
              </div>
            </div>

            <!-- Mic Waveform Visualizer -->
            <div v-if="microphoneEnabled && !recordingInterrupted" class="mb-4 p-3 bg-gray-50 rounded-lg">
              <div class="flex items-center gap-2 mb-2">
                <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
                <span class="text-xs font-medium text-gray-700">Microphone Active</span>
              </div>
              <div class="flex items-center justify-center gap-0.5 h-8">
                <div
                  v-for="i in 16"
                  :key="i"
                  class="w-1 bg-green-500 rounded-full transition-all duration-75"
                  :style="{
                    height: isPaused ? '4px' : (4 + (micAudioLevel * 24 * Math.sin(i * 0.5 + Date.now() * 0.005))) + 'px',
                    opacity: isPaused ? 0.3 : 0.5 + (micAudioLevel * 0.5)
                  }"
                ></div>
              </div>
            </div>

            <!-- Recording Settings Summary -->
            <div class="space-y-2 text-sm mb-4">
              <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Source</span>
                <span class="font-medium text-gray-900 capitalize">{{ selectedSource }}</span>
              </div>
              <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Microphone</span>
                <span :class="microphoneEnabled ? 'text-green-600' : 'text-gray-400'">
                  {{ microphoneEnabled ? 'On' : 'Off' }}
                </span>
              </div>
              <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Camera</span>
                <span :class="cameraEnabled ? 'text-green-600' : 'text-gray-400'">
                  {{ cameraEnabled ? 'On' : 'Off' }}
                </span>
              </div>
            </div>

            <!-- Recording Controls -->
            <div class="flex items-center gap-2 pt-2 border-t border-gray-200">
              <!-- Pause/Resume Button -->
              <button
                v-if="!isPaused && !recordingInterrupted"
                @click="pauseRecording"
                class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-sm font-medium"
                title="Pause Recording"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                </svg>
                Pause
              </button>

              <button
                v-else-if="!recordingInterrupted"
                @click="resumeRecording"
                class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium"
                title="Resume Recording"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
                Resume
              </button>

              <!-- Stop Button (disabled if interrupted) -->
              <button
                @click="handleStopRecording"
                :disabled="saving || recordingInterrupted"
                :class="[
                  'flex-1 flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg transition-colors text-sm font-medium',
                  recordingInterrupted
                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    : 'bg-orange-600 hover:bg-orange-700 text-white disabled:opacity-50 disabled:cursor-not-allowed'
                ]"
                :title="recordingInterrupted ? 'Cannot save interrupted recording' : 'Stop & Save Recording'"
              >
                <svg v-if="!saving" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M6 6h12v12H6z" />
                </svg>
                <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ saving ? 'Saving...' : 'Stop' }}
              </button>

              <!-- Delete Button -->
              <button
                @click="handleDeleteRecording"
                class="px-3 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                title="Delete Recording"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Setup View -->
          <div v-else>
            <!-- Header -->
            <div class="p-5 border-b border-gray-200">
              <div
                @mousedown="startDrag"
                class="flex items-center justify-between mb-3 cursor-move select-none"
              >
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                  </svg>
                  <svg class="w-4 h-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  <h2 class="text-base font-bold text-gray-900">ScreenBuddy</h2>
                </div>
                <button
                  @click="closeSetupPanel"
                  @mousedown.stop
                  class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
                >
                  <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Source Selection -->
              <div class="flex items-center gap-2">
                <button
                  @click="selectedSource = 'screen'"
                  :class="[
                    'flex-1 flex flex-col items-center gap-1.5 p-2.5 rounded-lg border-2 transition-all',
                    selectedSource === 'screen'
                      ? 'border-blue-600 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <span class="text-xs font-medium">Screen</span>
                </button>

                <button
                  @click="selectedSource = 'window'"
                  :class="[
                    'flex-1 flex flex-col items-center gap-1.5 p-2.5 rounded-lg border-2 transition-all',
                    selectedSource === 'window'
                      ? 'border-blue-600 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                  </svg>
                  <span class="text-xs font-medium">Window</span>
                </button>

                <button
                  @click="selectedSource = 'tab'"
                  :class="[
                    'flex-1 flex flex-col items-center gap-1.5 p-2.5 rounded-lg border-2 transition-all',
                    selectedSource === 'tab'
                      ? 'border-blue-600 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                  <span class="text-xs font-medium">Tab</span>
                </button>
              </div>
            </div>

            <!-- Recording Settings -->
            <div class="p-5 space-y-3">
              <h3 class="text-sm font-semibold text-gray-900">Recording Settings</h3>

              <!-- Microphone -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    Microphone
                  </label>
                  <button
                    @click="microphoneEnabled = !microphoneEnabled"
                    :class="[
                      'relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                      microphoneEnabled ? 'bg-blue-600' : 'bg-gray-300'
                    ]"
                  >
                    <span
                      :class="[
                        'inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform',
                        microphoneEnabled ? 'translate-x-5' : 'translate-x-0.5'
                      ]"
                    />
                  </button>
                </div>
                <select
                  v-if="microphoneEnabled"
                  v-model="selectedMicrophone"
                  class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option v-for="mic in availableMicrophones" :key="mic.deviceId" :value="mic.deviceId">
                    {{ mic.label || `Microphone ${availableMicrophones.indexOf(mic) + 1}` }}
                  </option>
                </select>
              </div>

              <!-- Camera -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Camera
                  </label>
                  <button
                    @click="cameraEnabled = !cameraEnabled"
                    :class="[
                      'relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                      cameraEnabled ? 'bg-blue-600' : 'bg-gray-300'
                    ]"
                  >
                    <span
                      :class="[
                        'inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform',
                        cameraEnabled ? 'translate-x-5' : 'translate-x-0.5'
                      ]"
                    />
                  </button>
                </div>
                <select
                  v-if="cameraEnabled"
                  v-model="selectedCamera"
                  class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option v-for="camera in availableCameras" :key="camera.deviceId" :value="camera.deviceId">
                    {{ camera.label || `Camera ${availableCameras.indexOf(camera) + 1}` }}
                  </option>
                </select>
              </div>

              <!-- Advanced Options (Collapsed by default, shows key specs) -->
              <div class="text-xs text-gray-500 pt-2">
                <p>4K • 60fps • VP9 codec</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="px-5 pb-5 pt-0 space-y-2">
              <button
                @click="handleStartRecording"
                :disabled="loading"
                class="w-full px-4 py-2.5 bg-gradient-to-r from-orange-600 to-red-600 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed text-sm"
              >
                <span v-if="!loading">Record Now</span>
                <span v-else class="flex items-center justify-center gap-2">
                  <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Starting...
                </span>
              </button>
              <button
                @click="closeSetupPanel"
                class="w-full px-4 py-2 text-sm text-gray-600 font-medium hover:text-gray-900 transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Delete Confirmation Modal -->
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
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-sm mx-4">
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
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useRecording } from '../../composables/useRecording';

const router = useRouter();

const {
  showSetupPanel,
  isRecording,
  isPaused,
  recordingInterrupted,
  recordingDuration,
  formatDuration,
  formattedUploadedBytes,
  micAudioLevel,
  selectedSource,
  microphoneEnabled,
  cameraEnabled,
  selectedMicrophone,
  selectedCamera,
  availableMicrophones,
  availableCameras,
  closeSetupPanel,
  startRecording,
  pauseRecording,
  resumeRecording,
  stopRecording,
  deleteRecording
} = useRecording();

const loading = ref(false);
const saving = ref(false);
const showDeleteConfirm = ref(false);
const cardElement = ref(null);

// Card position (draggable)
const POSITION_KEY = 'screensense_card_position';
const cardPosition = ref({
  x: window.innerWidth - 340 - 24, // Default: right side
  y: 96 // Default: top
});

// Dragging state
const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });

// Load saved position
const loadPosition = () => {
  try {
    const saved = localStorage.getItem(POSITION_KEY);
    if (saved) {
      const pos = JSON.parse(saved);
      // Validate position is within viewport
      if (pos.x >= 0 && pos.x <= window.innerWidth - 340 &&
          pos.y >= 0 && pos.y <= window.innerHeight - 200) {
        cardPosition.value = pos;
      }
    }
  } catch (error) {
    console.error('Failed to load card position:', error);
  }
};

// Save position
const savePosition = () => {
  localStorage.setItem(POSITION_KEY, JSON.stringify(cardPosition.value));
};

// Start dragging
const startDrag = (e) => {
  isDragging.value = true;
  dragOffset.value = {
    x: e.clientX - cardPosition.value.x,
    y: e.clientY - cardPosition.value.y
  };
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('mouseup', stopDrag);
};

// During drag
const onDrag = (e) => {
  if (isDragging.value) {
    const newX = e.clientX - dragOffset.value.x;
    const newY = e.clientY - dragOffset.value.y;

    // Keep within viewport bounds
    const maxX = window.innerWidth - 340;
    const maxY = window.innerHeight - 200;

    cardPosition.value = {
      x: Math.max(0, Math.min(newX, maxX)),
      y: Math.max(0, Math.min(newY, maxY))
    };
  }
};

// Stop dragging
const stopDrag = () => {
  if (isDragging.value) {
    isDragging.value = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    savePosition();
  }
};

onMounted(() => {
  loadPosition();
});

onUnmounted(() => {
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', stopDrag);
});

const handleStartRecording = async () => {
  loading.value = true;
  try {
    await startRecording();
  } catch (error) {
    console.error('Failed to start recording:', error);
    alert('Failed to start recording. Please ensure you have granted the necessary permissions.');
  } finally {
    loading.value = false;
  }
};

const handleStopRecording = async () => {
  saving.value = true;
  try {
    // stopRecording now completes the chunk upload and returns the video
    const video = await stopRecording();

    if (!video) {
      // Recording was interrupted, can't save
      alert('Recording was interrupted and cannot be saved. Please start a new recording.');
      return;
    }

    // Navigate to the video page
    router.push(`/video/${video.id}`);
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
