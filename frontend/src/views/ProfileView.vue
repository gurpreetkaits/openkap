<template>
  <div class="animate-fade-in">
    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-xl border border-gray-100 p-8 text-center">
      <div class="animate-spin w-6 h-6 border-2 border-orange-500 border-t-transparent rounded-full mx-auto"></div>
      <p class="text-sm text-gray-500 mt-2">Loading profile...</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Profile Card -->
      <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <!-- Header with gradient background -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-24 relative">
          <!-- Avatar positioned at bottom of header -->
          <div class="absolute -bottom-12 left-6">
            <img
              v-if="user?.avatar"
              :src="user.avatar"
              :alt="user.name"
              class="w-24 h-24 rounded-xl object-cover ring-4 ring-white shadow-lg"
            />
            <div v-else class="w-24 h-24 bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl flex items-center justify-center ring-4 ring-white shadow-lg">
              <span class="text-3xl font-bold text-white">{{ userInitial }}</span>
            </div>
          </div>
        </div>

        <!-- Profile Info -->
        <div class="pt-16 pb-6 px-6">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
              <h2 class="text-xl font-bold text-gray-900">{{ user?.name || 'User' }}</h2>
              <p class="text-sm text-gray-500 mt-0.5">{{ user?.email }}</p>
              <div class="flex items-center gap-2 mt-3">
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  Verified
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-50 text-gray-700 border border-gray-100">
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
      <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
          <h3 class="text-sm font-semibold text-gray-900">Account Details</h3>
        </div>
        <div class="divide-y divide-gray-100">
          <div class="px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-gray-500">Full name</span>
            <span class="text-sm font-medium text-gray-900">{{ user?.name || '-' }}</span>
          </div>
          <div class="px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-gray-500">Email address</span>
            <span class="text-sm font-medium text-gray-900">{{ user?.email || '-' }}</span>
          </div>
          <div class="px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-gray-500">Member since</span>
            <span class="text-sm font-medium text-gray-900">{{ memberSince }}</span>
          </div>
          <div class="px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-gray-500">Videos created</span>
            <span class="text-sm font-medium text-gray-900">{{ subscription?.videos_count || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Account Actions -->
      <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
          <h3 class="text-sm font-semibold text-gray-900">Account Actions</h3>
        </div>
        <div class="divide-y divide-gray-100">
          <!-- Export Data -->
          <div class="p-4 flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-900">Export Data</p>
              <p class="text-xs text-gray-500 mt-0.5">Download all your recordings and data</p>
            </div>
            <span class="px-3 py-1.5 text-xs font-medium text-gray-400 bg-gray-50 rounded-lg cursor-default">
              Coming Soon
            </span>
          </div>

          <!-- Delete Account -->
          <div class="p-4 flex items-center justify-between bg-red-50/50">
            <div>
              <p class="text-sm font-medium text-red-900">Delete Account</p>
              <p class="text-xs text-red-600 mt-0.5">Permanently delete your account and all data</p>
            </div>
            <span class="px-3 py-1.5 text-xs font-medium text-gray-400 bg-gray-50 rounded-lg cursor-default">
              Coming Soon
            </span>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '@/stores/auth'

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
