<script setup>
import { WRITING_STATUSES, findOption } from "@/constants/learning";

// "Waiting" / "Reviewed · 85" chip for a student's writing
const props = defineProps({
  status: { type: String, required: true },
  score: { type: Number, default: null },
  size: { type: String, default: "small" },
});

const option = computed(() => findOption(WRITING_STATUSES, props.status));
</script>

<template>
  <VChip :size="size" :color="option?.color" variant="tonal" class="font-weight-medium">
    <VIcon :icon="option?.icon" size="16" start />
    {{ $t(option?.short ?? status) }}<template v-if="status === 'reviewed' && score !== null"> · {{ score }}</template>
  </VChip>
</template>
