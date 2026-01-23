<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 text-center animate-pulse">
      <div class="w-16 h-16 rounded-lg bg-gray-200 mx-auto mb-4"></div>
      <div class="h-6 bg-gray-200 rounded w-48 mx-auto mb-2"></div>
      <div class="h-4 bg-gray-200 rounded w-64 mx-auto"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 text-center">
      <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </div>
      <h2 class="text-xl font-semibold text-gray-900 mb-2">Invitation Not Found</h2>
      <p class="text-gray-500 mb-6">This invitation may have expired or already been used.</p>
      <router-link
        to="/workspaces"
        class="inline-block w-full px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors text-center"
      >
        Go to Workspaces
      </router-link>
    </div>

    <!-- Invitation Card -->
    <div v-else-if="invitation" class="bg-white rounded-xl shadow-lg max-w-md w-full p-6">
      <!-- Workspace Logo/Icon -->
      <div class="text-center mb-6">
        <div v-if="invitation.workspace.logo_url" class="w-16 h-16 rounded-lg overflow-hidden mx-auto mb-4">
          <img :src="invitation.workspace.logo_url" :alt="invitation.workspace.name" class="w-full h-full object-cover" />
        </div>
        <div v-else class="w-16 h-16 rounded-lg bg-orange-100 flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-900">Join {{ invitation.workspace.name }}</h2>
        <p class="text-sm text-gray-500 mt-1">You've been invited to collaborate on videos</p>
      </div>

      <!-- Inviter Info -->
      <div class="flex items-center justify-center gap-3 p-4 bg-gray-50 rounded-lg mb-6">
        <img
          v-if="invitation.invited_by.avatar_url"
          :src="invitation.invited_by.avatar_url"
          :alt="invitation.invited_by.name"
          class="w-10 h-10 rounded-full object-cover"
        />
        <div v-else class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
          <span class="text-sm font-medium text-orange-600">{{ getInitials(invitation.invited_by.name) }}</span>
        </div>
        <div class="text-sm">
          <p>
            <span class="font-medium text-gray-900">{{ invitation.invited_by.name }}</span>
            <span class="text-gray-500"> invited you</span>
          </p>
          <p class="text-gray-500">to join as {{ invitation.role }}</p>
        </div>
      </div>

      <!-- Workspace Info -->
      <div class="space-y-3 mb-6">
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-500">Your role</span>
          <span class="px-2 py-1 font-medium text-orange-700 bg-orange-100 rounded-full capitalize">
            {{ invitation.role }}
          </span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-500">Team members</span>
          <div class="flex items-center gap-1 text-gray-900">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span>{{ invitation.workspace.members_count }}</span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-3 mb-4">
        <button
          @click="declineInvitation"
          class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          Decline
        </button>
        <button
          @click="acceptInvitation"
          :disabled="accepting"
          class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50"
        >
          <template v-if="accepting">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Joining...
          </template>
          <template v-else>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Accept Invitation
          </template>
        </button>
      </div>

      <p class="text-xs text-center text-gray-500">
        This invitation expires on {{ formatDate(invitation.expires_at) }}
      </p>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import workspaceService from '@/services/workspaceService'

export default {
  name: 'AcceptInvitationView',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const token = route.params.token

    const invitation = ref(null)
    const loading = ref(true)
    const error = ref(false)
    const accepting = ref(false)

    const fetchInvitation = async () => {
      loading.value = true
      error.value = false
      try {
        invitation.value = await workspaceService.getInvitationByToken(token)
      } catch (err) {
        console.error('Failed to fetch invitation:', err)
        error.value = true
      } finally {
        loading.value = false
      }
    }

    const acceptInvitation = async () => {
      accepting.value = true
      try {
        const result = await workspaceService.acceptInvitation(token)
        router.push(`/workspace/${result.workspace.slug}`)
      } catch (err) {
        alert(err.message || 'Failed to accept invitation')
      } finally {
        accepting.value = false
      }
    }

    const declineInvitation = () => {
      router.push('/workspaces')
    }

    const getInitials = (name) => {
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
      })
    }

    onMounted(fetchInvitation)

    return {
      invitation,
      loading,
      error,
      accepting,
      acceptInvitation,
      declineInvitation,
      getInitials,
      formatDate
    }
  }
}
</script>
