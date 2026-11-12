<script setup>
import { PUBLISH_STATUSES, findOption } from "@/constants/learning";

// Draft / Published label
const props = defineProps({
  status: { type: String, required: true },
});

const option = computed(() => findOption(PUBLISH_STATUSES, props.status));
</script>

<template>
  <VChip :color="option?.color" size="small" label>
    {{ $t(option?.title ?? status) }}
  </VChip>
</template>
