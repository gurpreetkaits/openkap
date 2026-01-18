import { ref, computed, watch } from 'vue';
import { useAuth } from '@/stores/auth';

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888';

// Global recording state
const showSetupPanel = ref(false);
const isRecording = ref(false);
const isPaused = ref(false);
const recordingDuration = ref(0);
const mediaRecorder = ref(null);
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

// Upload state
const sessionId = ref(null);
const uploadedBytes = ref(0);
const chunksUploaded = ref(0);
const uploadQueue = ref([]);
let chunkIndex = 0;

// Mic audio level for waveform
const micAudioLevel = ref(0);
let audioAnalyser = null;
let audioLevelInterval = null;

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
        sessionId: sessionId.value,
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
                sessionId.value = state.sessionId;

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

// Get auth token
const getAuthToken = () => {
    const auth = useAuth();
    return auth.token.value;
};

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

    // Start upload session
    const startUploadSession = async () => {
        const timestamp = new Date().toLocaleString();
        const title = `Recording ${timestamp}`;

        const response = await fetch(`${API_BASE_URL}/api/stream/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`
            },
            body: JSON.stringify({
                title,
                mime_type: 'video/webm'
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            if (response.status === 403 && errorData.error === 'video_limit_reached') {
                const error = new Error(errorData.message || 'You have reached your video limit. Please upgrade to continue.');
                error.code = 'VIDEO_LIMIT_REACHED';
                throw error;
            }
            throw new Error('Failed to start upload session');
        }

        const data = await response.json();
        return data.session_id;
    };

    // Upload a chunk to backend
    const uploadChunk = async (chunk, index) => {
        if (!sessionId.value) return;

        const formData = new FormData();
        formData.append('chunk', chunk, `chunk_${index}.webm`);
        formData.append('chunk_index', index);

        try {
            const response = await fetch(`${API_BASE_URL}/api/stream/${sessionId.value}/chunk`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${getAuthToken()}`
                },
                body: formData
            });

            if (response.ok) {
                const data = await response.json();
                uploadedBytes.value = data.total_size;
                chunksUploaded.value = data.chunks_received;
            }
        } catch (err) {
            console.error('Failed to upload chunk:', err);
            uploadQueue.value.push({ chunk, index });
        }
    };

    // Process upload queue (retry failed chunks)
    const processUploadQueue = async () => {
        while (uploadQueue.value.length > 0) {
            const { chunk, index } = uploadQueue.value.shift();
            await uploadChunk(chunk, index);
        }
    };

    // Complete upload - returns instantly, Bunny upload happens in background
    const completeUpload = async () => {
        if (!sessionId.value) return null;

        // Process any remaining chunks in queue
        await processUploadQueue();

        const response = await fetch(`${API_BASE_URL}/api/stream/${sessionId.value}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`
            },
            body: JSON.stringify({
                duration: recordingDuration.value
            })
        });

        if (!response.ok) {
            throw new Error('Failed to complete upload');
        }

        const data = await response.json();
        return data.video;
    };

    // Complete upload with retry logic (3 attempts)
    const completeUploadWithRetry = async (maxRetries = 3) => {
        let lastError = null;

        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                console.log(`Attempting to complete upload (attempt ${attempt}/${maxRetries})...`);
                const video = await completeUpload();
                console.log('Upload completed successfully');
                return video;
            } catch (error) {
                console.error(`Upload completion attempt ${attempt} failed:`, error);
                lastError = error;

                // Don't retry on auth errors
                if (error.message?.includes('401') || error.message?.includes('403')) {
                    throw error;
                }

                // Wait before retrying (exponential backoff: 1s, 2s, 4s)
                if (attempt < maxRetries) {
                    const delay = Math.pow(2, attempt - 1) * 1000;
                    console.log(`Retrying in ${delay}ms...`);
                    await new Promise(resolve => setTimeout(resolve, delay));
                }
            }
        }

        // All retries failed - but recording is safe on server (will be auto-recovered)
        throw lastError;
    };

    // Cancel upload session
    const cancelUpload = async () => {
        if (!sessionId.value) return;

        try {
            await fetch(`${API_BASE_URL}/api/stream/${sessionId.value}/cancel`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${getAuthToken()}`
                }
            });
        } catch (err) {
            console.error('Failed to cancel upload:', err);
        }
    };

    // Reset upload state
    const resetUploadState = () => {
        sessionId.value = null;
        uploadedBytes.value = 0;
        chunksUploaded.value = 0;
        uploadQueue.value = [];
        chunkIndex = 0;
    };

    const startRecording = async () => {
        try {
            // Start upload session FIRST
            sessionId.value = await startUploadSession();
            if (!sessionId.value) {
                throw new Error('Failed to start upload session');
            }

            // Reset upload state
            uploadedBytes.value = 0;
            chunksUploaded.value = 0;
            uploadQueue.value = [];
            chunkIndex = 0;

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

                // Set up audio analyser for mic waveform
                const micSource = audioContext.createMediaStreamSource(audioStream);
                audioAnalyser = audioContext.createAnalyser();
                audioAnalyser.fftSize = 256;
                micSource.connect(audioAnalyser);
                micSource.connect(destination);

                // Start monitoring mic levels
                const dataArray = new Uint8Array(audioAnalyser.frequencyBinCount);
                audioLevelInterval = setInterval(() => {
                    if (audioAnalyser && isRecording.value && !isPaused.value) {
                        audioAnalyser.getByteFrequencyData(dataArray);
                        const sum = dataArray.reduce((a, b) => a + b, 0);
                        const avg = sum / dataArray.length;
                        micAudioLevel.value = Math.min(avg / 128, 1); // Normalize to 0-1
                    } else {
                        micAudioLevel.value = 0;
                    }
                }, 50);

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

            // Stream chunks to backend during recording (fast, instant)
            // Backend handles Bunny upload in background after recording completes
            mediaRecorder.value.ondataavailable = async (event) => {
                if (event.data.size > 0) {
                    uploadChunk(event.data, chunkIndex);
                    chunkIndex++;
                }
            };

            mediaRecorder.value.onstop = () => {
                stopTimer();
            };

            // Handle stream ending (user clicks "Stop sharing")
            stream.value.getVideoTracks()[0].onended = () => {
                if (isRecording.value) {
                    // Stream ended by user, stop recording
                    mediaRecorder.value.stop();
                }
            };

            mediaRecorder.value.start(1000); // Collect data every second
            isRecording.value = true;
            startTimer();

        } catch (error) {
            console.error('Error starting recording:', error);
            // Cancel upload session if we started one
            if (sessionId.value) {
                cancelUpload();
                resetUploadState();
            }
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

    const stopAudioAnalysis = () => {
        if (audioLevelInterval) {
            clearInterval(audioLevelInterval);
            audioLevelInterval = null;
        }
        audioAnalyser = null;
        micAudioLevel.value = 0;
    };

    const stopRecording = async () => {
        return new Promise((resolve, reject) => {
            if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
                mediaRecorder.value.onstop = async () => {
                    stopTimer();
                    stopAudioAnalysis();

                    // Stop all tracks
                    if (stream.value) {
                        stream.value.getTracks().forEach(track => track.stop());
                    }

                    try {
                        // Complete the upload and get video info (with retry logic)
                        const video = await completeUploadWithRetry();

                        isRecording.value = false;
                        isPaused.value = false;
                        recordingInterrupted.value = false;
                        clearRecordingState();
                        resetUploadState();

                        resolve(video);
                    } catch (error) {
                        // Even if completion fails, recording is safe on server
                        console.error('Failed to complete upload after retries, but recording is safe:', error);
                        reject(error);
                    }
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

                // Cancel the upload session if there was one
                if (sessionId.value) {
                    cancelUpload();
                    resetUploadState();
                }

                resolve(null);
            } else {
                resolve(null);
            }
        });
    };

    const deleteRecording = async () => {
        if (mediaRecorder.value) {
            if (mediaRecorder.value.state !== 'inactive') {
                mediaRecorder.value.stop();
            }
        }

        if (stream.value) {
            stream.value.getTracks().forEach(track => track.stop());
        }

        // Cancel the upload session
        if (sessionId.value) {
            await cancelUpload();
            resetUploadState();
        }

        isRecording.value = false;
        isPaused.value = false;
        recordingDuration.value = 0;
        recordingInterrupted.value = false;
        stopTimer();
        stopAudioAnalysis();
        clearRecordingState();
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

    // Format bytes for display
    const formatBytes = (bytes) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const formattedUploadedBytes = computed(() => formatBytes(uploadedBytes.value));

    return {
        // State
        showSetupPanel,
        isRecording,
        isPaused,
        recordingDuration,
        recordingInterrupted,
        formatDuration,

        // Upload state
        uploadedBytes,
        chunksUploaded,
        formattedUploadedBytes,

        // Audio level for waveform
        micAudioLevel,

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
