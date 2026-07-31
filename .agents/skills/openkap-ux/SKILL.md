---
name: openkap-ux
description: Build OpenKap frontend UI following project conventions — Vue 3 Options API, Tailwind CSS, SB-prefixed global components, orange brand color, inline SVGs. Use when creating or editing Vue components, modals, toasts, download flows, or any frontend UI in the openkap project.
---

# OpenKap UX Conventions

Follow these patterns when building frontend UI in `/Users/gurpreetkait/code/openkap/frontend/`.

## Stack

| Layer | Technology |
|-------|-----------|
| Framework | Vue 3 (Options API with `setup()` — NOT `<script setup>` Composition API) |
| Styling | Tailwind CSS 3 (utility classes only, no custom CSS files) |
| Icons | Inline SVGs (no icon library — copy SVG markup directly) |
| Routing | Vue Router (`router-link`, `useRouter`) |
| HTTP | Native `fetch` in `frontend/src/services/videoService.js` |
| Auth | `useAuth` composable from `@/composables/useAuth` |
| Toasts | `useToast` from `@/services/toastService` — methods: `toast.success()`, `toast.error()`, `toast.info()` |
| State | Vue `ref`/`reactive`/`computed`, localStorage for persistence |

## Component Conventions

### Global Components (`frontend/src/components/Global/`)

All reusable UI primitives are prefixed `SB` and live here:

| Component | Usage |
|-----------|-------|
| `SBDropdown.vue` | Dropdown menu wrapper. Props: `modelValue` (v-model), `align` (left/right, default right), `side` (bottom/top), `width` (xs/sm/md/lg/xl/auto), `closeOnClick` (default true), `panelClass` (default 'menu-panel'). Slots: `trigger`, `default`. Handles click-outside, Escape key, arrow-key navigation, resize repositioning. **Must be used for ALL dropdown menus.** |
| `SBModal.vue` | Base modal. Props: `modelValue` (v-model), `title`, `size` (xs–7xl), `closable`, `closeOnBackdrop`. Slots: `default` (body), `header`, `footer`. |
| `SBConfirmModal.vue` | Confirmation dialog. Props: `modelValue`, `title`, `message`, `confirmText`, `confirmVariant` (danger/primary). Emits: `confirm`, `close`. |
| `SBDeleteModal.vue` | Delete confirmation with item name display. |
| `SBUpgradeModal.vue` | Subscription upgrade prompt. |
| `SBDownloadModal.vue` | Download options modal: quality selector, camera toggle, captions toggle, live estimates. Emits `download` with options object. |
| `SBRecordingModal.vue` | Recording UI. |
| `SBUploadVideoModal.vue` | Upload UI. |

### Modal Pattern

Always wrap with `SBModal`:

```vue
<template>
  <SBModal v-model="show" title="My Modal" size="md" @close="show = false">
    <div class="space-y-4">
      <!-- body content here -->
    </div>
    <template #footer>
      <div class="flex justify-end gap-3">
        <button @click="show = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
        <button @click="handleAction" class="px-5 py-2 text-sm font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700">Confirm</button>
      </div>
    </template>
  </SBModal>
</template>

<script>
import SBModal from '@/components/Global/SBModal.vue'

export default {
  name: 'MyComponent',
  components: { SBModal },
  // ...
}
</script>
```

### Button Styles

| Variant | Classes |
|---------|---------|
| Primary | `px-5 py-2 text-sm font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700 transition-colors` |
| Secondary | `px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors` |
| Ghost | `p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors` |
| Danger | `px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50` |
| Disabled | Add `disabled:opacity-50 disabled:cursor-not-allowed` |

### Toggle Switch

```html
<button
  type="button" role="switch" :aria-checked="enabled"
  @click="enabled = !enabled"
  :class="['relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2',
    enabled ? 'bg-orange-500' : 'bg-gray-200']"
>
  <span :class="['pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition duration-200',
    enabled ? 'translate-x-4' : 'translate-x-0']" />
</button>
```

### Segmented Button Group (Quality/Options Pills)

```html
<div class="grid grid-cols-4 gap-2">
  <button v-for="opt in options" :key="opt.value" @click="selected = opt.value"
    :class="['px-3 py-2 text-xs font-medium rounded-lg border transition-all',
      selected === opt.value
        ? 'border-orange-300 bg-orange-50 text-orange-700 shadow-sm'
        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50']">
    {{ opt.label }}
  </button>
</div>
```

### Estimate / Info Bar

```html
<div class="flex items-center gap-4 p-3 bg-emerald-50 rounded-xl border border-emerald-100 text-xs text-emerald-700">
  <div class="flex items-center gap-1">
    <svg class="w-3.5 h-3.5">...</svg>
    <span class="font-semibold">~48 MB</span>
  </div>
</div>
```

### Info / Warning Note

```html
<div class="flex items-start gap-2 p-2.5 bg-amber-50 rounded-lg border border-amber-100 text-[11px] text-amber-700">
  <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0">...</svg>
  <span>Message text here.</span>
</div>
```

## Design Tokens

| Token | Value | Usage |
|-------|-------|-------|
| Brand color | `#f97316` (orange-500) | Primary buttons, toggles, active states, focus rings |
| Brand hover | `#ea580c` (orange-600) | Button hover |
| Brand light | `bg-orange-50`, `text-orange-700`, `border-orange-200` | Selected pills, toggled states |
| Text primary | `text-gray-900` | Headings |
| Text secondary | `text-gray-700`, `text-gray-600` | Body |
| Text muted | `text-gray-500`, `text-gray-400` | Labels, placeholders |
| Border | `border-gray-200`, `border-gray-100` | Cards, inputs, dividers |
| Background | `bg-white`, `bg-gray-50` | Cards, hover states |
| Radius | `rounded-lg` (8px), `rounded-xl` (12px) | Cards, modals, buttons |
| Success | `bg-emerald-50`, `text-emerald-700`, `border-emerald-100` | Estimates, success states |
| Warning | `bg-amber-50`, `text-amber-700`, `border-amber-100` | Info notes |

## View Conventions

Views live in `frontend/src/views/`. Key views:

| View | Path | Purpose |
|------|------|---------|
| `VideosView.vue` | `/videos` | Video grid with thumbnails, actions, download |
| `VideoPlayerView.vue` | `/video/:id` | Single video player with comments, reactions, downloads |
| `WorkspaceView.vue` | `/workspace/:slug` | Workspace dashboard |

Views use Options API with `setup()`:

```javascript
export default {
  name: 'MyView',
  components: { ... },
  setup() {
    const auth = useAuth()
    const { trackDownload } = useDownloadTracker()
    // refs, computed, methods...
    return { ... }
  }
}
```

## Download Flow Integration

When adding download UI to any view:

1. Import `SBDownloadModal` and `useDownloadTracker`
2. Add `showDownloadModal` ref
3. On download click, show the modal instead of calling `requestDownloadMp4` directly
4. On modal `@download` event, extract video metadata (hasCamera: `video.has_camera`, hasCaptions: `!!video.transcription`)
5. Call `videoService.requestDownloadMp4(videoId, options)` with the options from the modal
6. If async, call `trackDownload(videoId, title)`

```javascript
const handleDownloadClick = (video) => {
  downloadVideo.value = video
  showDownloadModal.value = true
}

const handleDownloadOptions = async (options) => {
  showDownloadModal.value = false
  const video = downloadVideo.value
  try {
    toast.success('Preparing your download...')
    const result = await videoService.requestDownloadMp4(video.id, {
      includeCamera: options.includeCamera,
      cameraPosition: options.cameraPosition,
      cameraSize: options.cameraSize,
      includeCaptions: options.includeCaptions,
      quality: options.quality,
    })
    if (result?.mode === 'async') {
      trackDownload(video.id, video.title)
      toast.success('Converting — check the notification bell!')
    }
  } catch (err) {
    toast.error('Download failed')
  }
}
```

## File Naming

| Type | Pattern | Example |
|------|---------|---------|
| Global components | `SB` prefix + PascalCase | `SBDownloadModal.vue` |
| Views | PascalCase + `View` | `VideoPlayerView.vue` |
| Composables | `use` prefix + camelCase | `useDownloadProgress.js` |
| Services | camelCase + `Service` | `videoService.js` |

## Tailwind Utility Classes (use instead of inline classes)

These are defined in `frontend/src/style.css` `@layer components`. Always use them instead of writing the equivalent inline classes.

### Cards

| Class | Renders |
|-------|---------|
| `.card` | `bg-white rounded-xl border border-gray-100` |
| `.card-hover` | `hover:border-gray-200 hover:shadow-sm transition-all duration-200` |
| `.card-selected` | `ring-2 ring-orange-500 ring-offset-2 border-orange-300` |
| `.card-pad-sm` | `p-3` |
| `.card-pad-md` | `p-4` |
| `.card-pad-lg` | `p-5` |

```html
<!-- Static card -->
<div class="card card-pad-lg">...</div>

<!-- Interactive card -->
<div class="card card-hover card-pad-md cursor-pointer">...</div>

<!-- Selected interactive card -->
<div class="card card-hover card-selected card-pad-md cursor-pointer">...</div>
```

### Dropdown Panels

| Class | Renders |
|-------|---------|
| `.menu-panel` | `absolute bg-white rounded-xl shadow-lg border border-gray-100 py-1.5 z-50` |
| `.menu-panel-dark` | `absolute bg-black/80 backdrop-blur-xl rounded-lg shadow-2xl border border-white/10 py-1.5 z-50` |

### Dropdown Menu Items

| Class | Renders |
|-------|---------|
| `.menu-item` | `w-full flex items-center gap-3 px-3.5 py-2 text-xs text-gray-700 hover:bg-gray-50 rounded-lg transition-colors` |
| `.menu-item-danger` | (+menu-item) `text-red-600 hover:bg-red-50` |
| `.menu-item-active` | (+menu-item) `text-orange-600 font-medium bg-orange-50` |
| `.menu-divider` | `h-px bg-gray-100 mx-2 my-1` |

```html
<SBDropdown v-model="open" align="right">
  <template #trigger><button>...</button></template>
  <div class="menu-panel w-52">
    <button class="menu-item" data-dropdown-item>Copy link</button>
    <button class="menu-item" data-dropdown-item>Download</button>
    <div class="menu-divider"></div>
    <button class="menu-item menu-item-danger" data-dropdown-item>Delete</button>
  </div>
</SBDropdown>
```

### Badges

| Class | Base | Color |
|-------|------|-------|
| `.badge` | `inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-md` | — |
| `.badge-brand` | — | `bg-orange-50 text-orange-700` |
| `.badge-success` | — | `bg-emerald-50 text-emerald-700` |
| `.badge-warning` | — | `bg-amber-50 text-amber-700` |
| `.badge-danger` | — | `bg-red-50 text-red-700` |
| `.badge-neutral` | — | `bg-gray-100 text-gray-600` |

### Forms

| Class | Renders |
|-------|---------|
| `.form-group` | `space-y-1.5` |
| `.form-label` | `block text-xs font-medium text-gray-700` |
| `.form-input` | `w-full px-3 py-2 text-sm border border-gray-200 rounded-lg placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-300` |
| `.form-error` | `text-xs text-red-600 mt-1` |

### Empty States

| Class | Renders |
|-------|---------|
| `.empty-state` | `flex flex-col items-center justify-center py-16 px-6 text-center` |
| `.empty-state-icon` | `w-16 h-16 mb-4 text-gray-300` |
| `.empty-state-title` | `text-sm font-semibold text-gray-700 mb-1` |
| `.empty-state-desc` | `text-xs text-gray-500 max-w-xs` |

### Skeleton Loading

| Class | Renders |
|-------|---------|
| `.skeleton` | `bg-gray-200 animate-pulse rounded-lg` |
| `.skeleton-text` | `h-4 w-3/4 skeleton` |
| `.skeleton-title` | `h-5 w-1/2 skeleton` |
| `.skeleton-avatar` | `h-10 w-10 rounded-full skeleton` |
| `.skeleton-thumb` | `aspect-video w-full skeleton` |

### Table

| Class | Renders |
|-------|---------|
| `.table-base` | `w-full text-sm` (with header and row styles) |

## Mandatory Rules

These are non-negotiable. Any violation must be fixed before completing a task.

### Rule 1: Cards use `.card` class
**VIOLATION:** `bg-white rounded-xl border border-gray-100` written inline
**FIX:** Replace with `class="card card-pad-*"` (add `.card-hover` if interactive)

### Rule 2: Dropdowns use `SBDropdown` component
**VIOLATION:** Hand-rolled click-outside handler, inline positioned div with shadow/border
**FIX:** Wrap in `<SBDropdown v-model="open">` with `.menu-panel` and `.menu-item` classes

### Rule 3: All modals use `SBModal`
**VIOLATION:** Inline `<Teleport to="body">` with manual overlay/panel divs
**FIX:** Use `<SBModal v-model="show" title="...">` with header/body/footer slots

### Rule 4: All user-facing alerts use toast service
**VIOLATION:** Raw `alert()` call
**FIX:** `import toast from '@/services/toastService'` then `toast.error()` / `toast.success()` / `toast.warning()` / `toast.info()`

### Rule 5: All dropdown items use `.menu-item` class
**VIOLATION:** Inline `w-full px-3 py-2 text-xs hover:bg-gray-50` on dropdown buttons
**FIX:** Use `class="menu-item"` (add `menu-item-danger` for delete actions)

### Rule 6: Menu items have `data-dropdown-item` attribute
**VIOLATION:** Missing `data-dropdown-item` on clickable items inside SBDropdown
**FIX:** Add `data-dropdown-item` attribute (enables auto-close on click)

## Verification Commands

Run these to check for violations in the codebase:

```bash
# Check for inline card classes (should use .card instead)
grep -rn "bg-white rounded-xl border border-gray-100" frontend/src/views/ frontend/src/components/

# Check for raw alert() calls (should use toast)
grep -rn "\balert(" frontend/src/views/ frontend/src/components/ | grep -v toastService

# Check for inline Teleport modals (should use SBModal)
grep -rn "<Teleport to=\"body\">" frontend/src/views/ frontend/src/components/ | grep -v SBModal | grep -v SBDropdown

# Check for hand-rolled dropdown click-outside (should use SBDropdown)
grep -rn "document.addEventListener.*click" frontend/src/views/ --include="*.vue" | grep -v SBModal | grep -v SBDropdown

# Check for hover:bg-gray-50 without px-* (padding-less hover)
grep -rn "hover:bg-gray-50" frontend/src/ --include="*.vue" | grep -v "px-"
```

## Reference Page

A live reference page is available at `/standards` (admin-only) showing all design tokens, utility classes, and component usage patterns with visual examples.

**Download:**
```html
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
</svg>
```

**Close (X):**
```html
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
</svg>
```

**Spinner:**
```html
<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
</svg>
```

**Play:**
```html
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
  <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
</svg>
```

**Edit (pencil):**
```html
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
</svg>
```
