// Settings API Service
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
    window.location.href = '/login'
    return true
  }
  return false
}

class SettingsService {
  constructor() {
    this.cache = null
    this.cacheExpiry = null
    this.CACHE_TTL = 5 * 60 * 1000 // 5 minutes
    this.userSettingsCache = null
  }

  /**
   * Fetch public settings (subscription prices, limits)
   */
  async getSettings() {
    // Return cached if valid
    if (this.cache && this.cacheExpiry && Date.now() < this.cacheExpiry) {
      return this.cache
    }

    try {
      const response = await fetch(`${API_BASE_URL}/api/settings`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        }
      })

      if (!response.ok) {
        throw new Error(`Failed to fetch settings: ${response.statusText}`)
      }

      const data = await response.json()

      // Cache the result
      this.cache = data
      this.cacheExpiry = Date.now() + this.CACHE_TTL

      return data
    } catch (error) {
      console.error('Error fetching settings:', error)
      // Return defaults if fetch fails
      return this.getDefaults()
    }
  }

  /**
   * Get subscription settings
   */
  async getSubscriptionSettings() {
    const settings = await this.getSettings()
    return settings.subscription || this.getDefaults().subscription
  }

  /**
   * Get free plan limits
   */
  async getFreePlanLimits() {
    const settings = await this.getSettings()
    return settings.free_plan || this.getDefaults().free_plan
  }

  /**
   * Default values if API fails
   */
  getDefaults() {
    return {
      subscription: {
        free_video_limit: 1,
        free_recording_duration_limit: 300,
        monthly_price: 7,
        yearly_price: 80,
        yearly_monthly_price: 6.67,
        yearly_savings_percent: 5,
      },
      free_plan: {
        max_videos: 1,
        max_duration_seconds: 300,
        max_duration_minutes: 5,
      }
    }
  }

  /**
   * Clear cache (useful after settings update)
   */
  clearCache() {
    this.cache = null
    this.cacheExpiry = null
    this.userSettingsCache = null
  }

  // ============================================
  // USER SETTINGS (requires authentication)
  // ============================================

  /**
   * Get current user's settings
   */
  async getUserSettings() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/user/settings`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error('Failed to fetch user settings')
      }

      const data = await response.json()
      this.userSettingsCache = data.settings
      return data.settings
    } catch (error) {
      console.error('Error fetching user settings:', error)
      throw error
    }
  }

  /**
   * Update user's settings
   */
  async updateUserSettings(settings) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/user/settings`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify(settings)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to update settings')
      }

      const data = await response.json()
      this.userSettingsCache = data.settings
      return data.settings
    } catch (error) {
      console.error('Error updating user settings:', error)
      throw error
    }
  }

  /**
   * Reset user settings to defaults
   */
  async resetUserSettings() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/user/settings/reset`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error('Failed to reset settings')
      }

      const data = await response.json()
      this.userSettingsCache = data.settings
      return data.settings
    } catch (error) {
      console.error('Error resetting user settings:', error)
      throw error
    }
  }

  /**
   * Get user settings defaults
   */
  getUserSettingsDefaults() {
    return {
      auto_zoom_enabled: false,
      default_zoom_level: 2.0,
      default_zoom_duration_ms: 500
    }
  }
}

export default new SettingsService()
