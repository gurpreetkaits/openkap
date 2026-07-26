// API Token Service — manage personal access tokens for programmatic uploads
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

class TokenService {
  /**
   * List the current user's personal API tokens.
   */
  async listTokens() {
    const response = await fetch(`${API_BASE_URL}/api/tokens`, {
      method: 'GET',
      headers: getAuthHeaders()
    })

    if (handleUnauthorized(response)) return []

    if (!response.ok) {
      throw new Error('Failed to fetch API tokens')
    }

    const data = await response.json()
    return data.tokens || []
  }

  /**
   * Create a new personal API token.
   * @param {{ name: string, expires_in_days?: number|null }} payload
   * @returns {Promise<{ plain_text_token: string, token: object }>}
   */
  async createToken(payload) {
    const response = await fetch(`${API_BASE_URL}/api/tokens`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify(payload)
    })

    if (handleUnauthorized(response)) return null

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.message || 'Failed to create API token')
    }

    return await response.json()
  }

  /**
   * Revoke (delete) a personal API token by id.
   */
  async revokeToken(tokenId) {
    const response = await fetch(`${API_BASE_URL}/api/tokens/${tokenId}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    })

    if (handleUnauthorized(response)) return null

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.message || 'Failed to revoke API token')
    }

    return await response.json()
  }
}

export default new TokenService()
