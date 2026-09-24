<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <div
    data-slot="popover-title"
    :class="cn('text-sm font-medium cn-font-heading', props.class)"
  >
    <slot />
  </div>
</template>
