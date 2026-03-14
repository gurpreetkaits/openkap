// Integration API Service
const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

function getAuthToken() {
  return localStorage.getItem('auth_token')
}

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

class IntegrationService {
  /**
   * Get all available providers with their connection status
   */
  async getAvailableProviders() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/integrations`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error('Failed to fetch integrations')
      }

      const data = await response.json()
      return data.integrations
    } catch (error) {
      console.error('Error fetching integrations:', error)
      throw error
    }
  }

  /**
   * Initiate OAuth connection for a provider
   * Returns the authorization URL to redirect to
   */
  async connect(provider) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/integrations/${provider}/connect`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to connect integration')
      }

      const data = await response.json()
      return data.url
    } catch (error) {
      console.error('Error connecting integration:', error)
      throw error
    }
  }

  /**
   * Disconnect a provider
   */
  async disconnect(provider) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/integrations/${provider}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to disconnect integration')
      }

      return await response.json()
    } catch (error) {
      console.error('Error disconnecting integration:', error)
      throw error
    }
  }

  /**
   * Get available targets (channels, folders, projects, etc.) for a provider
   */
  async getTargets(provider) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/integrations/${provider}/targets`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to fetch targets')
      }

      const data = await response.json()
      return data.targets
    } catch (error) {
      console.error('Error fetching targets:', error)
      throw error
    }
  }

  /**
   * Share a video to an integration
   */
  async shareVideo(provider, videoId, data) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/integrations/${provider}/videos/${videoId}/share`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify(data)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to share video')
      }

      return await response.json()
    } catch (error) {
      console.error('Error sharing video:', error)
      throw error
    }
  }

  /**
   * Create a bug issue in an integration (e.g. Jira)
   */
  async createBug(provider, videoId, bugData) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/integrations/${provider}/videos/${videoId}/bug`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify(bugData)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to create bug')
      }

      return await response.json()
    } catch (error) {
      console.error('Error creating bug:', error)
      throw error
    }
  }

  /**
   * Get share history for a video
   */
  async getShareHistory(videoId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/integrations/videos/${videoId}/history`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error('Failed to fetch share history')
      }

      const data = await response.json()
      return data.history
    } catch (error) {
      console.error('Error fetching share history:', error)
      throw error
    }
  }

  /**
   * Send Trello token to backend (for fragment-based auth callback)
   */
  async sendTrelloToken(token, state) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/integrations/trello/callback?token=${encodeURIComponent(token)}&state=${encodeURIComponent(state)}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        redirect: 'manual'
      })

      // The callback endpoint redirects, so we handle that
      return { success: true }
    } catch (error) {
      console.error('Error sending Trello token:', error)
      throw error
    }
  }
}

export default new IntegrationService()
