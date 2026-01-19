import { ref, computed } from 'vue'

/**
 * Composable for tracking mouse clicks and keyboard events during screen recording.
 * Events are captured with timestamps relative to recording start time.
 */
export function useZoomTracking() {
  const events = ref([])
  const recordingStartTime = ref(0)
  const isTracking = ref(false)
  const recordingResolution = ref({ width: 1920, height: 1080 })

  // Zoom settings
  const zoomEnabled = ref(false)
  const zoomLevel = ref(2.0)
  const zoomDurationMs = ref(500)

  // Keyboard event buffer for grouping rapid keypresses
  let keyboardBuffer = ''
  let keyboardBufferTimeout = null
  let keyboardStartTime = 0

  /**
   * Handle click events
   * Always tracks events regardless of zoom enabled status
   * Events are stored for all videos, zoom processing only happens if enabled
   */
  const handleClick = (e) => {
    if (!isTracking.value) return

    // Get the click coordinates relative to the viewport
    const event = {
      type: 'click',
      timestamp_ms: Date.now() - recordingStartTime.value,
      x: e.clientX,
      y: e.clientY,
      zoom_enabled: zoomEnabled.value  // Track current zoom preference
    }

    events.value.push(event)
    console.log('[ZoomTracking] Click event captured:', event, 'Total events:', events.value.length)
  }

  /**
   * Handle keyboard events - buffers rapid keypresses into a single event
   * Always tracks events regardless of zoom enabled status
   */
  const handleKeydown = (e) => {
    if (!isTracking.value) return

    // Ignore modifier keys and special keys
    if (e.key.length > 1 && !['Enter', 'Tab', 'Backspace'].includes(e.key)) {
      return
    }

    const now = Date.now()

    // If this is the first key or the buffer has been idle, start a new event
    if (keyboardBuffer === '' || keyboardStartTime === 0) {
      keyboardStartTime = now - recordingStartTime.value
    }

    // Add key to buffer (handle special keys)
    if (e.key === 'Enter') {
      keyboardBuffer += '\n'
    } else if (e.key === 'Backspace' && keyboardBuffer.length > 0) {
      keyboardBuffer = keyboardBuffer.slice(0, -1)
    } else if (e.key.length === 1) {
      keyboardBuffer += e.key
    }

    // Clear any existing timeout
    if (keyboardBufferTimeout) {
      clearTimeout(keyboardBufferTimeout)
    }

    // Set a timeout to flush the buffer after 500ms of inactivity
    keyboardBufferTimeout = setTimeout(() => {
      flushKeyboardBuffer()
    }, 500)
  }

  /**
   * Flush the keyboard buffer to create a keyboard event
   */
  const flushKeyboardBuffer = () => {
    if (keyboardBuffer.length > 0) {
      const endTime = Date.now() - recordingStartTime.value
      const event = {
        type: 'keyboard',
        timestamp_ms: keyboardStartTime,
        keys: keyboardBuffer,
        duration_ms: endTime - keyboardStartTime,
        zoom_enabled: zoomEnabled.value  // Track current zoom preference
      }

      events.value.push(event)
    }

    // Reset buffer
    keyboardBuffer = ''
    keyboardStartTime = 0
    keyboardBufferTimeout = null
  }

  /**
   * Start tracking events
   */
  const startTracking = (resolution = { width: 1920, height: 1080 }) => {
    recordingStartTime.value = Date.now()
    events.value = []
    recordingResolution.value = resolution
    isTracking.value = true
    keyboardBuffer = ''
    keyboardStartTime = 0

    // Use capture phase to ensure we catch events before any handlers stop propagation
    document.addEventListener('click', handleClick, true)
    document.addEventListener('keydown', handleKeydown, true)
    console.log('[ZoomTracking] Started tracking events, resolution:', resolution)
  }

  /**
   * Stop tracking events
   */
  const stopTracking = () => {
    isTracking.value = false

    // Flush any remaining keyboard buffer
    flushKeyboardBuffer()

    document.removeEventListener('click', handleClick, true)
    document.removeEventListener('keydown', handleKeydown, true)
    console.log('[ZoomTracking] Stopped tracking. Total events captured:', events.value.length)
  }

  /**
   * Get all events in the format expected by the backend
   */
  const getEvents = () => {
    return {
      recording_resolution: recordingResolution.value,
      events: events.value
    }
  }

  /**
   * Get zoom settings for sending with upload
   */
  const getZoomSettings = () => {
    return {
      zoom_enabled: zoomEnabled.value,
      zoom_level: zoomLevel.value,
      zoom_duration_ms: zoomDurationMs.value,
      zoom_events: getEvents()
    }
  }

  /**
   * Toggle individual event's zoom_enabled status
   */
  const toggleEventZoom = (index) => {
    if (index >= 0 && index < events.value.length) {
      events.value[index].zoom_enabled = !events.value[index].zoom_enabled
    }
  }

  /**
   * Remove an event from the list
   */
  const removeEvent = (index) => {
    if (index >= 0 && index < events.value.length) {
      events.value.splice(index, 1)
    }
  }

  /**
   * Clear all events
   */
  const clearEvents = () => {
    events.value = []
  }

  /**
   * Enable or disable zoom feature
   */
  const setZoomEnabled = (enabled) => {
    zoomEnabled.value = enabled
  }

  /**
   * Update zoom level
   */
  const setZoomLevel = (level) => {
    zoomLevel.value = Math.max(1.2, Math.min(4.0, level))
  }

  /**
   * Update zoom animation duration
   */
  const setZoomDuration = (duration) => {
    zoomDurationMs.value = Math.max(100, Math.min(2000, duration))
  }

  // Computed properties
  const eventCount = computed(() => events.value.length)
  const enabledEventCount = computed(() => events.value.filter(e => e.zoom_enabled).length)
  const clickEventCount = computed(() => events.value.filter(e => e.type === 'click').length)
  const keyboardEventCount = computed(() => events.value.filter(e => e.type === 'keyboard').length)

  return {
    // State
    events,
    isTracking,
    zoomEnabled,
    zoomLevel,
    zoomDurationMs,
    recordingResolution,

    // Computed
    eventCount,
    enabledEventCount,
    clickEventCount,
    keyboardEventCount,

    // Methods
    startTracking,
    stopTracking,
    getEvents,
    getZoomSettings,
    toggleEventZoom,
    removeEvent,
    clearEvents,
    setZoomEnabled,
    setZoomLevel,
    setZoomDuration
  }
}

export default useZoomTracking
