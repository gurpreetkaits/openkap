<template>
  <div class="animate-fade-in max-w-5xl mx-auto p-6 lg:p-8">
    <!-- Success Alert -->
    <div v-if="showSuccessAlert" class="fixed top-20 left-1/2 -translate-x-1/2 z-50 animate-slide-down">
      <div class="alert alert-success shadow-lg">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <div>
          <h3 class="font-semibold">Welcome to ScreenSense Pro!</h3>
          <p class="text-sm opacity-90">Your subscription is now active. Enjoy unlimited recordings!</p>
        </div>
        <button @click="showSuccessAlert = false" class="btn btn-ghost btn-sm btn-circle">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Header -->
    <div class="mb-8 text-center">
      <h2 class="text-2xl font-bold text-base-content tracking-tight mb-2">Upgrade your plan</h2>
      <p class="text-base-content/60 text-sm max-w-lg mx-auto">Unlock unlimited recording, higher resolution exports, and priority support.</p>
    </div>

    <!-- Billing Cycle Toggle -->
    <div class="flex items-center justify-center gap-2 mb-10">
      <button
        @click="billingCycle = 'monthly'"
        class="btn btn-sm"
        :class="billingCycle === 'monthly' ? 'btn-neutral' : 'btn-ghost'"
      >
        Monthly
      </button>
      <button
        @click="billingCycle = 'yearly'"
        class="btn btn-sm"
        :class="billingCycle === 'yearly' ? 'btn-neutral' : 'btn-ghost'"
      >
        Yearly
        <span class="badge badge-success badge-sm text-[10px] font-bold">Save {{ settings.subscription?.yearly_savings_percent || 5 }}%</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-24">
      <span class="loading loading-spinner loading-lg text-primary"></span>
      <p class="mt-4 text-sm text-base-content/60">Loading subscription details...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-24">
      <div class="w-16 h-16 mx-auto mb-6 bg-error/10 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-base-content mb-2">{{ error }}</h3>
      <button @click="loadSubscription" class="btn btn-neutral btn-sm">
        Try Again
      </button>
    </div>

    <!-- Pricing Cards -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
      <!-- Free Plan Card -->
      <div class="card bg-base-100 border border-base-300 shadow-sm h-full">
        <div class="card-body">
          <div class="mb-5">
            <h3 class="card-title text-lg">Free Plan</h3>
            <p class="text-sm text-base-content/60 mt-1">Perfect for getting started</p>
          </div>
          <div class="mb-6">
            <span class="text-4xl font-bold text-base-content">$0</span>
            <span class="text-base-content/60 text-sm">/month</span>
          </div>
          <button
            v-if="!hasActiveSubscription"
            class="btn btn-disabled w-full mb-8"
          >
            Current Plan
          </button>
          <button
            v-else
            @click="cancelSubscription"
            :disabled="canceling || subscription?.is_in_grace_period"
            class="btn btn-outline w-full mb-8"
          >
            {{ canceling ? 'Processing...' : subscription?.is_in_grace_period ? 'Cancellation Pending' : 'Downgrade to Free' }}
          </button>
          <ul class="space-y-4 text-sm text-base-content/70 flex-1">
            <li class="flex items-center gap-3">
              <svg class="w-4 h-4 text-base-content/40 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              {{ settings.free_plan?.max_videos || 1 }} video{{ (settings.free_plan?.max_videos || 1) > 1 ? 's' : '' }}
            </li>
          </ul>

          <!-- Usage indicator for free plan -->
          <div v-if="!hasActiveSubscription && subscription" class="mt-6 pt-6 border-t border-base-200">
            <div class="flex items-center justify-between text-xs text-base-content/60 mb-2">
              <span>Videos used</span>
              <span class="font-medium text-base-content">{{ subscription?.videos_count || 0 }} / {{ subscription?.max_videos || settings.free_plan?.max_videos || 1 }}</span>
            </div>
            <progress
              class="progress progress-primary w-full"
              :value="Math.min(((subscription?.videos_count || 0) / (subscription?.max_videos || settings.free_plan?.max_videos || 1)) * 100, 100)"
              max="100"
            ></progress>
          </div>
        </div>
      </div>

      <!-- Pro Plan Card -->
      <div class="card bg-neutral text-neutral-content shadow-xl ring-1 ring-primary/20 h-full relative">
        <!-- Badge -->
        <div class="badge badge-primary absolute -top-3 right-4 text-[10px] font-bold uppercase tracking-wide">
          Recommended
        </div>
        <div class="card-body">
          <div class="mb-5">
            <h3 class="card-title text-lg">Pro Plan</h3>
            <p class="text-sm opacity-70 mt-1">For power users and teams</p>
          </div>
          <div class="mb-6">
            <template v-if="billingCycle === 'monthly'">
              <span class="text-4xl font-bold">${{ settings.subscription?.monthly_price || 7 }}</span>
              <span class="opacity-70 text-sm">/month</span>
            </template>
            <template v-else>
              <span class="text-4xl font-bold">${{ ((settings.subscription?.yearly_price || 80) / 12).toFixed(2) }}</span>
              <span class="opacity-70 text-sm">/month</span>
              <div class="text-xs opacity-70 mt-1">Billed ${{ settings.subscription?.yearly_price || 80 }}/year</div>
            </template>
          </div>
          <button
            v-if="hasActiveSubscription && !subscription?.is_in_grace_period"
            class="btn btn-success w-full mb-8"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Current Plan
          </button>
          <button
            v-else
            @click="startCheckout"
            :disabled="checkingOut"
            class="btn btn-primary w-full mb-8 group"
          >
            <template v-if="checkingOut">
              <span class="loading loading-spinner loading-sm"></span>
              Redirecting...
            </template>
            <template v-else>
              {{ subscription?.is_in_grace_period ? 'Resubscribe' : 'Upgrade to Pro' }}
              <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
              </svg>
            </template>
          </button>
          <ul class="space-y-4 text-sm flex-1">
            <li class="flex items-center gap-3">
              <div class="p-0.5 rounded-full bg-primary/20 text-primary flex-shrink-0">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              Unlimited videos
            </li>
            <li class="flex items-center gap-3">
              <div class="p-0.5 rounded-full bg-primary/20 text-primary flex-shrink-0">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              Unlimited recording time
            </li>
            <li class="flex items-center gap-3">
              <div class="p-0.5 rounded-full bg-primary/20 text-primary flex-shrink-0">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              4K Export quality
            </li>
            <li class="flex items-center gap-3">
              <div class="p-0.5 rounded-full bg-primary/20 text-primary flex-shrink-0">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              Priority Support
            </li>
            <li class="flex items-center gap-3">
              <div class="p-0.5 rounded-full bg-primary/20 text-primary flex-shrink-0">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              Custom Branding
            </li>
          </ul>

          <!-- Active subscription info -->
          <div v-if="hasActiveSubscription" class="mt-6 pt-6 border-t border-neutral-content/20">
            <div class="flex items-center justify-between text-xs mb-2">
              <span class="opacity-70">Status</span>
              <span class="font-medium" :class="subscription?.is_in_grace_period ? 'text-warning' : 'text-success'">
                {{ subscription?.is_in_grace_period ? 'Cancels ' + formatDate(subscription.expires_at) : 'Active' }}
              </span>
            </div>
            <button
              @click="openBillingPortal"
              :disabled="loadingPortal"
              class="btn btn-ghost btn-sm w-full mt-3"
            >
              {{ loadingPortal ? 'Loading...' : 'Manage Billing' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Enterprise Contact -->
    <div class="mt-12 text-center card bg-base-200 max-w-4xl mx-auto">
      <div class="card-body items-center">
        <h4 class="text-sm font-semibold text-base-content mb-1">Need a custom plan?</h4>
        <p class="text-sm text-base-content/60 mb-3">For large organizations with specific security and control needs.</p>
        <button class="btn btn-ghost btn-sm">
          Contact Sales
        </button>
      </div>
    </div>

    <!-- Subscription History -->
    <div v-if="history.length > 0" class="mt-12 max-w-4xl mx-auto">
      <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-base-200">
          <h3 class="text-sm font-semibold text-base-content">Subscription History</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="table table-zebra w-full text-sm">
            <thead>
              <tr>
                <th class="text-xs font-semibold text-base-content/60 uppercase tracking-wide">Event</th>
                <th class="text-xs font-semibold text-base-content/60 uppercase tracking-wide">Status</th>
                <th class="text-xs font-semibold text-base-content/60 uppercase tracking-wide">Amount</th>
                <th class="text-xs font-semibold text-base-content/60 uppercase tracking-wide">Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in history" :key="item.id">
                <td>
                  <span
                    class="badge badge-sm"
                    :class="getEventBadgeClass(item.event_type)"
                  >
                    {{ item.event_label }}
                  </span>
                </td>
                <td>
                  <span
                    class="badge badge-sm"
                    :class="getStatusBadgeClass(item.status)"
                  >
                    {{ item.status }}
                  </span>
                </td>
                <td class="text-base-content font-medium">{{ item.formatted_amount || '-' }}</td>
                <td class="text-base-content/60">{{ formatHistoryDate(item.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
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
const checkingOut = ref(false)
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

function getEventBadgeClass(eventType) {
  switch (eventType) {
    case 'created':
    case 'activated':
      return 'badge-success'
    case 'renewed':
      return 'badge-info'
    case 'canceled':
      return 'badge-warning'
    case 'revoked':
      return 'badge-error'
    default:
      return 'badge-ghost'
  }
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'active':
      return 'badge-success'
    case 'canceled':
      return 'badge-warning'
    case 'expired':
      return 'badge-error'
    default:
      return 'badge-ghost'
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

async function startCheckout() {
  checkingOut.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/api/subscription/checkout`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        plan: billingCycle.value,
      }),
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || 'Failed to create checkout session')
    }

    const data = await response.json()

    if (data.checkout_url) {
      window.location.href = data.checkout_url
    } else {
      throw new Error('Checkout URL not received')
    }
  } catch (e) {
    console.error('Error starting checkout:', e)
    toast.error(e.message || 'Failed to start checkout. Please try again.')
    checkingOut.value = false
  }
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
