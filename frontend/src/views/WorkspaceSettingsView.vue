<template>
  <div class="p-6 max-w-2xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
      <router-link :to="`/workspace/${slug}`" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </router-link>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Workspace Settings</h1>
        <p class="text-sm text-gray-500">{{ workspaceName }}</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-6 animate-pulse">
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="h-6 bg-gray-200 rounded w-32 mb-4"></div>
        <div class="space-y-4">
          <div class="h-10 bg-gray-200 rounded w-full"></div>
          <div class="h-20 bg-gray-200 rounded w-full"></div>
        </div>
      </div>
    </div>

    <div v-else-if="workspace">
      <!-- General Settings -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">General</h2>
        <p class="text-sm text-gray-500 mb-6">Basic workspace information</p>

        <div class="space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Workspace Name</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              :disabled="!canEdit"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all disabled:bg-gray-100 disabled:cursor-not-allowed"
            />
          </div>

          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              id="description"
              v-model="form.description"
              placeholder="What's this workspace for?"
              rows="3"
              :disabled="!canEdit"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all resize-none disabled:bg-gray-100 disabled:cursor-not-allowed"
            ></textarea>
          </div>

          <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">URL Slug</label>
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-500">/workspace/</span>
              <input
                id="slug"
                v-model="form.slug"
                type="text"
                :disabled="!canEdit"
                @input="sanitizeSlug"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all disabled:bg-gray-100 disabled:cursor-not-allowed"
              />
            </div>
            <p class="text-xs text-gray-500 mt-1">Only lowercase letters, numbers, and hyphens allowed.</p>
          </div>

          <button
            v-if="canEdit"
            @click="saveSettings"
            :disabled="saving || !hasChanges"
            class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>

      <!-- Your Plan -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Your Plan</h2>
        <p class="text-sm text-gray-500 mb-6">Your personal subscription status</p>

        <div class="flex items-center justify-between py-3 px-4 rounded-lg" :class="userSubscription?.is_active ? 'bg-green-50' : 'bg-gray-50'">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="userSubscription?.is_active ? 'bg-green-100' : 'bg-gray-200'">
              <svg v-if="userSubscription?.is_active" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ userSubscription?.is_active ? 'Pro Plan' : 'Free Plan' }}</p>
              <p class="text-sm text-gray-500">
                {{ userSubscription?.is_active ? 'You have access to all features' : 'Upgrade to unlock more features' }}
              </p>
            </div>
          </div>
          <router-link
            to="/subscription"
            class="text-sm font-medium text-orange-600 hover:text-orange-700"
          >
            {{ userSubscription?.is_active ? 'Manage' : 'Upgrade' }}
          </router-link>
        </div>
      </div>

      <!-- Workspace Plan -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Workspace Plan</h2>
        <p class="text-sm text-gray-500 mb-6">This workspace's subscription and limits</p>

        <div class="space-y-4">
          <div class="flex items-center justify-between py-2">
            <span class="text-gray-500">Plan</span>
            <span class="font-medium text-gray-900">
              {{ workspace.has_active_subscription
                ? (workspace.subscription_plan === 'team_plus' ? 'Team Plus' : 'Team')
                : 'Free' }}
            </span>
          </div>

          <div class="border-t border-gray-100"></div>

          <div class="flex items-center justify-between py-2">
            <span class="text-gray-500">Members</span>
            <span class="text-gray-900">{{ workspace.members_count }} / {{ workspace.max_members }}</span>
          </div>

          <div class="flex items-center justify-between py-2">
            <span class="text-gray-500">Storage</span>
            <span class="text-gray-900">{{ workspace.storage_used_gb?.toFixed(1) }} GB / {{ workspace.max_storage_gb }} GB</span>
          </div>

          <div class="flex items-center justify-between py-2">
            <span class="text-gray-500">Max Recording Length</span>
            <span class="text-gray-900">{{ workspace.max_recording_minutes }} min</span>
          </div>

          <div v-if="workspace.subscription_expires_at" class="border-t border-gray-100"></div>

          <div v-if="workspace.subscription_expires_at" class="flex items-center justify-between py-2">
            <span class="text-gray-500">{{ workspace.is_in_grace_period ? 'Expires' : 'Renews' }}</span>
            <span class="text-gray-900">{{ formatDate(workspace.subscription_expires_at) }}</span>
          </div>

          <button
            v-if="!workspace.has_active_subscription"
            class="w-full mt-4 px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors"
          >
            Upgrade to Team
          </button>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="bg-white rounded-xl border border-red-200 p-6">
        <h2 class="text-lg font-semibold text-red-600 mb-1">Danger Zone</h2>
        <p class="text-sm text-gray-500 mb-6">Irreversible actions</p>

        <!-- Leave Workspace (for non-owners) -->
        <div v-if="!workspace.is_owner" class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">Leave Workspace</p>
            <p class="text-sm text-gray-500">You will lose access to all workspace videos</p>
          </div>
          <button
            @click="showLeaveModal = true"
            class="px-4 py-2 text-sm font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
          >
            Leave
          </button>
        </div>

        <!-- Delete Workspace (for owners) -->
        <div v-else class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">Delete Workspace</p>
            <p class="text-sm text-gray-500">Permanently delete this workspace and all its data</p>
          </div>
          <button
            @click="showDeleteModal = true"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Leave Confirmation Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showLeaveModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
          @click.self="showLeaveModal = false"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-2">Leave Workspace</h2>
              <p class="text-sm text-gray-500 mb-6">
                Are you sure you want to leave "{{ workspaceName }}"? You will lose access to all workspace videos and will need a new invitation to rejoin.
              </p>

              <div class="flex justify-end gap-3">
                <button
                  @click="showLeaveModal = false"
                  class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="leaveWorkspace"
                  :disabled="leaving"
                  class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                >
                  {{ leaving ? 'Leaving...' : 'Leave Workspace' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showDeleteModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
          @click.self="showDeleteModal = false"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-2">Delete Workspace</h2>
              <p class="text-sm text-gray-500 mb-4">
                This action cannot be undone. All workspace data including videos will be permanently deleted.
              </p>
              <p class="text-sm text-gray-700 mb-4">
                Type <strong>{{ workspaceName }}</strong> to confirm.
              </p>

              <input
                v-model="deleteConfirmName"
                type="text"
                :placeholder="workspaceName"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none mb-6"
              />

              <div class="flex justify-end gap-3">
                <button
                  @click="showDeleteModal = false; deleteConfirmName = ''"
                  class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="deleteWorkspace"
                  :disabled="deleteConfirmName !== workspaceName || deleting"
                  class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ deleting ? 'Deleting...' : 'Delete Workspace' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import workspaceService from '@/services/workspaceService'
import { useAuth } from '@/stores/auth'

export default {
  name: 'WorkspaceSettingsView',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const auth = useAuth()
    const slug = route.params.slug

    const workspace = ref(null)
    const loading = ref(true)
    const userSubscription = computed(() => auth.subscription.value)
    const saving = ref(false)
    const leaving = ref(false)
    const deleting = ref(false)
    const showLeaveModal = ref(false)
    const showDeleteModal = ref(false)
    const deleteConfirmName = ref('')

    const form = ref({
      name: '',
      description: '',
      slug: ''
    })

    const workspaceName = computed(() => workspace.value?.name || '')
    const canEdit = computed(() => workspace.value?.is_owner)

    const hasChanges = computed(() => {
      if (!workspace.value) return false
      return (
        form.value.name !== workspace.value.name ||
        form.value.description !== (workspace.value.description || '') ||
        form.value.slug !== workspace.value.slug
      )
    })

    const fetchWorkspace = async () => {
      loading.value = true
      try {
        workspace.value = await workspaceService.getWorkspace(slug)
        form.value = {
          name: workspace.value.name,
          description: workspace.value.description || '',
          slug: workspace.value.slug
        }
      } catch (err) {
        console.error('Failed to fetch workspace:', err)
      } finally {
        loading.value = false
      }
    }

    const sanitizeSlug = () => {
      form.value.slug = form.value.slug.toLowerCase().replace(/[^a-z0-9-]/g, '')
    }

    const saveSettings = async () => {
      if (!hasChanges.value) return

      saving.value = true
      try {
        const updates = {}
        if (form.value.name !== workspace.value.name) updates.name = form.value.name
        if (form.value.description !== (workspace.value.description || '')) updates.description = form.value.description
        if (form.value.slug !== workspace.value.slug) updates.slug = form.value.slug

        const result = await workspaceService.updateWorkspace(slug, updates)

        // If slug changed, navigate to new URL
        if (updates.slug && result.workspace.slug !== slug) {
          router.replace(`/workspace/${result.workspace.slug}/settings`)
        }

        workspace.value = result.workspace
        alert('Settings saved successfully')
      } catch (err) {
        alert(err.message || 'Failed to save settings')
      } finally {
        saving.value = false
      }
    }

    const leaveWorkspace = async () => {
      leaving.value = true
      try {
        await workspaceService.leaveWorkspace(slug)
        router.push('/workspaces')
      } catch (err) {
        alert(err.message || 'Failed to leave workspace')
      } finally {
        leaving.value = false
      }
    }

    const deleteWorkspace = async () => {
      if (deleteConfirmName.value !== workspaceName.value) return

      deleting.value = true
      try {
        await workspaceService.deleteWorkspace(slug)
        router.push('/workspaces')
      } catch (err) {
        alert(err.message || 'Failed to delete workspace')
      } finally {
        deleting.value = false
      }
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
      })
    }

    onMounted(async () => {
      await Promise.all([
        fetchWorkspace(),
        auth.fetchSubscription()
      ])
    })

    return {
      slug,
      workspace,
      workspaceName,
      loading,
      saving,
      leaving,
      deleting,
      form,
      canEdit,
      hasChanges,
      showLeaveModal,
      showDeleteModal,
      deleteConfirmName,
      userSubscription,
      sanitizeSlug,
      saveSettings,
      leaveWorkspace,
      deleteWorkspace,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-content-enter-active {
  transition: all 0.2s ease-out;
}

.modal-content-leave-active {
  transition: all 0.15s ease-in;
}

.modal-content-enter-from {
  opacity: 0;
  transform: scale(0.95) translateY(-10px);
}

.modal-content-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-10px);
}
</style>
