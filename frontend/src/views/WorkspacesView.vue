<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Workspaces</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your team workspaces and collaborate on videos</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- User Plan Badge -->
        <span
          v-if="subscription"
          class="px-3 py-1.5 text-xs font-medium rounded-full"
          :class="subscription.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
        >
          {{ subscription.is_active ? 'Pro Plan' : 'Free Plan' }}
        </span>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          New Workspace
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="i in 3" :key="i" class="bg-white rounded-xl border border-gray-100 p-5 animate-pulse">
        <div class="flex items-start gap-3 mb-4">
          <div class="w-10 h-10 rounded-lg bg-gray-200"></div>
          <div class="flex-1">
            <div class="h-5 bg-gray-200 rounded w-32 mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-20"></div>
          </div>
        </div>
        <div class="h-4 bg-gray-200 rounded w-48"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="workspaces.length === 0" class="bg-white rounded-xl border border-gray-100 p-12 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">No workspaces yet</h3>
      <p class="text-gray-500 mb-6">Create your first workspace to start collaborating with your team.</p>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Create Workspace
      </button>
    </div>

    <!-- Workspaces Grid -->
    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <router-link
        v-for="workspace in workspaces"
        :key="workspace.id"
        :to="`/workspace/${workspace.slug}`"
        class="bg-white rounded-xl border border-gray-100 p-5 hover:shadow-sm hover:border-gray-300 transition-all group"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div v-if="workspace.logo_url" class="w-10 h-10 rounded-lg overflow-hidden">
              <img :src="workspace.logo_url" :alt="workspace.name" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
              <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 group-hover:text-orange-600 transition-colors">{{ workspace.name }}</h3>
              <span
                class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full capitalize"
                :class="getRoleBadgeClass(workspace.user_role)"
              >
                <component :is="getRoleIcon(workspace.user_role)" class="w-3 h-3" />
                {{ workspace.user_role }}
              </span>
            </div>
          </div>
          <span
            v-if="workspace.has_active_subscription"
            class="px-2 py-1 text-xs font-medium text-orange-700 bg-orange-100 rounded-full"
          >
            {{ workspace.subscription_plan === 'team_plus' ? 'Team Plus' : 'Team' }}
          </span>
        </div>

        <div class="flex items-center gap-4 text-sm text-gray-500">
          <div class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span>{{ workspace.members_count }} members</span>
          </div>
          <div class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <span>{{ workspace.videos_count }} videos</span>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Create Workspace Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showCreateModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
          @click.self="showCreateModal = false"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-1">Create Workspace</h2>
              <p class="text-sm text-gray-500 mb-6">Create a new workspace to collaborate with your team.</p>

              <div class="space-y-4">
                <div>
                  <label for="workspace-name" class="block text-sm font-medium text-gray-700 mb-1">Workspace Name</label>
                  <input
                    id="workspace-name"
                    v-model="newWorkspace.name"
                    type="text"
                    placeholder="My Team"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                  />
                  <p v-if="createError" class="mt-1 text-sm text-red-600">{{ createError }}</p>
                </div>
                <div>
                  <label for="workspace-description" class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                  <textarea
                    id="workspace-description"
                    v-model="newWorkspace.description"
                    placeholder="What's this workspace for?"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all resize-none"
                  ></textarea>
                </div>
              </div>

              <div class="flex justify-end gap-3 mt-6">
                <button
                  @click="showCreateModal = false"
                  class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="createWorkspace"
                  :disabled="!newWorkspace.name.trim() || creating"
                  class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ creating ? 'Creating...' : 'Create Workspace' }}
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
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import workspaceService from '@/services/workspaceService'
import { useAuth } from '@/stores/auth'

export default {
  name: 'WorkspacesView',
  setup() {
    const router = useRouter()
    const auth = useAuth()
    const workspaces = ref([])
    const loading = ref(true)
    const showCreateModal = ref(false)
    const creating = ref(false)
    const createError = ref('')
    const newWorkspace = ref({
      name: '',
      description: ''
    })

    const subscription = computed(() => auth.subscription.value)

    const fetchWorkspaces = async () => {
      loading.value = true
      try {
        workspaces.value = await workspaceService.getWorkspaces()
      } catch (error) {
        console.error('Failed to fetch workspaces:', error)
      } finally {
        loading.value = false
      }
    }

    const createWorkspace = async () => {
      if (!newWorkspace.value.name.trim()) return

      creating.value = true
      createError.value = ''
      try {
        const result = await workspaceService.createWorkspace({
          name: newWorkspace.value.name.trim(),
          description: newWorkspace.value.description.trim() || undefined
        })
        showCreateModal.value = false
        newWorkspace.value = { name: '', description: '' }
        router.push(`/workspace/${result.workspace.slug}`)
      } catch (error) {
        console.error('Failed to create workspace:', error)
        createError.value = error.message || 'Failed to create workspace'
      } finally {
        creating.value = false
      }
    }

    const getRoleBadgeClass = (role) => {
      switch (role) {
        case 'owner':
          return 'bg-orange-100 text-orange-700'
        case 'admin':
          return 'bg-blue-100 text-blue-700'
        default:
          return 'bg-gray-100 text-gray-700'
      }
    }

    const getRoleIcon = (role) => {
      // Return icon component name based on role
      return 'span' // Simplified - icons are rendered inline
    }

    onMounted(async () => {
      await Promise.all([
        fetchWorkspaces(),
        auth.fetchSubscription()
      ])
    })

    return {
      workspaces,
      loading,
      showCreateModal,
      creating,
      createError,
      newWorkspace,
      subscription,
      createWorkspace,
      getRoleBadgeClass,
      getRoleIcon
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
