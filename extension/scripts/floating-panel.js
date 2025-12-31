// Floating Panel UI for ScreenSense Extension
// Handles device selection, recording controls, and camera preview

class FloatingPanel {
  constructor() {
    this.panel = null;
    this.cameraPreview = null;
    this.devices = {
      cameras: [],
      microphones: []
    };
    this.selectedDevices = {
      camera: null,
      microphone: null
    };
    this.isRecording = false;
    this.isPaused = false;
    this.startTime = null;
    this.timerInterval = null;
  }

  async initialize() {
    await this.enumerateDevices();
    this.createSetupPanel();
  }

  async enumerateDevices() {
    try {
      // Request permissions first
      await navigator.mediaDevices.getUserMedia({ audio: true, video: true });

      const devices = await navigator.mediaDevices.enumerateDevices();

      this.devices.cameras = devices.filter(device => device.kind === 'videoinput');
      this.devices.microphones = devices.filter(device => device.kind === 'audioinput');

      // Set default selections
      if (this.devices.cameras.length > 0) {
        this.selectedDevices.camera = this.devices.cameras[0].deviceId;
      }
      if (this.devices.microphones.length > 0) {
        this.selectedDevices.microphone = this.devices.microphones[0].deviceId;
      }

      console.log('Devices enumerated:', {
        cameras: this.devices.cameras.length,
        microphones: this.devices.microphones.length
      });
    } catch (error) {
      console.error('Error enumerating devices:', error);
    }
  }

  createSetupPanel() {
    if (this.panel) {
      this.panel.remove();
    }

    this.panel = document.createElement('div');
    this.panel.id = 'screensense-floating-panel';
    this.panel.innerHTML = `
      <div class="ss-panel-header">
        <div class="ss-panel-logo">
          <img src="${chrome.runtime.getURL('icons/logo.png')}" alt="ScreenSense Logo" />
        </div>
        <div class="ss-panel-title">
          <div class="ss-panel-title-text">ScreenSense</div>
          <div class="ss-panel-subtitle">Screen Recorder</div>
        </div>
        <button class="ss-panel-close" id="ss-close-panel">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <div class="ss-panel-body">
        <div class="ss-device-section">
          <label class="ss-label">
            <svg class="ss-label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M23 7l-7 5 7 5V7z"/>
              <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
            </svg>
            <span>Camera</span>
          </label>
          <div class="ss-select-wrapper">
            <select id="ss-camera-select" class="ss-select">
              ${this.devices.cameras.map(camera =>
                `<option value="${camera.deviceId}">${camera.label || 'Camera ' + (this.devices.cameras.indexOf(camera) + 1)}</option>`
              ).join('')}
              ${this.devices.cameras.length === 0 ? '<option>No cameras found</option>' : ''}
            </select>
          </div>
          <label class="ss-toggle-wrapper">
            <input type="checkbox" id="ss-camera-toggle" ${this.devices.cameras.length > 0 ? 'checked' : 'disabled'}>
            <span class="ss-toggle-text">Enable Camera</span>
          </label>
        </div>

        <div class="ss-device-section">
          <label class="ss-label">
            <svg class="ss-label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
              <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
              <line x1="12" y1="19" x2="12" y2="23"/>
              <line x1="8" y1="23" x2="16" y2="23"/>
            </svg>
            <span>Microphone</span>
          </label>
          <div class="ss-select-wrapper">
            <select id="ss-mic-select" class="ss-select">
              ${this.devices.microphones.map(mic =>
                `<option value="${mic.deviceId}">${mic.label || 'Microphone ' + (this.devices.microphones.indexOf(mic) + 1)}</option>`
              ).join('')}
              ${this.devices.microphones.length === 0 ? '<option>No microphones found</option>' : ''}
            </select>
          </div>
          <label class="ss-toggle-wrapper">
            <input type="checkbox" id="ss-mic-toggle" ${this.devices.microphones.length > 0 ? 'checked' : 'disabled'}>
            <span class="ss-toggle-text">Enable Microphone</span>
          </label>
        </div>

        <button id="ss-start-recording" class="ss-start-btn">
          <span class="ss-rec-dot"></span>
          Start Recording
        </button>
      </div>
    `;

    // Add styles
    this.injectStyles();

    // Add event listeners
    this.attachSetupEventListeners();

    // Append to body
    document.body.appendChild(this.panel);
  }

  createRecordingPanel() {
    if (this.panel) {
      this.panel.remove();
    }

    this.panel = document.createElement('div');
    this.panel.id = 'screensense-floating-panel';
    this.panel.classList.add('ss-recording');
    this.panel.innerHTML = `
      <div class="ss-panel-header">
        <div class="ss-panel-logo">
          <img src="${chrome.runtime.getURL('icons/logo.png')}" alt="ScreenSense Logo" />
        </div>
        <div class="ss-panel-title">
          <div class="ss-panel-title-text">Recording</div>
          <div class="ss-panel-subtitle">
            <span class="ss-pulse-dot"></span>
            <span id="ss-timer">00:00</span>
          </div>
        </div>
      </div>

      <div class="ss-panel-body">
        <div class="ss-recording-controls">
          <button id="ss-pause-btn" class="ss-control-btn" title="Pause">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <rect x="6" y="4" width="4" height="16"/>
              <rect x="14" y="4" width="4" height="16"/>
            </svg>
            <span>Pause</span>
          </button>
          <button id="ss-resume-btn" class="ss-control-btn ss-hidden" title="Resume">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <polygon points="5 3 19 12 5 21 5 3"/>
            </svg>
            <span>Resume</span>
          </button>
          <button id="ss-stop-btn" class="ss-control-btn ss-stop" title="Stop">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <rect x="4" y="4" width="16" height="16" rx="2"/>
            </svg>
            <span>Stop</span>
          </button>
          <button id="ss-delete-btn" class="ss-control-btn ss-delete" title="Delete">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            <span>Delete</span>
          </button>
        </div>
      </div>
    `;

    this.attachRecordingEventListeners();
    document.body.appendChild(this.panel);
    this.startTimer();
  }

  attachSetupEventListeners() {
    const closeBtn = this.panel.querySelector('#ss-close-panel');
    const startBtn = this.panel.querySelector('#ss-start-recording');
    const cameraSelect = this.panel.querySelector('#ss-camera-select');
    const micSelect = this.panel.querySelector('#ss-mic-select');
    const cameraToggle = this.panel.querySelector('#ss-camera-toggle');
    const micToggle = this.panel.querySelector('#ss-mic-toggle');

    closeBtn?.addEventListener('click', () => this.hide());

    startBtn?.addEventListener('click', () => {
      const options = {
        camera: cameraToggle?.checked || false,
        microphone: micToggle?.checked || false,
        cameraDeviceId: cameraSelect?.value || null,
        microphoneDeviceId: micSelect?.value || null
      };
      this.startRecording(options);
    });

    cameraSelect?.addEventListener('change', (e) => {
      this.selectedDevices.camera = e.target.value;
    });

    micSelect?.addEventListener('change', (e) => {
      this.selectedDevices.microphone = e.target.value;
    });
  }

  attachRecordingEventListeners() {
    const pauseBtn = this.panel.querySelector('#ss-pause-btn');
    const resumeBtn = this.panel.querySelector('#ss-resume-btn');
    const stopBtn = this.panel.querySelector('#ss-stop-btn');
    const deleteBtn = this.panel.querySelector('#ss-delete-btn');

    pauseBtn?.addEventListener('click', () => this.pauseRecording());
    resumeBtn?.addEventListener('click', () => this.resumeRecording());
    stopBtn?.addEventListener('click', () => this.stopRecording());
    deleteBtn?.addEventListener('click', () => this.deleteRecording());
  }

  async startRecording(options) {
    console.log('Starting recording with options:', options);

    try {
      // Call the recording function directly from content.js
      const recordingOptions = {
        screen: true, // Always capture screen
        camera: options.camera,
        microphone: options.microphone,
        cameraDeviceId: options.cameraDeviceId,
        microphoneDeviceId: options.microphoneDeviceId
      };

      // Call initRecording directly from content.js
      await window.__screensense.startRecording(recordingOptions);

      // Recording started successfully
      this.isRecording = true;
      this.startTime = Date.now();

      // Hide the setup panel - we don't need the big recording panel
      // The small control bar in content.js will handle everything
      this.hide();

    } catch (error) {
      console.error('Failed to start recording:', error);
      alert('Failed to start recording: ' + error.message);
    }
  }

  pauseRecording() {
    try {
      window.__screensense.pauseRecording();
      this.isPaused = true;
      const pauseBtn = this.panel.querySelector('#ss-pause-btn');
      const resumeBtn = this.panel.querySelector('#ss-resume-btn');
      pauseBtn?.classList.add('ss-hidden');
      resumeBtn?.classList.remove('ss-hidden');
    } catch (error) {
      console.error('Failed to pause recording:', error);
    }
  }

  resumeRecording() {
    try {
      window.__screensense.resumeRecording();
      this.isPaused = false;
      const pauseBtn = this.panel.querySelector('#ss-pause-btn');
      const resumeBtn = this.panel.querySelector('#ss-resume-btn');
      pauseBtn?.classList.remove('ss-hidden');
      resumeBtn?.classList.add('ss-hidden');
    } catch (error) {
      console.error('Failed to resume recording:', error);
    }
  }

  async stopRecording() {
    try {
      await window.__screensense.stopRecording();
      this.isRecording = false;
      this.stopTimer();
      this.hide();
    } catch (error) {
      console.error('Failed to stop recording:', error);
    }
  }

  async deleteRecording() {
    if (confirm('Are you sure you want to delete this recording?')) {
      try {
        await window.__screensense.deleteRecording();
        this.isRecording = false;
        this.stopTimer();
        this.hide();
      } catch (error) {
        console.error('Failed to delete recording:', error);
      }
    }
  }

  // Camera preview is now handled by content.js createCameraOverlay()
  // These methods are kept for compatibility but don't do anything
  showCameraPreview(deviceId) {
    // Camera overlay is created by content.js
    console.log('Camera preview will be shown by content.js');
  }

  hideCameraPreview() {
    // Camera overlay is removed by content.js
    console.log('Camera preview will be hidden by content.js');
  }

  show() {
    if (!this.panel) {
      this.initialize();
    } else {
      this.panel.style.display = 'block';
    }
  }

  hide() {
    if (this.panel) {
      this.panel.remove();
      this.panel = null;
    }
  }

  startTimer() {
    this.updateTimer();
    this.timerInterval = setInterval(() => this.updateTimer(), 1000);
  }

  stopTimer() {
    if (this.timerInterval) {
      clearInterval(this.timerInterval);
      this.timerInterval = null;
    }
  }

  updateTimer() {
    if (!this.startTime || !this.panel) return;

    const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;
    const timerElement = this.panel.querySelector('#ss-timer');
    if (timerElement) {
      timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
  }

  makeDraggable(element) {
    let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

    element.onmousedown = dragMouseDown;

    function dragMouseDown(e) {
      e = e || window.event;
      e.preventDefault();
      pos3 = e.clientX;
      pos4 = e.clientY;
      document.onmouseup = closeDragElement;
      document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
      e = e || window.event;
      e.preventDefault();
      pos1 = pos3 - e.clientX;
      pos2 = pos4 - e.clientY;
      pos3 = e.clientX;
      pos4 = e.clientY;
      element.style.top = (element.offsetTop - pos2) + "px";
      element.style.left = (element.offsetLeft - pos1) + "px";
      element.style.bottom = 'auto';
      element.style.right = 'auto';
    }

    function closeDragElement() {
      document.onmouseup = null;
      document.onmousemove = null;
    }
  }

  injectStyles() {
    if (document.getElementById('screensense-panel-styles')) {
      return;
    }

    const style = document.createElement('style');
    style.id = 'screensense-panel-styles';
    style.textContent = `
      #screensense-floating-panel {
        position: fixed;
        top: 80px;
        right: 20px;
        width: 360px;
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 1px rgba(255, 255, 255, 0.1);
        z-index: 999999;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.1);
      }

      .ss-panel-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }

      .ss-panel-logo {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
      }

      .ss-panel-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
      }

      .ss-panel-title {
        flex: 1;
      }

      .ss-panel-title-text {
        font-size: 16px;
        font-weight: 700;
        color: #ea580c;
      }

      .ss-panel-subtitle {
        font-size: 11px;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 2px;
      }

      .ss-panel-close {
        width: 28px;
        height: 28px;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 6px;
        color: #9ca3af;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
      }

      .ss-panel-close:hover {
        background: rgba(255, 255, 255, 0.2);
        color: white;
      }

      .ss-panel-close svg {
        width: 14px;
        height: 14px;
      }

      .ss-panel-body {
        padding: 20px;
      }

      .ss-device-section {
        margin-bottom: 20px;
      }

      .ss-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #d1d5db;
        margin-bottom: 8px;
      }

      .ss-label-icon {
        width: 16px;
        height: 16px;
        color: #ea580c;
      }

      .ss-select-wrapper {
        margin-bottom: 10px;
      }

      .ss-select {
        width: 100%;
        padding: 10px 12px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: white;
        font-size: 13px;
        cursor: pointer;
        outline: none;
        transition: all 0.2s;
      }

      .ss-select:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(234, 88, 12, 0.3);
      }

      .ss-select:focus {
        background: rgba(255, 255, 255, 0.08);
        border-color: #ea580c;
      }

      .ss-select option {
        background: #1f2937;
        color: white;
      }

      .ss-toggle-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
      }

      .ss-toggle-wrapper input[type="checkbox"] {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #ea580c;
      }

      .ss-toggle-text {
        font-size: 13px;
        color: #9ca3af;
      }

      .ss-start-btn {
        width: 100%;
        padding: 14px 20px;
        background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
        margin-top: 8px;
      }

      .ss-start-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(234, 88, 12, 0.4);
      }

      .ss-start-btn:active {
        transform: translateY(0);
      }

      .ss-rec-dot {
        width: 12px;
        height: 12px;
        background: white;
        border-radius: 50%;
      }

      /* Recording panel styles */
      .ss-recording-controls {
        display: flex;
        flex-direction: column;
        gap: 10px;
      }

      .ss-control-btn {
        width: 100%;
        padding: 12px;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 10px;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
      }

      .ss-control-btn:hover {
        background: rgba(255, 255, 255, 0.15);
      }

      .ss-control-btn svg {
        width: 18px;
        height: 18px;
      }

      .ss-control-btn.ss-stop {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      }

      .ss-control-btn.ss-stop:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
      }

      .ss-control-btn.ss-delete {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
      }

      .ss-control-btn.ss-delete:hover {
        background: rgba(239, 68, 68, 0.2);
      }

      .ss-pulse-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        animation: ss-pulse 1.5s ease-in-out infinite;
      }

      @keyframes ss-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
      }

      .ss-hidden {
        display: none !important;
      }

      /* Camera preview is handled by content.js and content.css */
    `;

    document.head.appendChild(style);
  }
}

// Export for use in content script
window.FloatingPanel = FloatingPanel;
