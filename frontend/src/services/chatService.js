// Chat API Service
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

class ChatService {
  async getConversations() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/chat/conversations`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch conversations: ${response.statusText}`)
      }

      const data = await response.json()
      return data.conversations || []
    } catch (error) {
      console.error('Error fetching conversations:', error)
      throw error
    }
  }

  async getMessages(conversationId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/chat/conversations/${conversationId}/messages`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch messages: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error fetching messages:', error)
      throw error
    }
  }

  async sendMessage(message, conversationId = null) {
    try {
      const body = { message }
      if (conversationId) {
        body.conversation_id = conversationId
      }

      const response = await fetch(`${API_BASE_URL}/api/chat/send`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify(body)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.message || `Failed to send message: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error sending message:', error)
      throw error
    }
  }

  async createConversation() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/chat/conversations`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to create conversation: ${response.statusText}`)
      }

      const data = await response.json()
      return data.conversation
    } catch (error) {
      console.error('Error creating conversation:', error)
      throw error
    }
  }

  async deleteConversation(conversationId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/chat/conversations/${conversationId}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        throw new Error(`Failed to delete conversation: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error deleting conversation:', error)
      throw error
    }
  }
}

export default new ChatService()
