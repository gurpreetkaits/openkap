<template>
  <Dialog :open="modelValue" @update:open="onOpenChange">
    <DialogContent
      :class="cn(sizeClass, paddingClass)"
      :show-close-button="closable"
      @interact-outside="onInteractOutside"
      @escape-key-down="onEscapeKeyDown"
    >
      <DialogHeader v-if="title || $slots.header">
        <slot name="header">
          <DialogTitle>{{ title }}</DialogTitle>
        </slot>
      </DialogHeader>

      <slot />

      <DialogFooter v-if="$slots.footer">
        <slot name="footer" />
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
/**
 * Thin wrapper over the shadcn Dialog.
 *
 * The original API (v-model, title, size, closable, closeOnBackdrop,
 * padding, plus header/footer slots) is preserved so the thirteen existing
 * call sites keep working. New code should use `@/components/ui/dialog`
 * directly.
 */
import { computed } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { cn } from '@/lib/utils'

defineOptions({ name: 'SBModal' })

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  title: { type: String, default: '' },
  size: {
    type: String,
    default: 'md',
    validator: (v) =>
      ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'].includes(v),
  },
  closable: { type: Boolean, default: true },
  closeOnBackdrop: { type: Boolean, default: true },
  padding: {
    type: String,
    default: 'default',
    validator: (v) => ['none', 'sm', 'default', 'lg'].includes(v),
  },
})

const emit = defineEmits(['update:modelValue', 'close'])

const SIZES = {
  xs: 'sm:max-w-xs',
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-md',
  lg: 'sm:max-w-lg',
  xl: 'sm:max-w-xl',
  '2xl': 'sm:max-w-2xl',
  '3xl': 'sm:max-w-3xl',
  '4xl': 'sm:max-w-4xl',
  '5xl': 'sm:max-w-5xl',
  '6xl': 'sm:max-w-6xl',
  '7xl': 'sm:max-w-7xl',
}

const PADDING = {
  none: 'p-0',
  sm: 'p-4',
  default: 'p-6',
  lg: 'p-8',
}

const sizeClass = computed(() => SIZES[props.size] ?? SIZES.md)
const paddingClass = computed(() => PADDING[props.padding] ?? PADDING.default)

function close() {
  emit('update:modelValue', false)
  emit('close')
}

function onOpenChange(open) {
  if (!open) close()
}

// `closable` and `closeOnBackdrop` were independent in the original: a modal
// could be dismissed by clicking away but not by a close button, or vice
// versa. Preserve both by vetoing the matching Dialog events.
function onInteractOutside(event) {
  if (!props.closeOnBackdrop) event.preventDefault()
}

function onEscapeKeyDown(event) {
  if (!props.closable) event.preventDefault()
}
</script>
