<template>
  <div class="max-w-5xl mx-auto py-10 px-6 space-y-12">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">UI Standards</h1>
      <p class="text-sm text-gray-500 mt-1">
        Reference page for agents and developers. Every new UI must follow these patterns.
      </p>
    </div>

    <!-- Design Tokens -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Design Tokens</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="token in designTokens" :key="token.label" class="space-y-1">
          <div class="text-[11px] text-gray-400 uppercase tracking-wider">{{ token.label }}</div>
          <div v-if="token.swatch" class="h-10 rounded-lg" :style="{ background: token.swatch }"></div>
          <div class="text-xs font-mono text-gray-600">{{ token.value }}</div>
          <div class="text-[11px] text-gray-400">{{ token.usage }}</div>
        </div>
      </div>
    </section>

    <!-- Typography -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Typography</h2>
      <div class="space-y-4">
        <div><span class="text-[11px] text-gray-400 uppercase tracking-wider">Headings</span><div class="text-2xl font-bold text-gray-900 mt-1">Heading · text-2xl font-bold text-gray-900</div></div>
        <div><span class="text-[11px] text-gray-400 uppercase tracking-wider">Section Title</span><div class="text-lg font-semibold text-gray-900 mt-1">Section Title · text-lg font-semibold text-gray-900</div></div>
        <div><span class="text-[11px] text-gray-400 uppercase tracking-wider">Body</span><div class="text-sm text-gray-700 mt-1">Body text · text-sm text-gray-700</div></div>
        <div><span class="text-[11px] text-gray-400 uppercase tracking-wider">Caption</span><div class="text-xs text-gray-500 mt-1">Caption text · text-xs text-gray-500</div></div>
        <div><span class="text-[11px] text-gray-400 uppercase tracking-wider">Fine Print</span><div class="text-[11px] text-gray-400 mt-1">Fine print · text-[11px] text-gray-400</div></div>
      </div>
    </section>

    <!-- Buttons -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Button Variants</h2>
      <div class="flex flex-wrap items-center gap-3">
        <button class="px-5 py-2 text-sm font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700 transition-colors">Primary</button>
        <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Secondary</button>
        <button class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
        </button>
        <button class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50">Danger</button>
        <button class="px-5 py-2 text-sm font-semibold text-white bg-orange-600 rounded-lg opacity-50 cursor-not-allowed">Disabled</button>
      </div>
      <div class="mt-4 grid grid-cols-2 gap-2 text-[11px] font-mono text-gray-500">
        <div><code class="bg-gray-100 px-1 rounded">Primary</code> → px-5 py-2 text-sm font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700 transition-colors</div>
        <div><code class="bg-gray-100 px-1 rounded">Secondary</code> → px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors</div>
        <div><code class="bg-gray-100 px-1 rounded">Ghost</code> → p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors</div>
        <div><code class="bg-gray-100 px-1 rounded">Danger</code> → px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50</div>
      </div>
    </section>

    <!-- Cards -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Cards</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card card-pad-md">
          <div class="text-xs font-medium text-gray-900 mb-1">Static card</div>
          <div class="text-[11px] text-gray-500"><code>card card-pad-md</code></div>
        </div>
        <div class="card card-hover card-pad-md cursor-pointer">
          <div class="text-xs font-medium text-gray-900 mb-1">Interactive card</div>
          <div class="text-[11px] text-gray-500"><code>card card-hover card-pad-md</code></div>
        </div>
        <div class="card card-hover card-selected card-pad-md cursor-pointer">
          <div class="text-xs font-medium text-gray-900 mb-1">Selected card</div>
          <div class="text-[11px] text-gray-500"><code>card card-hover card-selected card-pad-md</code></div>
        </div>
      </div>
      <div class="mt-4 text-[11px] font-mono text-gray-500 space-y-1">
        <div><code>card</code> → bg-white rounded-xl border border-gray-100</div>
        <div><code>card-hover</code> → hover:border-gray-200 hover:shadow-sm transition-all duration-200</div>
        <div><code>card-selected</code> → ring-2 ring-orange-500 ring-offset-2 border-orange-300</div>
        <div>Padding variants: <code>card-pad-sm</code> (p-3) · <code>card-pad-md</code> (p-4) · <code>card-pad-lg</code> (p-5)</div>
        <div class="text-amber-600 mt-2">Always use <code>card</code> class instead of writing <code>bg-white rounded-xl border border-gray-100</code> inline.</div>
      </div>
    </section>

    <!-- Dropdowns -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Dropdowns</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Light dropdown example -->
        <div>
          <div class="text-xs font-medium text-gray-500 mb-2">Light dropdown panels</div>
          <div class="menu-panel relative w-48 shadow-none ring-1 ring-gray-200">
            <button class="menu-item" data-dropdown-item>
              <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
              <span>Copy link</span>
            </button>
            <button class="menu-item" data-dropdown-item>
              <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              <span>Download</span>
            </button>
            <div class="menu-divider"></div>
            <button class="menu-item menu-item-danger" data-dropdown-item>
              <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              <span>Delete</span>
            </button>
          </div>
        </div>
        <!-- Dark dropdown example -->
        <div>
          <div class="text-xs font-medium text-gray-500 mb-2">Dark dropdown (video player)</div>
          <div class="relative h-36 rounded-xl bg-gray-900 p-4">
            <div class="menu-panel-dark relative shadow-none bottom-0 right-0 w-36">
              <button class="w-full text-left px-3 py-1.5 text-[11px] text-white/80 hover:bg-white/10 rounded-lg transition-colors">1080p</button>
              <button class="w-full text-left px-3 py-1.5 text-[11px] text-white/80 hover:bg-white/10 rounded-lg transition-colors">720p</button>
              <button class="w-full text-left px-3 py-1.5 text-[11px] text-white/80 hover:bg-white/10 rounded-lg transition-colors">480p</button>
              <button class="w-full text-left px-3 py-1.5 text-[11px] text-white/80 hover:bg-white/10 rounded-lg transition-colors">Auto</button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 text-[11px] font-mono text-gray-500 space-y-1">
        <div><code>menu-panel</code> → absolute bg-white rounded-xl shadow-lg border border-gray-100 py-1.5 z-50</div>
        <div><code>menu-panel-dark</code> → absolute bg-black/80 backdrop-blur-xl rounded-lg shadow-2xl border border-white/10 py-1.5 z-50</div>
        <div><code>menu-item</code> → w-full flex items-center gap-3 px-3.5 py-2 text-xs text-gray-700 hover:bg-gray-50 rounded-lg transition-colors duration-150</div>
        <div><code>menu-item-danger</code> (+menu-item) → text-red-600 hover:bg-red-50</div>
        <div><code>menu-item-active</code> (+menu-item) → text-orange-600 font-medium bg-orange-50</div>
        <div><code>menu-divider</code> → h-px bg-gray-100 mx-2 my-1</div>
        <div><code>data-dropdown-item</code> → attribute on clickable items (enables auto-close on SBDropdown)</div>
      </div>
    </section>

    <!-- Badges -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Badges</h2>
      <div class="flex flex-wrap items-center gap-3">
        <span class="badge badge-neutral">Neutral</span>
        <span class="badge badge-brand">Brand</span>
        <span class="badge badge-success">Success</span>
        <span class="badge badge-warning">Warning</span>
        <span class="badge badge-danger">Danger</span>
      </div>
      <div class="mt-3 grid grid-cols-2 gap-1 text-[11px] font-mono text-gray-500">
        <div><code>badge</code> → inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-md</div>
        <div><code>badge-brand</code> → bg-orange-50 text-orange-700</div>
        <div><code>badge-success</code> → bg-emerald-50 text-emerald-700</div>
        <div><code>badge-neutral</code> → bg-gray-100 text-gray-600</div>
      </div>
    </section>

    <!-- Forms -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Form Elements</h2>
      <div class="max-w-sm space-y-4">
        <div class="form-group">
          <label class="form-label">Label</label>
          <input type="text" class="form-input" placeholder="Placeholder text" />
        </div>
        <div class="form-group">
          <label class="form-label">With error</label>
          <input type="text" class="form-input border-red-300 focus:ring-red-500 focus:border-red-300" value="bad value" />
          <p class="form-error">This field has an error.</p>
        </div>
      </div>
      <div class="mt-4 text-[11px] font-mono text-gray-500 space-y-1">
        <div><code>form-group</code> → space-y-1.5 (wraps label+input+error)</div>
        <div><code>form-label</code> → block text-xs font-medium text-gray-700</div>
        <div><code>form-input</code> → w-full px-3 py-2 text-sm border border-gray-200 rounded-lg placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-300</div>
        <div><code>form-error</code> → text-xs text-red-600 mt-1</div>
      </div>
    </section>

    <!-- Empty States -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Empty States</h2>
      <div class="empty-state">
        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
        <h3 class="empty-state-title">No items yet</h3>
        <p class="empty-state-desc">Get started by creating your first item.</p>
      </div>
      <div class="mt-4 text-[11px] font-mono text-gray-500">
        <div><code>empty-state</code> → flex flex-col items-center justify-center py-16 px-6 text-center</div>
        <div><code>empty-state-icon</code> → w-16 h-16 mb-4 text-gray-300</div>
        <div><code>empty-state-title</code> → text-sm font-semibold text-gray-700 mb-1</div>
        <div><code>empty-state-desc</code> → text-xs text-gray-500 max-w-xs</div>
      </div>
    </section>

    <!-- Skeleton Loading -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Skeleton Loading</h2>
      <div class="space-y-3 max-w-sm">
        <div class="flex items-center gap-3">
          <div class="skeleton-avatar"></div>
          <div class="space-y-2 flex-1">
            <div class="skeleton-title"></div>
            <div class="skeleton-text"></div>
          </div>
        </div>
      </div>
      <div class="mt-4 text-[11px] font-mono text-gray-500">
        <div><code>skeleton</code> → bg-gray-200 animate-pulse rounded-lg</div>
        <div><code>skeleton-text</code> / <code>skeleton-title</code> / <code>skeleton-avatar</code> / <code>skeleton-thumb</code></div>
      </div>
    </section>

    <!-- Component Usage -->
    <section class="card card-pad-lg">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Component Usage Rules</h2>
      <div class="space-y-4 text-sm text-gray-700">
        <div>
          <h3 class="font-semibold text-gray-900">SBDropdown</h3>
          <p class="text-xs text-gray-500 mt-0.5">Use for all dropdown menus. Never hand-roll click-outside or Escape key behavior.</p>
          <pre class="mt-2 p-3 bg-gray-50 rounded-lg text-[11px] text-gray-600 overflow-x-auto">&lt;SBDropdown v-model="open" align="right" width="md"&gt;
  &lt;template #trigger&gt;
    &lt;button class="..."&gt;Open Menu&lt;/button&gt;
  &lt;/template&gt;
  &lt;div class="menu-panel"&gt;
    &lt;button class="menu-item" data-dropdown-item&gt;Action 1&lt;/button&gt;
    &lt;button class="menu-item menu-item-danger" data-dropdown-item&gt;Delete&lt;/button&gt;
  &lt;/div&gt;
&lt;/SBDropdown&gt;</pre>
        </div>
        <div class="border-t border-gray-100 pt-4">
          <h3 class="font-semibold text-gray-900">Cards must use <code>.card</code></h3>
          <p class="text-xs text-gray-500 mt-0.5">Never write <code>bg-white rounded-xl border border-gray-100</code> inline. Use <code>.card</code> plus padding and hover variants.</p>
        </div>
        <div class="border-t border-gray-100 pt-4">
          <h3 class="font-semibold text-gray-900">All alerts must use <code>toast</code></h3>
          <p class="text-xs text-gray-500 mt-0.5">Never call raw <code>alert()</code>. Use <code>toast.success()</code>, <code>toast.error()</code>, <code>toast.warning()</code>, <code>toast.info()</code>.</p>
        </div>
        <div class="border-t border-gray-100 pt-4">
          <h3 class="font-semibold text-gray-900">All modals must use SBModal</h3>
          <p class="text-xs text-gray-500 mt-0.5">Never create inline <code>&lt;Teleport to="body"&gt;</code> overlays. Use SBModal with its header/body/footer slots.</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'StandardsView',
  setup() {
    const designTokens = computed(() => [
      { label: 'Brand', swatch: '#f97316', value: '#f97316 (orange-500)', usage: 'Primary buttons, toggles, focus rings' },
      { label: 'Brand hover', swatch: '#ea580c', value: '#ea580c (orange-600)', usage: 'Button hover state' },
      { label: 'Brand light', swatch: '#fff7ed', value: 'bg-orange-50', usage: 'Selected pills, toggled states' },
      { label: 'Text primary', swatch: '#111827', value: 'text-gray-900', usage: 'Headings' },
      { label: 'Text secondary', swatch: '#374151', value: 'text-gray-700', usage: 'Body text' },
      { label: 'Text muted', swatch: '#6b7280', value: 'text-gray-500', usage: 'Labels, captions' },
      { label: 'Border', swatch: '#f3f4f6', value: 'border-gray-100', usage: 'Cards, inputs, dividers' },
      { label: 'Border dark', swatch: '#e5e7eb', value: 'border-gray-200', usage: 'Input borders, hover cards' },
      { label: 'Background', swatch: '#f9fafb', value: 'bg-gray-50', usage: 'Page bg, hover rows' },
      { label: 'Success', swatch: '#ecfdf5', value: 'bg-emerald-50', usage: 'Success states, estimates' },
      { label: 'Warning', swatch: '#fffbeb', value: 'bg-amber-50', usage: 'Warning notes' },
      { label: 'Danger', swatch: '#fef2f2', value: 'bg-red-50', usage: 'Danger states' },
    ])

    return { designTokens }
  },
}
</script>
