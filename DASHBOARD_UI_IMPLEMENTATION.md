# Dashboard UI Improvement - Implementation Guide

**Branch:** `fix/improve-dashboard-ui`
**Primary File:** `frontend/src/views/VideosView.vue`

---

## Core Principles

### 1. Preserve All API Functionality
All existing API calls must continue to work exactly as they do now:

| Action | Service Method | Description |
|--------|---------------|-------------|
| Fetch Videos | `videoService.getVideos()` | Load user's video library |
| Delete Video | `videoService.deleteVideo(id)` | Remove video from library |
| Fetch Subscription | `auth.fetchSubscription()` | Get user subscription status |

### 2. Preserve All JavaScript Functionality
Adapt the new design to work with existing JS logic in `VideosView.vue`:

#### Filters
- **Date Filters**: all, today, yesterday, week, month, custom (with date pickers)
- **Sort Options**:
  - Newest/Oldest First
  - Title A-Z / Z-A
  - Longest/Shortest First
  - Most Viewed
  - Most Reactions

#### View Modes
- Grid view (card-based layout)
- List view (horizontal row layout)

#### Pagination
- Items per page: 15
- Previous/Next navigation
- Page number buttons with ellipsis for large sets

### 3. Preserve All Action Functions
These functions must work identically after the redesign:

| Function | Trigger | Description |
|----------|---------|-------------|
| `shareVideo(video)` | Share button click | Copies share URL to clipboard |
| `embedVideo(video)` | Embed button click | Copies iframe embed code to clipboard |
| `downloadVideo(video)` | Download button click | Downloads video as .webm file |
| `deleteVideo(video)` | Delete button click | Opens delete confirmation modal |
| `openVideo(id)` | Card/row click | Navigates to video player |
| `goToRecord()` | Record button click | Opens recording setup panel |
| `openUpgradeModal()` | Upgrade CTA click | Opens subscription upgrade modal |

### 4. Keep Existing Popup/Alert Design
Use the current toast and modal system for all user feedback:

#### Toast Notifications (`toastService`)
Location: `frontend/src/services/toastService.js`

```javascript
// Success messages
toast.success('Share link copied to clipboard!')
toast.success('Embed code copied to clipboard!')
toast.success('Starting download...')
toast.success('Download complete!')
toast.success('Video deleted successfully!')

// Error messages
toast.error('Failed to copy link. Please try again.')
toast.error('Failed to copy embed code. Please try again.')
toast.error('Failed to download video. Please try again.')
toast.error('Failed to delete video. Please try again.')
```

#### Modal Components
| Component | Location | Usage |
|-----------|----------|-------|
| `SBDeleteModal` | `components/Global/SBDeleteModal.vue` | Delete confirmation |
| `SBUpgradeModal` | `components/Global/SBUpgradeModal.vue` | Subscription upgrade |
| `SBConfirmModal` | `components/Global/SBConfirmModal.vue` | General confirmations |
| `SBModal` | `components/Global/SBModal.vue` | Base modal wrapper |

---

## Implementation Checklist

### Before Starting
- [ ] Read and understand current `VideosView.vue` completely
- [ ] Identify all event handlers and their bindings
- [ ] Note all computed properties used in the template

### During Implementation
- [ ] Keep all `@click` handlers and their function mappings
- [ ] Preserve all `v-model` bindings (showDeleteModal, viewMode, etc.)
- [ ] Maintain all `v-if`/`v-else` conditional rendering logic
- [ ] Keep all `v-for` loops with their `:key` attributes
- [ ] Preserve component imports and registrations

### Functionality Testing Checklist
After implementing the new design, verify each action works:

#### Video Actions
- [ ] Click video card/row opens video player
- [ ] Share button copies link + shows success toast
- [ ] Embed button copies iframe code + shows success toast
- [ ] Download button downloads video + shows toasts
- [ ] Delete button opens delete modal
- [ ] Confirm delete removes video + shows success toast
- [ ] Cancel delete closes modal without action

#### Filters & Sorting
- [ ] "All" filter shows all videos
- [ ] "Today" filter shows only today's videos
- [ ] "Yesterday" filter works correctly
- [ ] "This Week" filter works correctly
- [ ] "This Month" filter works correctly
- [ ] "Custom" filter shows date pickers and filters correctly
- [ ] All sort options work (newest, oldest, title, duration, views, reactions)

#### View Modes
- [ ] Grid view displays properly
- [ ] List view displays properly
- [ ] Toggle between views works
- [ ] Active view is visually indicated

#### Pagination
- [ ] Pagination appears when > 15 videos
- [ ] Previous/Next buttons work
- [ ] Page numbers navigate correctly
- [ ] Disabled state works on first/last page
- [ ] "Showing X to Y of Z" text updates correctly

#### Other Features
- [ ] Record button opens recording setup panel
- [ ] Record button disabled when quota exceeded
- [ ] Upgrade banner shows when quota reached
- [ ] Upgrade modal opens and functions correctly
- [ ] Empty state displays when no videos
- [ ] Loading state displays during fetch
- [ ] Error state displays with retry button

---

## Files That Must NOT Be Modified

These files contain the business logic and should remain unchanged:

1. `frontend/src/services/videoService.js` - Video API calls
2. `frontend/src/services/toastService.js` - Toast notifications
3. `frontend/src/stores/auth.js` - Authentication & subscription state
4. `frontend/src/composables/useRecording.js` - Recording functionality

---

## Component Dependencies

The following components are used and their interfaces must be respected:

```vue
<SBDeleteModal
  v-model="showDeleteModal"
  title="Delete Video"
  :message="..."
  :loading="isDeleting"
  @confirm="confirmDeleteVideo"
  @cancel="showDeleteModal = false"
/>

<SBUpgradeModal
  :show="showUpgradeModal"
  @close="showUpgradeModal = false"
  @success="handleUpgradeSuccess"
/>
```

---

## Notes

- All CSS/styling changes are allowed
- HTML structure can be modified for the new design
- Component hierarchy can be adjusted as needed
- **DO NOT** change function implementations in the `<script>` section unless absolutely necessary for the new design
- Test on both desktop and mobile viewports (responsive design)

---

---

## Implementation Status

### Completed Changes

#### AppLayout.vue
- [x] New sidebar design with 260px width
- [x] Updated logo section with gradient icon
- [x] "New Recording" button with hover effects
- [x] Navigation items with active state indicators
- [x] Upgrade badge with progress bar for free plan users
- [x] User footer with avatar and status indicator
- [x] Mobile responsive sidebar overlay
- [x] Custom scrollbar styles
- [x] Fade-in animations
- [x] Header with breadcrumb navigation
- [x] Desktop search bar
- [x] Notifications button with badge indicator

#### VideosView.vue
- [x] New toolbar with Videos/Archived tabs
- [x] Sort dropdown with all sorting options preserved
- [x] Filter dropdown with date range options
- [x] View toggle (grid/list) with new styling
- [x] New video card design with hover overlay
- [x] Action buttons (favorite, share, download, delete) on hover
- [x] Center play button on thumbnail hover
- [x] Duration badge with backdrop blur
- [x] Updated video info layout (title, date, views)
- [x] New list view design with inline actions
- [x] Updated pagination styling
- [x] Updated empty state design
- [x] Subscription quota banner preserved
- [x] All modals (delete, upgrade) preserved

#### SubscriptionView.vue
- [x] Two-column pricing cards layout
- [x] Free Plan card with feature list
- [x] Pro Plan card with dark theme
- [x] "Recommended" badge on Pro plan
- [x] Enterprise contact section
- [x] Subscription history table
- [x] All original functionality preserved (cancel, billing portal, etc.)

#### NotificationsView.vue (NEW)
- [x] Notifications list with different types (viewer, comment, warning, success)
- [x] Read/unread state with visual indicators
- [x] "Mark all read" functionality
- [x] Notification icons based on type
- [x] Relative timestamps (using date-fns)
- [x] Loading and empty states
- [x] Route added to router/index.js

### Functionality Preserved
- [x] `videoService.getVideos()` - loads videos
- [x] `videoService.deleteVideo()` - deletes video
- [x] `shareVideo()` - copies share URL
- [x] `embedVideo()` - copies embed code
- [x] `downloadVideo()` - downloads video file
- [x] `deleteVideo()` / `confirmDeleteVideo()` - delete flow
- [x] `openVideo()` - navigates to player
- [x] `goToRecord()` - opens recording panel
- [x] All date filters working
- [x] All sort options working
- [x] Pagination working
- [x] Toast notifications preserved
- [x] Modal dialogs preserved

**Last Updated:** 2026-01-01
