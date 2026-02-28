<template>
  <div v-if="integrations.length > 0" class="border-t border-gray-100 pt-4 mt-4">
    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Share to Integration</h4>

    <!-- Connected Integrations -->
    <div class="space-y-2">
      <button
        v-for="integration in integrations"
        :key="integration.id"
        @click="openShareModal(integration)"
        class="w-full flex items-center gap-3 p-2.5 rounded-lg border border-gray-100 hover:border-orange-200 hover:bg-orange-50/50 transition-all group text-left"
      >
        <div class="w-7 h-7 rounded-md flex items-center justify-center" :class="getProviderBgColor(integration.id)">
          <!-- Jira -->
          <svg v-if="integration.id === 'jira'" class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
            <path d="M11.571 11.513H0a5.218 5.218 0 0 0 5.232 5.215h2.13v2.057A5.215 5.215 0 0 0 12.575 24V12.518a1.005 1.005 0 0 0-1.005-1.005zm5.723-5.756H5.736a5.215 5.215 0 0 0 5.215 5.214h2.129v2.058a5.218 5.218 0 0 0 5.215 5.214V6.758a1.001 1.001 0 0 0-1.001-1.001zM23.013 0H11.455a5.215 5.215 0 0 0 5.215 5.215h2.129v2.057A5.215 5.215 0 0 0 24.013 12.487V1.005A1.005 1.005 0 0 0 23.013 0z"/>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <span class="text-xs font-medium text-gray-700 group-hover:text-orange-700">{{ integration.name }}</span>
        </div>
        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
        </svg>
      </button>
    </div>

    <!-- Share History -->
    <div v-if="history.length > 0" class="mt-4">
      <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Recent Shares</h4>
      <div class="space-y-1.5">
        <div
          v-for="item in history.slice(0, 5)"
          :key="item.id"
          class="flex items-center gap-2 text-xs text-gray-500 py-1"
        >
          <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="item.status === 'completed' ? 'bg-green-400' : item.status === 'failed' ? 'bg-red-400' : 'bg-yellow-400'"></span>
          <span class="font-medium text-gray-600 capitalize">{{ formatProviderName(item.provider) }}</span>
          <span v-if="item.target_name" class="truncate">{{ item.target_name }}</span>
          <span class="ml-auto text-gray-400 flex-shrink-0">{{ formatTime(item.created_at) }}</span>
          <a
            v-if="item.external_url"
            :href="item.external_url"
            target="_blank"
            class="text-orange-500 hover:text-orange-600 flex-shrink-0"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
          </a>
        </div>
      </div>
    </div>

    <!-- Share Modal -->
    <div v-if="showShareModal" class="fixed inset-0 z-[70] flex items-center justify-center">
      <div class="absolute inset-0 bg-gray-900/20 backdrop-blur-sm" @click="closeShareModal"></div>
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm relative z-10 border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
          <h3 class="text-sm font-semibold text-gray-900">Share to {{ selectedProvider?.name }}</h3>
          <button @click="closeShareModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="p-5 space-y-4">
          <!-- Target Selector -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">
              {{ getTargetLabel(selectedProvider?.id) }}
            </label>
            <div v-if="loadingTargets" class="flex items-center gap-2 text-xs text-gray-500 py-2">
              <div class="w-4 h-4 border-2 border-gray-300 border-t-transparent rounded-full animate-spin"></div>
              Loading...
            </div>
            <select
              v-else
              v-model="shareForm.target_id"
              class="w-full rounded-lg border border-gray-200 text-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
            >
              <option value="" disabled>Select...</option>
              <option v-for="target in targets" :key="target.id" :value="target.id">
                {{ target.name }}
              </option>
            </select>
          </div>

          <!-- Message -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Message (optional)</label>
            <textarea
              v-model="shareForm.message"
              rows="3"
              maxlength="1000"
              class="w-full rounded-lg border border-gray-200 text-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none"
              placeholder="Add a message..."
            ></textarea>
          </div>

          <!-- Send Button -->
          <button
            @click="executeShare"
            :disabled="!shareForm.target_id || sharing"
            class="w-full px-4 py-2.5 text-sm font-semibold rounded-lg bg-orange-600 text-white hover:bg-orange-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <div v-if="sharing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
            {{ sharing ? 'Sharing...' : 'Share' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue'
import integrationService from '@/services/integrationService'
import { useToast } from '@/services/toastService'
import { formatDistanceToNow } from 'date-fns'

export default {
  name: 'VideoShareIntegrations',
  props: {
    videoId: {
      type: [Number, String],
      required: true
    }
  },
  setup(props) {
    const toast = useToast()
    const integrations = ref([])
    const history = ref([])
    const showShareModal = ref(false)
    const selectedProvider = ref(null)
    const targets = ref([])
    const loadingTargets = ref(false)
    const sharing = ref(false)
    const shareForm = ref({
      target_id: '',
      target_name: '',
      message: ''
    })

    const fetchIntegrations = async () => {
      try {
        const data = await integrationService.getAvailableProviders()
        if (data) {
          integrations.value = data.filter(p => p.connected)
        }
      } catch {
        // Silently fail - component is optional
      }
    }

    const fetchHistory = async () => {
      try {
        const data = await integrationService.getShareHistory(props.videoId)
        if (data) {
          history.value = data
        }
      } catch {
        // Silently fail
      }
    }

    const openShareModal = async (provider) => {
      selectedProvider.value = provider
      showShareModal.value = true
      shareForm.value = { target_id: '', target_name: '', message: '' }

      try {
        loadingTargets.value = true
        const data = await integrationService.getTargets(provider.id)
        targets.value = data || []
      } catch (error) {
        toast.error('Failed to load targets')
        targets.value = []
      } finally {
        loadingTargets.value = false
      }
    }

    const closeShareModal = () => {
      showShareModal.value = false
      selectedProvider.value = null
      targets.value = []
    }

    const executeShare = async () => {
      if (!selectedProvider.value || !shareForm.value.target_id) return

      // Set target_name from selected target
      const target = targets.value.find(t => t.id === shareForm.value.target_id)
      shareForm.value.target_name = target?.name || ''

      try {
        sharing.value = true
        const result = await integrationService.shareVideo(
          selectedProvider.value.id,
          props.videoId,
          shareForm.value
        )

        if (result?.success) {
          toast.success(`Shared to ${selectedProvider.value.name}!`)
          closeShareModal()
          fetchHistory()
        } else {
          toast.error(result?.error || 'Share failed')
        }
      } catch (error) {
        toast.error(error.message || 'Failed to share')
      } finally {
        sharing.value = false
      }
    }

    const getProviderBgColor = (id) => {
      const colors = {
        jira: 'bg-blue-100',
      }
      return colors[id] || 'bg-gray-100'
    }

    const getTargetLabel = (providerId) => {
      const labels = {
        jira: 'Project',
      }
      return labels[providerId] || 'Target'
    }

    const formatProviderName = (provider) => {
      const names = {
        jira: 'Jira',
      }
      return names[provider] || provider
    }

    const formatTime = (dateStr) => {
      try {
        return formatDistanceToNow(new Date(dateStr), { addSuffix: true })
      } catch {
        return ''
      }
    }

    onMounted(() => {
      fetchIntegrations()
      fetchHistory()
    })

    watch(() => props.videoId, () => {
      fetchHistory()
    })

    return {
      integrations,
      history,
      showShareModal,
      selectedProvider,
      targets,
      loadingTargets,
      sharing,
      shareForm,
      openShareModal,
      closeShareModal,
      executeShare,
      getProviderBgColor,
      getTargetLabel,
      formatProviderName,
      formatTime,
    }
  }
}
</script>
