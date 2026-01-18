# Bunny Stream Implementation Guide

## Overview

This document describes the Bunny Stream integration implemented in ScreenBuddy. It covers secure video uploading via TUS protocol and signed URL playback.

---

## Table of Contents

1. [Setup](#1-setup)
2. [Architecture](#2-architecture)
3. [API Endpoints](#3-api-endpoints)
4. [Extension Integration](#4-extension-integration)
5. [Webhook Setup](#5-webhook-setup)
6. [Security](#6-security)

---

## 1. Setup

### Environment Variables

Add these to your `.env` file:

```env
# Bunny Stream Configuration
BUNNY_STREAM_LIBRARY_ID=123456
BUNNY_STREAM_API_KEY=your-api-key-here
BUNNY_STREAM_CDN_HOSTNAME=vz-xxxxxx-xxx.b-cdn.net
BUNNY_STREAM_SECURITY_KEY=your-security-key-for-signed-urls
BUNNY_STREAM_PLAYBACK_EXPIRY=3600
BUNNY_STREAM_UPLOAD_EXPIRY=7200
```

### Run Migration

```bash
php artisan migrate
```

This adds the following fields to the `videos` table:
- `bunny_video_id` - Bunny's video GUID
- `bunny_library_id` - Library ID
- `bunny_status` - Status (pending, uploading, processing, transcoding, ready, error)
- `bunny_error` - Error message if any
- `bunny_resolution` - Video resolution (e.g., "1080p")
- `bunny_file_size` - File size in bytes
- `storage_type` - Either "local" or "bunny"

---

## 2. Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      UPLOAD FLOW                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Extension              Backend                    Bunny         │
│      │                     │                         │           │
│      │  1. POST /create    │                         │           │
│      │ ───────────────────▶│                         │           │
│      │                     │  Create video entry     │           │
│      │                     │ ───────────────────────▶│           │
│      │                     │                         │           │
│      │  2. Upload creds    │                         │           │
│      │ ◀───────────────────│                         │           │
│      │                     │                         │           │
│      │  3. TUS Upload (direct) ─────────────────────▶│           │
│      │     500MB video                               │           │
│      │                                               │           │
│      │  4. POST /complete  │                         │           │
│      │ ───────────────────▶│                         │           │
│      │                     │                         │           │
│      │  5. Share URL       │                         │           │
│      │ ◀───────────────────│                         │           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      PLAYBACK FLOW                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Viewer               Backend                    Bunny CDN       │
│     │                    │                          │            │
│     │  GET /playback     │                          │            │
│     │ ──────────────────▶│                          │            │
│     │                    │  Check access            │            │
│     │                    │  Generate signed URL     │            │
│     │  Signed HLS URL    │                          │            │
│     │ ◀──────────────────│                          │            │
│     │                                               │            │
│     │  Stream video (direct) ──────────────────────▶│            │
│     │                                               │            │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. API Endpoints

### Create Video & Get Upload Credentials

**POST** `/api/bunny/videos/create`

Creates a video entry and returns TUS upload credentials.

**Request:**
```json
{
  "title": "My Recording",
  "description": "Optional description"
}
```

**Response:**
```json
{
  "success": true,
  "video": {
    "id": 123,
    "title": "My Recording",
    "share_token": "abc123..."
  },
  "bunny_video_id": "guid-from-bunny",
  "upload_credentials": {
    "uploadUrl": "https://video.bunnycdn.com/tusupload",
    "libraryId": "123456",
    "videoId": "guid-from-bunny",
    "expireTime": 1705590000,
    "signature": "sha256-signature"
  }
}
```

### Mark Upload Complete

**POST** `/api/bunny/videos/{id}/complete`

Called after TUS upload finishes.

**Response:**
```json
{
  "success": true,
  "video": {
    "id": 123,
    "title": "My Recording",
    "status": "processing",
    "share_url": "https://your-app.com/share/video/abc123",
    "share_token": "abc123..."
  },
  "message": "Upload complete. Video is being processed."
}
```

### Get Video Status

**GET** `/api/bunny/videos/{id}/status`

Check processing status.

**Response:**
```json
{
  "status": "ready",
  "progress": 100,
  "duration": 120,
  "resolution": "1080p",
  "is_ready": true
}
```

### Get Playback URLs (Authenticated)

**GET** `/api/bunny/videos/{id}/playback`

Returns signed playback URLs for the video owner.

**Response:**
```json
{
  "video": {
    "id": 123,
    "title": "My Recording",
    "duration": 120,
    "resolution": "1080p"
  },
  "playback": {
    "hlsUrl": "https://cdn.example.com/video-id/playlist.m3u8?token=xxx&expires=xxx",
    "embedUrl": "https://iframe.mediadelivery.net/embed/lib-id/video-id?token=xxx",
    "thumbnailUrl": "https://cdn.example.com/video-id/thumbnail.jpg?token=xxx",
    "expiresAt": "2024-01-18T12:00:00Z"
  }
}
```

### Get Shared Video Playback (Public)

**GET** `/api/bunny/share/{token}/playback`

Returns signed playback URLs for shared videos.

### Delete Video

**DELETE** `/api/bunny/videos/{id}`

Deletes video from both database and Bunny.

---

## 4. Extension Integration

### TUS Upload Client

Use tus-js-client for resumable uploads:

```javascript
import * as tus from 'tus-js-client';

async function uploadToBunny(videoBlob, authToken) {
  // Step 1: Get upload credentials from your backend
  const response = await fetch('https://your-api.com/api/bunny/videos/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ title: 'My Recording' })
  });

  const { video, upload_credentials } = await response.json();

  // Step 2: Upload directly to Bunny using TUS
  return new Promise((resolve, reject) => {
    const upload = new tus.Upload(videoBlob, {
      endpoint: upload_credentials.uploadUrl,
      retryDelays: [0, 1000, 3000, 5000],
      chunkSize: 5 * 1024 * 1024, // 5MB chunks
      headers: {
        'AuthorizationSignature': upload_credentials.signature,
        'AuthorizationExpire': upload_credentials.expireTime.toString(),
        'VideoId': upload_credentials.videoId,
        'LibraryId': upload_credentials.libraryId
      },
      metadata: {
        filetype: videoBlob.type,
        title: 'recording'
      },
      onError: (error) => reject(error),
      onProgress: (bytesUploaded, bytesTotal) => {
        const percentage = Math.round((bytesUploaded / bytesTotal) * 100);
        console.log(`Upload progress: ${percentage}%`);
      },
      onSuccess: async () => {
        // Step 3: Notify backend that upload is complete
        await fetch(`https://your-api.com/api/bunny/videos/${video.id}/complete`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${authToken}` }
        });
        resolve(video);
      }
    });

    upload.start();
  });
}
```

---

## 5. Webhook Setup

### Configure in Bunny Dashboard

1. Go to your Video Library settings
2. Navigate to **Webhooks**
3. Add webhook URL: `https://your-api.com/api/webhooks/bunny`
4. Select events: VideoProcessingFinished, VideoProcessingFailed

### Webhook Handler

The webhook handler at `/api/webhooks/bunny` automatically:
- Updates video status when encoding completes
- Captures video duration and resolution
- Logs any processing errors

---

## 6. Security

### Upload Security

- **Signed Credentials**: Upload credentials include a SHA256 signature
- **Time-Limited**: Credentials expire after 2 hours (configurable)
- **Formula**: `sha256(library_id + api_key + expiration_time + video_id)`

### Playback Security

- **Signed URLs**: All playback URLs are signed and time-limited
- **Token Authentication**: Enabled in Bunny dashboard
- **Expiry**: URLs expire after 1 hour (configurable)
- **Formula**: `sha256(security_key + path + expiration)`

### Access Control

- Private videos: Only owner can access
- Public videos: Anyone with share link
- Password-protected: Requires password (future feature)

---

## Files Created

| File | Purpose |
|------|---------|
| `app/Services/BunnyStreamService.php` | Core Bunny API integration |
| `app/Http/Controllers/BunnyVideoController.php` | Video upload/playback endpoints |
| `app/Http/Controllers/BunnyWebhookController.php` | Webhook handler |
| `database/migrations/..._add_bunny_stream_fields_to_videos_table.php` | Database migration |
| `config/services.php` | Bunny configuration (added) |

---

## Testing

### Manual Test Flow

1. Create video:
```bash
curl -X POST https://your-api.com/api/bunny/videos/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "Test Video"}'
```

2. Upload via TUS (use tus-js-client or similar)

3. Mark complete:
```bash
curl -X POST https://your-api.com/api/bunny/videos/123/complete \
  -H "Authorization: Bearer YOUR_TOKEN"
```

4. Check status:
```bash
curl https://your-api.com/api/bunny/videos/123/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

5. Get playback URL:
```bash
curl https://your-api.com/api/bunny/videos/123/playback \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Troubleshooting

### Video stuck in "processing"

1. Check Bunny dashboard for the video status
2. Verify webhook is configured correctly
3. Check logs for webhook errors: `storage/logs/laravel.log`

### Upload fails

1. Verify credentials haven't expired
2. Check signature generation
3. Ensure video format is supported (MP4, WebM, MOV)

### Playback URL not working

1. Verify security key is correct
2. Check URL hasn't expired
3. Ensure Token Authentication is enabled in Bunny
