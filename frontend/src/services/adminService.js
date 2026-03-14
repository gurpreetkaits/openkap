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
    window.location.href = import.meta.env.BASE_URL + 'login'
    return true
  }
  return false
}

class AdminService {
  async getDashboardStats() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/admin/dashboard`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch dashboard stats: ${response.statusText}`)
      }

      const data = await response.json()
      return data.dashboard
    } catch (error) {
      console.error('Error fetching dashboard stats:', error)
      throw error
    }
  }
}

export default new AdminService()
