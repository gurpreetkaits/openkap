// Playlist API Service
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
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    localStorage.setItem('auth_redirect', window.location.pathname)
    window.location.href = import.meta.env.BASE_URL + 'login'
    return true
  }
  return false
}

class PlaylistService {
  /**
   * Fetch all playlists for the current user
   */
  async getPlaylists() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch playlists: ${response.statusText}`)
      }

      const data = await response.json()
      return data.playlists || []
    } catch (error) {
      console.error('Error fetching playlists:', error)
      throw error
    }
  }

  /**
   * Get a specific playlist by ID
   */
  async getPlaylist(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${id}`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch playlist: ${response.statusText}`)
      }

      const data = await response.json()
      return data.playlist
    } catch (error) {
      console.error('Error fetching playlist:', error)
      throw error
    }
  }

  /**
   * Create a new playlist
   */
  async createPlaylist(data) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify(data)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to create playlist: ${response.statusText}`)
      }

      const result = await response.json()
      return result.playlist
    } catch (error) {
      console.error('Error creating playlist:', error)
      throw error
    }
  }

  /**
   * Update a playlist
   */
  async updatePlaylist(id, data) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${id}`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify(data)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to update playlist: ${response.statusText}`)
      }

      const result = await response.json()
      return result.playlist
    } catch (error) {
      console.error('Error updating playlist:', error)
      throw error
    }
  }

  /**
   * Delete a playlist
   */
  async deletePlaylist(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${id}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        throw new Error(`Failed to delete playlist: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error deleting playlist:', error)
      throw error
    }
  }

  /**
   * Toggle playlist sharing (public/private)
   */
  async toggleSharing(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${id}/toggle-sharing`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to toggle sharing: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error toggling sharing:', error)
      throw error
    }
  }

  /**
   * Update sort order for a playlist
   */
  async updateSortBy(id, sortBy) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${id}/sort-by`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify({ sort_by: sortBy })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to update sort order: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error updating sort order:', error)
      throw error
    }
  }

  /**
   * Add a video to a playlist
   */
  async addVideo(playlistId, videoId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${playlistId}/videos`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({ video_id: videoId })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to add video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.playlist
    } catch (error) {
      console.error('Error adding video:', error)
      throw error
    }
  }

  /**
   * Bulk add videos to a playlist
   * @param {number} playlistId - The playlist ID
   * @param {number[]} videoIds - Array of video IDs to add
   * @returns {Promise<{message: string, added: number, skipped: number, errors: array, playlist: object}>}
   */
  async bulkAddVideos(playlistId, videoIds) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${playlistId}/bulk-add-videos`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({ video_ids: videoIds })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to add videos: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error bulk adding videos:', error)
      throw error
    }
  }

  /**
   * Remove a video from a playlist
   */
  async removeVideo(playlistId, videoId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${playlistId}/videos/${videoId}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to remove video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.playlist
    } catch (error) {
      console.error('Error removing video:', error)
      throw error
    }
  }

  /**
   * Reorder videos in a playlist
   */
  async reorderVideos(playlistId, videoIds) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${playlistId}/reorder`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify({ video_ids: videoIds })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to reorder videos: ${response.statusText}`)
      }

      const data = await response.json()
      return data.playlist
    } catch (error) {
      console.error('Error reordering videos:', error)
      throw error
    }
  }

  /**
   * Set a share password on a playlist
   */
  async setPassword(playlistId, password) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/playlists/${playlistId}/password`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify({ password })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to set password: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error setting playlist password:', error)
      throw error
    }
  }

  /**
   * Remove the share password from a playlist
   */
  async removePassword(playlistId) {
    return this.setPassword(playlistId, null)
  }

  /**
   * Get a shared playlist by token (public access)
   */
  async getSharedPlaylist(token, password = null) {
    try {
      let url = `${API_BASE_URL}/api/share/playlist/${token}`
      if (password) {
        url += `?password=${encodeURIComponent(password)}`
      }

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      if (response.status === 423) {
        const data = await response.json()
        const err = new Error(data.message || 'Password required')
        err.passwordRequired = true
        throw err
      }

      if (!response.ok) {
        if (response.status === 404) {
          throw new Error('Playlist not found')
        }
        if (response.status === 403) {
          throw new Error('This playlist is no longer available')
        }
        throw new Error(`Failed to fetch shared playlist: ${response.statusText}`)
      }

      const data = await response.json()
      return data.playlist
    } catch (error) {
      console.error('Error fetching shared playlist:', error)
      throw error
    }
  }
}

export default new PlaylistService()
