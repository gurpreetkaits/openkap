// Video API Service
const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

// Helper to get auth token
function getAuthToken() {
  return localStorage.getItem('auth_token')
}

// Helper to get auth headers
function getAuthHeaders() {
  const token = getAuthToken()
  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }
  return headers
}

// Handle 401 responses by redirecting to login
function handleUnauthorized(response) {
  if (response.status === 401) {
    // Clear auth data
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    // Store current path for redirect after login
    localStorage.setItem('auth_redirect', window.location.pathname)
    // Redirect to login
    window.location.href = import.meta.env.BASE_URL + 'login'
    return true
  }
  return false
}

class VideoService {
  /**
   * Fetch all videos for the current user
   */
  async getVideos() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch videos: ${response.statusText}`)
      }

      const data = await response.json()
      return data.videos || []
    } catch (error) {
      console.error('Error fetching videos:', error)
      throw error
    }
  }

  /**
   * Get a specific video by ID
   */
  async getVideo(id) {
    try {
      const headers = getAuthHeaders()
      headers['Cache-Control'] = 'no-cache, no-store, must-revalidate'
      headers['Pragma'] = 'no-cache'
      headers['Expires'] = '0'

      const response = await fetch(`${API_BASE_URL}/api/videos/${id}`, {
        method: 'GET',
        headers
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error fetching video:', error)
      throw error
    }
  }

  /**
   * Upload a new video
   */
  async uploadVideo(formData) {
    try {
      const token = getAuthToken()
      const headers = { 'Accept': 'application/json' }
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${API_BASE_URL}/api/videos`, {
        method: 'POST',
        body: formData,
        headers
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to upload video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error uploading video:', error)
      throw error
    }
  }

  /**
   * Update video details (title, description)
   */
  async updateVideo(id, updates) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify(updates)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to update video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error updating video:', error)
      throw error
    }
  }

  /**
   * Delete a video
   */
  async deleteVideo(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to delete video: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error deleting video:', error)
      throw error
    }
  }

  /**
   * Bulk delete multiple videos
   * @param {number[]} videoIds - Array of video IDs to delete
   * @returns {Promise<{message: string, deleted: number, failed: number, errors: array}>}
   */
  async bulkDeleteVideos(videoIds) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/bulk-delete`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({ video_ids: videoIds })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const data = await response.json().catch(() => ({}))
        throw new Error(data.message || `Failed to delete videos: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error bulk deleting videos:', error)
      throw error
    }
  }

  /**
   * Toggle video sharing status
   */
  async toggleSharing(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/toggle-sharing`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to toggle sharing: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error toggling sharing:', error)
      throw error
    }
  }

  /**
   * Regenerate share token for a video
   */
  async regenerateShareToken(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/regenerate-token`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to regenerate token: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error regenerating token:', error)
      throw error
    }
  }

  /**
   * Get comments for a video
   */
  async getComments(videoId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/comments`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch comments: ${response.statusText}`)
      }

      const data = await response.json()
      return data.comments || []
    } catch (error) {
      console.error('Error fetching comments:', error)
      return []
    }
  }

  /**
   * Add a comment to a video
   */
  async addComment(videoId, content, authorName = 'You', timestampSeconds = null) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/comments`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({
          content,
          author_name: authorName,
          timestamp_seconds: timestampSeconds
        })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to add comment: ${response.statusText}`)
      }

      const data = await response.json()
      return data.comment
    } catch (error) {
      console.error('Error adding comment:', error)
      throw error
    }
  }

  /**
   * Get commenters for @ mention autocomplete
   */
  async getCommenters(videoId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/commenters`, {
        headers: getAuthHeaders()
      })
      if (!response.ok) return []
      const data = await response.json()
      return data.commenters || []
    } catch (error) {
      return []
    }
  }

  /**
   * Delete a comment
   */
  async deleteComment(videoId, commentId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/comments/${commentId}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        throw new Error(`Failed to delete comment: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error deleting comment:', error)
      throw error
    }
  }

  /**
   * Record a video view
   */
  async recordView(videoId, watchDuration = 0, completed = false) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/view`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({
          watch_duration: watchDuration,
          completed
        })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        // Failed to record view
        return null
      }

      const data = await response.json()
      return data
    } catch (error) {
      // Error recording view
      return null
    }
  }

  /**
   * Record a view for a shared video
   */
  async recordSharedView(shareToken) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/share/video/${shareToken}/view`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (!response.ok) {
        // Failed to record shared view
        return null
      }

      const data = await response.json()
      return data
    } catch (error) {
      // Error recording shared view
      return null
    }
  }

  /**
   * Trim a video to specified start and end times
   */
  async trimVideo(videoId, startTime, endTime) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/trim`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({
          start_time: startTime,
          end_time: endTime
        })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to trim video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error trimming video:', error)
      throw error
    }
  }

  /**
   * Get video statistics
   */
  async getVideoStats(videoId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/stats`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch stats: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error fetching video stats:', error)
      return null
    }
  }

  /**
   * Get favourite videos for the current user
   */
  async getFavourites() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/favourites`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch favourites: ${response.statusText}`)
      }

      const data = await response.json()
      return data.videos || []
    } catch (error) {
      console.error('Error fetching favourites:', error)
      throw error
    }
  }

  /**
   * Toggle favourite status for a video
   */
  async toggleFavourite(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/toggle-favourite`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to toggle favourite: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error toggling favourite:', error)
      throw error
    }
  }

  /**
   * Bulk add videos to favourites
   * @param {number[]} videoIds - Array of video IDs
   * @returns {Promise<{message: string, added: number, failed: number}>}
   */
  async bulkAddToFavourites(videoIds) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/bulk-favourite`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({ video_ids: videoIds })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const data = await response.json().catch(() => ({}))
        throw new Error(data.message || `Failed to add to favourites: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error bulk adding to favourites:', error)
      throw error
    }
  }

  /**
   * Bulk remove videos from favourites
   * @param {number[]} videoIds - Array of video IDs
   * @returns {Promise<{message: string, removed: number, failed: number}>}
   */
  async bulkRemoveFromFavourites(videoIds) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/bulk-favourite`, {
        method: 'DELETE',
        headers: getAuthHeaders(),
        body: JSON.stringify({ video_ids: videoIds })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const data = await response.json().catch(() => ({}))
        throw new Error(data.message || `Failed to remove from favourites: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error bulk removing from favourites:', error)
      throw error
    }
  }

  /**
   * Request transcription for a video
   */
  async requestTranscription(id, generateSummary = true, generateTitle = true) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/transcription`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({
          generate_summary: generateSummary,
          generate_title: generateTitle
        })
      })

      if (handleUnauthorized(response)) return null

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error requesting transcription:', error)
      throw error
    }
  }

  /**
   * Get transcription and summary for a video
   */
  async getTranscription(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/transcription`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch transcription: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error fetching transcription:', error)
      throw error
    }
  }

  /**
   * Get transcription status for a video
   */
  async getTranscriptionStatus(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/transcription/status`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch transcription status: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error fetching transcription status:', error)
      throw error
    }
  }

  /**
   * Get Bunny playback data for a video (authenticated)
   * Returns HLS URL, thumbnail, and available resolutions
   */
  async getBunnyPlayback(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/bunny/videos/${id}/playback`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const data = await response.json().catch(() => ({}))
        throw new Error(data.message || `Failed to fetch Bunny playback: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error fetching Bunny playback:', error)
      throw error
    }
  }

  /**
   * Get Bunny playback data for a shared video (public)
   * Returns HLS URL, thumbnail, and available resolutions
   */
  async getBunnySharedPlayback(shareToken) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/bunny/share/${shareToken}/playback`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      if (!response.ok) {
        const data = await response.json().catch(() => ({}))
        throw new Error(data.message || `Failed to fetch Bunny playback: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error fetching Bunny shared playback:', error)
      throw error
    }
  }

  /**
   * Get Bunny download URL for a shared video (public)
   * Returns a signed download URL
   */
  async getBunnySharedDownload(shareToken) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/bunny/share/${shareToken}/download`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      })

      if (!response.ok) {
        const data = await response.json().catch(() => ({}))
        throw new Error(data.message || `Failed to get download URL: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error fetching Bunny shared download:', error)
      throw error
    }
  }

  /**
   * Get Bunny video status
   * Returns encoding status, progress, and available resolutions
   */
  async getBunnyStatus(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/bunny/videos/${id}/status`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch Bunny status: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error fetching Bunny status:', error)
      throw error
    }
  }

  // ============================================
  // MP4 DOWNLOAD METHODS
  // ============================================

  /**
   * Request MP4 download for a video
   * Returns { mode: 'sync', blob } for short videos or { mode: 'async' } for long ones
   */
  async requestDownloadMp4(id) {
    try {
      const token = getAuthToken()
      const headers = { 'Accept': 'application/json' }
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/request-download-mp4`, {
        method: 'POST',
        headers
      })

      if (handleUnauthorized(response)) return null

      if (response.status === 202) {
        const data = await response.json().catch(() => ({}))
        if (data.mode === 'processing') {
          return { mode: 'processing', bunnyStatus: data.bunny_status, message: data.message }
        }
        return { mode: 'async' }
      }

      if (!response.ok) {
        const error = await response.json().catch(() => ({}))
        throw new Error(error.message || `Failed to request MP4 download: ${response.statusText}`)
      }

      // Check content type to determine if it's JSON (redirect mode) or a file blob (sync mode)
      const contentType = response.headers.get('content-type') || ''
      if (contentType.includes('application/json')) {
        const data = await response.json()
        if (data.mode === 'redirect') {
          return { mode: 'redirect', url: data.url, fileName: data.file_name }
        }
      }

      const blob = await response.blob()
      return { mode: 'sync', blob }
    } catch (error) {
      console.error('Error requesting MP4 download:', error)
      throw error
    }
  }

  /**
   * Download a previously converted MP4 file
   * Returns a blob for the file download
   */
  async downloadMp4(id) {
    try {
      const token = getAuthToken()
      const headers = {}
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/download-mp4`, {
        method: 'GET',
        headers
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json().catch(() => ({}))
        throw new Error(error.message || `Failed to download MP4: ${response.statusText}`)
      }

      return await response.blob()
    } catch (error) {
      console.error('Error downloading MP4:', error)
      throw error
    }
  }

  // ============================================
  // ZOOM EFFECT METHODS
  // ============================================

  /**
   * Update zoom settings for a video
   */
  async updateZoomSettings(id, settings) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/zoom-settings`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify(settings)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to update zoom settings: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error updating zoom settings:', error)
      throw error
    }
  }

  /**
   * Get zoom events for a video
   */
  async getZoomEvents(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/zoom-events`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch zoom events: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error fetching zoom events:', error)
      throw error
    }
  }

  /**
   * Update zoom events for a video
   */
  async updateZoomEvents(id, zoomEvents) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/zoom-events`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify({ zoom_events: zoomEvents })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to update zoom events: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error updating zoom events:', error)
      throw error
    }
  }

  /**
   * Get zoom status for a video
   */
  async getZoomStatus(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/zoom-status`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch zoom status: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error fetching zoom status:', error)
      throw error
    }
  }

  // ============================================
  // BLUR EFFECT METHODS
  // ============================================

  /**
   * Apply blur to a region of the video
   * @param {number} id - Video ID
   * @param {Object} blurRegion - Blur region coordinates (x, y, width, height as percentages)
   * @param {number|null} startTime - Start time in seconds (null for entire video)
   * @param {number|null} endTime - End time in seconds (null for entire video)
   * @returns {Promise<{message: string, video: Object}>}
   */
  async applyBlur(id, blurRegion, startTime = null, endTime = null) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/blur`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({
          blur_region: blurRegion,
          start_time: startTime,
          end_time: endTime
        })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to apply blur: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error applying blur:', error)
      throw error
    }
  }

  // ============================================
  // VIDEO EDITOR METHODS
  // ============================================

  /**
   * Apply edits (blur regions + overlays) to a video
   * @param {number} id - Video ID
   * @param {Array} blurRegions - Array of blur region objects
   * @param {Array} overlayConfigs - Array of overlay config objects
   * @param {File[]} overlayFiles - Array of overlay video files
   */
  async applyEdits(id, blurRegions = [], overlayConfigs = [], overlayFiles = [], textOverlays = [], trimStart = null, trimEnd = null, mergeVideoIds = [], mainVideoPosition = 0) {
    try {
      const formData = new FormData()

      blurRegions.forEach((region, i) => {
        Object.entries(region).forEach(([key, value]) => {
          if (value !== null && value !== undefined) {
            formData.append(`blur_regions[${i}][${key}]`, value)
          }
        })
      })

      overlayConfigs.forEach((config, i) => {
        Object.entries(config).forEach(([key, value]) => {
          if (value !== null && value !== undefined) {
            formData.append(`overlay_configs[${i}][${key}]`, value)
          }
        })
      })

      textOverlays.forEach((overlay, i) => {
        Object.entries(overlay).forEach(([key, value]) => {
          if (value !== null && value !== undefined) {
            formData.append(`text_overlays[${i}][${key}]`, value)
          }
        })
      })

      overlayFiles.forEach((file) => {
        formData.append('overlay_files[]', file)
      })

      if (trimStart !== null && trimEnd !== null) {
        formData.append('trim_start', trimStart)
        formData.append('trim_end', trimEnd)
      }

      if (mergeVideoIds.length) {
        mergeVideoIds.forEach((vid, i) => {
          formData.append(`merge_video_ids[${i}]`, vid)
        })
        formData.append('main_video_position', mainVideoPosition)
      }

      const token = getAuthToken()
      const headers = { 'Accept': 'application/json' }
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/apply-edits`, {
        method: 'POST',
        headers,
        body: formData
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to apply edits: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error applying edits:', error)
      throw error
    }
  }

  /**
   * Update transcription text for a video
   */
  async updateTranscription(id, text, segments = null) {
    try {
      const body = { text }
      if (segments) {
        body.segments = segments
      }

      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/transcription`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify(body)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to update transcription: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error updating transcription:', error)
      throw error
    }
  }

  /**
   * Get edit status for a video
   */
  async getEditStatus(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/edit-status`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch edit status: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error fetching edit status:', error)
      throw error
    }
  }

  /**
   * Get blur status for a video
   */
  async getBlurStatus(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/blur-status`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch blur status: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error fetching blur status:', error)
      throw error
    }
  }

  async askTranscriptQuestion(id, question) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/transcript-chat`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({ question })
      })

      if (handleUnauthorized(response)) return null

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to get answer')
      }

      return data
    } catch (error) {
      console.error('Error asking transcript question:', error)
      throw error
    }
  }
}

export default new VideoService()
