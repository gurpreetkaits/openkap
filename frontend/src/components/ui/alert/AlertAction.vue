<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <div
    data-slot="alert-action"
    :class="
      cn(
        'absolute top-[calc(--spacing(1.25))] right-[calc(--spacing(1.25))]',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
