// Settings API Service
const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

class SettingsService {
  constructor() {
    this.cache = null
    this.cacheExpiry = null
    this.CACHE_TTL = 5 * 60 * 1000 // 5 minutes
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
  }
}

export default new SettingsService()
