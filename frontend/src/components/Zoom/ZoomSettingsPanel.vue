<template>
  <div class="bg-white border-2 border-gray-200 rounded-xl p-6 transition-all" :class="{ 'border-orange-500 bg-orange-50': zoomEnabled }">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
          <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
          </svg>
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">Auto-Zoom</h3>
          <p class="text-sm text-gray-500">Focus on clicks during recording</p>
        </div>
      </div>

      <!-- Toggle Switch -->
      <button
        @click="toggleZoom"
        :class="[
          'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2',
          zoomEnabled ? 'bg-orange-600' : 'bg-gray-200'
        ]"
      >
        <span
          :class="[
            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
            zoomEnabled ? 'translate-x-5' : 'translate-x-0'
          ]"
        />
      </button>
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
      <div v-if="zoomEnabled" class="mt-4 pt-4 border-t border-gray-200 space-y-4">
        <!-- Zoom Level -->
        <div>
          <label class="flex items-center justify-between text-sm font-medium text-gray-700 mb-2">
            <span>Zoom Level</span>
            <span class="text-orange-600 font-semibold">{{ zoomLevel }}x</span>
          </label>
          <input
            type="range"
            :value="zoomLevel"
            @input="updateZoomLevel($event.target.value)"
            min="1.2"
            max="4"
            step="0.1"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-600"
          />
          <div class="flex justify-between text-xs text-gray-500 mt-1">
            <span>1.2x</span>
            <span>4x</span>
          </div>
        </div>

        <!-- Animation Duration -->
        <div>
          <label class="flex items-center justify-between text-sm font-medium text-gray-700 mb-2">
            <span>Animation Speed</span>
            <span class="text-orange-600 font-semibold">{{ zoomDurationMs }}ms</span>
          </label>
          <input
            type="range"
            :value="zoomDurationMs"
            @input="updateZoomDuration($event.target.value)"
            min="100"
            max="2000"
            step="50"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-600"
          />
          <div class="flex justify-between text-xs text-gray-500 mt-1">
            <span>Fast</span>
            <span>Slow</span>
          </div>
        </div>

        <!-- Info -->
        <div class="bg-orange-50 rounded-lg p-3">
          <div class="flex items-start space-x-2">
            <svg class="w-5 h-5 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-orange-700">
              Clicks and keystrokes will be tracked during recording. The video will automatically zoom in on these points.
            </p>
          </div>
        </div>

        <!-- Event Counter (during/after recording) -->
        <div v-if="eventCount > 0" class="bg-gray-50 rounded-lg p-3">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Events tracked:</span>
            <span class="font-semibold text-gray-900">{{ eventCount }}</span>
          </div>
          <div class="flex items-center justify-between text-xs text-gray-500 mt-1">
            <span>{{ clickEventCount }} clicks, {{ keyboardEventCount }} keyboard events</span>
          </div>
        </div>
      </div>
    </transition>
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
