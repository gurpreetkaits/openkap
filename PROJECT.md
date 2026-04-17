# OpenKap Backend

Laravel 12 API backend for the OpenKap screen recording platform.

## Stack

- **Framework**: Laravel 12
- **Auth**: Laravel Sanctum (token-based API auth) + Google OAuth via Socialite
- **Billing**: Polar (via `danestves/laravel-polar`)
- **Video Storage**: Bunny Stream
- **Database**: MySQL/MariaDB

## Key Concepts

### Plans & Limits

Users are either **free** or **paid** (Pro / Teams). Plan type is determined by subscription status, not a static column.

- `User::getPlanType()` returns `'free'`, `'pro'`, or `'teams'`
- `User::hasActiveSubscription()` checks for active paid subscription
- Limits are admin-configurable via `Setting::getFreeVideoLimit()` and `Setting::getFreeRecordingDurationLimit()`

**Free plan defaults:**
- 5 videos max
- 5 minutes max recording duration

**Pro plan:**
- Unlimited videos
- 30 minutes max recording duration

### Extension API Contract

The Chrome extension relies on `GET /api/auth/me` to get user data including plan limits. The response includes:

```json
{
  "user": {
    "id": 1,
    "name": "...",
    "email": "...",
    "plan_type": "free|pro|teams",
    "can_record": true,
    "videos_count": 3,
    "max_videos": 5,
    "max_recording_seconds": 300,
    "is_admin": false
  }
}
```

The extension uses these fields to:
- Gate recording start (`can_record`)
- Auto-stop recordings at the time limit (`max_recording_seconds`)
- Show upgrade prompts when video limit reached (`videos_count` vs `max_videos`)

### API Routes

- Auth: `/api/auth/*` (Google OAuth, token management)
- Videos: `/api/videos/*` (CRUD, sharing)
- Streaming: `/api/stream/*` (chunked upload from extension)
- Screenshots: `/api/screenshots/*`
- Workspaces: `/api/workspaces/*`
- Admin: `/api/admin/*`

### Key Models

- `User` - Core user with subscription fields and plan logic
- `Video` - Recorded videos (belongs to User)
- `Workspace` - Team workspaces with their own subscriptions
- `Setting` - Admin-configurable settings (free plan limits, etc.)
