<template>
  <SBModal v-model="showModal" @close="handleClose" size="lg">
    <div class="p-2">
      <div class="text-center mb-6">
        <h2 class="text-2xl font-bold">Upgrade to Pro</h2>
        <p class="text-base-content/70 mt-1">Get unlimited video recordings</p>
      </div>

      <!-- Plan Selection -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <button
          class="card bg-base-100 border-2 transition-all p-4"
          :class="selectedPlan === 'monthly' ? 'border-primary bg-primary/5' : 'border-base-300 hover:border-primary/50'"
          @click="selectedPlan = 'monthly'"
        >
          <div class="text-center">
            <div class="text-sm font-semibold text-base-content/60 mb-1">Monthly</div>
            <div class="text-2xl font-bold">$7<span class="text-sm font-normal text-base-content/60">/month</span></div>
          </div>
        </button>
        <button
          class="card bg-base-100 border-2 transition-all p-4 relative"
          :class="selectedPlan === 'yearly' ? 'border-primary bg-primary/5' : 'border-base-300 hover:border-primary/50'"
          @click="selectedPlan = 'yearly'"
        >
          <div class="badge badge-primary badge-sm absolute -top-2 right-2">Save 5%</div>
          <div class="text-center">
            <div class="text-sm font-semibold text-base-content/60 mb-1">Yearly</div>
            <div class="text-2xl font-bold">$80<span class="text-sm font-normal text-base-content/60">/year</span></div>
            <div class="text-xs text-base-content/50 mt-1">~$6.67/month</div>
          </div>
        </button>
      </div>

      <div class="bg-base-200 rounded-xl p-4 mb-6">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm">Unlimited video recordings</span>
          </div>
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm">HD video quality</span>
          </div>
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm">Unlimited storage</span>
          </div>
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm">Priority support</span>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-6">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <p class="text-base-content/70 mt-2">Redirecting to checkout...</p>
      </div>

      <div v-else-if="error" class="text-center py-4">
        <div class="alert alert-error mb-4">
          <span>{{ error }}</span>
        </div>
        <button @click="startCheckout" class="btn btn-primary">Try Again</button>
      </div>

      <div v-else>
        <button @click="startCheckout" class="btn btn-primary btn-block">
          Continue to Payment
        </button>
      </div>
    </div>
  </SBModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import SBModal from './SBModal.vue'
import { useAuth } from '@/stores/auth'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

const props = defineProps({
  show: Boolean,
})

const emit = defineEmits(['close', 'success', 'update:show'])

const auth = useAuth()
const loading = ref(false)
const error = ref(null)
const selectedPlan = ref('yearly')

const showModal = computed({
  get: () => props.show,
  set: (value) => {
    if (!value) {
      emit('close')
    }
    emit('update:show', value)
  }
})

async function startCheckout() {
  loading.value = true
  error.value = null

  try {
    const response = await fetch(`${API_BASE_URL}/api/subscription/checkout`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        plan: selectedPlan.value,
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
    error.value = e.message || 'Failed to start checkout. Please try again.'
    loading.value = false
  }
}

function handleClose() {
  error.value = null
  emit('close')
}

watch(() => props.show, (newShow) => {
  if (newShow) {
    error.value = null
  }
})
</script>
