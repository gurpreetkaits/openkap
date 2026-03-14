// Notification API Service
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

class NotificationService {
  /**
   * Fetch all notifications for the current user
   */
  async getNotifications(page = 1, perPage = 20) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/notifications?page=${page}&per_page=${perPage}`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return { notifications: [], pagination: {} }

      if (!response.ok) {
        throw new Error(`Failed to fetch notifications: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error fetching notifications:', error)
      throw error
    }
  }

  /**
   * Get unread notifications count
   */
  async getUnreadCount() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/notifications/unread-count`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return 0

      if (!response.ok) {
        throw new Error(`Failed to fetch unread count: ${response.statusText}`)
      }

      const data = await response.json()
      return data.unread_count || 0
    } catch (error) {
      console.error('Error fetching unread count:', error)
      return 0
    }
  }

  /**
   * Mark a notification as read
   */
  async markAsRead(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/notifications/${id}/read`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        throw new Error(`Failed to mark notification as read: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error marking notification as read:', error)
      throw error
    }
  }

  /**
   * Mark all notifications as read
   */
  async markAllAsRead() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/notifications/mark-all-read`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        throw new Error(`Failed to mark all notifications as read: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error marking all notifications as read:', error)
      throw error
    }
  }

  /**
   * Delete a notification
   */
  async deleteNotification(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/notifications/${id}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        throw new Error(`Failed to delete notification: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error deleting notification:', error)
      throw error
    }
  }
}

export default new NotificationService()
