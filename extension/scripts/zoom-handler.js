// Zoom Handler for ScreenSense - Auto-zoom on mouse clicks during recording
// Based on OBS zoom-to-mouse logic

class ZoomHandler {
  constructor() {
    this.enabled = false;
    this.isZoomed = false;
    this.targetZoom = 1.0;
    this.currentZoom = 1.0;
    this.targetX = 0;
    this.targetY = 0;
    this.currentX = 0;
    this.currentY = 0;

    // Configuration (matching OBS zoom-to-mouse behavior)
    this.config = {
      zoomLevel: 2.0,           // 2x zoom on click
      zoomDuration: 300,         // ms to zoom in/out
      holdDuration: 2000,        // ms to hold zoom before auto-unzoom
      safeZoneRadius: 100,       // pixels - cursor can move in this area without panning
      followBorderDistance: 50,  // pixels from edge to trigger follow
      followSpeed: 0.1,          // smoothness of camera follow (0-1)
      autoUnzoom: true,          // auto-unzoom after holdDuration
    };

    // State
    this.zoomTimeout = null;
    this.animationFrame = null;
    this.lastClickTime = 0;
    this.mouseX = 0;
    this.mouseY = 0;

    // Click tracking for backend post-processing
    this.clickEvents = [];
    this.recordingStartTime = null;

    // Overlay elements
    this.overlay = null;
    this.zoomIndicator = null;

    // Bind methods
    this.handleClick = this.handleClick.bind(this);
    this.handleMouseMove = this.handleMouseMove.bind(this);
    this.animate = this.animate.bind(this);
  }

  /**
   * Enable zoom-on-click feature
   * @param {number} startTime - Recording start timestamp (Date.now())
   */
  enable(startTime = null) {
    if (this.enabled) return;

    this.enabled = true;
    this.recordingStartTime = startTime || Date.now();
    this.clickEvents = []; // Reset click events
    document.addEventListener('click', this.handleClick, true);
    document.addEventListener('mousemove', this.handleMouseMove, true);
    this.createOverlay();
    this.startAnimation();

    console.log('Zoom-on-click enabled, tracking clicks from', this.recordingStartTime);
  }

  /**
   * Disable zoom-on-click feature
   * @returns {Array} The collected click events for backend processing
   */
  disable() {
    if (!this.enabled) return [];

    this.enabled = false;
    document.removeEventListener('click', this.handleClick, true);
    document.removeEventListener('mousemove', this.handleMouseMove, true);
    this.removeOverlay();
    this.stopAnimation();

    // Reset state
    this.isZoomed = false;
    this.targetZoom = 1.0;
    this.currentZoom = 1.0;

    console.log('Zoom-on-click disabled, collected', this.clickEvents.length, 'click events');
    return this.clickEvents;
  }

  /**
   * Get collected click events for backend processing
   * @returns {Array} Click events with timestamp and normalized coordinates
   */
  getClickEvents() {
    return [...this.clickEvents];
  }

  /**
   * Clear click events
   */
  clearClickEvents() {
    this.clickEvents = [];
  }

  /**
   * Create visual overlay to show zoom state
   */
  createOverlay() {
    // Create zoom indicator (small circle that shows when zoom is active)
    this.zoomIndicator = document.createElement('div');
    this.zoomIndicator.id = 'screensense-zoom-indicator';
    this.zoomIndicator.style.cssText = `
      position: fixed;
      width: 60px;
      height: 60px;
      border: 3px solid rgba(234, 88, 12, 0.8);
      border-radius: 50%;
      pointer-events: none;
      z-index: 999997;
      display: none;
      background: radial-gradient(circle, rgba(234, 88, 12, 0.1) 0%, transparent 70%);
      box-shadow: 0 0 20px rgba(234, 88, 12, 0.4);
      transition: opacity 0.2s ease, transform 0.2s ease;
    `;
    document.body.appendChild(this.zoomIndicator);
  }

  /**
   * Remove visual overlay
   */
  removeOverlay() {
    if (this.zoomIndicator) {
      this.zoomIndicator.remove();
      this.zoomIndicator = null;
    }
  }

  /**
   * Handle mouse click - trigger zoom and track for backend
   */
  handleClick(event) {
    if (!this.enabled) return;

    const now = Date.now();

    // Get click position
    this.targetX = event.clientX;
    this.targetY = event.clientY;
    this.mouseX = event.clientX;
    this.mouseY = event.clientY;

    // Calculate timestamp relative to recording start (in seconds)
    const timestampMs = now - this.recordingStartTime;
    const timestamp = timestampMs / 1000;

    // Calculate normalized coordinates (0-1 range)
    const normalizedX = event.clientX / window.innerWidth;
    const normalizedY = event.clientY / window.innerHeight;

    // Track ALL click events for backend processing (not just zoom-in clicks)
    // The backend will deduplicate clicks that are too close together
    if (timestamp >= 0) {
      const clickEvent = {
        timestamp: parseFloat(timestamp.toFixed(2)),
        normalizedX: parseFloat(normalizedX.toFixed(4)),
        normalizedY: parseFloat(normalizedY.toFixed(4)),
        screenWidth: window.innerWidth,
        screenHeight: window.innerHeight
      };
      this.clickEvents.push(clickEvent);
      console.log('Tracked click event:', clickEvent, 'Total clicks:', this.clickEvents.length);
    }

    // Toggle zoom state for live preview
    if (this.isZoomed) {
      // If already zoomed, clicking again zooms out
      this.zoomOut();
    } else {
      // Zoom in to click position
      this.zoomIn();
    }

    this.lastClickTime = now;
  }

  /**
   * Handle mouse move - update cursor position for follow logic
   */
  handleMouseMove(event) {
    if (!this.enabled) return;

    this.mouseX = event.clientX;
    this.mouseY = event.clientY;

    // Update indicator position if visible
    if (this.isZoomed && this.zoomIndicator) {
      this.updateIndicatorPosition();
    }
  }

  /**
   * Zoom in to target position
   */
  zoomIn() {
    this.isZoomed = true;
    this.targetZoom = this.config.zoomLevel;

    // Initialize current position to target if first zoom
    if (this.currentZoom === 1.0) {
      this.currentX = this.targetX;
      this.currentY = this.targetY;
    }

    // Show zoom indicator
    if (this.zoomIndicator) {
      this.zoomIndicator.style.display = 'block';
      this.updateIndicatorPosition();
    }

    // Apply zoom CSS transform to body
    this.applyZoomTransform();

    // Clear existing timeout
    if (this.zoomTimeout) {
      clearTimeout(this.zoomTimeout);
    }

    // Auto-unzoom after holdDuration if enabled
    if (this.config.autoUnzoom) {
      this.zoomTimeout = setTimeout(() => {
        this.zoomOut();
      }, this.config.holdDuration);
    }
  }

  /**
   * Zoom out to normal view
   */
  zoomOut() {
    this.isZoomed = false;
    this.targetZoom = 1.0;

    // Hide zoom indicator
    if (this.zoomIndicator) {
      this.zoomIndicator.style.display = 'none';
    }

    // Clear timeout
    if (this.zoomTimeout) {
      clearTimeout(this.zoomTimeout);
      this.zoomTimeout = null;
    }

    // Apply zoom transform
    this.applyZoomTransform();
  }

  /**
   * Start animation loop
   */
  startAnimation() {
    this.animate();
  }

  /**
   * Stop animation loop
   */
  stopAnimation() {
    if (this.animationFrame) {
      cancelAnimationFrame(this.animationFrame);
      this.animationFrame = null;
    }
  }

  /**
   * Animation loop - smooth zoom and pan
   */
  animate() {
    if (!this.enabled) return;

    // Smooth zoom level interpolation
    const zoomDelta = this.targetZoom - this.currentZoom;
    if (Math.abs(zoomDelta) > 0.01) {
      this.currentZoom += zoomDelta * 0.15; // Smooth easing
      this.applyZoomTransform();
    } else {
      this.currentZoom = this.targetZoom;
    }

    // Follow cursor when zoomed (OBS-style logic)
    if (this.isZoomed && this.currentZoom > 1.5) {
      this.updateFollowPosition();
    }

    // Position interpolation
    const xDelta = this.targetX - this.currentX;
    const yDelta = this.targetY - this.currentY;

    if (Math.abs(xDelta) > 0.5 || Math.abs(yDelta) > 0.5) {
      this.currentX += xDelta * this.config.followSpeed;
      this.currentY += yDelta * this.config.followSpeed;
      this.applyZoomTransform();
    } else {
      this.currentX = this.targetX;
      this.currentY = this.targetY;
    }

    this.animationFrame = requestAnimationFrame(this.animate);
  }

  /**
   * Update follow position based on mouse movement (OBS-style)
   * Only pan when cursor approaches the edge of the zoomed area
   */
  updateFollowPosition() {
    // Calculate distance from current zoom center to mouse
    const dx = this.mouseX - this.currentX;
    const dy = this.mouseY - this.currentY;
    const distance = Math.sqrt(dx * dx + dy * dy);

    // Only follow if mouse is outside safe zone
    if (distance > this.config.safeZoneRadius) {
      // Smoothly move target towards mouse position
      this.targetX = this.mouseX;
      this.targetY = this.mouseY;
    }
  }

  /**
   * Apply zoom CSS transform to document
   * This creates a "zoomed in" effect by scaling and translating the entire page
   */
  applyZoomTransform() {
    const zoom = this.currentZoom;

    if (zoom <= 1.0) {
      // No zoom, reset transform
      document.documentElement.style.transform = '';
      document.documentElement.style.transformOrigin = '';
      document.body.style.transform = '';
      document.body.style.transformOrigin = '';
      return;
    }

    // Calculate transform origin (center of zoom)
    const originX = this.currentX;
    const originY = this.currentY;

    // Apply transform to html and body elements
    // We use transform-origin to zoom from the click point
    const transform = `scale(${zoom})`;
    const transformOrigin = `${originX}px ${originY}px`;

    document.documentElement.style.transform = transform;
    document.documentElement.style.transformOrigin = transformOrigin;
    document.documentElement.style.transition = `transform ${this.config.zoomDuration}ms cubic-bezier(0.4, 0.0, 0.2, 1)`;
  }

  /**
   * Update zoom indicator position
   */
  updateIndicatorPosition() {
    if (!this.zoomIndicator) return;

    this.zoomIndicator.style.left = `${this.currentX - 30}px`;
    this.zoomIndicator.style.top = `${this.currentY - 30}px`;
    this.zoomIndicator.style.opacity = Math.min(1, (this.currentZoom - 1) / (this.config.zoomLevel - 1));
    this.zoomIndicator.style.transform = `scale(${this.currentZoom})`;
  }

  /**
   * Update configuration
   */
  updateConfig(newConfig) {
    this.config = { ...this.config, ...newConfig };
  }
}

// Export for use in content.js
if (typeof window !== 'undefined') {
  window.ScreenSenseZoomHandler = ZoomHandler;
}
