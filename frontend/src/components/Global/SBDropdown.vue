<template>
  <div ref="containerRef" class="relative inline-block">
    <div ref="triggerRef" @click="toggle">
      <slot name="trigger" />
    </div>

    <Teleport to="body">
      <Transition
        enter-active-class="dropdown-enter-active"
        leave-active-class="dropdown-leave-active"
        enter-from-class="dropdown-enter-from"
        leave-to-class="dropdown-leave-to"
      >
        <div
          v-if="modelValue"
          ref="panelRef"
          :style="panelStyle"
          :class="['absolute z-50', panelClass]"
          role="menu"
          aria-orientation="vertical"
          @click.stop="handleItemClick"
        >
          <slot />
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue'

export default {
  name: 'SBDropdown',
  emits: ['update:modelValue', 'open', 'close'],
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    align: {
      type: String,
      default: 'right',
      validator: (v) => ['left', 'right'].includes(v),
    },
    side: {
      type: String,
      default: 'bottom',
      validator: (v) => ['bottom', 'top'].includes(v),
    },
    width: {
      type: String,
      default: 'auto',
      validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl', 'auto'].includes(v),
    },
    closeOnClick: {
      type: Boolean,
      default: true,
    },
    panelClass: {
      type: String,
      default: 'menu-panel',
    },
  },
  setup(props, { emit }) {
    const containerRef = ref(null)
    const triggerRef = ref(null)
    const panelRef = ref(null)

    const widthClass = computed(() => {
      const widths = { xs: 'w-36', sm: 'w-44', md: 'w-52', lg: 'w-56', xl: 'w-64', auto: '' }
      return widths[props.width] || ''
    })

    const panelStyle = ref({})

    const recalculatePosition = () => {
      if (!triggerRef.value) return

      const trigger = triggerRef.value.getBoundingClientRect()
      const style = { minWidth: trigger.width + 'px' }

      if (props.align === 'right') {
        style.right = window.innerWidth - trigger.right + 'px'
      } else {
        style.left = trigger.left + 'px'
      }

      if (props.side === 'top') {
        style.bottom = window.innerHeight - trigger.top + 4 + 'px'
      } else {
        style.top = trigger.bottom + 4 + 'px'
      }

      panelStyle.value = style
    }

    const toggle = () => {
      if (props.modelValue) {
        close()
      } else {
        open()
      }
    }

    const open = () => {
      recalculatePosition()
      emit('update:modelValue', true)
      emit('open')
      nextTick(() => panelRef.value?.focus())
    }

    const close = () => {
      emit('update:modelValue', false)
      emit('close')
    }

    const handleItemClick = (e) => {
      if (props.closeOnClick && e.target.closest('[data-dropdown-item]')) {
        close()
      }
    }

    const onClickOutside = (e) => {
      if (
        props.modelValue &&
        containerRef.value &&
        !containerRef.value.contains(e.target) &&
        panelRef.value &&
        !panelRef.value.contains(e.target)
      ) {
        close()
      }
    }

    const onKeydown = (e) => {
      if (!props.modelValue) return

      if (e.key === 'Escape') {
        e.preventDefault()
        close()
        nextTick(() => triggerRef.value?.focus())
        return
      }

      if (!panelRef.value) return

      const items = panelRef.value.querySelectorAll(
        '[data-dropdown-item]:not([disabled]):not(.opacity-40)'
      )
      if (items.length === 0) return

      const current = Array.from(items).indexOf(document.activeElement)

      if (e.key === 'ArrowDown') {
        e.preventDefault()
        const next = current < items.length - 1 ? current + 1 : 0
        items[next].focus()
      } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        const prev = current > 0 ? current - 1 : items.length - 1
        items[prev].focus()
      }
    }

    watch(
      () => props.modelValue,
      (val) => {
        if (val) {
          recalculatePosition()
          document.addEventListener('click', onClickOutside, true)
          document.addEventListener('keydown', onKeydown)
          window.addEventListener('resize', recalculatePosition)
          window.addEventListener('scroll', recalculatePosition, true)
        } else {
          document.removeEventListener('click', onClickOutside, true)
          document.removeEventListener('keydown', onKeydown)
          window.removeEventListener('resize', recalculatePosition)
          window.removeEventListener('scroll', recalculatePosition, true)
        }
      }
    )

    onBeforeUnmount(() => {
      document.removeEventListener('click', onClickOutside, true)
      document.removeEventListener('keydown', onKeydown)
      window.removeEventListener('resize', recalculatePosition)
      window.removeEventListener('scroll', recalculatePosition, true)
    })

    return {
      containerRef,
      triggerRef,
      panelRef,
      panelStyle,
      toggle,
      open,
      close,
      handleItemClick,
      widthClass,
    }
  },
}
</script>
