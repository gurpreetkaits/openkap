<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <div
    data-slot="card-description"
    :class="cn('text-muted-foreground text-xs/relaxed', props.class)"
  >
    <slot />
  </div>
</template>
