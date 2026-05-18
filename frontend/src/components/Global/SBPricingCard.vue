<template>
  <div
    class="bg-white rounded-xl border p-5 flex flex-col"
    :class="highlight ? 'border-orange-500 ring-1 ring-orange-200' : 'border-gray-200'"
  >
    <!-- Plan name + tagline -->
    <div class="flex items-baseline justify-between mb-1">
      <h3 class="text-sm font-semibold text-gray-900">{{ planName }}</h3>
      <span
        v-if="highlight"
        class="inline-flex items-center px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-[10px] font-semibold"
      >
        Most popular
      </span>
    </div>
    <p v-if="tagline" class="text-xs text-gray-500 mb-4">{{ tagline }}</p>

    <!-- Price -->
    <div class="mb-1">
      <span class="text-3xl font-bold text-gray-900">${{ displayedPrice }}</span>
      <span class="text-sm text-gray-400">{{ priceSuffix }}</span>
    </div>

    <!-- Billing toggle (only when both prices set) -->
    <div v-if="hasYearly" class="flex items-center gap-2 mt-1 mb-4">
      <button
        @click="billingCycle = 'monthly'"
        class="text-[11px] font-medium transition-colors"
        :class="billingCycle === 'monthly' ? 'text-gray-900' : 'text-gray-400 hover:text-gray-600'"
      >
        Monthly
      </button>
      <span class="text-gray-300">·</span>
      <button
        @click="billingCycle = 'yearly'"
        class="text-[11px] font-medium transition-colors flex items-center gap-1"
        :class="billingCycle === 'yearly' ? 'text-gray-900' : 'text-gray-400 hover:text-gray-600'"
      >
        Yearly
        <span class="text-[10px] font-semibold text-green-600">−{{ yearlySavingsPercent }}%</span>
      </button>
    </div>

    <!-- Features -->
    <ul class="space-y-2 text-sm text-gray-700 mb-5 flex-1">
      <li v-for="feature in features" :key="feature" class="flex items-start gap-2">
        <svg class="w-4 h-4 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        <span>{{ feature }}</span>
      </li>
    </ul>

    <!-- CTA -->
    <button
      v-if="isCurrentPlan"
      class="w-full py-2 rounded-lg bg-gray-100 text-xs font-medium text-gray-600 cursor-default"
    >
      Current plan
    </button>
    <button
      v-else
      @click="$emit('checkout', { plan: planKey, billingCycle })"
      :disabled="isCheckingOut"
      class="w-full py-2 rounded-lg text-xs font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1.5"
      :class="highlight
        ? 'bg-orange-600 hover:bg-orange-500 text-white'
        : 'bg-gray-900 hover:bg-gray-800 text-white'"
    >
      <template v-if="isCheckingOut">
        <div class="animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent"></div>
        Redirecting…
      </template>
      <template v-else>
        {{ ctaLabel }}
      </template>
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  planName: { type: String, default: 'Pro' },
  planKey: { type: String, default: 'pro' },
  tagline: { type: String, default: '' },
  monthlyPrice: { type: [Number, String], default: 0 },
  yearlyPrice: { type: [Number, String, null], default: null },
  yearlySavingsPercent: { type: [Number, String], default: 17 },
  features: { type: Array, default: () => [] },
  isCurrentPlan: { type: Boolean, default: false },
  // boolean OR a plan-key string for matched loading state
  checkingOut: { type: [Boolean, String], default: false },
  ctaLabel: { type: String, default: 'Upgrade' },
  defaultBillingCycle: {
    type: String,
    default: 'monthly',
    validator: (v) => ['monthly', 'yearly'].includes(v),
  },
  // Highlights this card with orange accent + "Most popular" pill
  highlight: { type: Boolean, default: false },
})

defineEmits(['checkout'])

const billingCycle = ref(props.defaultBillingCycle)

const hasYearly = computed(() => props.yearlyPrice !== null && props.yearlyPrice !== '')

const displayedPrice = computed(() =>
  billingCycle.value === 'yearly' && hasYearly.value ? props.yearlyPrice : props.monthlyPrice
)

const priceSuffix = computed(() =>
  billingCycle.value === 'yearly' && hasYearly.value ? '/yr' : '/mo'
)

const isCheckingOut = computed(() => {
  if (typeof props.checkingOut === 'string') {
    return props.checkingOut === props.planKey
  }
  return Boolean(props.checkingOut)
})
</script>
