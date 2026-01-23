// Workspace API Service
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

class WorkspaceService {
  /**
   * Fetch all workspaces for the current user
   */
  async getWorkspaces() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch workspaces: ${response.statusText}`)
      }

      const data = await response.json()
      return data.workspaces || []
    } catch (error) {
      console.error('Error fetching workspaces:', error)
      throw error
    }
  }

  /**
   * Get a specific workspace by slug
   */
  async getWorkspace(slug) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch workspace: ${response.statusText}`)
      }

      const data = await response.json()
      return data.workspace
    } catch (error) {
      console.error('Error fetching workspace:', error)
      throw error
    }
  }

  /**
   * Create a new workspace
   */
  async createWorkspace(data) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify(data)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to create workspace: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error creating workspace:', error)
      throw error
    }
  }

  /**
   * Update a workspace
   */
  async updateWorkspace(slug, data) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}`, {
        method: 'PATCH',
        headers: getAuthHeaders(),
        body: JSON.stringify(data)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to update workspace: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error updating workspace:', error)
      throw error
    }
  }

  /**
   * Delete a workspace
   */
  async deleteWorkspace(slug) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to delete workspace: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error deleting workspace:', error)
      throw error
    }
  }

  /**
   * Leave a workspace
   */
  async leaveWorkspace(slug) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}/leave`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to leave workspace: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error leaving workspace:', error)
      throw error
    }
  }

  /**
   * Get workspace members
   */
  async getMembers(slug) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}/members`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch members: ${response.statusText}`)
      }

      const data = await response.json()
      return data.members || []
    } catch (error) {
      console.error('Error fetching members:', error)
      throw error
    }
  }

  /**
   * Invite a member to workspace
   */
  async inviteMember(slug, data) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}/invitations`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify(data)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to invite member: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error inviting member:', error)
      throw error
    }
  }

  /**
   * Update member role
   */
  async updateMemberRole(slug, userId, role) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}/members/${userId}`, {
        method: 'PATCH',
        headers: getAuthHeaders(),
        body: JSON.stringify({ role })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to update member role: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error updating member role:', error)
      throw error
    }
  }

  /**
   * Remove a member from workspace
   */
  async removeMember(slug, userId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}/members/${userId}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to remove member: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error removing member:', error)
      throw error
    }
  }

  /**
   * Get workspace invitations
   */
  async getInvitations(slug) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}/invitations`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch invitations: ${response.statusText}`)
      }

      const data = await response.json()
      return data.invitations || []
    } catch (error) {
      console.error('Error fetching invitations:', error)
      throw error
    }
  }

  /**
   * Cancel an invitation
   */
  async cancelInvitation(slug, invitationId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}/invitations/${invitationId}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to cancel invitation: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error canceling invitation:', error)
      throw error
    }
  }

  /**
   * Resend an invitation
   */
  async resendInvitation(slug, invitationId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}/invitations/${invitationId}/resend`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to resend invitation: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error resending invitation:', error)
      throw error
    }
  }

  /**
   * Get invitation details by token (public)
   */
  async getInvitationByToken(token) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/invitations/${token}`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (!response.ok) {
        throw new Error(`Invitation not found or expired`)
      }

      const data = await response.json()
      return data.invitation
    } catch (error) {
      console.error('Error fetching invitation:', error)
      throw error
    }
  }

  /**
   * Accept an invitation
   */
  async acceptInvitation(token) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/invitations/${token}/accept`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to accept invitation: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Error accepting invitation:', error)
      throw error
    }
  }

  /**
   * Start checkout for workspace subscription
   */
  async startCheckout(slug) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/subscription/checkout`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({ plan: 'teams_monthly', workspace_slug: slug })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || error.error || `Failed to start checkout: ${response.statusText}`)
      }

      const data = await response.json()
      if (data.checkout_url) {
        window.location.href = data.checkout_url
      }
      return data
    } catch (error) {
      console.error('Error starting checkout:', error)
      throw error
    }
  }

  /**
   * Get workspace videos
   */
  async getWorkspaceVideos(slug) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/workspaces/${slug}/videos`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch workspace videos: ${response.statusText}`)
      }

      const data = await response.json()
      return data.videos || []
    } catch (error) {
      console.error('Error fetching workspace videos:', error)
      throw error
    }
  }
}

export default new WorkspaceService()
