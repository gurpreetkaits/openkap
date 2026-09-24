<template>
  <Button
    :type="type"
    :variant="shadcnVariant"
    :size="shadcnSize"
    :disabled="disabled || loading"
    :class="cn(extraClasses, fullWidth && 'w-full')"
    @click="$emit('click', $event)"
    v-bind="$attrs"
  >
    <LoaderCircleIcon v-if="loading" class="animate-spin" />

    <slot name="iconLeft" v-if="$slots.iconLeft && !loading" />

    <slot />

    <slot name="iconRight" v-if="$slots.iconRight && !loading" />
  </Button>
</template>

<script setup>
/**
 * Thin wrapper over the shadcn Button.
 *
 * The original props (variant/size/rounded/fullWidth/loading) are kept so the
 * existing call sites do not have to change; they are mapped onto shadcn's
 * variants here. New code should reach for `@/components/ui/button` directly.
 */
import { computed } from 'vue'
import { LoaderCircleIcon } from "@lucide/vue"
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

defineOptions({ name: 'SBPrimaryButton', inheritAttrs: false })
defineEmits(['click'])

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'danger', 'success', 'ghost'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v),
  },
  type: {
    type: String,
    default: 'button',
    validator: (v) => ['button', 'submit', 'reset'].includes(v),
  },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  fullWidth: { type: Boolean, default: false },
  rounded: {
    type: String,
    default: 'md',
    validator: (v) => ['none', 'sm', 'md', 'lg', 'full'].includes(v),
  },
})

const shadcnVariant = computed(() => ({
  primary: 'default',
  secondary: 'secondary',
  danger: 'destructive',
  success: 'default',
  ghost: 'ghost',
}[props.variant] ?? 'default'))

const shadcnSize = computed(() => ({
  xs: 'sm',
  sm: 'sm',
  md: 'default',
  lg: 'lg',
  xl: 'lg',
}[props.size] ?? 'default'))

// Success has no shadcn equivalent, and `rounded` is not part of the Button
// API, so both are applied as overrides on top of the variant.
const extraClasses = computed(() => {
  const classes = []

  if (props.variant === 'success') {
    classes.push('bg-emerald-600 text-white hover:bg-emerald-700')
  }

  const radius = {
    none: 'rounded-none',
    sm: 'rounded-xs',
    md: 'rounded-md',
    lg: 'rounded-lg',
    full: 'rounded-full',
  }[props.rounded]

  if (radius) classes.push(radius)

  return classes
})
</script>
