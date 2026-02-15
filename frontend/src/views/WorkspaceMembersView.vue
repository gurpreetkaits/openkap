<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-4">
        <router-link :to="`/workspace/${slug}`" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </router-link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Team Members</h1>
          <p class="text-sm text-gray-500">{{ workspaceName }}</p>
        </div>
      </div>
      <!-- Member Quota Badge -->
      <div v-if="workspace" class="flex items-center gap-2 text-sm">
        <span class="text-gray-500">{{ workspace.members_count }}/{{ workspace.max_members }} members</span>
        <span
          v-if="workspace.remaining_slots <= 0"
          class="px-2 py-1 text-xs font-medium text-amber-700 bg-amber-100 rounded-full"
        >
          Limit Reached
        </span>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 border-b border-gray-100">
      <button
        @click="activeTab = 'members'"
        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
        :class="activeTab === 'members' ? 'text-orange-600 border-orange-600' : 'text-gray-500 border-transparent hover:text-gray-700'"
      >
        Members ({{ members.length }})
      </button>
      <button
        @click="activeTab = 'invitations'"
        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
        :class="activeTab === 'invitations' ? 'text-orange-600 border-orange-600' : 'text-gray-500 border-transparent hover:text-gray-700'"
      >
        Pending Invitations ({{ invitations.length }})
      </button>
    </div>

    <!-- Members Tab -->
    <div v-if="activeTab === 'members'">
      <!-- Invite Button -->
      <div class="flex justify-end mb-4">
        <button
          @click="showInviteModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
          </svg>
          Invite Member
        </button>
      </div>

      <!-- Members List -->
      <div class="bg-white rounded-xl border border-gray-100 divide-y divide-gray-200">
        <div v-if="loading" class="p-4 animate-pulse">
          <div v-for="i in 3" :key="i" class="flex items-center gap-4 py-3">
            <div class="w-10 h-10 rounded-full bg-gray-200"></div>
            <div class="flex-1">
              <div class="h-4 bg-gray-200 rounded w-32 mb-2"></div>
              <div class="h-3 bg-gray-200 rounded w-48"></div>
            </div>
          </div>
        </div>

        <div v-else-if="members.length === 0" class="p-8 text-center text-gray-500">
          No members found.
        </div>

        <div
          v-else
          v-for="member in members"
          :key="member.user_id"
          class="flex items-center justify-between p-4 hover:bg-gray-50"
        >
          <div class="flex items-center gap-3">
            <img
              v-if="member.avatar_url"
              :src="member.avatar_url"
              :alt="member.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <div v-else class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
              <span class="text-sm font-medium text-orange-600">{{ getInitials(member.name) }}</span>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ member.name }}</p>
              <p class="text-sm text-gray-500">{{ member.email }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <span
              class="px-2 py-1 text-xs font-medium rounded-full capitalize"
              :class="getRoleBadgeClass(member.role)"
            >
              {{ member.role }}
            </span>

            <div v-if="canManageMember(member)" class="relative">
              <button
                @click="toggleMemberMenu(member.user_id)"
                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                </svg>
              </button>

              <div
                v-if="openMemberMenu === member.user_id"
                class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-10"
              >
                <button
                  v-if="member.role !== 'admin'"
                  @click="updateRole(member.user_id, 'admin')"
                  class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                >
                  Make Admin
                </button>
                <button
                  v-if="member.role === 'admin'"
                  @click="updateRole(member.user_id, 'member')"
                  class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                >
                  Remove Admin
                </button>
                <button
                  @click="confirmRemoveMember(member)"
                  class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                >
                  Remove from workspace
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Invitations Tab -->
    <div v-if="activeTab === 'invitations'">
      <div class="bg-white rounded-xl border border-gray-100 divide-y divide-gray-200">
        <div v-if="invitationsLoading" class="p-4 animate-pulse">
          <div v-for="i in 2" :key="i" class="flex items-center gap-4 py-3">
            <div class="flex-1">
              <div class="h-4 bg-gray-200 rounded w-48 mb-2"></div>
              <div class="h-3 bg-gray-200 rounded w-32"></div>
            </div>
          </div>
        </div>

        <div v-else-if="invitations.length === 0" class="p-8 text-center text-gray-500">
          No pending invitations.
        </div>

        <div
          v-else
          v-for="invitation in invitations"
          :key="invitation.id"
          class="flex items-center justify-between p-4 hover:bg-gray-50"
        >
          <div>
            <p class="font-medium text-gray-900">{{ invitation.email }}</p>
            <p class="text-sm text-gray-500">
              Invited as {{ invitation.role }} &middot; Expires {{ formatDate(invitation.expires_at) }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="resendInvitation(invitation.id)"
              class="px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              Resend
            </button>
            <button
              @click="cancelInvitation(invitation.id)"
              class="px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Invite Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showInviteModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
          @click.self="showInviteModal = false"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-1">Invite Team Member</h2>
              <p class="text-sm text-gray-500 mb-6">Send an invitation to join your workspace.</p>

              <!-- Limit Warning -->
              <div
                v-if="workspace && workspace.remaining_slots <= 0"
                class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg"
              >
                <p class="text-sm text-amber-800">
                  This workspace has reached its member limit. Upgrade to add more members.
                </p>
              </div>

              <div class="space-y-4">
                <div>
                  <label for="invite-email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                  <input
                    id="invite-email"
                    v-model="inviteForm.email"
                    type="email"
                    placeholder="colleague@company.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                  />
                  <p v-if="inviteError" class="mt-1 text-sm text-red-600">{{ inviteError }}</p>
                </div>
                <div>
                  <label for="invite-role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                  <select
                    id="invite-role"
                    v-model="inviteForm.role"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                  >
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>
              </div>

              <div class="flex justify-end gap-3 mt-6">
                <button
                  @click="showInviteModal = false"
                  class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="sendInvitation"
                  :disabled="!inviteForm.email || inviting"
                  class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ inviting ? 'Sending...' : 'Send Invitation' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- Remove Member Confirmation -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="memberToRemove"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
          @click.self="memberToRemove = null"
        >
          <Transition name="modal-content" appear>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-2">Remove Member</h2>
              <p class="text-sm text-gray-500 mb-6">
                Are you sure you want to remove <strong>{{ memberToRemove.name }}</strong> from this workspace?
                They will lose access to all workspace videos.
              </p>

              <div class="flex justify-end gap-3">
                <button
                  @click="memberToRemove = null"
                  class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="removeMember"
                  :disabled="removing"
                  class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                >
                  {{ removing ? 'Removing...' : 'Remove Member' }}
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
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import workspaceService from '@/services/workspaceService'

export default {
  name: 'WorkspaceMembersView',
  setup() {
    const route = useRoute()
    const slug = route.params.slug

    const workspace = ref(null)
    const workspaceName = ref('')
    const members = ref([])
    const invitations = ref([])
    const loading = ref(true)
    const invitationsLoading = ref(true)
    const activeTab = ref('members')
    const showInviteModal = ref(false)
    const inviting = ref(false)
    const inviteError = ref('')
    const openMemberMenu = ref(null)
    const memberToRemove = ref(null)
    const removing = ref(false)
    const currentUserRole = ref('')

    const inviteForm = ref({
      email: '',
      role: 'member'
    })

    const fetchWorkspace = async () => {
      try {
        workspace.value = await workspaceService.getWorkspace(slug)
        workspaceName.value = workspace.value.name
        currentUserRole.value = workspace.value.user_role
      } catch (err) {
        console.error('Failed to fetch workspace:', err)
      }
    }

    const fetchMembers = async () => {
      loading.value = true
      try {
        members.value = await workspaceService.getMembers(slug)
      } catch (err) {
        console.error('Failed to fetch members:', err)
      } finally {
        loading.value = false
      }
    }

    const fetchInvitations = async () => {
      invitationsLoading.value = true
      try {
        invitations.value = await workspaceService.getInvitations(slug)
      } catch (err) {
        console.error('Failed to fetch invitations:', err)
      } finally {
        invitationsLoading.value = false
      }
    }

    const sendInvitation = async () => {
      if (!inviteForm.value.email) return

      inviting.value = true
      inviteError.value = ''
      try {
        await workspaceService.inviteMember(slug, {
          email: inviteForm.value.email,
          role: inviteForm.value.role
        })
        showInviteModal.value = false
        inviteForm.value = { email: '', role: 'member' }
        fetchInvitations()
        fetchWorkspace() // Refresh member count
      } catch (err) {
        inviteError.value = err.message || 'Failed to send invitation'
      } finally {
        inviting.value = false
      }
    }

    const updateRole = async (userId, role) => {
      openMemberMenu.value = null
      try {
        await workspaceService.updateMemberRole(slug, userId, role)
        fetchMembers()
      } catch (err) {
        alert(err.message || 'Failed to update role')
      }
    }

    const confirmRemoveMember = (member) => {
      openMemberMenu.value = null
      memberToRemove.value = member
    }

    const removeMember = async () => {
      if (!memberToRemove.value) return

      removing.value = true
      try {
        await workspaceService.removeMember(slug, memberToRemove.value.user_id)
        memberToRemove.value = null
        fetchMembers()
      } catch (err) {
        alert(err.message || 'Failed to remove member')
      } finally {
        removing.value = false
      }
    }

    const cancelInvitation = async (invitationId) => {
      try {
        await workspaceService.cancelInvitation(slug, invitationId)
        fetchInvitations()
      } catch (err) {
        alert(err.message || 'Failed to cancel invitation')
      }
    }

    const resendInvitation = async (invitationId) => {
      try {
        await workspaceService.resendInvitation(slug, invitationId)
        alert('Invitation resent successfully')
      } catch (err) {
        alert(err.message || 'Failed to resend invitation')
      }
    }

    const toggleMemberMenu = (userId) => {
      openMemberMenu.value = openMemberMenu.value === userId ? null : userId
    }

    const canManageMember = (member) => {
      if (member.role === 'owner') return false
      return currentUserRole.value === 'owner' || currentUserRole.value === 'admin'
    }

    const getInitials = (name) => {
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
    }

    const getRoleBadgeClass = (role) => {
      switch (role) {
        case 'owner': return 'bg-orange-100 text-orange-700'
        case 'admin': return 'bg-blue-100 text-blue-700'
        default: return 'bg-gray-100 text-gray-700'
      }
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })
    }

    // Close menu when clicking outside
    const handleClickOutside = (e) => {
      if (!e.target.closest('.relative')) {
        openMemberMenu.value = null
      }
    }

    onMounted(() => {
      fetchWorkspace()
      fetchMembers()
      fetchInvitations()
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      slug,
      workspace,
      workspaceName,
      members,
      invitations,
      loading,
      invitationsLoading,
      activeTab,
      showInviteModal,
      inviting,
      inviteError,
      inviteForm,
      openMemberMenu,
      memberToRemove,
      removing,
      sendInvitation,
      updateRole,
      confirmRemoveMember,
      removeMember,
      cancelInvitation,
      resendInvitation,
      toggleMemberMenu,
      canManageMember,
      getInitials,
      getRoleBadgeClass,
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
