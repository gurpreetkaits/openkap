// Screenshot API Service
const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

// Helper to get auth token
function getAuthToken() {
  return localStorage.getItem('auth_token')
}

// Helper to get auth headers
function getAuthHeaders() {
  const token = getAuthToken()
  const headers = {
    'Accept': 'application/json'
  }
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }
  return headers
}

// Helper to handle unauthorized responses
function handleUnauthorized(response) {
  if (response.status === 401) {
    localStorage.removeItem('auth_token')
    window.location.href = import.meta.env.BASE_URL + 'login'
    return true
  }
  return false
}

class ScreenshotService {
  /**
   * Fetch all screenshots for the current user
   */
  async getScreenshots() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/screenshots`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch screenshots: ${response.statusText}`)
      }

      const data = await response.json()
      return data.screenshots || []
    } catch (error) {
      console.error('Error fetching screenshots:', error)
      throw error
    }
  }

  /**
   * Get a specific screenshot by ID
   */
  async getScreenshot(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/screenshots/${id}`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch screenshot: ${response.statusText}`)
      }

      const data = await response.json()
      return data.screenshot
    } catch (error) {
      console.error('Error fetching screenshot:', error)
      throw error
    }
  }

  /**
   * Upload a new screenshot
   */
  async uploadScreenshot(file, title = null) {
    try {
      const formData = new FormData()
      formData.append('image', file)
      if (title) {
        formData.append('title', title)
      }

      const token = getAuthToken()
      const headers = {
        'Accept': 'application/json'
      }
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${API_BASE_URL}/api/screenshots`, {
        method: 'POST',
        headers,
        body: formData
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const data = await response.json().catch(() => ({}))
        throw new Error(data.message || `Failed to upload screenshot: ${response.statusText}`)
      }

      const data = await response.json()
      return data.screenshot
    } catch (error) {
      console.error('Error uploading screenshot:', error)
      throw error
    }
  }

  /**
   * Update screenshot details (title)
   */
  async updateScreenshot(id, updates) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/screenshots/${id}`, {
        method: 'PUT',
        headers: {
          ...getAuthHeaders(),
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(updates)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to update screenshot: ${response.statusText}`)
      }

      const data = await response.json()
      return data.screenshot
    } catch (error) {
      console.error('Error updating screenshot:', error)
      throw error
    }
  }

  /**
   * Delete a screenshot
   */
  async deleteScreenshot(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/screenshots/${id}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to delete screenshot: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error deleting screenshot:', error)
      throw error
    }
  }

  /**
   * Toggle screenshot sharing status
   */
  async toggleSharing(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/screenshots/${id}/toggle-sharing`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to toggle sharing: ${response.statusText}`)
      }

      const data = await response.json()
      return data.screenshot
    } catch (error) {
      console.error('Error toggling sharing:', error)
      throw error
    }
  }

  /**
   * Get a shared screenshot by token (public)
   */
  async getSharedScreenshot(token) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/share/screenshot/${token}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      })

      if (!response.ok) {
        if (response.status === 404 || response.status === 403) {
          return null
        }
        throw new Error(`Failed to fetch shared screenshot: ${response.statusText}`)
      }

      const data = await response.json()
      return data.screenshot
    } catch (error) {
      console.error('Error fetching shared screenshot:', error)
      throw error
    }
  }

  /**
   * Copy share URL to clipboard
   */
  async copyShareUrl(screenshot) {
    const shareUrl = screenshot.share_url || screenshot.shareUrl
    if (shareUrl) {
      await navigator.clipboard.writeText(shareUrl)
      return true
    }
    return false
  }
}

export const screenshotService = new ScreenshotService()
export default screenshotService
