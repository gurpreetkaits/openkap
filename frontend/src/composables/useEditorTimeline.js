import { ref, computed } from 'vue'
import { useEditorState } from './useEditorState'

export function useEditorTimeline() {
  const state = useEditorState()
  const { duration, currentTime, items, videoEl } = state

  const zoom = ref(1)
  const scrollLeft = ref(0)
  const containerWidth = ref(800)

  const pixelsPerSecond = computed(() => {
    if (!duration.value) return 50
    return (containerWidth.value * zoom.value) / duration.value
  })

  const totalTimelineWidth = computed(() => {
    if (!duration.value) return containerWidth.value
    return duration.value * pixelsPerSecond.value
  })

  function zoomIn() {
    zoom.value = Math.min(20, zoom.value + 1)
  }

  function zoomOut() {
    zoom.value = Math.max(1, zoom.value - 1)
  }

  function timeToPixels(seconds) {
    return seconds * pixelsPerSecond.value
  }

  function pixelsToTime(px) {
    if (!pixelsPerSecond.value) return 0
    return px / pixelsPerSecond.value
  }

  function seekToTime(seconds) {
    const el = videoEl.value
    if (el && duration.value) {
      const clamped = Math.max(0, Math.min(duration.value, seconds))
      el.currentTime = clamped
      currentTime.value = clamped
    }
  }

  function getBlockStyle(item) {
    if (!duration.value) return { display: 'none' }
    const start = item.entireVideo ? 0 : (item.start_time || 0)
    const end = item.entireVideo ? duration.value : (item.end_time || duration.value)
    return {
      left: timeToPixels(start) + 'px',
      width: Math.max(4, timeToPixels(end - start)) + 'px',
    }
  }

  function getTimelineMarkerStyle(item) {
    if (!duration.value) return { display: 'none' }
    const start = item.entireVideo ? 0 : (item.start_time || 0)
    const end = item.entireVideo ? duration.value : (item.end_time || duration.value)
    return {
      left: (start / duration.value) * 100 + '%',
      width: Math.max(0.5, ((end - start) / duration.value) * 100) + '%',
    }
  }

  function generateRulerMarks() {
    if (!duration.value) return []
    const marks = []
    // Choose interval based on zoom
    let interval = 5
    if (pixelsPerSecond.value > 30) interval = 2
    if (pixelsPerSecond.value > 60) interval = 1
    if (pixelsPerSecond.value > 120) interval = 0.5

    for (let t = 0; t <= duration.value; t += interval) {
      marks.push({
        time: t,
        position: timeToPixels(t),
        label: formatRulerTime(t),
        major: t % (interval * 5 === 0 ? 1 : 5) === 0,
      })
    }
    return marks
  }

  function formatRulerTime(seconds) {
    const m = Math.floor(seconds / 60)
    const s = Math.floor(seconds % 60)
    return `${m}:${s.toString().padStart(2, '0')}`
  }

  return {
    zoom,
    scrollLeft,
    containerWidth,
    pixelsPerSecond,
    totalTimelineWidth,
    zoomIn,
    zoomOut,
    timeToPixels,
    pixelsToTime,
    seekToTime,
    getBlockStyle,
    getTimelineMarkerStyle,
    generateRulerMarks,
  }
}
