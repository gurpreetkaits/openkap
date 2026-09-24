<template>
  <SBModal
    v-model="isOpen"
    :title="title || 'Delete Item'"
    size="sm"
    @close="handleClose"
  >
    <div class="flex gap-4">
      <!-- Warning Icon -->
      <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
        <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <p class="text-sm text-gray-600 leading-relaxed">
          <slot>
            {{ message || 'Are you sure you want to delete this item? This action cannot be undone.' }}
          </slot>
        </p>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Button variant="outline"
          @click="handleCancel"
          :disabled="loading">
          {{ cancelText || 'Cancel' }}
        </Button>
        <Button variant="destructive"
          @click="handleConfirm"
          :disabled="loading"
          class="inline-flex items-center gap-2">
          <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ loading ? 'Deleting...' : (confirmText || 'Delete') }}
        </Button>
      </div>
    </template>
  </SBModal>
</template>

<script>
import { Button } from '@/components/ui/button'
import { computed } from 'vue'
import SBModal from './SBModal.vue'

export default {
  name: 'SBDeleteModal',
  components: { Button,
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
