<template>
  <SBModal v-model="showModal" @close="handleClose" size="lg">
    <div class="upgrade-modal">
      <div class="header">
        <h2 class="title">Upgrade to Pro</h2>
        <p class="subtitle">Get unlimited recordings and 500 minutes / month</p>
      </div>

      <SBPricingCard
        plan-key="pro"
        plan-name="Pro"
        tagline="Everything you need, no limits to speak of"
        :monthly-price="pricing.monthly_price"
        :yearly-price="pricing.yearly_price"
        :yearly-savings-percent="pricing.yearly_savings_percent"
        :features="features"
        :checking-out="loading ? 'pro' : false"
        cta-label="Continue to payment"
        default-billing-cycle="yearly"
        highlight
        @checkout="startCheckout"
      />

      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </SBModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import SBModal from './SBModal.vue'
import SBPricingCard from './SBPricingCard.vue'
import { useAuth } from '@/stores/auth'
import settingsService from '@/services/settingsService'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

const props = defineProps({
  show: Boolean,
})

const emit = defineEmits(['close', 'success', 'update:show'])

const auth = useAuth()
const loading = ref(false)
const error = ref(null)
const pricing = ref(settingsService.getDefaults().subscription)

const features = computed(() => {
  const cap = pricing.value.pro_monthly_recording_minutes_limit ?? 500
  return [
    'Unlimited videos',
    'Unlimited recording length',
    `${cap} recording minutes / month`,
    'HLS streaming',
    'Priority support',
    'No watermarks',
  ]
})

onMounted(async () => {
  try {
    const s = await settingsService.getSubscriptionSettings()
    if (s) pricing.value = { ...pricing.value, ...s }
  } catch (e) {
    // keep defaults
  }
})

// Sync with v-model on SBModal
const showModal = computed({
  get: () => props.show,
  set: (value) => {
    if (!value) {
      emit('close')
    }
    emit('update:show', value)
  },
})

async function startCheckout({ billingCycle }) {
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
      body: JSON.stringify({ plan: billingCycle }),
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

<style scoped>
.upgrade-modal {
  padding: 1.5rem;
}

.header {
  text-align: center;
  margin-bottom: 1.25rem;
}

.title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #111827;
  margin: 0 0 0.25rem 0;
}

.subtitle {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
}

.error {
  margin-top: 1rem;
  text-align: center;
  color: #dc2626;
  font-size: 0.875rem;
}
</style>
