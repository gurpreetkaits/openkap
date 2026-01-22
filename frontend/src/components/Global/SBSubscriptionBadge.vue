<template>
  <div class="inline-flex items-center cursor-pointer" @click="handleClick">
    <div class="flex flex-col items-end gap-1">
      <div :class="badgeClasses">
        {{ badgeText }}
      </div>
      <div v-if="subscription" class="text-xs text-base-content/60 font-medium">
        {{ quotaText }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  subscription: {
    type: Object,
    default: null,
  },
  clickable: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['click'])

const badgeText = computed(() => {
  if (!props.subscription) return 'Free'
  return props.subscription.is_active ? 'Pro' : 'Free'
})

const badgeClasses = computed(() => {
  const base = 'badge badge-sm font-semibold uppercase tracking-wide transition-all'
  if (!props.subscription || !props.subscription.is_active) {
    return `${base} badge-ghost`
  }
  return `${base} badge-primary`
})

const quotaText = computed(() => {
  if (!props.subscription) return '0/1 videos'

  if (props.subscription.remaining_quota === null) {
    return 'Unlimited'
  }

  const used = props.subscription.videos_count || 0
  const total = used + (props.subscription.remaining_quota || 0)
  return `${used}/${total} videos`
})

function handleClick() {
  if (props.clickable) {
    emit('click')
  }
}
</script>
