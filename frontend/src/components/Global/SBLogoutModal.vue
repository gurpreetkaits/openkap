<template>
  <SBModal
    v-model="isOpen"
    title="Logout"
    size="md"
    @close="handleClose"
  >
    <div class="flex flex-col sm:flex-row gap-4">
      <!-- Logout Icon -->
      <div class="flex-shrink-0 mx-auto sm:mx-0">
        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
          <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
          </svg>
        </div>
      </div>

      <!-- Content -->
      <div class="text-center sm:text-left">
        <h3 class="text-base font-semibold">
          Logout
        </h3>
        <p class="mt-2 text-sm text-base-content/70">
          <slot>
            {{ message || 'Are you sure you want to logout? Any unsaved work will be lost.' }}
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
          class="btn btn-primary"
          @click="handleConfirm"
        >
          <span v-if="loading" class="loading loading-spinner loading-sm"></span>
          {{ confirmText || 'Logout' }}
        </button>
      </div>
    </template>
  </SBModal>
</template>

<script>
import { computed } from 'vue'
import SBModal from './SBModal.vue'

export default {
  name: 'SBLogoutModal',
  components: {
    SBModal
  },
  emits: ['update:modelValue', 'confirm', 'cancel', 'close'],
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    message: {
      type: String,
      default: ''
    },
    confirmText: {
      type: String,
      default: 'Logout'
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
