# PRD: Workspaces & Team Plans

**Version:** 1.0
**Author:** ScreenSense Team
**Date:** January 2026
**Status:** In Development

---

## 1. Executive Summary

### Problem
ScreenSense currently only supports individual users. Small teams (startups, agencies, small businesses) want to:
- Share recordings with teammates
- Collaborate on video content
- Manage team access centrally
- Pay with a single team subscription

### Solution
Implement **Workspaces** - shared spaces where teams can collaborate on recordings with a single team subscription.

### Success Metrics
- 20% of Pro users upgrade to Team within 3 months
- Team plan ARPU > Individual Pro ARPU
- Positive unit economics from Day 1

---

## 2. Pricing Strategy

### Plans Overview

| Plan | Price | Users | Storage | Recording Limit | Videos | Encoding |
|------|-------|-------|---------|-----------------|--------|----------|
| **Free** | $0/mo | 1 | 1 GB | 5 min/video | 10 total | **No** (raw upload) |
| **Pro** | $8/mo | 1 | 50 GB | 30 min/video | Unlimited | Yes (HLS) |
| **Team** | $39/mo | 5 | 100 GB | 60 min/video | Unlimited | Yes (HLS) |
| **Team Plus** | $69/mo | 15 | 500 GB | 2 hr/video | Unlimited | Yes (HLS) |

### Yearly Pricing (17% discount)
- Pro: $80/year ($6.67/mo)
- Team: $390/year ($32.50/mo)
- Team Plus: $690/year ($57.50/mo)

### No-Encoding Strategy for Free Tier

**Why:** Encoding is the biggest cost on Bunny ($0.005/minute). Free users get:
- Direct video upload (WebM/MP4)
- No HLS adaptive streaming
- No multi-resolution variants
- Basic playback only

**Benefits:**
- Saves ~$0.25 per free user (10 videos × 5 min)
- Free tier becomes truly free to operate
- Incentive to upgrade for better quality

### Cost Analysis (Bunny Stream)

```
Bunny Pricing:
- Storage: $0.005/GB/month
- Encoding: $0.005/minute (one-time) - PAID PLANS ONLY
- Bandwidth: $0.01/GB

Free User (10 videos, no encoding):
- 10 × 50MB = 500MB storage = $0.0025/month
- No encoding cost = $0
- 10 × 5 views × 50MB = 2.5GB bandwidth = $0.025
- Total: ~$0.03/month ✓ Sustainable

Pro User (heavy usage):
- 50 videos × 50MB = 2.5GB storage = $0.0125
- 50 × 5 min encoding = $1.25 (one-time)
- Bandwidth varies
- Monthly: ~$0.50-1.00
- Revenue: $8, Profit: ~$7

Team Plan (5 users, heavy usage):
- 750 videos × 50MB = 37.5GB storage = $0.19
- 750 × 5 min encoding = $18.75 (first month)
- 375GB bandwidth = $3.75
- Monthly after first: ~$4-5
- Revenue: $39, Profit: ~$34
```

---

## 3. Feature Requirements

### 3.1 Workspace Management

#### Create Workspace
- **Who:** Any Pro/Team subscriber
- **Fields:**
  - Name (required, 3-50 chars)
  - Slug (auto-generated, editable, unique)
  - Logo (optional, max 2MB)
  - Description (optional, max 500 chars)
- **Limits:**
  - Free users: Cannot create workspaces
  - Pro users: Can create 1 personal workspace (for future upgrade path)
  - Team users: 1 workspace included

#### Workspace Settings
- Rename workspace
- Change logo
- Change slug (with redirect from old slug)
- Delete workspace (owner only, requires confirmation)

### 3.2 Team Members

#### Roles

| Role | Permissions |
|------|-------------|
| **Owner** | Full access, billing, delete workspace, transfer ownership |
| **Admin** | Manage members, manage all videos, settings (no billing) |
| **Member** | Upload videos, manage own videos, view all team videos |

#### Invite Flow
1. Owner/Admin enters email address(es)
2. System sends invitation email with unique link
3. Link valid for 7 days
4. Recipient clicks link:
   - If has account: Added to workspace
   - If no account: Signup flow → Added to workspace
5. Owner sees pending/accepted invitations

#### Member Management
- View all members with roles
- Change member roles (Owner/Admin only)
- Remove members (Owner/Admin only)
- Leave workspace (any member except owner)
- Transfer ownership (Owner only)

### 3.3 Video Management

#### Video Ownership
- Videos belong to **User** (creator) AND optionally **Workspace**
- User can upload to:
  - Personal library (only they see it)
  - Workspace library (team sees it)

#### Team Video Library
- All workspace members see team videos
- Filter by: uploader, date, tags
- Search across all team videos
- Bulk actions (Admin/Owner only): delete, move to folder

#### Video Permissions in Workspace
| Action | Owner | Admin | Member |
|--------|-------|-------|--------|
| View team videos | ✓ | ✓ | ✓ |
| Upload to workspace | ✓ | ✓ | ✓ |
| Edit own videos | ✓ | ✓ | ✓ |
| Edit any video | ✓ | ✓ | ✗ |
| Delete own videos | ✓ | ✓ | ✓ |
| Delete any video | ✓ | ✓ | ✗ |
| Share externally | ✓ | ✓ | ✓ |

### 3.4 Recording & Limits

#### Who Can Record?
```
Decision Tree:
1. Is user in a workspace context?
   → Check workspace subscription
2. Is user in personal context?
   → Check user's personal subscription
3. Has limit been reached?
   → Show upgrade modal
```

#### Limit Enforcement

| Check | Free | Pro | Team Member | Team Plus Member |
|-------|------|-----|-------------|------------------|
| Video count | 10 total | Unlimited | Unlimited | Unlimited |
| Recording duration | 5 min | 30 min | 60 min | 120 min |
| Storage | 1 GB | 50 GB | Shared 100 GB | Shared 500 GB |
| HLS Encoding | **No** | Yes | Yes | Yes |
| Can record? | Own sub | Own sub | Workspace sub | Workspace sub |

#### Encoding Logic
```
When video is uploaded:
1. Check user's plan (personal) or workspace plan (team context)
2. If Free plan:
   - Store raw video only (WebM/MP4)
   - Skip Bunny encoding
   - Direct playback URL
3. If Paid plan (Pro/Team/Team Plus):
   - Upload to Bunny Stream
   - Trigger HLS encoding
   - Multiple quality variants (360p, 720p, 1080p, 4K)
```

#### Storage Tracking
- Track storage per user (personal videos)
- Track storage per workspace (team videos)
- Show usage in dashboard: "Using 45 GB of 100 GB"
- Warn at 80%, block uploads at 100%

### 3.5 Billing & Subscription

#### Team Subscription Model
- Subscription attached to **Workspace**, not user
- Owner pays via Polar
- All members get Team benefits within workspace
- Members still have personal Free/Pro plan for personal videos

#### Upgrade Paths
```
Free User → Pro ($8/mo)
Free User → Create Team ($39/mo) [becomes owner]
Pro User → Create Team ($39/mo) [keeps Pro for personal]
Team → Team Plus ($69/mo)
```

#### Seat Management
- Team: 5 seats included
- Team Plus: 15 seats included
- If at limit: "Upgrade to add more members"
- No per-seat billing (simplicity)

---

## 4. Technical Design

### 4.1 Database Schema

#### New Tables

```sql
-- Workspaces
CREATE TABLE workspaces (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    logo_url VARCHAR(500) NULL,
    owner_id BIGINT UNSIGNED NOT NULL,

    -- Subscription (via Polar)
    polar_customer_id VARCHAR(255) NULL,
    polar_subscription_id VARCHAR(255) NULL,
    subscription_status ENUM('free', 'active', 'canceled', 'past_due') DEFAULT 'free',
    subscription_plan ENUM('team', 'team_plus') NULL,
    subscription_started_at TIMESTAMP NULL,
    subscription_expires_at TIMESTAMP NULL,
    subscription_canceled_at TIMESTAMP NULL,

    -- Limits
    max_members INT UNSIGNED DEFAULT 5,
    max_storage_gb INT UNSIGNED DEFAULT 100,
    max_recording_minutes INT UNSIGNED DEFAULT 60,

    -- Usage tracking
    storage_used_bytes BIGINT UNSIGNED DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_owner (owner_id)
);

-- Workspace Members (pivot)
CREATE TABLE workspace_members (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    workspace_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('owner', 'admin', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    invited_by BIGINT UNSIGNED NULL,

    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_membership (workspace_id, user_id),
    INDEX idx_user_workspaces (user_id)
);

-- Workspace Invitations
CREATE TABLE workspace_invitations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    workspace_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') DEFAULT 'member',
    token VARCHAR(64) NOT NULL UNIQUE,
    invited_by BIGINT UNSIGNED NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_email (email)
);
```

#### Modified Tables

```sql
-- Add to videos table
ALTER TABLE videos ADD COLUMN workspace_id BIGINT UNSIGNED NULL;
ALTER TABLE videos ADD FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE SET NULL;
ALTER TABLE videos ADD INDEX idx_workspace (workspace_id);

-- Add storage tracking to videos
ALTER TABLE videos ADD COLUMN file_size_bytes BIGINT UNSIGNED DEFAULT 0;
```

### 4.2 API Endpoints

#### Workspace Management
```
POST   /api/workspaces                 Create workspace
GET    /api/workspaces                 List user's workspaces
GET    /api/workspaces/{slug}          Get workspace details
PATCH  /api/workspaces/{slug}          Update workspace
DELETE /api/workspaces/{slug}          Delete workspace
POST   /api/workspaces/{slug}/logo     Upload workspace logo
```

#### Members
```
GET    /api/workspaces/{slug}/members              List members
POST   /api/workspaces/{slug}/members/invite       Send invitation
DELETE /api/workspaces/{slug}/members/{userId}     Remove member
PATCH  /api/workspaces/{slug}/members/{userId}     Update role
POST   /api/workspaces/{slug}/leave                Leave workspace
POST   /api/workspaces/{slug}/transfer             Transfer ownership
```

#### Invitations
```
GET    /api/workspaces/{slug}/invitations          List pending invitations
DELETE /api/workspaces/{slug}/invitations/{id}     Cancel invitation
POST   /api/invitations/{token}/accept             Accept invitation
GET    /api/invitations/{token}                    Get invitation details
```

#### Workspace Videos
```
GET    /api/workspaces/{slug}/videos               List workspace videos
POST   /api/workspaces/{slug}/videos               Upload to workspace
```

#### Workspace Subscription
```
GET    /api/workspaces/{slug}/subscription         Get subscription status
POST   /api/workspaces/{slug}/subscription/checkout Create checkout
POST   /api/workspaces/{slug}/subscription/cancel  Cancel subscription
GET    /api/workspaces/{slug}/subscription/portal  Get billing portal URL
```

### 4.3 Backend Structure

#### New Files
```
app/
├── Models/
│   ├── Workspace.php
│   ├── WorkspaceMember.php
│   └── WorkspaceInvitation.php
├── Managers/
│   ├── WorkspaceManager.php
│   └── WorkspaceInvitationManager.php
├── Repositories/
│   ├── WorkspaceRepository.php
│   └── WorkspaceInvitationRepository.php
├── Http/
│   ├── Controllers/
│   │   ├── WorkspaceController.php
│   │   ├── WorkspaceMemberController.php
│   │   ├── WorkspaceInvitationController.php
│   │   └── WorkspaceSubscriptionController.php
│   ├── Requests/
│   │   ├── CreateWorkspaceRequest.php
│   │   ├── UpdateWorkspaceRequest.php
│   │   └── InviteMemberRequest.php
│   └── Middleware/
│       ├── WorkspaceAccess.php
│       └── WorkspaceRole.php
├── Data/
│   ├── WorkspaceData.php
│   ├── WorkspaceMemberData.php
│   └── WorkspaceInvitationData.php
├── Policies/
│   └── WorkspacePolicy.php
└── Mail/
    └── WorkspaceInvitation.php

database/
└── migrations/
    ├── 2026_01_24_000001_create_workspaces_table.php
    ├── 2026_01_24_000002_create_workspace_members_table.php
    ├── 2026_01_24_000003_create_workspace_invitations_table.php
    └── 2026_01_24_000004_add_workspace_id_to_videos_table.php
```

### 4.4 Frontend Structure

#### New Pages
```
frontend/src/
├── views/
│   ├── WorkspacesView.vue          # List workspaces
│   ├── WorkspaceDetailView.vue     # Workspace dashboard
│   ├── WorkspaceSettingsView.vue   # Settings & billing
│   ├── WorkspaceMembersView.vue    # Manage members
│   └── InvitationAcceptView.vue    # Accept invitation
├── components/
│   └── Workspace/
│       ├── CreateWorkspaceModal.vue
│       ├── InviteMemberModal.vue
│       ├── WorkspaceSwitcher.vue
│       ├── MemberList.vue
│       └── StorageUsage.vue
└── stores/
    └── workspace.js                # Workspace state management
```

---

## 5. Implementation Phases

### Phase 1: Core Workspace (Week 1)
- [ ] Database migrations
- [ ] Workspace model & repository
- [ ] Create/read/update/delete workspace
- [ ] Basic workspace API endpoints
- [ ] Workspace switcher in frontend

### Phase 2: Team Members (Week 1-2)
- [ ] Member model & relationships
- [ ] Invite flow (send email, accept)
- [ ] Role management
- [ ] Member list UI
- [ ] Remove/leave workspace

### Phase 3: Video Integration (Week 2)
- [ ] Add workspace_id to videos
- [ ] Upload to workspace
- [ ] Workspace video library view
- [ ] Video permissions by role
- [ ] Storage tracking

### Phase 4: Team Billing (Week 2-3)
- [ ] Create Polar products for Team/Team Plus
- [ ] Workspace subscription logic
- [ ] Checkout flow for workspace
- [ ] Recording limits based on workspace plan
- [ ] Upgrade/downgrade flows

### Phase 5: Polish & Launch (Week 3)
- [ ] Email templates for invitations
- [ ] Notification system
- [ ] Usage analytics dashboard
- [ ] Documentation
- [ ] Testing

---

## 6. Edge Cases & Rules

### Workspace Deletion
- Only owner can delete
- All videos remain but become "personal" to their creators
- Members notified via email
- 30-day grace period before permanent deletion

### Owner Leaves
- Cannot leave, must transfer ownership first
- Transfer requires other member to be Admin
- New owner gets billing responsibility

### Subscription Expires
- Workspace becomes read-only
- No new uploads
- Existing videos still viewable
- Members notified to renew

### Member at Capacity
- Show "Team is full" message
- Prompt to upgrade to Team Plus
- Cannot send more invitations

### Storage Limit Reached
- Block new uploads
- Existing videos unaffected
- Show warning in dashboard
- Prompt to upgrade or delete videos

---

## 7. Security Considerations

### Access Control
- All workspace endpoints require authentication
- Role-based access enforced at controller level
- Policy classes for authorization
- Rate limiting on invitation sends (10/hour)

### Invitation Security
- Tokens are 64-char random strings
- Expire after 7 days
- Single use (deleted after acceptance)
- Cannot be used if user already member

### Data Isolation
- Workspace videos only visible to members
- API queries scoped to user's workspaces
- No cross-workspace data leakage

---

## 8. Analytics & Monitoring

### Key Metrics to Track
- Workspace creation rate
- Invitation send/accept rate
- Team plan conversion rate
- Storage usage per workspace
- Videos uploaded per workspace
- Member churn rate

### Alerts
- Workspace approaching storage limit (80%)
- High invitation bounce rate
- Subscription payment failures

---

## 9. Future Considerations (Not in V1)

- [ ] Folders within workspace
- [ ] Custom branding per workspace
- [ ] SSO/SAML for enterprise
- [ ] Audit logs
- [ ] Advanced analytics
- [ ] API access tokens per workspace
- [ ] Guest access (view-only, no account needed)
- [ ] Comment @mentions across workspace

---

## 10. Open Questions

1. **Should Pro users get a personal workspace?**
   - Current: No, workspace = Team feature
   - Alternative: Pro gets 1-person workspace for consistency

2. **What happens to videos when member is removed?**
   - Current: Videos stay in workspace (team owns content)
   - Alternative: Videos transfer to personal library

3. **Can same email be invited to multiple workspaces?**
   - Current: Yes, user can be in multiple workspaces
   - Each workspace billed separately

---

## Appendix A: Email Templates

### Invitation Email
```
Subject: Join {workspace_name} on ScreenSense

Hi,

{inviter_name} has invited you to join {workspace_name} on ScreenSense.

ScreenSense is a screen recording tool that helps teams share knowledge
through video.

[Accept Invitation]

This invitation expires in 7 days.

---
ScreenSense
```

### Welcome to Team Email
```
Subject: Welcome to {workspace_name}!

Hi {user_name},

You're now part of {workspace_name} on ScreenSense!

You can now:
- Record and share videos with your team
- View all team recordings
- Collaborate with {member_count} teammates

[Go to Workspace]

---
ScreenSense
```

---

## Appendix B: Polar Product Setup

### Products to Create in Polar

1. **ScreenSense Team (Monthly)**
   - Price: $39/month
   - Product ID: Store in `POLAR_TEAM_PRODUCT_ID_MONTHLY`

2. **ScreenSense Team (Yearly)**
   - Price: $390/year
   - Product ID: Store in `POLAR_TEAM_PRODUCT_ID_YEARLY`

3. **ScreenSense Team Plus (Monthly)**
   - Price: $69/month
   - Product ID: Store in `POLAR_TEAM_PLUS_PRODUCT_ID_MONTHLY`

4. **ScreenSense Team Plus (Yearly)**
   - Price: $690/year
   - Product ID: Store in `POLAR_TEAM_PLUS_PRODUCT_ID_YEARLY`

### Webhook Events to Handle
- `subscription.created` - Activate workspace subscription
- `subscription.updated` - Update plan/status
- `subscription.canceled` - Mark as canceled (grace period)
- `subscription.revoked` - Immediate access removal
