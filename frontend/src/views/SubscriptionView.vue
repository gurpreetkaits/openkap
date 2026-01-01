<template>
  <div class="animate-fade-in max-w-5xl mx-auto p-6 lg:p-8">
    <!-- Success Alert -->
    <div v-if="showSuccessAlert" class="fixed top-20 left-1/2 -translate-x-1/2 z-50 animate-slide-down">
      <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-lg shadow-green-500/20 flex items-center gap-3">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <div>
          <h3 class="font-semibold">Welcome to ScreenSense Pro!</h3>
          <p class="text-sm text-white/90">Your subscription is now active. Enjoy unlimited recordings!</p>
        </div>
        <button @click="showSuccessAlert = false" class="ml-4 p-1 hover:bg-white/20 rounded-lg transition-colors">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Header -->
    <div class="mb-8 text-center">
      <h2 class="text-2xl font-bold text-gray-900 tracking-tight mb-2">Upgrade your plan</h2>
      <p class="text-gray-500 text-sm max-w-lg mx-auto">Unlock unlimited recording, higher resolution exports, and priority support.</p>
    </div>

    <!-- Billing Cycle Toggle -->
    <div class="flex items-center justify-center gap-4 mb-10">
      <button
        @click="billingCycle = 'monthly'"
        class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
        :class="billingCycle === 'monthly' ? 'bg-gray-900 text-white' : 'text-gray-600 hover:text-gray-900'"
      >
        Monthly
      </button>
      <button
        @click="billingCycle = 'yearly'"
        class="px-4 py-2 text-sm font-medium rounded-lg transition-all flex items-center gap-2"
        :class="billingCycle === 'yearly' ? 'bg-gray-900 text-white' : 'text-gray-600 hover:text-gray-900'"
      >
        Yearly
        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-green-500 text-white font-bold">Save {{ settings.subscription?.yearly_savings_percent || 5 }}%</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-orange-600 border-t-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">Loading subscription details...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-24">
      <div class="w-16 h-16 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ error }}</h3>
      <button @click="loadSubscription" class="inline-flex items-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm shadow-sm transition-colors">
        Try Again
      </button>
    </div>

    <!-- Pricing Cards -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
      <!-- Free Plan Card -->
      <div class="bg-white rounded-2xl border border-gray-200 p-8 flex flex-col relative shadow-sm h-full">
        <div class="mb-5">
          <h3 class="text-lg font-semibold text-gray-900">Free Plan</h3>
          <p class="text-sm text-gray-500 mt-1">Perfect for getting started</p>
        </div>
        <div class="mb-6">
          <span class="text-4xl font-bold text-gray-900">$0</span>
          <span class="text-gray-500 text-sm">/month</span>
        </div>
        <button
          v-if="!hasActiveSubscription"
          class="w-full py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-900 bg-gray-50 cursor-default mb-8"
        >
          Current Plan
        </button>
        <button
          v-else
          @click="cancelSubscription"
          :disabled="canceling || subscription?.is_in_grace_period"
          class="w-full py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors mb-8 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ canceling ? 'Processing...' : subscription?.is_in_grace_period ? 'Cancellation Pending' : 'Downgrade to Free' }}
        </button>
        <ul class="space-y-4 text-sm text-gray-600 flex-1">
          <li class="flex items-center gap-3">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ settings.free_plan?.max_videos || 1 }} video{{ (settings.free_plan?.max_videos || 1) > 1 ? 's' : '' }}
          </li>
          <li class="flex items-center gap-3">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ settings.free_plan?.max_duration_minutes || 5 }} minute recording limit
          </li>
          <li class="flex items-center gap-3">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            720p Export quality
          </li>
          <li class="flex items-center gap-3">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Standard Support
          </li>
        </ul>

        <!-- Usage indicator for free plan -->
        <div v-if="!hasActiveSubscription && subscription" class="mt-6 pt-6 border-t border-gray-100">
          <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
            <span>Videos used</span>
            <span class="font-medium text-gray-900">{{ subscription?.videos_count || 0 }} / {{ subscription?.max_videos || settings.free_plan?.max_videos || 1 }}</span>
          </div>
          <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
            <div
              class="bg-orange-500 h-full rounded-full transition-all duration-500"
              :style="{ width: Math.min(((subscription?.videos_count || 0) / (subscription?.max_videos || settings.free_plan?.max_videos || 1)) * 100, 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Pro Plan Card -->
      <div class="bg-gray-900 rounded-2xl border border-gray-800 p-8 flex flex-col relative shadow-xl shadow-orange-900/10 h-full ring-1 ring-orange-500/20">
        <!-- Badge -->
        <div class="absolute top-0 right-0 -mt-3 mr-4 bg-gradient-to-r from-orange-500 to-orange-600 text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg tracking-wide uppercase">
          Recommended
        </div>
        <div class="mb-5">
          <h3 class="text-lg font-semibold text-white">Pro Plan</h3>
          <p class="text-sm text-gray-400 mt-1">For power users and teams</p>
        </div>
        <div class="mb-6">
          <template v-if="billingCycle === 'monthly'">
            <span class="text-4xl font-bold text-white">${{ settings.subscription?.monthly_price || 7 }}</span>
            <span class="text-gray-400 text-sm">/month</span>
          </template>
          <template v-else>
            <span class="text-4xl font-bold text-white">${{ ((settings.subscription?.yearly_price || 80) / 12).toFixed(2) }}</span>
            <span class="text-gray-400 text-sm">/month</span>
            <div class="text-xs text-gray-400 mt-1">Billed ${{ settings.subscription?.yearly_price || 80 }}/year</div>
          </template>
        </div>
        <button
          v-if="hasActiveSubscription && !subscription?.is_in_grace_period"
          class="w-full py-2.5 rounded-lg bg-green-600 text-sm font-medium text-white mb-8 flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          Current Plan
        </button>
        <button
          v-else
          @click="showUpgradeModal = true"
          class="w-full py-2.5 rounded-lg bg-orange-600 hover:bg-orange-500 text-sm font-medium text-white transition-all shadow-lg shadow-orange-900/20 mb-8 flex items-center justify-center gap-2 group"
        >
          {{ subscription?.is_in_grace_period ? 'Resubscribe' : 'Upgrade to Pro' }}
          <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
          </svg>
        </button>
        <ul class="space-y-4 text-sm text-gray-300 flex-1">
          <li class="flex items-center gap-3 text-white">
            <div class="p-0.5 rounded-full bg-orange-500/20 text-orange-400 flex-shrink-0">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            Unlimited videos
          </li>
          <li class="flex items-center gap-3 text-white">
            <div class="p-0.5 rounded-full bg-orange-500/20 text-orange-400 flex-shrink-0">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            Unlimited recording time
          </li>
          <li class="flex items-center gap-3 text-white">
            <div class="p-0.5 rounded-full bg-orange-500/20 text-orange-400 flex-shrink-0">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            4K Export quality
          </li>
          <li class="flex items-center gap-3 text-white">
            <div class="p-0.5 rounded-full bg-orange-500/20 text-orange-400 flex-shrink-0">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            Priority Support
          </li>
          <li class="flex items-center gap-3 text-white">
            <div class="p-0.5 rounded-full bg-orange-500/20 text-orange-400 flex-shrink-0">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            Custom Branding
          </li>
        </ul>

        <!-- Active subscription info -->
        <div v-if="hasActiveSubscription" class="mt-6 pt-6 border-t border-gray-700">
          <div class="flex items-center justify-between text-xs mb-2">
            <span class="text-gray-400">Status</span>
            <span class="font-medium" :class="subscription?.is_in_grace_period ? 'text-yellow-400' : 'text-green-400'">
              {{ subscription?.is_in_grace_period ? 'Cancels ' + formatDate(subscription.expires_at) : 'Active' }}
            </span>
          </div>
          <button
            @click="openBillingPortal"
            :disabled="loadingPortal"
            class="w-full mt-3 py-2 rounded-lg bg-gray-800 hover:bg-gray-700 text-sm font-medium text-gray-300 transition-colors disabled:opacity-50"
          >
            {{ loadingPortal ? 'Loading...' : 'Manage Billing' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Enterprise Contact -->
    <div class="mt-12 text-center p-6 bg-gray-100 rounded-xl border border-gray-200 max-w-4xl mx-auto">
      <h4 class="text-sm font-semibold text-gray-900 mb-1">Need a custom plan?</h4>
      <p class="text-sm text-gray-500 mb-3">For large organizations with specific security and control needs.</p>
      <button class="text-sm font-medium text-gray-900 border-b border-gray-300 hover:border-gray-900 transition-colors">
        Contact Sales
      </button>
    </div>

    <!-- Subscription History -->
    <div v-if="history.length > 0" class="mt-12 max-w-4xl mx-auto">
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h3 class="text-sm font-semibold text-gray-900">Subscription History</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50">
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Event</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in history" :key="item.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-medium rounded-md"
                    :class="getEventClass(item.event_type)"
                  >
                    {{ item.event_label }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                    :class="getStatusClass(item.status)"
                  >
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-900 font-medium">{{ item.formatted_amount || '-' }}</td>
                <td class="px-6 py-4 text-gray-500">{{ formatHistoryDate(item.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Upgrade Modal -->
    <SBUpgradeModal
      :show="showUpgradeModal"
      @close="showUpgradeModal = false"
      @success="handleUpgradeSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import SBUpgradeModal from '@/components/Global/SBUpgradeModal.vue'
import { useAuth } from '@/stores/auth'
import toast from '@/services/toastService'
import settingsService from '@/services/settingsService'
import confetti from 'canvas-confetti/dist/confetti.module.mjs'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

const router = useRouter()
const route = useRoute()
const auth = useAuth()

const subscription = ref(null)
const history = ref([])
const loading = ref(false)
const error = ref(null)
const showUpgradeModal = ref(false)
const canceling = ref(false)
const loadingPortal = ref(false)
const showSuccessAlert = ref(false)
const billingCycle = ref('monthly')

// Settings from backend
const settings = ref({
  subscription: {
    monthly_price: 7,
    yearly_price: 80,
    yearly_monthly_price: 6.67,
    yearly_savings_percent: 5,
  },
  free_plan: {
    max_videos: 1,
    max_duration_minutes: 5,
  }
})

const hasActiveSubscription = computed(() => {
  return subscription.value?.is_active === true
})

async function loadSubscription() {
  loading.value = true
  error.value = null

  try {
    const sub = await auth.fetchSubscription()
    subscription.value = sub
    await fetchHistory()
  } catch (e) {
    console.error('Error loading subscription:', e)
    error.value = 'Failed to load subscription details'
  } finally {
    loading.value = false
  }
}

async function fetchHistory() {
  try {
    const response = await fetch(`${API_BASE_URL}/api/subscription/history`, {
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
      },
    })

    if (response.ok) {
      const data = await response.json()
      history.value = data.history || []
    }
  } catch (e) {
    console.error('Error loading subscription history:', e)
  }
}

function getEventClass(eventType) {
  switch (eventType) {
    case 'created':
    case 'activated':
      return 'bg-green-100 text-green-700'
    case 'renewed':
      return 'bg-blue-100 text-blue-700'
    case 'canceled':
      return 'bg-yellow-100 text-yellow-700'
    case 'revoked':
      return 'bg-red-100 text-red-700'
    default:
      return 'bg-gray-100 text-gray-700'
  }
}

function getStatusClass(status) {
  switch (status) {
    case 'active':
      return 'bg-green-100 text-green-700'
    case 'canceled':
      return 'bg-yellow-100 text-yellow-700'
    case 'expired':
      return 'bg-red-100 text-red-700'
    default:
      return 'bg-gray-100 text-gray-700'
  }
}

function formatHistoryDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

async function cancelSubscription() {
  if (!confirm('Are you sure you want to cancel your subscription? You will keep access until the end of your billing period.')) {
    return
  }

  canceling.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/api/subscription/cancel`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || 'Failed to cancel subscription')
    }

    await loadSubscription()
    toast.success('Subscription canceled. You will keep access until ' + formatDate(subscription.value.expires_at))
  } catch (e) {
    console.error('Error canceling subscription:', e)
    toast.error('Failed to cancel subscription: ' + e.message)
  } finally {
    canceling.value = false
  }
}

async function openBillingPortal() {
  loadingPortal.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/api/subscription/portal`, {
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Failed to get billing portal URL')
    }

    const data = await response.json()
    if (data.portal_url) {
      window.location.href = data.portal_url
    }
  } catch (e) {
    console.error('Error opening billing portal:', e)
    toast.error('Failed to open billing portal')
  } finally {
    loadingPortal.value = false
  }
}

function handleUpgradeSuccess() {
  showUpgradeModal.value = false
  loadSubscription()
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function triggerConfetti() {
  const duration = 3000
  const animationEnd = Date.now() + duration
  const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 }

  function randomInRange(min, max) {
    return Math.random() * (max - min) + min
  }

  const interval = setInterval(() => {
    const timeLeft = animationEnd - Date.now()

    if (timeLeft <= 0) {
      return clearInterval(interval)
    }

    const particleCount = 50 * (timeLeft / duration)

    confetti({
      ...defaults,
      particleCount,
      origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
    })
    confetti({
      ...defaults,
      particleCount,
      origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
    })
  }, 250)
}

async function loadSettings() {
  try {
    const data = await settingsService.getSettings()
    settings.value = data
  } catch (e) {
    console.error('Error loading settings:', e)
  }
}

onMounted(async () => {
  if (route.query.success === 'true') {
    showSuccessAlert.value = true
    triggerConfetti()
    router.replace({ query: {} })
  }

  await loadSettings()
  loadSubscription()
})
</script>

<style scoped>
/* Fade in animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }

/* Slide down animation for success alert */
@keyframes slideDown {
  from { opacity: 0; transform: translate(-50%, -1rem); }
  to { opacity: 1; transform: translate(-50%, 0); }
}
.animate-slide-down { animation: slideDown 0.5s ease-out forwards; }
</style>
