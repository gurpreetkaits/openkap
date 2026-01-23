const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

function getAuthHeaders() {
  const token = localStorage.getItem('auth_token')
  return {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
}

export default {
  /**
   * Get all folders for current user
   */
  async getFolders() {
    const response = await fetch(`${API_BASE_URL}/api/folders`, {
      headers: getAuthHeaders()
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to fetch folders')
    }

    const data = await response.json()
    return data.folders
  },

  /**
   * Create a new folder
   */
  async createFolder(name, color = null) {
    const response = await fetch(`${API_BASE_URL}/api/folders`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({ name, color })
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to create folder')
    }

    return await response.json()
  },

  /**
   * Update a folder
   */
  async updateFolder(folderId, data) {
    const response = await fetch(`${API_BASE_URL}/api/folders/${folderId}`, {
      method: 'PATCH',
      headers: getAuthHeaders(),
      body: JSON.stringify(data)
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to update folder')
    }

    return await response.json()
  },

  /**
   * Delete a folder
   */
  async deleteFolder(folderId) {
    const response = await fetch(`${API_BASE_URL}/api/folders/${folderId}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to delete folder')
    }

    return await response.json()
  },

  /**
   * Get videos in a folder
   */
  async getFolderVideos(folderId) {
    const response = await fetch(`${API_BASE_URL}/api/folders/${folderId}/videos`, {
      headers: getAuthHeaders()
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to fetch folder videos')
    }

    const data = await response.json()
    return data.videos
  },

  /**
   * Add videos to a folder
   */
  async addVideosToFolder(folderId, videoIds) {
    const response = await fetch(`${API_BASE_URL}/api/folders/${folderId}/videos`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({ video_ids: videoIds })
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to add videos to folder')
    }

    return await response.json()
  },

  /**
   * Remove a video from a folder
   */
  async removeVideoFromFolder(folderId, videoId) {
    const response = await fetch(`${API_BASE_URL}/api/folders/${folderId}/videos/${videoId}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to remove video from folder')
    }

    return await response.json()
  }
}
