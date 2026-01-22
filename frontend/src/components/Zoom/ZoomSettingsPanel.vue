<template>
  <div class="card bg-base-100 border-2 transition-all" :class="zoomEnabled ? 'border-primary bg-primary/5' : 'border-base-200'">
    <div class="card-body p-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
            </svg>
          </div>
          <div>
            <h3 class="font-semibold">Auto-Zoom</h3>
            <p class="text-sm text-base-content/60">Focus on clicks during recording</p>
          </div>
        </div>

        <!-- Toggle Switch -->
        <input
          type="checkbox"
          :checked="zoomEnabled"
          @change="toggleZoom"
          class="toggle toggle-primary"
        />
      </div>

      <!-- Settings (shown when enabled) -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-if="zoomEnabled" class="mt-4 pt-4 border-t border-base-200 space-y-4">
          <!-- Zoom Level -->
          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Zoom Level</span>
              <span class="label-text-alt text-primary font-semibold">{{ zoomLevel }}x</span>
            </label>
            <input
              type="range"
              :value="zoomLevel"
              @input="updateZoomLevel($event.target.value)"
              min="1.2"
              max="4"
              step="0.1"
              class="range range-primary range-sm"
            />
            <div class="flex justify-between text-xs text-base-content/50 mt-1">
              <span>1.2x</span>
              <span>4x</span>
            </div>
          </div>

          <!-- Animation Duration -->
          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Animation Speed</span>
              <span class="label-text-alt text-primary font-semibold">{{ zoomDurationMs }}ms</span>
            </label>
            <input
              type="range"
              :value="zoomDurationMs"
              @input="updateZoomDuration($event.target.value)"
              min="100"
              max="2000"
              step="50"
              class="range range-primary range-sm"
            />
            <div class="flex justify-between text-xs text-base-content/50 mt-1">
              <span>Fast</span>
              <span>Slow</span>
            </div>
          </div>

          <!-- Info -->
          <div class="alert bg-primary/10 border-none">
            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm text-primary-content/80">
              Clicks and keystrokes will be tracked during recording. The video will automatically zoom in on these points.
            </span>
          </div>

          <!-- Event Counter (during/after recording) -->
          <div v-if="eventCount > 0" class="stats bg-base-200 w-full">
            <div class="stat p-4">
              <div class="stat-title text-xs">Events tracked</div>
              <div class="stat-value text-lg">{{ eventCount }}</div>
              <div class="stat-desc">{{ clickEventCount }} clicks, {{ keyboardEventCount }} keyboard events</div>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'ZoomSettingsPanel',
  props: {
    zoomEnabled: {
      type: Boolean,
      default: false
    },
    zoomLevel: {
      type: Number,
      default: 2.0
    },
    zoomDurationMs: {
      type: Number,
      default: 500
    },
    eventCount: {
      type: Number,
      default: 0
    },
    clickEventCount: {
      type: Number,
      default: 0
    },
    keyboardEventCount: {
      type: Number,
      default: 0
    }
  },
  emits: ['update:zoomEnabled', 'update:zoomLevel', 'update:zoomDurationMs'],
  setup(props, { emit }) {
    const toggleZoom = () => {
      emit('update:zoomEnabled', !props.zoomEnabled)
    }

    const updateZoomLevel = (value) => {
      emit('update:zoomLevel', parseFloat(value))
    }

    const updateZoomDuration = (value) => {
      emit('update:zoomDurationMs', parseInt(value))
    }

    return {
      toggleZoom,
      updateZoomLevel,
      updateZoomDuration
    }
  }
})
</script>
