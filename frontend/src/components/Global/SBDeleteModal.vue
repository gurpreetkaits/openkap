<template>
  <SBModal
    v-model="isOpen"
    :title="title || 'Delete Item'"
    size="md"
    @close="handleClose"
  >
    <div class="flex flex-col sm:flex-row gap-4">
      <!-- Warning Icon -->
      <div class="flex-shrink-0 mx-auto sm:mx-0">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center">
          <svg class="h-6 w-6 text-error" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
      </div>

      <!-- Content -->
      <div class="text-center sm:text-left">
        <h3 class="text-base font-semibold">
          {{ title || 'Delete Item' }}
        </h3>
        <p class="mt-2 text-sm text-base-content/70">
          <slot>
            {{ message || 'Are you sure you want to delete this item? This action cannot be undone.' }}
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
          class="btn btn-error"
          @click="handleConfirm"
          :class="{ 'loading': loading }"
        >
          <span v-if="loading" class="loading loading-spinner loading-sm"></span>
          {{ confirmText || 'Delete' }}
        </button>
      </div>
    </template>
  </SBModal>
</template>

<script>
import { computed } from 'vue'
import SBModal from './SBModal.vue'

export default {
  name: 'SBDeleteModal',
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
    confirmText: {
      type: String,
      default: 'Delete'
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
      handleConfirm,
      handleCancel,
      handleClose
    }
  }
}
</script>
