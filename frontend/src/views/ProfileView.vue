<template>
  <div class="animate-fade-in max-w-2xl mx-auto py-6 px-4">
    <!-- Loading State -->
    <div v-if="loading" class="card bg-base-100 border border-base-300 shadow-sm p-8 text-center">
      <span class="loading loading-spinner loading-md text-primary mx-auto"></span>
      <p class="text-sm text-base-content/60 mt-2">Loading profile...</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Profile Card -->
      <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
        <!-- Header with gradient background -->
        <div class="bg-gradient-to-r from-primary to-primary-focus h-24 relative">
          <!-- Avatar positioned at bottom of header -->
          <div class="absolute -bottom-12 left-6">
            <div v-if="user?.avatar" class="avatar">
              <div class="w-24 rounded-xl ring ring-base-100 ring-offset-base-100 ring-offset-2 shadow-lg">
                <img :src="user.avatar" :alt="user.name" />
              </div>
            </div>
            <div v-else class="avatar placeholder">
              <div class="w-24 rounded-xl bg-neutral text-neutral-content ring ring-base-100 ring-offset-base-100 ring-offset-2 shadow-lg">
                <span class="text-3xl font-bold">{{ userInitial }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Profile Info -->
        <div class="card-body pt-16">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
              <h2 class="card-title text-xl">{{ user?.name || 'User' }}</h2>
              <p class="text-sm text-base-content/60 mt-0.5">{{ user?.email }}</p>
              <div class="flex items-center gap-2 mt-3">
                <span class="badge badge-success badge-outline gap-1">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  Verified
                </span>
                <span class="badge badge-ghost gap-1">
                  <svg class="w-3 h-3" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                  </svg>
                  Google
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Account Details -->
      <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-base-200">
          <h3 class="text-sm font-semibold text-base-content">Account Details</h3>
        </div>
        <div class="divide-y divide-base-200">
          <div class="px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-base-content/60">Full name</span>
            <span class="text-sm font-medium text-base-content">{{ user?.name || '-' }}</span>
          </div>
          <div class="px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-base-content/60">Email address</span>
            <span class="text-sm font-medium text-base-content">{{ user?.email || '-' }}</span>
          </div>
          <div class="px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-base-content/60">Member since</span>
            <span class="text-sm font-medium text-base-content">{{ memberSince }}</span>
          </div>
          <div class="px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-base-content/60">Videos created</span>
            <span class="text-sm font-medium text-base-content">{{ subscription?.videos_count || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Account Actions -->
      <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-base-200">
          <h3 class="text-sm font-semibold text-base-content">Account Actions</h3>
        </div>
        <div class="divide-y divide-base-200">
          <!-- Export Data -->
          <div class="p-4 flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-base-content">Export Data</p>
              <p class="text-xs text-base-content/60 mt-0.5">Download all your recordings and data</p>
            </div>
            <button
              @click="exportData"
              class="btn btn-sm btn-ghost"
            >
              Export
            </button>
          </div>

          <!-- Delete Account -->
          <div class="p-4 flex items-center justify-between bg-error/5">
            <div>
              <p class="text-sm font-medium text-error">Delete Account</p>
              <p class="text-xs text-error/70 mt-0.5">Permanently delete your account and all data</p>
            </div>
            <button
              @click="deleteAccount"
              class="btn btn-sm btn-error btn-outline"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Account Modal -->
    <SBDeleteModal
      v-model="showDeleteAccountModal"
      title="Delete Account"
      message="Are you sure you want to delete your account? This action cannot be undone. All your videos and data will be permanently deleted."
      confirm-text="Delete Account"
      :loading="isDeletingAccount"
      @confirm="confirmDeleteAccount"
      @cancel="showDeleteAccountModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '@/stores/auth'
import toast from '@/services/toastService'
import SBDeleteModal from '@/components/Global/SBDeleteModal.vue'

const auth = useAuth()
const loading = ref(true)

// Get user from auth store
const user = computed(() => auth.user.value)
const subscription = computed(() => auth.subscription.value)

// Get user initial for fallback avatar
const userInitial = computed(() => {
  const name = user.value?.name || ''
  return name.charAt(0).toUpperCase() || 'U'
})

// Format member since date
const memberSince = computed(() => {
  if (!user.value?.created_at) return '-'
  try {
    const date = new Date(user.value.created_at)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch {
    return '-'
  }
})

// Delete account modal state
const showDeleteAccountModal = ref(false)
const isDeletingAccount = ref(false)

onMounted(async () => {
  try {
    await auth.fetchUser()
    await auth.fetchSubscription()
  } catch (error) {
    console.error('Failed to fetch user:', error)
  } finally {
    loading.value = false
  }
})

const exportData = () => {
  toast.info('Data export will be available for download shortly')
}

const deleteAccount = () => {
  showDeleteAccountModal.value = true
}

const confirmDeleteAccount = () => {
  isDeletingAccount.value = true
  setTimeout(() => {
    toast.warning('Account deletion functionality coming soon')
    showDeleteAccountModal.value = false
    isDeletingAccount.value = false
  }, 1000)
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
