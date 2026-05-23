// Lightweight viewer tracking for video playback.
// Generates a stable per-tab session id and sends progress heartbeats
// every HEARTBEAT_INTERVAL_MS while the player is playing.
//
// One tracker per VideoPlayerView instance:
//   const tracker = createTracker({ videoId, shareToken, getVideoEl })
//   tracker.start()      // call after view is recorded
//   tracker.stop()       // call on unmount

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''
const HEARTBEAT_INTERVAL_MS = 10_000
const SESSION_KEY = 'openkap_view_session_id'

function getOrCreateSessionId() {
  try {
    let id = sessionStorage.getItem(SESSION_KEY)
    if (!id) {
      id = (crypto?.randomUUID?.() ?? `s-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`)
      sessionStorage.setItem(SESSION_KEY, id)
    }
    return id
  } catch (e) {
    return `s-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`
  }
}

function getTimezone() {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone || null
  } catch (e) {
    return null
  }
}

function authHeaders() {
  const token = localStorage.getItem('auth_token')
  const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' }
  if (token) headers['Authorization'] = `Bearer ${token}`
  return headers
}

/**
 * Initial view-record payload, augmented with referrer / timezone / session.
 */
export function buildViewPayload(extra = {}) {
  return {
    referrer: document.referrer || null,
    timezone: getTimezone(),
    session_id: getOrCreateSessionId(),
    ...extra,
  }
}

export function createTracker({ videoId, shareToken, getVideoEl }) {
  const sessionId = getOrCreateSessionId()
  let timer = null
  let lastProgressSent = -1
  let stopped = false

  const url = shareToken
    ? `${API_BASE_URL}/api/share/video/${shareToken}/progress`
    : `${API_BASE_URL}/api/videos/${videoId}/progress`

  function snapshot() {
    const el = getVideoEl?.()
    if (!el) return null
    const progress = Math.floor(el.currentTime || 0)
    const duration = Math.floor(el.duration || 0)
    if (!progress) return null
    const completed = duration > 0 && progress >= duration - 1
    return { progress_seconds: progress, watch_duration: progress, completed, session_id: sessionId }
  }

  async function send(payload, useBeacon = false) {
    if (!payload) return
    if (payload.progress_seconds === lastProgressSent && !payload.completed) return
    lastProgressSent = payload.progress_seconds

    try {
      if (useBeacon && navigator.sendBeacon) {
        const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' })
        navigator.sendBeacon(url, blob)
        return
      }
      await fetch(url, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify(payload),
        keepalive: true,
      })
    } catch (e) {
      // tracking is best-effort
    }
  }

  function tick() {
    if (stopped) return
    send(snapshot())
  }

  function onVisibilityChange() {
    if (document.visibilityState === 'hidden') {
      send(snapshot(), true)
    }
  }

  function onBeforeUnload() {
    send(snapshot(), true)
  }

  function start() {
    if (timer) return
    timer = setInterval(tick, HEARTBEAT_INTERVAL_MS)
    document.addEventListener('visibilitychange', onVisibilityChange)
    window.addEventListener('beforeunload', onBeforeUnload)
    window.addEventListener('pagehide', onBeforeUnload)
  }

  function stop() {
    stopped = true
    if (timer) {
      clearInterval(timer)
      timer = null
    }
    document.removeEventListener('visibilitychange', onVisibilityChange)
    window.removeEventListener('beforeunload', onBeforeUnload)
    window.removeEventListener('pagehide', onBeforeUnload)
    // Send one final heartbeat
    send(snapshot(), true)
  }

  function flush() {
    send(snapshot(), true)
  }

  return { start, stop, flush, sessionId }
}
