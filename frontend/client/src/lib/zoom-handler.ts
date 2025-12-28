// Zoom Handler for ScreenSense Web App - Auto-zoom on mouse clicks during recording
// Based on OBS zoom-to-mouse logic

export interface ZoomConfig {
  zoomLevel: number;
  zoomDuration: number;
  holdDuration: number;
  safeZoneRadius: number;
  followBorderDistance: number;
  followSpeed: number;
  autoUnzoom: boolean;
}

export class ZoomHandler {
  private enabled = false;
  private isZoomed = false;
  private targetZoom = 1.0;
  private currentZoom = 1.0;
  private targetX = 0;
  private targetY = 0;
  private currentX = 0;
  private currentY = 0;

  // Configuration (matching OBS zoom-to-mouse behavior)
  private config: ZoomConfig = {
    zoomLevel: 2.0,
    zoomDuration: 300,
    holdDuration: 2000,
    safeZoneRadius: 100,
    followBorderDistance: 50,
    followSpeed: 0.1,
    autoUnzoom: true,
  };

  // State
  private zoomTimeout: NodeJS.Timeout | null = null;
  private animationFrame: number | null = null;
  private lastClickTime = 0;
  private mouseX = 0;
  private mouseY = 0;

  // Overlay elements
  private zoomIndicator: HTMLDivElement | null = null;

  constructor(config?: Partial<ZoomConfig>) {
    if (config) {
      this.config = { ...this.config, ...config };
    }
  }

  /**
   * Enable zoom-on-click feature
   */
  enable(): void {
    if (this.enabled) return;

    this.enabled = true;
    document.addEventListener('click', this.handleClick, true);
    document.addEventListener('mousemove', this.handleMouseMove, true);
    this.createOverlay();
    this.startAnimation();

    console.log('Zoom-on-click enabled');
  }

  /**
   * Disable zoom-on-click feature
   */
  disable(): void {
    if (!this.enabled) return;

    this.enabled = false;
    document.removeEventListener('click', this.handleClick, true);
    document.removeEventListener('mousemove', this.handleMouseMove, true);
    this.removeOverlay();
    this.stopAnimation();

    // Reset state
    this.isZoomed = false;
    this.targetZoom = 1.0;
    this.currentZoom = 1.0;

    // Reset document transform
    document.documentElement.style.transform = '';
    document.documentElement.style.transformOrigin = '';
    document.documentElement.style.transition = '';

    console.log('Zoom-on-click disabled');
  }

  /**
   * Create visual overlay to show zoom state
   */
  private createOverlay(): void {
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
  private removeOverlay(): void {
    if (this.zoomIndicator) {
      this.zoomIndicator.remove();
      this.zoomIndicator = null;
    }
  }

  /**
   * Handle mouse click - trigger zoom
   */
  private handleClick = (event: MouseEvent): void => {
    if (!this.enabled) return;

    const now = Date.now();

    // Get click position
    this.targetX = event.clientX;
    this.targetY = event.clientY;
    this.mouseX = event.clientX;
    this.mouseY = event.clientY;

    // Toggle zoom state
    if (this.isZoomed) {
      // If already zoomed, clicking again zooms out
      this.zoomOut();
    } else {
      // Zoom in to click position
      this.zoomIn();
    }

    this.lastClickTime = now;
  };

  /**
   * Handle mouse move - update cursor position for follow logic
   */
  private handleMouseMove = (event: MouseEvent): void => {
    if (!this.enabled) return;

    this.mouseX = event.clientX;
    this.mouseY = event.clientY;

    // Update indicator position if visible
    if (this.isZoomed && this.zoomIndicator) {
      this.updateIndicatorPosition();
    }
  };

  /**
   * Zoom in to target position
   */
  private zoomIn(): void {
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
  private zoomOut(): void {
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
  private startAnimation(): void {
    this.animate();
  }

  /**
   * Stop animation loop
   */
  private stopAnimation(): void {
    if (this.animationFrame) {
      cancelAnimationFrame(this.animationFrame);
      this.animationFrame = null;
    }
  }

  /**
   * Animation loop - smooth zoom and pan
   */
  private animate = (): void => {
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
  };

  /**
   * Update follow position based on mouse movement (OBS-style)
   * Only pan when cursor approaches the edge of the zoomed area
   */
  private updateFollowPosition(): void {
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
  private applyZoomTransform(): void {
    const zoom = this.currentZoom;

    if (zoom <= 1.0) {
      // No zoom, reset transform
      document.documentElement.style.transform = '';
      document.documentElement.style.transformOrigin = '';
      document.documentElement.style.transition = '';
      return;
    }

    // Calculate transform origin (center of zoom)
    const originX = this.currentX;
    const originY = this.currentY;

    // Apply transform to html element
    const transform = `scale(${zoom})`;
    const transformOrigin = `${originX}px ${originY}px`;

    document.documentElement.style.transform = transform;
    document.documentElement.style.transformOrigin = transformOrigin;
    document.documentElement.style.transition = `transform ${this.config.zoomDuration}ms cubic-bezier(0.4, 0.0, 0.2, 1)`;
  }

  /**
   * Update zoom indicator position
   */
  private updateIndicatorPosition(): void {
    if (!this.zoomIndicator) return;

    this.zoomIndicator.style.left = `${this.currentX - 30}px`;
    this.zoomIndicator.style.top = `${this.currentY - 30}px`;
    this.zoomIndicator.style.opacity = String(Math.min(1, (this.currentZoom - 1) / (this.config.zoomLevel - 1)));
    this.zoomIndicator.style.transform = `scale(${this.currentZoom})`;
  }

  /**
   * Update configuration
   */
  updateConfig(newConfig: Partial<ZoomConfig>): void {
    this.config = { ...this.config, ...newConfig };
  }
}
