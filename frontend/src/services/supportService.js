// Support Chat API Service
const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

function getAuthToken() {
  return localStorage.getItem('auth_token')
}

function getAuthHeaders() {
  const token = getAuthToken()
  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
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

class SupportService {
  async getMyConversation() {
    const response = await fetch(`${API_BASE_URL}/api/support/conversation`, {
      method: 'GET',
      headers: getAuthHeaders(),
    })
    if (handleUnauthorized(response)) return null
    if (!response.ok) throw new Error(`Failed to fetch conversation: ${response.statusText}`)
    const json = await response.json()
    return json.data
  }

  async sendMessage(body) {
    const response = await fetch(`${API_BASE_URL}/api/support/messages`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({ body }),
    })
    if (handleUnauthorized(response)) return null
    if (!response.ok) {
      const error = await response.json().catch(() => ({}))
      throw new Error(error.message || `Failed to send message: ${response.statusText}`)
    }
    const json = await response.json()
    return json.data
  }

  async markMyConversationRead() {
    const response = await fetch(`${API_BASE_URL}/api/support/conversation/mark-read`, {
      method: 'POST',
      headers: getAuthHeaders(),
    })
    if (handleUnauthorized(response)) return null
    if (!response.ok) return null
    const json = await response.json()
    return json.data
  }

  // ---- Admin ----

  async listAdminConversations({ search = '', page = 1, perPage = 25 } = {}) {
    const params = new URLSearchParams()
    if (search) params.set('search', search)
    if (page > 1) params.set('page', String(page))
    if (perPage !== 25) params.set('per_page', String(perPage))
    const qs = params.toString() ? `?${params.toString()}` : ''
    const response = await fetch(`${API_BASE_URL}/api/admin/support/conversations${qs}`, {
      method: 'GET',
      headers: getAuthHeaders(),
    })
    if (handleUnauthorized(response)) return null
    if (!response.ok) throw new Error(`Failed to list conversations: ${response.statusText}`)
    return await response.json()
  }

  async getAdminConversation(conversationId) {
    const response = await fetch(
      `${API_BASE_URL}/api/admin/support/conversations/${conversationId}`,
      { method: 'GET', headers: getAuthHeaders() },
    )
    if (handleUnauthorized(response)) return null
    if (!response.ok) throw new Error(`Failed to fetch conversation: ${response.statusText}`)
    const json = await response.json()
    return json.data
  }

  async replyAsAdmin(conversationId, body) {
    const response = await fetch(
      `${API_BASE_URL}/api/admin/support/conversations/${conversationId}/messages`,
      {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({ body }),
      },
    )
    if (handleUnauthorized(response)) return null
    if (!response.ok) {
      const error = await response.json().catch(() => ({}))
      throw new Error(error.message || `Failed to send reply: ${response.statusText}`)
    }
    const json = await response.json()
    return json.data
  }

  async markAdminConversationRead(conversationId) {
    const response = await fetch(
      `${API_BASE_URL}/api/admin/support/conversations/${conversationId}/mark-read`,
      { method: 'POST', headers: getAuthHeaders() },
    )
    if (handleUnauthorized(response)) return null
    if (!response.ok) return null
    const json = await response.json()
    return json.data
  }
}

export default new SupportService()
