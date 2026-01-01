import { ref, computed, watch } from 'vue';

// Global recording state
const showSetupPanel = ref(false);
const isRecording = ref(false);
const isPaused = ref(false);
const recordingDuration = ref(0);
const mediaRecorder = ref(null);
const recordedChunks = ref([]);
const stream = ref(null);
const recordingInterrupted = ref(false);

// Recording settings
const selectedSource = ref('screen'); // 'screen', 'window', 'tab'
const microphoneEnabled = ref(true);
const cameraEnabled = ref(false);
const selectedMicrophone = ref(null);
const selectedCamera = ref(null);
const availableMicrophones = ref([]);
const availableCameras = ref([]);

// Recording timer
let timerInterval = null;

// LocalStorage keys
const RECORDING_STATE_KEY = 'screensense_recording_state';

// Save recording state to localStorage
const saveRecordingState = () => {
    const state = {
        isRecording: isRecording.value,
        isPaused: isPaused.value,
        duration: recordingDuration.value,
        selectedSource: selectedSource.value,
        microphoneEnabled: microphoneEnabled.value,
        cameraEnabled: cameraEnabled.value,
        timestamp: Date.now()
    };
    localStorage.setItem(RECORDING_STATE_KEY, JSON.stringify(state));
};

// Clear recording state from localStorage
const clearRecordingState = () => {
    localStorage.removeItem(RECORDING_STATE_KEY);
};

// Restore recording state from localStorage
const restoreRecordingState = () => {
    try {
        const saved = localStorage.getItem(RECORDING_STATE_KEY);
        if (saved) {
            const state = JSON.parse(saved);
            const timeSinceRecording = Date.now() - state.timestamp;

            // Only restore if recording was active within last 2 hours
            if (state.isRecording && timeSinceRecording < 2 * 60 * 60 * 1000) {
                // Restore settings
                selectedSource.value = state.selectedSource;
                microphoneEnabled.value = state.microphoneEnabled;
                cameraEnabled.value = state.cameraEnabled;
                recordingDuration.value = state.duration;
                isPaused.value = state.isPaused;

                // Mark as interrupted since MediaRecorder can't persist
                recordingInterrupted.value = true;
                isRecording.value = true; // Show the card

                return true;
            }
        }
    } catch (error) {
        console.error('Failed to restore recording state:', error);
    }
    return false;
};

// Watch recording state changes and save to localStorage
watch([isRecording, isPaused, recordingDuration], () => {
    if (isRecording.value) {
        saveRecordingState();
    }
}, { deep: true });

// Initialize once globally
let initialized = false;
if (!initialized) {
    restoreRecordingState();
    initialized = true;
}

export function useRecording() {
    const openSetupPanel = () => {
        showSetupPanel.value = true;
        loadDevices();
    };

    const closeSetupPanel = () => {
        // Don't allow closing during recording - card must stay visible
        if (isRecording.value) {
            return; // Prevent closing
        }
        showSetupPanel.value = false;
    };

    const loadDevices = async () => {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            availableMicrophones.value = devices.filter(device => device.kind === 'audioinput');
            availableCameras.value = devices.filter(device => device.kind === 'videoinput');

            // Set default devices if available
            if (availableMicrophones.value.length > 0 && !selectedMicrophone.value) {
                selectedMicrophone.value = availableMicrophones.value[0].deviceId;
            }
            if (availableCameras.value.length > 0 && !selectedCamera.value) {
                selectedCamera.value = availableCameras.value[0].deviceId;
            }
        } catch (error) {
            console.error('Error loading devices:', error);
        }
    };

    const startRecording = async () => {
        try {
            // Request screen capture based on selected source
            const displayMediaOptions = {
                video: {
                    width: { ideal: 3840 },
                    height: { ideal: 2160 },
                    frameRate: { ideal: 60 }
                },
                audio: true, // System audio
                preferCurrentTab: selectedSource.value === 'tab' // Auto-select current tab
            };

            // Add displaySurface hint (some browsers support this)
            if (selectedSource.value === 'screen') {
                displayMediaOptions.video.displaySurface = 'monitor';
            } else if (selectedSource.value === 'window') {
                displayMediaOptions.video.displaySurface = 'window';
            } else if (selectedSource.value === 'tab') {
                displayMediaOptions.video.displaySurface = 'browser';
            }

            // For Chrome/Edge: use surfaceSwitching and selfBrowserSurface
            if (selectedSource.value === 'tab') {
                displayMediaOptions.selfBrowserSurface = 'include'; // Include current tab
                displayMediaOptions.surfaceSwitching = 'include'; // Allow switching
                displayMediaOptions.systemAudio = 'exclude'; // For tab, exclude system audio
            }

            stream.value = await navigator.mediaDevices.getDisplayMedia(displayMediaOptions);

            // Add microphone audio if enabled
            if (microphoneEnabled.value && selectedMicrophone.value) {
                const audioStream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        deviceId: selectedMicrophone.value,
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                });

                // Mix audio tracks
                const audioContext = new AudioContext();
                const destination = audioContext.createMediaStreamDestination();

                stream.value.getAudioTracks().forEach(track => {
                    const source = audioContext.createMediaStreamSource(new MediaStream([track]));
                    source.connect(destination);
                });

                audioStream.getAudioTracks().forEach(track => {
                    const source = audioContext.createMediaStreamSource(new MediaStream([track]));
                    source.connect(destination);
                });

                // Replace audio track with mixed audio
                const existingAudioTracks = stream.value.getAudioTracks();
                existingAudioTracks.forEach(track => {
                    track.stop();
                    stream.value.removeTrack(track);
                });
                destination.stream.getAudioTracks().forEach(track => stream.value.addTrack(track));
            }

            // Add camera if enabled
            if (cameraEnabled.value && selectedCamera.value) {
                const cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { deviceId: selectedCamera.value }
                });
                cameraStream.getVideoTracks().forEach(track => stream.value.addTrack(track));
            }

            // Determine codec and bitrate
            const videoTrack = stream.value.getVideoTracks()[0];
            const settings = videoTrack.getSettings();
            const height = settings.height;

            let videoBitsPerSecond;
            if (height >= 2160) videoBitsPerSecond = 40000000; // 4K: 40 Mbps
            else if (height >= 1440) videoBitsPerSecond = 20000000; // 1440p: 20 Mbps
            else if (height >= 1080) videoBitsPerSecond = 12000000; // 1080p: 12 Mbps
            else videoBitsPerSecond = 8000000; // 720p and below: 8 Mbps

            let mimeType = 'video/webm;codecs=vp9';
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/webm;codecs=vp8';
            }
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/webm';
            }

            // Create MediaRecorder
            mediaRecorder.value = new MediaRecorder(stream.value, {
                mimeType,
                videoBitsPerSecond
            });

            recordedChunks.value = [];

            mediaRecorder.value.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    recordedChunks.value.push(event.data);
                }
            };

            mediaRecorder.value.onstop = () => {
                stopTimer();
            };

            mediaRecorder.value.start(1000); // Collect data every second
            isRecording.value = true;
            // Don't close the panel - it will transform to show recording status
            startTimer();

        } catch (error) {
            console.error('Error starting recording:', error);
            throw error;
        }
    };

    const pauseRecording = () => {
        if (mediaRecorder.value && mediaRecorder.value.state === 'recording') {
            mediaRecorder.value.pause();
            isPaused.value = true;
            stopTimer();
        }
    };

    const resumeRecording = () => {
        if (mediaRecorder.value && mediaRecorder.value.state === 'paused') {
            mediaRecorder.value.resume();
            isPaused.value = false;
            startTimer();
        }
    };

    const stopRecording = () => {
        return new Promise((resolve) => {
            if (mediaRecorder.value) {
                mediaRecorder.value.onstop = () => {
                    stopTimer();
                    isRecording.value = false;
                    isPaused.value = false;
                    recordingInterrupted.value = false;
                    clearRecordingState(); // Clear from localStorage

                    // Create blob from recorded chunks
                    const blob = new Blob(recordedChunks.value, {
                        type: mediaRecorder.value.mimeType
                    });

                    // Stop all tracks
                    if (stream.value) {
                        stream.value.getTracks().forEach(track => track.stop());
                    }

                    resolve(blob);
                };

                mediaRecorder.value.stop();
            } else if (recordingInterrupted.value) {
                // Recording was interrupted (page reload) - can't recover media
                stopTimer();
                isRecording.value = false;
                isPaused.value = false;
                recordingInterrupted.value = false;
                recordingDuration.value = 0;
                clearRecordingState();
                resolve(null);
            }
        });
    };

    const deleteRecording = () => {
        if (mediaRecorder.value) {
            if (mediaRecorder.value.state !== 'inactive') {
                mediaRecorder.value.stop();
            }
            recordedChunks.value = [];
        }

        if (stream.value) {
            stream.value.getTracks().forEach(track => track.stop());
        }

        isRecording.value = false;
        isPaused.value = false;
        recordingDuration.value = 0;
        recordingInterrupted.value = false;
        stopTimer();
        clearRecordingState(); // Clear from localStorage
    };

    const startTimer = () => {
        timerInterval = setInterval(() => {
            recordingDuration.value++;
        }, 1000);
    };

    const stopTimer = () => {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    };

    const formatDuration = computed(() => {
        const hours = Math.floor(recordingDuration.value / 3600);
        const minutes = Math.floor((recordingDuration.value % 3600) / 60);
        const seconds = recordingDuration.value % 60;

        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    });

    return {
        // State
        showSetupPanel,
        isRecording,
        isPaused,
        recordingDuration,
        recordingInterrupted,
        formatDuration,

        // Settings
        selectedSource,
        microphoneEnabled,
        cameraEnabled,
        selectedMicrophone,
        selectedCamera,
        availableMicrophones,
        availableCameras,

        // Actions
        openSetupPanel,
        closeSetupPanel,
        startRecording,
        pauseRecording,
        resumeRecording,
        stopRecording,
        deleteRecording,
        loadDevices
    };
}
