<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="$emit('click', $event)"
    v-bind="$attrs"
  >
    <!-- Loading Spinner -->
    <span v-if="loading" class="loading loading-spinner loading-sm"></span>

    <!-- Icon (left) -->
    <span v-if="$slots.iconLeft && !loading">
      <slot name="iconLeft" />
    </span>

    <!-- Button Text -->
    <slot />

    <!-- Icon (right) -->
    <span v-if="$slots.iconRight && !loading">
      <slot name="iconRight" />
    </span>
  </button>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'SBPrimaryButton',
  inheritAttrs: false,
  emits: ['click'],
  props: {
    variant: {
      type: String,
      default: 'primary',
      validator: (value) => ['primary', 'secondary', 'danger', 'success', 'ghost'].includes(value)
    },
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    type: {
      type: String,
      default: 'button',
      validator: (value) => ['button', 'submit', 'reset'].includes(value)
    },
    disabled: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    },
    fullWidth: {
      type: Boolean,
      default: false
    },
    rounded: {
      type: String,
      default: 'md',
      validator: (value) => ['none', 'sm', 'md', 'lg', 'full'].includes(value)
    }
  },
  setup(props) {
    const variantClasses = computed(() => {
      const variants = {
        primary: 'btn-primary',
        secondary: 'btn-outline btn-primary',
        danger: 'btn-error',
        success: 'btn-success',
        ghost: 'btn-ghost text-primary'
      }
      return variants[props.variant] || variants.primary
    })

    const sizeClasses = computed(() => {
      const sizes = {
        xs: 'btn-xs',
        sm: 'btn-sm',
        md: '',
        lg: 'btn-lg',
        xl: 'btn-lg'
      }
      return sizes[props.size] || ''
    })

    const roundedClasses = computed(() => {
      const rounded = {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: '',
        lg: 'rounded-lg',
        full: 'rounded-full'
      }
      return rounded[props.rounded] || ''
    })

    const widthClasses = computed(() => {
      return props.fullWidth ? 'w-full' : ''
    })

    const buttonClasses = computed(() => {
      return [
        'btn',
        variantClasses.value,
        sizeClasses.value,
        roundedClasses.value,
        widthClasses.value
      ].filter(Boolean).join(' ')
    })

    return {
      buttonClasses
    }
  }
}
</script>
