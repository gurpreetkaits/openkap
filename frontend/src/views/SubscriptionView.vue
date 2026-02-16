<template>
  <div class="animate-fade-in">
    <!-- Success Alert -->
    <div v-if="showSuccessAlert" class="fixed top-20 left-1/2 -translate-x-1/2 z-50 animate-slide-down">
      <div class="bg-white border border-green-200 text-gray-900 px-5 py-3.5 rounded-lg shadow-sm flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-semibold">Welcome to ScreenSense Pro!</h3>
          <p class="text-xs text-gray-500">Your subscription is now active. Enjoy unlimited recordings!</p>
        </div>
        <button @click="showSuccessAlert = false" class="ml-3 p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Row 1: Page Header -->
    <div class="mb-8">
      <h1 class="text-xl font-bold text-gray-900 tracking-tight">Plans & Billing</h1>
      <p class="text-gray-500 text-sm mt-1">Manage your subscription and payment details</p>
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
      <button @click="loadSubscription" class="inline-flex items-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-medium text-sm transition-colors">
        Try Again
      </button>
    </div>

    <template v-else>
      <!-- Row 2: Current Plan -->
      <div class="bg-white rounded-xl border border-gray-100 p-5 mb-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="currentPlanIcon.bg">
              <svg class="w-5 h-5" :class="currentPlanIcon.text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
              </svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-900">Current Plan</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold" :class="currentPlanBadge.class">
                  {{ currentPlanBadge.label }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-0.5">
                <template v-if="subscription?.plan_type === 'free'">
                  {{ subscription?.videos_count || 0 }} / {{ subscription?.max_videos || settings.free_plan?.max_videos || 5 }} videos used
                </template>
                <template v-else-if="subscription?.is_in_grace_period">
                  Cancels on {{ formatDate(subscription.expires_at) }}
                </template>
                <template v-else-if="subscription?.expires_at">
                  Renews on {{ formatDate(subscription.expires_at) }}
                </template>
                <template v-else>
                  Active subscription
                </template>
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="subscription?.plan_type !== 'free'"
              @click="openBillingPortal"
              :disabled="loadingPortal"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              {{ loadingPortal ? 'Loading...' : 'Manage Billing' }}
            </button>
            <button
              v-if="subscription?.plan_type !== 'free' && !subscription?.is_in_grace_period"
              @click="cancelSubscription"
              :disabled="canceling"
              class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-gray-200 rounded-lg hover:bg-red-50 hover:border-red-200 transition-colors disabled:opacity-50"
            >
              {{ canceling ? 'Processing...' : 'Cancel Plan' }}
            </button>
          </div>
        </div>
        <!-- Usage bar for free plan -->
        <div v-if="subscription?.plan_type === 'free'" class="mt-4 pt-4 border-t border-gray-100">
          <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
            <span>Videos used</span>
            <span class="font-medium text-gray-900">{{ subscription?.videos_count || 0 }} / {{ subscription?.max_videos || settings.free_plan?.max_videos || 5 }}</span>
          </div>
          <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
            <div
              class="bg-orange-500 h-full rounded-full transition-all duration-500"
              :style="{ width: Math.min(((subscription?.videos_count || 0) / (subscription?.max_videos || settings.free_plan?.max_videos || 5)) * 100, 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Row 3: Choose Your Plan — Single Card -->
      <div class="bg-white rounded-xl border border-gray-100 p-6 mb-8">
        <!-- Header + Switch -->
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-sm font-semibold text-gray-900">Choose Your Plan</h2>
          <div class="flex items-center gap-0.5 bg-gray-100 rounded-lg p-0.5">
            <button
              @click="selectedPlan = 'free'"
              class="px-3 py-1.5 text-xs font-medium rounded-md transition-all"
              :class="selectedPlan === 'free' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >
              Free
            </button>
            <button
              @click="selectedPlan = 'pro'"
              class="px-3 py-1.5 text-xs font-medium rounded-md transition-all"
              :class="selectedPlan === 'pro' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >
              Pro
            </button>
          </div>
        </div>

        <!-- Row 2: Plan Title + Price (centered) -->
        <div class="text-center py-6">
          <!-- Free -->
          <template v-if="selectedPlan === 'free'">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Free</h3>
            <p class="text-xs text-gray-400 mb-4">For getting started</p>
            <div>
              <span class="text-4xl font-bold text-gray-900">$0</span>
              <span class="text-gray-400 text-sm">/mo</span>
            </div>
          </template>

          <!-- Pro -->
          <template v-else-if="selectedPlan === 'pro'">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Pro</h3>
            <p class="text-xs text-gray-400 mb-3">For individuals & creators</p>
            <!-- Billing cycle toggle -->
            <div class="flex items-center justify-center mb-4">
              <div class="flex items-center gap-0.5 bg-gray-100 rounded-md p-0.5">
                <button
                  @click="billingCycle = 'monthly'"
                  class="px-2.5 py-1 text-[11px] font-medium rounded transition-all"
                  :class="billingCycle === 'monthly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                >
                  Monthly
                </button>
                <button
                  @click="billingCycle = 'yearly'"
                  class="px-2.5 py-1 text-[11px] font-medium rounded transition-all"
                  :class="billingCycle === 'yearly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                >
                  Yearly
                </button>
              </div>
            </div>
            <div>
              <template v-if="billingCycle === 'yearly'">
                <span class="text-4xl font-bold text-gray-900">${{ settings.subscription?.yearly_price || 80 }}</span>
                <span class="text-gray-400 text-sm">/yr</span>
                <div class="mt-1.5">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-medium">
                    Save {{ settings.subscription?.yearly_savings_percent || 17 }}%
                  </span>
                </div>
              </template>
              <template v-else>
                <span class="text-4xl font-bold text-gray-900">${{ settings.subscription?.monthly_price || 8 }}</span>
                <span class="text-gray-400 text-sm">/mo</span>
              </template>
            </div>
          </template>
        </div>

        <!-- Row 3: What's Included -->
        <div class="py-5">

          <!-- Free benefits -->
          <ul v-if="selectedPlan === 'free'" class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 text-sm text-gray-700">
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              5 videos
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              5 min per recording
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              No watermarks
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Shareable links
            </li>
          </ul>

          <!-- Pro benefits -->
          <ul v-else-if="selectedPlan === 'pro'" class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 text-sm text-gray-700">
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Unlimited videos
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Unlimited recording
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              HLS streaming
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Priority support
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              No watermarks
            </li>
          </ul>

        </div>

        <!-- Row 4: Action Button -->
        <div class="pt-5 flex justify-center">
          <!-- Free -->
          <template v-if="selectedPlan === 'free'">
            <button
              v-if="subscription?.plan_type === 'free'"
              class="w-full max-w-sm py-2.5 rounded-lg border border-gray-100 text-xs font-medium text-gray-500 bg-gray-50 cursor-default"
            >
              Current Plan
            </button>
            <button
              v-else
              @click="cancelSubscription"
              :disabled="canceling || subscription?.is_in_grace_period"
              class="w-full max-w-sm py-2.5 rounded-lg border border-gray-100 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ canceling ? 'Processing...' : subscription?.is_in_grace_period ? 'Cancellation Pending' : 'Downgrade to Free' }}
            </button>
          </template>

          <!-- Pro -->
          <template v-else-if="selectedPlan === 'pro'">
            <button
              v-if="subscription?.plan_type === 'pro' && !subscription?.is_in_grace_period"
              class="w-full max-w-sm py-2.5 rounded-lg bg-green-600 text-xs font-medium text-white flex items-center justify-center gap-1.5 cursor-default"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Current Plan
            </button>
            <button
              v-else
              @click="startCheckout('pro')"
              :disabled="checkingOut"
              class="w-full max-w-sm py-2.5 rounded-lg bg-orange-600 hover:bg-orange-500 text-xs font-medium text-white transition-all flex items-center justify-center gap-1.5 group disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <template v-if="checkingOut === 'pro'">
                <div class="animate-spin rounded-full h-3.5 w-3.5 border-2 border-white border-t-transparent"></div>
                Redirecting...
              </template>
              <template v-else>
                {{ subscription?.is_in_grace_period ? 'Resubscribe' : 'Upgrade to Pro' }}
                <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </template>
            </button>
          </template>

        </div>
      </div>

      <!-- Enterprise Contact -->
      <div class="mt-8 flex items-center justify-between p-5 bg-white rounded-xl border border-gray-100">
        <div>
          <h4 class="text-sm font-semibold text-gray-900">Need a custom enterprise plan?</h4>
          <p class="text-xs text-gray-500 mt-0.5">For large organizations with specific security, SSO, and compliance needs.</p>
        </div>
        <button class="inline-flex items-center px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors shrink-0">
          Contact Sales
        </button>
      </div>

      <!-- Billing History -->
      <div v-if="history.length > 0" class="mt-8">
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
          <div class="px-5 py-3.5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">Billing History</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50">
                  <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Event</th>
                  <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                  <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                  <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in history" :key="item.id" class="hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3">
                    <span class="inline-flex px-2 py-0.5 text-[11px] font-medium rounded-md" :class="getEventClass(item.event_type)">{{ item.event_label }}</span>
                  </td>
                  <td class="px-5 py-3">
                    <span class="inline-flex px-2 py-0.5 text-[11px] font-medium rounded-full" :class="getStatusClass(item.status)">{{ item.status }}</span>
                  </td>
                  <td class="px-5 py-3 text-xs text-gray-900 font-medium">{{ item.formatted_amount || '-' }}</td>
                  <td class="px-5 py-3 text-xs text-gray-500">{{ formatHistoryDate(item.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>
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
const checkingOut = ref(null) // 'pro' or 'teams' or null
const canceling = ref(false)
const loadingPortal = ref(false)
const showSuccessAlert = ref(false)
const billingCycle = ref('monthly')
const selectedPlan = ref('free')

// Settings from backend
const settings = ref({
  subscription: {
    monthly_price: 8,
    yearly_price: 80,
    yearly_monthly_price: 6.67,
    yearly_savings_percent: 17,
  },
  free_plan: {
    max_videos: 5,
    max_duration_minutes: 5,
  }
})

const hasActiveSubscription = computed(() => {
  return subscription.value?.is_active === true
})

const currentPlanBadge = computed(() => {
  const plan = subscription.value?.plan_type
  if (plan === 'pro') return { label: 'Pro', class: 'bg-orange-100 text-orange-700' }
  return { label: 'Free', class: 'bg-gray-100 text-gray-600' }
})

const currentPlanIcon = computed(() => {
  const plan = subscription.value?.plan_type
  if (plan === 'pro') return { bg: 'bg-orange-100', text: 'text-orange-600' }
  return { bg: 'bg-gray-100', text: 'text-gray-500' }
})

async function loadSubscription() {
  loading.value = true
  error.value = null

  try {
    const sub = await auth.fetchSubscription()
    subscription.value = sub
    selectedPlan.value = sub?.plan_type || 'free'
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

async function startCheckout(planType) {
  checkingOut.value = planType

  try {
    // Determine the plan to send to backend
    const plan = billingCycle.value // 'monthly' or 'yearly'

    const response = await fetch(`${API_BASE_URL}/api/subscription/checkout`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ plan }),
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
    checkingOut.value = null
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
