<template>
  <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4">
      <h3 class="text-lg font-semibold text-white">Zoom Events Editor</h3>
      <p class="text-sm text-orange-100 mt-1">Review and edit zoom points for your video</p>
    </div>

    <!-- Timeline visualization -->
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
      <div class="relative h-12 bg-gray-200 rounded-lg overflow-hidden">
        <!-- Video duration bar -->
        <div class="absolute inset-0 bg-gradient-to-r from-orange-200 to-orange-300"></div>

        <!-- Event markers -->
        <div
          v-for="(event, index) in events"
          :key="index"
          class="absolute top-0 bottom-0 w-1 cursor-pointer transition-all hover:w-2"
          :style="{ left: getEventPosition(event) + '%' }"
          :class="[
            event.zoom_enabled ? 'bg-orange-600' : 'bg-gray-400',
            event.type === 'click' ? 'rounded-full h-8 top-2' : 'h-full'
          ]"
          :title="getEventTooltip(event)"
          @click="scrollToEvent(index)"
        >
          <!-- Event icon -->
          <div
            class="absolute -top-1 left-1/2 -translate-x-1/2 w-4 h-4 rounded-full flex items-center justify-center text-white text-xs"
            :class="event.zoom_enabled ? 'bg-orange-600' : 'bg-gray-400'"
          >
            <span v-if="event.type === 'click'">C</span>
            <span v-else>K</span>
          </div>
        </div>
      </div>

      <!-- Time labels -->
      <div class="flex justify-between text-xs text-gray-500 mt-1">
        <span>0:00</span>
        <span>{{ formatDuration(videoDuration) }}</span>
      </div>
    </div>

    <!-- Event List -->
    <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
      <div
        v-for="(event, index) in events"
        :key="index"
        :ref="el => eventRefs[index] = el"
        class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
      >
        <div class="flex items-center space-x-4">
          <!-- Event Icon -->
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center"
            :class="event.zoom_enabled ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-400'"
          >
            <svg v-if="event.type === 'click'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
            </svg>
          </div>

          <!-- Event Details -->
          <div>
            <div class="font-medium text-gray-900">
              {{ event.type === 'click' ? 'Click Event' : 'Keyboard Event' }}
              <span class="text-gray-500 font-normal ml-2">at {{ formatTime(event.timestamp_ms) }}</span>
            </div>
            <div class="text-sm text-gray-500">
              <span v-if="event.type === 'click'">
                Position: ({{ event.x }}, {{ event.y }})
              </span>
              <span v-else>
                Keys: "{{ truncateText(event.keys, 30) }}"
                <span v-if="event.duration_ms" class="ml-1">({{ event.duration_ms }}ms)</span>
              </span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center space-x-2">
          <!-- Enable/Disable Toggle -->
          <button
            @click="toggleEvent(index)"
            :class="[
              'px-3 py-1 rounded-full text-sm font-medium transition-colors',
              event.zoom_enabled
                ? 'bg-orange-100 text-orange-700 hover:bg-orange-200'
                : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
            ]"
          >
            {{ event.zoom_enabled ? 'Enabled' : 'Disabled' }}
          </button>

          <!-- Delete Button -->
          <button
            @click="removeEvent(index)"
            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors"
            title="Remove event"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="events.length === 0" class="px-6 py-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <p class="text-gray-500">No zoom events recorded</p>
        <p class="text-sm text-gray-400 mt-1">Click and keyboard events during recording will appear here</p>
      </div>
    </div>

    <!-- Footer -->
    <div v-if="events.length > 0" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
          <span class="font-medium">{{ enabledCount }}</span> of <span class="font-medium">{{ events.length }}</span> events enabled
        </div>
        <div class="flex space-x-2">
          <button
            @click="enableAll"
            class="px-3 py-1 text-sm font-medium text-orange-600 hover:bg-orange-50 rounded transition-colors"
          >
            Enable All
          </button>
          <button
            @click="disableAll"
            class="px-3 py-1 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded transition-colors"
          >
            Disable All
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent, computed, ref } from 'vue'

export default defineComponent({
  name: 'ZoomEventEditor',
  props: {
    events: {
      type: Array,
      default: () => []
    },
    videoDuration: {
      type: Number,
      default: 0
    }
  },
  emits: ['toggle-event', 'remove-event', 'update-events'],
  setup(props, { emit }) {
    const eventRefs = ref({})

    const enabledCount = computed(() => {
      return props.events.filter(e => e.zoom_enabled).length
    })

    const formatTime = (ms) => {
      const seconds = Math.floor(ms / 1000)
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    }

    const formatDuration = (seconds) => {
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    }

    const getEventPosition = (event) => {
      if (props.videoDuration <= 0) return 0
      const seconds = event.timestamp_ms / 1000
      return (seconds / props.videoDuration) * 100
    }

    const getEventTooltip = (event) => {
      const time = formatTime(event.timestamp_ms)
      if (event.type === 'click') {
        return `Click at ${time} (${event.x}, ${event.y})`
      }
      return `Keyboard at ${time}: "${event.keys}"`
    }

    const truncateText = (text, maxLength) => {
      if (!text) return ''
      if (text.length <= maxLength) return text
      return text.substring(0, maxLength) + '...'
    }

    const toggleEvent = (index) => {
      emit('toggle-event', index)
    }

    const removeEvent = (index) => {
      emit('remove-event', index)
    }

    const scrollToEvent = (index) => {
      const el = eventRefs.value[index]
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }
    }

    const enableAll = () => {
      const updated = props.events.map(e => ({ ...e, zoom_enabled: true }))
      emit('update-events', updated)
    }

    const disableAll = () => {
      const updated = props.events.map(e => ({ ...e, zoom_enabled: false }))
      emit('update-events', updated)
    }

    return {
      eventRefs,
      enabledCount,
      formatTime,
      formatDuration,
      getEventPosition,
      getEventTooltip,
      truncateText,
      toggleEvent,
      removeEvent,
      scrollToEvent,
      enableAll,
      disableAll
    }
  }
})
</script>
