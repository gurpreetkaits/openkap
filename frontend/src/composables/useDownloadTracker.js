import { ref, computed } from 'vue'
import notificationService from '@/services/notificationService'
import videoService from '@/services/videoService'

// Shared state across all component instances
const downloads = ref(loadFromStorage())
let pollInterval = null
let pollRefCount = 0

function loadFromStorage() {
  try {
    const stored = localStorage.getItem('openkap_downloads')
    if (stored) {
      const parsed = JSON.parse(stored)
      // Remove downloads older than 24 hours
      const cutoff = Date.now() - 24 * 60 * 60 * 1000
      return parsed.filter(d => d.timestamp > cutoff)
    }
  } catch {}
  return []
}

function saveToStorage() {
  localStorage.setItem('openkap_downloads', JSON.stringify(downloads.value))
}

function startPolling() {
  if (pollInterval) return
  pollInterval = setInterval(pollForReady, 8000)
  // Immediate first check
  pollForReady()
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

async function pollForReady() {
  const processing = downloads.value.filter(d => d.status === 'processing')
  if (processing.length === 0) {
    stopPolling()
    return
  }

  try {
    const data = await notificationService.getNotifications(1, 20)
    const notifications = data.notifications || []

    for (const download of processing) {
      // Check for a matching download-ready notification
      const match = notifications.find(n =>
        n.type === 'download' &&
        n.link &&
        n.link.includes(`/videos/${download.videoId}/download-mp4`)
      )

      // Also check for failure notification
      const failMatch = notifications.find(n =>
        n.type === 'info' &&
        n.message &&
        n.message.includes(download.videoTitle) &&
        n.message.includes('failed')
      )

      if (match) {
        download.status = 'ready'
        download.notificationId = match.id
      } else if (failMatch) {
        download.status = 'failed'
        download.notificationId = failMatch.id
      }
    }

    saveToStorage()
  } catch (err) {
    console.error('Download tracker poll error:', err)
  }
}

export function useDownloadTracker() {
  // Track component mount/unmount for polling lifecycle
  function startTracking() {
    pollRefCount++
    const hasProcessing = downloads.value.some(d => d.status === 'processing')
    if (hasProcessing) {
      startPolling()
    }
  }

  function stopTracking() {
    pollRefCount--
    if (pollRefCount <= 0) {
      pollRefCount = 0
      stopPolling()
    }
  }

  // Add a new download to track
  function trackDownload(videoId, videoTitle) {
    // Don't duplicate
    const existing = downloads.value.find(d => d.videoId === videoId && d.status === 'processing')
    if (existing) return

    downloads.value.push({
      videoId,
      videoTitle,
      status: 'processing',
      timestamp: Date.now(),
      notificationId: null
    })
    saveToStorage()
    startPolling()
  }

  // Trigger the actual file download for a ready item
  async function triggerDownload(download) {
    try {
      const blob = await videoService.downloadMp4(download.videoId)
      if (blob) {
        const blobUrl = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = blobUrl
        link.download = `${download.videoTitle || 'video'}.mp4`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(blobUrl)
      }

      // Mark notification as read
      if (download.notificationId) {
        try {
          await notificationService.markAsRead(download.notificationId)
        } catch {}
      }

      // Remove from tracker
      removeDownload(download.videoId)
    } catch (err) {
      console.error('Failed to download:', err)
    }
  }

  function removeDownload(videoId) {
    downloads.value = downloads.value.filter(d => d.videoId !== videoId)
    saveToStorage()
  }

  function clearAll() {
    downloads.value = []
    saveToStorage()
    stopPolling()
  }

  const processingCount = computed(() =>
    downloads.value.filter(d => d.status === 'processing').length
  )

  const readyCount = computed(() =>
    downloads.value.filter(d => d.status === 'ready').length
  )

  const totalCount = computed(() => downloads.value.length)

  const badgeCount = computed(() => processingCount.value + readyCount.value)

  return {
    downloads,
    trackDownload,
    triggerDownload,
    removeDownload,
    clearAll,
    startTracking,
    stopTracking,
    processingCount,
    readyCount,
    totalCount,
    badgeCount
  }
}
