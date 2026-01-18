# Bunny Stream Implementation Guide

## Overview

This guide covers secure video recording, uploading to Bunny Stream, and controlled access for users.

---

## Table of Contents

1. [Bunny Stream Setup](#1-bunny-stream-setup)
2. [Architecture](#2-architecture)
3. [Backend Implementation](#3-backend-implementation)
4. [Extension Changes](#4-extension-changes)
5. [Secure Video Access](#5-secure-video-access)
6. [Database Schema](#6-database-schema)
7. [API Endpoints](#7-api-endpoints)
8. [Security Considerations](#8-security-considerations)

---

## 1. Bunny Stream Setup

### Step 1: Create Bunny Account
1. Sign up at [bunny.net](https://bunny.net)
2. Go to **Stream** → **Video Libraries**
3. Create a new Video Library

### Step 2: Get Credentials
From your Video Library settings, note down:
- **Library ID**: `123456`
- **API Key**: `your-api-key-here`
- **CDN Hostname**: `vz-xxxxxx-xxx.b-cdn.net`

### Step 3: Configure Security Settings
In Video Library → Security:
- ✅ Enable **Token Authentication**
- ✅ Enable **Signed URLs** (for private videos)
- Set **Token Expiry Time**: 3600 seconds (1 hour)
- Copy the **Security Key** for generating signed URLs

---

## 2. Architecture

```
┌──────────────────────────────────────────────────────────────────────┐
│                         RECORDING FLOW                                │
├──────────────────────────────────────────────────────────────────────┤
│                                                                       │
│   ┌─────────────┐      ┌─────────────┐      ┌─────────────────┐     │
│   │  Extension  │ ───▶ │ Your Server │ ───▶ │  Bunny Stream   │     │
│   │  (Record)   │      │ (Auth+Meta) │      │  (Store+HLS)    │     │
│   └─────────────┘      └─────────────┘      └─────────────────┘     │
│         │                    │                      │                │
│         │                    ▼                      │                │
│         │              ┌───────────┐                │                │
│         │              │ Database  │                │                │
│         │              │ (Metadata)│                │                │
│         │              └───────────┘                │                │
│         │                                           │                │
│         └───────────────────────────────────────────┘                │
│                    Direct TUS Upload                                  │
│                                                                       │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│                         PLAYBACK FLOW                                 │
├──────────────────────────────────────────────────────────────────────┤
│                                                                       │
│   ┌─────────────┐      ┌─────────────┐      ┌─────────────────┐     │
│   │   Viewer    │ ───▶ │ Your Server │ ───▶ │  Bunny CDN      │     │
│   │  (Browser)  │      │(Signed URL) │      │  (Deliver)      │     │
│   └─────────────┘      └─────────────┘      └─────────────────┘     │
│                              │                                       │
│                              ▼                                       │
│                    Check permissions                                 │
│                    Generate signed URL                               │
│                    Return to viewer                                  │
│                                                                       │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 3. Backend Implementation

### Environment Variables

```env
# .env
BUNNY_LIBRARY_ID=123456
BUNNY_API_KEY=your-api-key-here
BUNNY_CDN_HOSTNAME=vz-xxxxxx-xxx.b-cdn.net
BUNNY_SECURITY_KEY=your-security-key-for-signed-urls
```

### Bunny Service (Node.js)

```javascript
// services/bunnyService.js
const crypto = require('crypto');

class BunnyService {
  constructor() {
    this.libraryId = process.env.BUNNY_LIBRARY_ID;
    this.apiKey = process.env.BUNNY_API_KEY;
    this.cdnHostname = process.env.BUNNY_CDN_HOSTNAME;
    this.securityKey = process.env.BUNNY_SECURITY_KEY;
    this.baseUrl = 'https://video.bunnycdn.com';
  }

  /**
   * Create a new video entry in Bunny
   * Call this before uploading
   */
  async createVideo(title, userId) {
    const response = await fetch(
      `${this.baseUrl}/library/${this.libraryId}/videos`,
      {
        method: 'POST',
        headers: {
          'AccessKey': this.apiKey,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          title: title,
          collectionId: userId // Optional: organize by user
        })
      }
    );

    if (!response.ok) {
      throw new Error(`Failed to create video: ${response.statusText}`);
    }

    return response.json();
  }

  /**
   * Generate TUS upload credentials for direct upload from extension
   * This keeps your API key secure on the server
   */
  generateUploadCredentials(videoId, expiresInSeconds = 3600) {
    const expireTime = Math.floor(Date.now() / 1000) + expiresInSeconds;

    // Create signature for TUS upload
    const signatureString = `${this.libraryId}${this.apiKey}${expireTime}${videoId}`;
    const signature = crypto
      .createHash('sha256')
      .update(signatureString)
      .digest('hex');

    return {
      uploadUrl: `${this.baseUrl}/tusupload`,
      libraryId: this.libraryId,
      videoId: videoId,
      expireTime: expireTime,
      signature: signature
    };
  }

  /**
   * Generate signed URL for secure video playback
   * Only users with valid signed URLs can watch
   */
  generateSignedPlaybackUrl(videoId, expiresInSeconds = 3600) {
    const expireTime = Math.floor(Date.now() / 1000) + expiresInSeconds;
    const path = `/${videoId}/playlist.m3u8`;

    // Create token hash
    const tokenString = `${this.securityKey}${path}${expireTime}`;
    const token = crypto
      .createHash('sha256')
      .update(tokenString)
      .digest('hex');

    return {
      hlsUrl: `https://${this.cdnHostname}${path}?token=${token}&expires=${expireTime}`,
      embedUrl: `https://iframe.mediadelivery.net/embed/${this.libraryId}/${videoId}?token=${token}&expires=${expireTime}`,
      expiresAt: new Date(expireTime * 1000).toISOString()
    };
  }

  /**
   * Generate signed thumbnail URL
   */
  generateSignedThumbnailUrl(videoId, expiresInSeconds = 86400) {
    const expireTime = Math.floor(Date.now() / 1000) + expiresInSeconds;
    const path = `/${videoId}/thumbnail.jpg`;

    const tokenString = `${this.securityKey}${path}${expireTime}`;
    const token = crypto
      .createHash('sha256')
      .update(tokenString)
      .digest('hex');

    return `https://${this.cdnHostname}${path}?token=${token}&expires=${expireTime}`;
  }

  /**
   * Get video status from Bunny
   */
  async getVideoStatus(videoId) {
    const response = await fetch(
      `${this.baseUrl}/library/${this.libraryId}/videos/${videoId}`,
      {
        headers: {
          'AccessKey': this.apiKey
        }
      }
    );

    if (!response.ok) {
      throw new Error(`Failed to get video: ${response.statusText}`);
    }

    const data = await response.json();

    // Status codes: 0=created, 1=uploaded, 2=processing, 3=transcoding, 4=finished, 5=error
    const statusMap = {
      0: 'created',
      1: 'uploaded',
      2: 'processing',
      3: 'transcoding',
      4: 'ready',
      5: 'error'
    };

    return {
      videoId: data.guid,
      status: statusMap[data.status] || 'unknown',
      duration: data.length,
      size: data.storageSize,
      width: data.width,
      height: data.height,
      availableResolutions: data.availableResolutions
    };
  }

  /**
   * Delete a video from Bunny
   */
  async deleteVideo(videoId) {
    const response = await fetch(
      `${this.baseUrl}/library/${this.libraryId}/videos/${videoId}`,
      {
        method: 'DELETE',
        headers: {
          'AccessKey': this.apiKey
        }
      }
    );

    return response.ok;
  }
}

module.exports = new BunnyService();
```

---

## 4. Extension Changes

### TUS Upload Client

Install the TUS client library or use the CDN version:

```html
<!-- In offscreen.html or include in extension -->
<script src="https://cdn.jsdelivr.net/npm/tus-js-client@latest/dist/tus.min.js"></script>
```

### Upload Manager

```javascript
// extension/uploadManager.js

class BunnyUploadManager {
  constructor() {
    this.currentUpload = null;
  }

  /**
   * Initialize upload - call your server first to get credentials
   */
  async initializeUpload(title, authToken) {
    const response = await fetch('https://your-api.com/api/videos/create', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ title })
    });

    if (!response.ok) {
      throw new Error('Failed to initialize upload');
    }

    return response.json();
    // Returns: { videoId, internalId, uploadCredentials: { uploadUrl, libraryId, videoId, expireTime, signature } }
  }

  /**
   * Upload video blob to Bunny using TUS protocol
   */
  async uploadVideo(videoBlob, uploadCredentials, onProgress) {
    return new Promise((resolve, reject) => {
      const { uploadUrl, libraryId, videoId, expireTime, signature } = uploadCredentials;

      this.currentUpload = new tus.Upload(videoBlob, {
        endpoint: uploadUrl,
        retryDelays: [0, 1000, 3000, 5000, 10000],
        chunkSize: 5 * 1024 * 1024, // 5MB chunks
        metadata: {
          filetype: videoBlob.type,
          title: videoId
        },
        headers: {
          'AuthorizationSignature': signature,
          'AuthorizationExpire': expireTime.toString(),
          'VideoId': videoId,
          'LibraryId': libraryId.toString()
        },
        onError: (error) => {
          console.error('Upload failed:', error);
          reject(error);
        },
        onProgress: (bytesUploaded, bytesTotal) => {
          const percentage = Math.round((bytesUploaded / bytesTotal) * 100);
          if (onProgress) {
            onProgress(percentage, bytesUploaded, bytesTotal);
          }
        },
        onSuccess: () => {
          console.log('Upload complete!');
          resolve({ success: true, videoId });
        }
      });

      // Check for previous uploads to resume
      this.currentUpload.findPreviousUploads().then((previousUploads) => {
        if (previousUploads.length > 0) {
          this.currentUpload.resumeFromPreviousUpload(previousUploads[0]);
        }
        this.currentUpload.start();
      });
    });
  }

  /**
   * Pause current upload
   */
  pauseUpload() {
    if (this.currentUpload) {
      this.currentUpload.abort();
    }
  }

  /**
   * Resume paused upload
   */
  resumeUpload() {
    if (this.currentUpload) {
      this.currentUpload.start();
    }
  }

  /**
   * Full upload flow
   */
  async uploadRecording(videoBlob, title, authToken, onProgress) {
    try {
      // Step 1: Get upload credentials from your server
      const { internalId, uploadCredentials } = await this.initializeUpload(title, authToken);

      // Step 2: Upload to Bunny
      await this.uploadVideo(videoBlob, uploadCredentials, onProgress);

      // Step 3: Notify your server that upload is complete
      await fetch('https://your-api.com/api/videos/upload-complete', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ videoId: internalId })
      });

      return { success: true, videoId: internalId };
    } catch (error) {
      console.error('Upload flow failed:', error);
      throw error;
    }
  }
}

// Export for use in extension
window.BunnyUploadManager = BunnyUploadManager;
```

### Integration with Recording

```javascript
// In your recording completion handler (e.g., offscreen.js)

async function onRecordingComplete(videoBlob) {
  const uploadManager = new BunnyUploadManager();
  const authToken = await getAuthToken(); // Your auth implementation

  try {
    // Show upload progress UI
    showUploadProgress(0);

    const result = await uploadManager.uploadRecording(
      videoBlob,
      `Recording ${new Date().toISOString()}`,
      authToken,
      (percentage) => {
        showUploadProgress(percentage);
      }
    );

    // Show success and share link
    const shareUrl = `https://your-app.com/v/${result.videoId}`;
    showSuccess(shareUrl);

    // Copy to clipboard
    navigator.clipboard.writeText(shareUrl);

  } catch (error) {
    showError('Upload failed. Please try again.');
  }
}
```

---

## 5. Secure Video Access

### Video Access Levels

```javascript
// models/Video.js or similar

const VIDEO_ACCESS = {
  PRIVATE: 'private',      // Only owner can view
  UNLISTED: 'unlisted',    // Anyone with link can view
  PUBLIC: 'public',        // Listed publicly
  PASSWORD: 'password'     // Requires password
};
```

### Access Control Middleware

```javascript
// middleware/videoAccess.js

const bunnyService = require('../services/bunnyService');
const Video = require('../models/Video');

async function checkVideoAccess(req, res, next) {
  const { videoId } = req.params;
  const userId = req.user?.id; // From auth middleware, may be null

  try {
    const video = await Video.findById(videoId);

    if (!video) {
      return res.status(404).json({ error: 'Video not found' });
    }

    // Check access based on video settings
    switch (video.accessLevel) {
      case 'private':
        if (!userId || video.userId !== userId) {
          return res.status(403).json({ error: 'Access denied' });
        }
        break;

      case 'password':
        const providedPassword = req.headers['x-video-password'] || req.query.password;
        if (video.password && video.password !== providedPassword) {
          return res.status(403).json({ error: 'Password required', requiresPassword: true });
        }
        break;

      case 'unlisted':
      case 'public':
        // Anyone with the link can access
        break;
    }

    // Attach video to request
    req.video = video;
    next();

  } catch (error) {
    res.status(500).json({ error: 'Server error' });
  }
}

module.exports = checkVideoAccess;
```

### Secure Playback Endpoint

```javascript
// routes/videos.js

const express = require('express');
const router = express.Router();
const bunnyService = require('../services/bunnyService');
const checkVideoAccess = require('../middleware/videoAccess');
const authMiddleware = require('../middleware/auth');

/**
 * Get video playback URLs
 * Returns signed URLs that expire
 */
router.get('/:videoId/playback',
  authMiddleware.optional, // Allow both authenticated and anonymous
  checkVideoAccess,
  async (req, res) => {
    const { video } = req;

    try {
      // Generate signed URLs (expire in 1 hour)
      const playbackUrls = bunnyService.generateSignedPlaybackUrl(
        video.bunnyVideoId,
        3600
      );

      const thumbnailUrl = bunnyService.generateSignedThumbnailUrl(
        video.bunnyVideoId,
        86400 // 24 hours for thumbnail
      );

      // Increment view count
      await video.incrementViews();

      res.json({
        video: {
          id: video.id,
          title: video.title,
          duration: video.duration,
          createdAt: video.createdAt,
          owner: {
            name: video.user.name,
            avatar: video.user.avatar
          }
        },
        playback: {
          ...playbackUrls,
          thumbnailUrl
        }
      });

    } catch (error) {
      console.error('Playback error:', error);
      res.status(500).json({ error: 'Failed to generate playback URL' });
    }
  }
);

/**
 * Update video settings (owner only)
 */
router.patch('/:videoId',
  authMiddleware.required,
  async (req, res) => {
    const { videoId } = req.params;
    const userId = req.user.id;
    const { title, accessLevel, password } = req.body;

    try {
      const video = await Video.findOne({
        where: { id: videoId, userId }
      });

      if (!video) {
        return res.status(404).json({ error: 'Video not found' });
      }

      // Update fields
      if (title) video.title = title;
      if (accessLevel) video.accessLevel = accessLevel;
      if (accessLevel === 'password' && password) {
        video.password = password; // Hash in production
      }

      await video.save();

      res.json({ success: true, video });

    } catch (error) {
      res.status(500).json({ error: 'Update failed' });
    }
  }
);

module.exports = router;
```

---

## 6. Database Schema

### PostgreSQL Schema

```sql
-- Users table (if not exists)
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email VARCHAR(255) UNIQUE NOT NULL,
  name VARCHAR(255),
  avatar_url TEXT,
  plan VARCHAR(50) DEFAULT 'free', -- 'free', 'pro', 'team'
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Videos table
CREATE TABLE videos (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,

  -- Bunny Stream reference
  bunny_video_id VARCHAR(255) NOT NULL,
  bunny_library_id VARCHAR(50) NOT NULL,

  -- Video metadata
  title VARCHAR(255) NOT NULL,
  description TEXT,
  duration_seconds INTEGER,
  file_size_bytes BIGINT,
  resolution VARCHAR(20), -- '1080p', '720p', etc.

  -- Status
  status VARCHAR(50) DEFAULT 'uploading', -- 'uploading', 'processing', 'ready', 'error'

  -- Access control
  access_level VARCHAR(20) DEFAULT 'unlisted', -- 'private', 'unlisted', 'public', 'password'
  password VARCHAR(255), -- Hashed password for protected videos

  -- Analytics
  views_count INTEGER DEFAULT 0,
  unique_views_count INTEGER DEFAULT 0,

  -- Timestamps
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW(),
  deleted_at TIMESTAMP -- Soft delete
);

-- Indexes
CREATE INDEX idx_videos_user_id ON videos(user_id);
CREATE INDEX idx_videos_bunny_video_id ON videos(bunny_video_id);
CREATE INDEX idx_videos_status ON videos(status);
CREATE INDEX idx_videos_created_at ON videos(created_at DESC);

-- Video views tracking (for analytics)
CREATE TABLE video_views (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  video_id UUID REFERENCES videos(id) ON DELETE CASCADE,
  viewer_id UUID REFERENCES users(id) ON DELETE SET NULL, -- NULL for anonymous
  ip_hash VARCHAR(64), -- Hashed IP for unique view counting
  user_agent TEXT,
  referrer TEXT,
  watch_duration_seconds INTEGER,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_video_views_video_id ON video_views(video_id);
CREATE INDEX idx_video_views_created_at ON video_views(created_at);
```

### Sequelize Model Example

```javascript
// models/Video.js
const { Model, DataTypes } = require('sequelize');

class Video extends Model {
  static init(sequelize) {
    return super.init({
      id: {
        type: DataTypes.UUID,
        defaultValue: DataTypes.UUIDV4,
        primaryKey: true
      },
      userId: {
        type: DataTypes.UUID,
        allowNull: false,
        field: 'user_id'
      },
      bunnyVideoId: {
        type: DataTypes.STRING,
        allowNull: false,
        field: 'bunny_video_id'
      },
      bunnyLibraryId: {
        type: DataTypes.STRING,
        allowNull: false,
        field: 'bunny_library_id'
      },
      title: {
        type: DataTypes.STRING,
        allowNull: false
      },
      description: DataTypes.TEXT,
      durationSeconds: {
        type: DataTypes.INTEGER,
        field: 'duration_seconds'
      },
      fileSizeBytes: {
        type: DataTypes.BIGINT,
        field: 'file_size_bytes'
      },
      resolution: DataTypes.STRING,
      status: {
        type: DataTypes.ENUM('uploading', 'processing', 'ready', 'error'),
        defaultValue: 'uploading'
      },
      accessLevel: {
        type: DataTypes.ENUM('private', 'unlisted', 'public', 'password'),
        defaultValue: 'unlisted',
        field: 'access_level'
      },
      password: DataTypes.STRING,
      viewsCount: {
        type: DataTypes.INTEGER,
        defaultValue: 0,
        field: 'views_count'
      }
    }, {
      sequelize,
      tableName: 'videos',
      underscored: true,
      paranoid: true // Soft deletes
    });
  }

  async incrementViews() {
    this.viewsCount += 1;
    await this.save();
  }
}

module.exports = Video;
```

---

## 7. API Endpoints

### Complete API Reference

```
POST   /api/videos/create          - Initialize video upload
POST   /api/videos/upload-complete - Mark upload as complete
GET    /api/videos                 - List user's videos
GET    /api/videos/:id             - Get video details
GET    /api/videos/:id/playback    - Get signed playback URLs
PATCH  /api/videos/:id             - Update video settings
DELETE /api/videos/:id             - Delete video
GET    /api/videos/:id/analytics   - Get video analytics
```

### Full Implementation

```javascript
// routes/videos.js

const express = require('express');
const router = express.Router();
const bunnyService = require('../services/bunnyService');
const Video = require('../models/Video');
const auth = require('../middleware/auth');

/**
 * POST /api/videos/create
 * Initialize a new video upload
 */
router.post('/create', auth.required, async (req, res) => {
  const { title } = req.body;
  const userId = req.user.id;

  try {
    // Create video in Bunny
    const bunnyVideo = await bunnyService.createVideo(title, userId);

    // Create video record in our database
    const video = await Video.create({
      userId,
      bunnyVideoId: bunnyVideo.guid,
      bunnyLibraryId: process.env.BUNNY_LIBRARY_ID,
      title: title || 'Untitled Recording',
      status: 'uploading'
    });

    // Generate upload credentials for extension
    const uploadCredentials = bunnyService.generateUploadCredentials(
      bunnyVideo.guid,
      7200 // 2 hours to complete upload
    );

    res.json({
      videoId: video.id,
      bunnyVideoId: bunnyVideo.guid,
      uploadCredentials
    });

  } catch (error) {
    console.error('Create video error:', error);
    res.status(500).json({ error: 'Failed to create video' });
  }
});

/**
 * POST /api/videos/upload-complete
 * Called by extension when upload finishes
 */
router.post('/upload-complete', auth.required, async (req, res) => {
  const { videoId } = req.body;
  const userId = req.user.id;

  try {
    const video = await Video.findOne({
      where: { id: videoId, userId }
    });

    if (!video) {
      return res.status(404).json({ error: 'Video not found' });
    }

    // Update status to processing
    video.status = 'processing';
    await video.save();

    // Start polling for transcoding completion (or use webhook)
    pollVideoStatus(video.id, video.bunnyVideoId);

    res.json({
      success: true,
      shareUrl: `${process.env.APP_URL}/v/${video.id}`
    });

  } catch (error) {
    res.status(500).json({ error: 'Failed to complete upload' });
  }
});

/**
 * GET /api/videos
 * List user's videos
 */
router.get('/', auth.required, async (req, res) => {
  const userId = req.user.id;
  const { page = 1, limit = 20, status } = req.query;

  try {
    const where = { userId };
    if (status) where.status = status;

    const videos = await Video.findAndCountAll({
      where,
      order: [['createdAt', 'DESC']],
      limit: parseInt(limit),
      offset: (parseInt(page) - 1) * parseInt(limit)
    });

    // Add thumbnail URLs
    const videosWithThumbnails = videos.rows.map(video => ({
      ...video.toJSON(),
      thumbnailUrl: bunnyService.generateSignedThumbnailUrl(video.bunnyVideoId)
    }));

    res.json({
      videos: videosWithThumbnails,
      total: videos.count,
      page: parseInt(page),
      totalPages: Math.ceil(videos.count / parseInt(limit))
    });

  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch videos' });
  }
});

/**
 * DELETE /api/videos/:id
 */
router.delete('/:id', auth.required, async (req, res) => {
  const { id } = req.params;
  const userId = req.user.id;

  try {
    const video = await Video.findOne({ where: { id, userId } });

    if (!video) {
      return res.status(404).json({ error: 'Video not found' });
    }

    // Delete from Bunny
    await bunnyService.deleteVideo(video.bunnyVideoId);

    // Soft delete from database
    await video.destroy();

    res.json({ success: true });

  } catch (error) {
    res.status(500).json({ error: 'Failed to delete video' });
  }
});

// Helper: Poll video status until ready
async function pollVideoStatus(videoId, bunnyVideoId) {
  const maxAttempts = 60; // 5 minutes max
  let attempts = 0;

  const poll = async () => {
    attempts++;

    try {
      const status = await bunnyService.getVideoStatus(bunnyVideoId);

      if (status.status === 'ready') {
        await Video.update(
          {
            status: 'ready',
            durationSeconds: status.duration,
            fileSizeBytes: status.size,
            resolution: `${status.height}p`
          },
          { where: { id: videoId } }
        );
        return;
      }

      if (status.status === 'error') {
        await Video.update(
          { status: 'error' },
          { where: { id: videoId } }
        );
        return;
      }

      if (attempts < maxAttempts) {
        setTimeout(poll, 5000); // Poll every 5 seconds
      }

    } catch (error) {
      console.error('Poll error:', error);
    }
  };

  poll();
}

module.exports = router;
```

---

## 8. Security Considerations

### Checklist

- [ ] **Never expose Bunny API key to client/extension**
  - Always generate upload credentials on your server
  - Use signed URLs for playback

- [ ] **Use HTTPS everywhere**
  - Your API must use HTTPS
  - Bunny CDN uses HTTPS by default

- [ ] **Validate user ownership**
  - Always check `userId` matches before any operation
  - Don't trust client-provided user IDs

- [ ] **Implement rate limiting**
  ```javascript
  const rateLimit = require('express-rate-limit');

  const uploadLimiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 10 // 10 uploads per 15 minutes
  });

  router.post('/create', uploadLimiter, auth.required, createVideo);
  ```

- [ ] **Set URL expiration appropriately**
  - Upload credentials: 1-2 hours
  - Playback URLs: 1-4 hours
  - Thumbnails: 24 hours

- [ ] **Monitor for abuse**
  - Track upload sizes per user
  - Set maximum video duration limits
  - Implement storage quotas per plan

### Storage Quotas Example

```javascript
// middleware/checkQuota.js

const PLAN_LIMITS = {
  free: {
    maxVideos: 25,
    maxDurationSeconds: 300, // 5 minutes per video
    totalStorageBytes: 1 * 1024 * 1024 * 1024 // 1GB
  },
  pro: {
    maxVideos: 1000,
    maxDurationSeconds: 3600, // 1 hour per video
    totalStorageBytes: 100 * 1024 * 1024 * 1024 // 100GB
  }
};

async function checkUploadQuota(req, res, next) {
  const user = req.user;
  const limits = PLAN_LIMITS[user.plan] || PLAN_LIMITS.free;

  const videoCount = await Video.count({ where: { userId: user.id } });

  if (videoCount >= limits.maxVideos) {
    return res.status(403).json({
      error: 'Video limit reached',
      limit: limits.maxVideos,
      upgrade: true
    });
  }

  req.quotaLimits = limits;
  next();
}
```

---

## Quick Start Checklist

1. [ ] Create Bunny Stream account and Video Library
2. [ ] Enable Token Authentication in Bunny settings
3. [ ] Set environment variables
4. [ ] Implement backend BunnyService
5. [ ] Create database tables
6. [ ] Add API routes
7. [ ] Update extension with TUS upload
8. [ ] Test end-to-end flow
9. [ ] Add error handling and retry logic
10. [ ] Implement analytics tracking

---

## Webhook Alternative (Optional)

Instead of polling, Bunny can send webhooks when encoding completes:

```javascript
// routes/webhooks.js

router.post('/bunny', express.raw({ type: '*/*' }), async (req, res) => {
  const event = JSON.parse(req.body);

  if (event.VideoGuid && event.Status === 4) { // 4 = finished
    await Video.update(
      { status: 'ready' },
      { where: { bunnyVideoId: event.VideoGuid } }
    );
  }

  res.status(200).send('OK');
});
```

Configure webhook URL in Bunny dashboard: `https://your-api.com/webhooks/bunny`
