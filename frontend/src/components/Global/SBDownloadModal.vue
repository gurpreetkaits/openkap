<template>
  <SBModal
    :modelValue="modelValue"
    @update:modelValue="$emit('update:modelValue', $event)"
    @close="$emit('close')"
    title="Download Video"
    size="md"
    :closeOnBackdrop="!isDownloading"
  >
    <!-- Body -->
    <div class="space-y-5">
      <!-- Video preview card -->
      <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
        <div class="w-16 h-9 rounded-lg bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center flex-shrink-0 shadow-sm">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-900 truncate">{{ videoTitle }}</p>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ formattedDuration }}
            <template v-if="hasCamera">· Screen + Camera</template>
          </p>
        </div>
      </div>

      <div class="h-px bg-gray-100"></div>

      <!-- Quality -->
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Quality</label>
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="q in qualities"
            :key="q.value"
            @click="options.quality = q.value"
            :class="[
              'px-3 py-2 text-xs font-medium rounded-lg border transition-all',
              options.quality === q.value
                ? 'border-orange-300 bg-orange-50 text-orange-700 shadow-sm'
                : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'
            ]"
          >
            {{ q.label }}
          </button>
        </div>
      </div>

      <!-- Camera toggle -->
      <div v-if="hasCamera" class="space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">Include camera overlay</p>
            <p class="text-xs text-gray-500">Merge your webcam as picture-in-picture</p>
          </div>
          <button
            type="button"
            role="switch"
            :aria-checked="options.includeCamera"
            @click="options.includeCamera = !options.includeCamera"
            :class="[
              'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2',
              options.includeCamera ? 'bg-orange-500' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition duration-200 ease-in-out',
                options.includeCamera ? 'translate-x-4' : 'translate-x-0'
              ]"
            />
          </button>
        </div>

        <!-- Camera sub-options -->
        <div
          v-if="options.includeCamera"
          class="ml-2 pl-4 border-l-2 border-orange-200 space-y-3"
        >
          <!-- Position -->
          <div class="space-y-1.5">
            <label class="text-xs font-medium text-gray-600">Position</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="pos in cameraPositions"
                :key="pos.value"
                @click="options.cameraPosition = pos.value"
                :class="[
                  'flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border transition-all',
                  options.cameraPosition === pos.value
                    ? 'border-orange-300 bg-orange-50 text-orange-700'
                    : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300'
                ]"
              >
                <span class="text-[10px]">{{ pos.icon }}</span>
                {{ pos.label }}
              </button>
            </div>
          </div>

          <!-- Size -->
          <div class="space-y-1.5">
            <label class="text-xs font-medium text-gray-600">Size: {{ cameraSizeLabel }}</label>
            <input
              type="range"
              :min="10"
              :max="40"
              :step="5"
              :value="Math.round(options.cameraSize * 100)"
              @input="options.cameraSize = ($event.target.value / 100)"
              class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-500"
            />
            <div class="flex justify-between text-[10px] text-gray-400">
              <span>Small</span>
              <span>Large</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Captions toggle -->
      <div v-if="hasCaptions" class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-900">Burn captions into video</p>
          <p class="text-xs text-gray-500">English captions available from transcription</p>
        </div>
        <button
          type="button"
          role="switch"
          :aria-checked="options.includeCaptions"
          @click="options.includeCaptions = !options.includeCaptions"
          :class="[
            'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2',
            options.includeCaptions ? 'bg-orange-500' : 'bg-gray-200'
          ]"
        >
          <span
            :class="[
              'pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition duration-200 ease-in-out',
              options.includeCaptions ? 'translate-x-4' : 'translate-x-0'
            ]"
          />
        </button>
      </div>

      <div class="h-px bg-gray-100"></div>

      <!-- Estimate -->
      <div class="flex items-center gap-4 p-3 bg-emerald-50 rounded-xl border border-emerald-100 text-xs text-emerald-700">
        <div class="flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
          </svg>
          <span class="font-semibold">{{ estimatedSize }}</span>
        </div>
        <div class="flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="font-semibold">{{ estimatedTime }}</span>
        </div>
      </div>

      <!-- Info note -->
      <div class="flex items-start gap-2 p-2.5 bg-amber-50 rounded-lg border border-amber-100 text-[11px] text-amber-700 leading-relaxed">
        <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Your download will be converted in the background. You'll get a notification when it's ready — no need to wait on this page.</span>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <button
          @click="$emit('close')"
          :disabled="isDownloading"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
        >
          Cancel
        </button>
        <button
          @click="handleDownload"
          :disabled="isDownloading"
          class="px-5 py-2 text-sm font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg
            v-if="isDownloading"
            class="animate-spin w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ isDownloading ? 'Starting...' : 'Download MP4' }}
        </button>
      </div>
    </template>
  </SBModal>
</template>

<script>
import { computed, reactive } from 'vue'
import SBModal from '@/components/Global/SBModal.vue'

export default {
  name: 'SBDownloadModal',
  components: { SBModal },
  emits: ['update:modelValue', 'close', 'download'],
  props: {
    modelValue: { type: Boolean, required: true },
    videoTitle: { type: String, default: 'Untitled Video' },
    duration: { type: Number, default: 0 },
    hasCamera: { type: Boolean, default: false },
    hasCaptions: { type: Boolean, default: false },
    isDownloading: { type: Boolean, default: false },
  },
  setup(props, { emit }) {
    const options = reactive({
      quality: '1080p',
      includeCamera: props.hasCamera,
      cameraPosition: 'bottom-right',
      cameraSize: 0.25,
      includeCaptions: false,
    })

    const qualities = [
      { value: '1080p', label: '1080p' },
      { value: '720p', label: '720p' },
      { value: '480p', label: '480p' },
      { value: 'original', label: 'Original' },
    ]

    const cameraPositions = [
      { value: 'bottom-right', label: 'Bottom Right', icon: '↘' },
      { value: 'bottom-left', label: 'Bottom Left', icon: '↙' },
      { value: 'top-right', label: 'Top Right', icon: '↗' },
      { value: 'top-left', label: 'Top Left', icon: '↖' },
    ]

    const formattedDuration = computed(() => {
      const mins = Math.floor(props.duration / 60)
      const secs = props.duration % 60
      return `${mins}:${String(secs).padStart(2, '0')}`
    })

    const cameraSizeLabel = computed(() => {
      const pct = Math.round(options.cameraSize * 100)
      if (pct <= 15) return 'Small'
      if (pct <= 25) return 'Normal'
      return 'Large'
    })

    const estimatedSize = computed(() => {
      let base = 35
      if (options.quality === 'original') base = 60
      if (options.quality === '720p') base = 22
      if (options.quality === '480p') base = 14
      if (options.includeCamera && props.hasCamera) base += 10
      if (options.includeCaptions) base += 3
      const dur = Math.max(props.duration, 1) / 60
      return `~${Math.round(base * dur)} MB`
    })

    const estimatedTime = computed(() => {
      let base = 20
      if (options.quality === 'original') base = 15
      if (options.includeCamera && props.hasCamera) base += 10
      if (options.includeCaptions) base += 5
      const dur = Math.max(props.duration, 1) / 60
      return `~${Math.round(base * dur)} sec`
    })

    const handleDownload = () => {
      emit('download', {
        quality: options.quality,
        includeCamera: options.includeCamera && props.hasCamera,
        cameraPosition: options.cameraPosition,
        cameraSize: options.cameraSize,
        includeCaptions: options.includeCaptions && props.hasCaptions,
      })
    }

    return {
      options, qualities, cameraPositions,
      formattedDuration, cameraSizeLabel,
      estimatedSize, estimatedTime,
      handleDownload,
    }
  },
}
</script>
