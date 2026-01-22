<template>
  <Teleport to="body">
    <dialog
      ref="dialogRef"
      class="modal"
      :class="{ 'modal-open': modelValue }"
      @click="handleBackdropClick"
    >
      <div :class="modalClasses" @click.stop>
        <!-- Header -->
        <div v-if="$slots.header || title || closable" class="flex items-center justify-between mb-4">
          <div>
            <slot name="header">
              <h3 v-if="title" class="font-bold text-lg">
                {{ title }}
              </h3>
            </slot>
          </div>

          <button
            v-if="closable"
            @click="close"
            class="btn btn-sm btn-circle btn-ghost"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div :class="bodyClasses">
          <slot />
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer" class="modal-action">
          <slot name="footer" />
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button v-if="closeOnBackdrop" @click="close">close</button>
      </form>
    </dialog>
  </Teleport>
</template>

<script>
import { computed, ref, watch } from 'vue'

export default {
  name: 'SBModal',
  emits: ['update:modelValue', 'close'],
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    title: {
      type: String,
      default: ''
    },
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'].includes(value)
    },
    closable: {
      type: Boolean,
      default: true
    },
    closeOnBackdrop: {
      type: Boolean,
      default: true
    },
    padding: {
      type: String,
      default: 'default',
      validator: (value) => ['none', 'sm', 'default', 'lg'].includes(value)
    }
  },
  setup(props, { emit }) {
    const dialogRef = ref(null)

    const sizeClasses = computed(() => {
      const sizes = {
        xs: 'max-w-xs',
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
        '3xl': 'max-w-3xl',
        '4xl': 'max-w-4xl',
        '5xl': 'max-w-5xl',
        '6xl': 'max-w-6xl',
        '7xl': 'max-w-7xl'
      }
      return sizes[props.size] || sizes.md
    })

    const modalClasses = computed(() => {
      return [
        'modal-box',
        sizeClasses.value
      ].join(' ')
    })

    const bodyClasses = computed(() => {
      const paddingClasses = {
        none: '',
        sm: 'py-2',
        default: 'py-4',
        lg: 'py-6'
      }
      return paddingClasses[props.padding] || paddingClasses.default
    })

    const close = () => {
      emit('update:modelValue', false)
      emit('close')
    }

    const handleBackdropClick = (e) => {
      if (e.target === dialogRef.value && props.closeOnBackdrop) {
        close()
      }
    }

    watch(() => props.modelValue, (newVal) => {
      if (dialogRef.value) {
        if (newVal) {
          dialogRef.value.showModal?.()
        } else {
          dialogRef.value.close?.()
        }
      }
    })

    return {
      dialogRef,
      modalClasses,
      bodyClasses,
      close,
      handleBackdropClick
    }
  }
}
</script>
