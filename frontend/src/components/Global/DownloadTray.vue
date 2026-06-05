<template>
  <Transition name="tray">
    <div
      v-if="visibleDownloads.length > 0"
      class="fixed bottom-4 right-4 z-[70] w-[340px] max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden"
    >
      <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 border-b border-gray-100">
        <div class="flex items-center gap-2">
          <svg
            v-if="hasProcessing"
            class="w-3.5 h-3.5 text-orange-500 animate-spin"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <svg
            v-else
            class="w-3.5 h-3.5 text-emerald-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            stroke-width="2.5"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span class="text-xs font-semibold text-gray-700">
            {{ headerText }}
          </span>
        </div>
        <button
          @click="clearAll"
          class="text-[10px] text-gray-400 hover:text-gray-600 font-medium"
        >
          Clear all
        </button>
      </div>

      <div class="max-h-[280px] overflow-y-auto divide-y divide-gray-50">
        <div
          v-for="dl in visibleDownloads"
          :key="dl.downloadId"
          class="px-4 py-3"
        >
          <div class="flex items-start gap-3">
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-gray-900 truncate">
                {{ dl.videoTitle }}
              </p>
              <p
                class="text-[10px] mt-0.5"
                :class="statusColor(dl)"
              >
                {{ statusLabel(dl) }}
              </p>
              <div
                v-if="dl.status === 'queued' || dl.status === 'converting'"
                class="mt-1.5 h-1 bg-gray-100 rounded-full overflow-hidden"
              >
                <div
                  class="h-full bg-orange-500 transition-all duration-500"
                  :style="{ width: `${Math.max(5, dl.progress || 0)}%` }"
                />
              </div>
              <p
                v-if="dl.status === 'failed' && dl.errorMessage"
                class="text-[10px] text-red-500 mt-0.5 truncate"
                :title="dl.errorMessage"
              >
                {{ dl.errorMessage }}
              </p>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <button
                v-if="dl.status === 'ready'"
                @click="handleDownload(dl)"
                :disabled="busyIds.has(dl.downloadId)"
                class="px-2.5 py-1 text-[10px] font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-md transition-colors disabled:opacity-50"
              >
                {{ busyIds.has(dl.downloadId) ? '...' : 'Download' }}
              </button>
              <button
                @click="removeDownload(dl.downloadId)"
                class="p-1 text-gray-300 hover:text-gray-500 rounded-md transition-colors"
                title="Dismiss"
              >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script>
import { computed, onMounted, onUnmounted, reactive } from 'vue'
import { useDownloadProgress } from '@/composables/useDownloadProgress'

export default {
  name: 'DownloadTray',
  setup() {
    const {
      downloads,
      triggerFileDownload,
      removeDownload,
      clearAll,
      startTracking,
      stopTracking,
    } = useDownloadProgress()

    const busyIds = reactive(new Set())

    const visibleDownloads = computed(() =>
      downloads.value.filter((d) => d.status !== 'expired'),
    )

    const hasProcessing = computed(() =>
      visibleDownloads.value.some(
        (d) => d.status === 'queued' || d.status === 'converting',
      ),
    )

    const headerText = computed(() => {
      const processing = visibleDownloads.value.filter(
        (d) => d.status === 'queued' || d.status === 'converting',
      ).length
      const ready = visibleDownloads.value.filter(
        (d) => d.status === 'ready',
      ).length
      if (processing && ready) return `${processing} preparing, ${ready} ready`
      if (processing) return `Preparing ${processing} download${processing > 1 ? 's' : ''}`
      if (ready) return `${ready} download${ready > 1 ? 's' : ''} ready`
      return 'Downloads'
    })

    const statusLabel = (dl) => {
      if (dl.status === 'ready') return 'Ready to download'
      if (dl.status === 'failed') return 'Failed'
      if (dl.status === 'converting') return `Converting · ${dl.progress || 0}%`
      return 'Queued'
    }

    const statusColor = (dl) => {
      if (dl.status === 'ready') return 'text-emerald-600'
      if (dl.status === 'failed') return 'text-red-500'
      return 'text-orange-600'
    }

    const handleDownload = async (dl) => {
      if (busyIds.has(dl.downloadId)) return
      busyIds.add(dl.downloadId)
      try {
        await triggerFileDownload(dl)
      } catch (err) {
        console.error('Tray download failed:', err)
      } finally {
        busyIds.delete(dl.downloadId)
      }
    }

    onMounted(() => startTracking())
    onUnmounted(() => stopTracking())

    return {
      visibleDownloads,
      hasProcessing,
      headerText,
      statusLabel,
      statusColor,
      handleDownload,
      removeDownload,
      clearAll,
      busyIds,
    }
  },
}
</script>

<style scoped>
.tray-enter-active,
.tray-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}
.tray-enter-from,
.tray-leave-to {
  opacity: 0;
  transform: translateY(16px);
}
</style>
