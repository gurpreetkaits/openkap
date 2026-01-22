<template>
  <SBModal
    v-model="isOpen"
    :title="title || 'Confirm Action'"
    size="md"
    @close="handleClose"
  >
    <div class="flex flex-col sm:flex-row gap-4">
      <!-- Dynamic Icon -->
      <div class="flex-shrink-0 mx-auto sm:mx-0">
        <div :class="iconContainerClasses">
          <component :is="iconComponent" :class="iconClasses" />
        </div>
      </div>

      <!-- Content -->
      <div class="text-center sm:text-left">
        <h3 class="text-base font-semibold">
          {{ title || 'Confirm Action' }}
        </h3>
        <p class="mt-2 text-sm text-base-content/70">
          <slot>
            {{ message || 'Are you sure you want to proceed with this action?' }}
          </slot>
        </p>
      </div>
    </div>

    <template #footer>
      <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3">
        <button
          class="btn btn-ghost"
          @click="handleCancel"
          :disabled="loading"
        >
          {{ cancelText || 'Cancel' }}
        </button>

        <button
          :class="['btn', confirmButtonClass]"
          @click="handleConfirm"
        >
          <span v-if="loading" class="loading loading-spinner loading-sm"></span>
          {{ confirmText || 'Confirm' }}
        </button>
      </div>
    </template>
  </SBModal>
</template>

<script>
import { computed, h } from 'vue'
import SBModal from './SBModal.vue'

export default {
  name: 'SBConfirmModal',
  components: {
    SBModal
  },
  emits: ['update:modelValue', 'confirm', 'cancel', 'close'],
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    title: {
      type: String,
      default: ''
    },
    message: {
      type: String,
      default: ''
    },
    type: {
      type: String,
      default: 'info',
      validator: (value) => ['info', 'warning', 'danger', 'success'].includes(value)
    },
    confirmText: {
      type: String,
      default: 'Confirm'
    },
    cancelText: {
      type: String,
      default: 'Cancel'
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  setup(props, { emit }) {
    const isOpen = computed({
      get: () => props.modelValue,
      set: (value) => emit('update:modelValue', value)
    })

    const typeConfig = computed(() => {
      const configs = {
        info: {
          containerClass: 'bg-info/10',
          iconClass: 'text-info',
          buttonClass: 'btn-primary'
        },
        warning: {
          containerClass: 'bg-warning/10',
          iconClass: 'text-warning',
          buttonClass: 'btn-warning'
        },
        danger: {
          containerClass: 'bg-error/10',
          iconClass: 'text-error',
          buttonClass: 'btn-error'
        },
        success: {
          containerClass: 'bg-success/10',
          iconClass: 'text-success',
          buttonClass: 'btn-success'
        }
      }
      return configs[props.type] || configs.info
    })

    const iconContainerClasses = computed(() => {
      return `w-12 h-12 rounded-full flex items-center justify-center ${typeConfig.value.containerClass}`
    })

    const iconClasses = computed(() => {
      return `h-6 w-6 ${typeConfig.value.iconClass}`
    })

    const confirmButtonClass = computed(() => {
      return typeConfig.value.buttonClass
    })

    const iconComponent = computed(() => {
      const icons = {
        info: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          'stroke-width': '1.5',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            d: 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'
          })
        ]),
        warning: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          'stroke-width': '1.5',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            d: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'
          })
        ]),
        danger: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          'stroke-width': '1.5',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            d: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'
          })
        ]),
        success: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          'stroke-width': '1.5',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'd': 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
          })
        ])
      }
      return icons[props.type] || icons.info
    })

    const handleConfirm = () => {
      emit('confirm')
    }

    const handleCancel = () => {
      emit('cancel')
      isOpen.value = false
    }

    const handleClose = () => {
      emit('close')
      isOpen.value = false
    }

    return {
      isOpen,
      iconContainerClasses,
      iconClasses,
      iconComponent,
      confirmButtonClass,
      handleConfirm,
      handleCancel,
      handleClose
    }
  }
}
</script>
