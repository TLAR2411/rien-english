<script setup>
/**
 * How quiz tries went: a ring with the share that passed, then passed / not passed and the average.
 * `stats` = GET admin/dashboard.
 */
const props = defineProps({
  stats: { type: Object, required: true },
});

const taken = computed(() => props.stats.quizzes_taken ?? 0);
const passed = computed(() => props.stats.quizzes_passed ?? 0);
const passRate = computed(() => (taken.value ? Math.round((passed.value / taken.value) * 100) : 0));
</script>

<template>
  <VCard class="h-100">
    <VCardItem>
      <VCardTitle>{{ $t("Quiz results") }}</VCardTitle>
      <VCardSubtitle>{{ $t("All quiz tries") }}</VCardSubtitle>
    </VCardItem>

    <VCardText v-if="taken" class="d-flex flex-column align-center gap-5">
      <VProgressCircular :model-value="passRate" :size="148" :width="12" color="success" bg-color="warning" class="results-ring">
        <div class="text-center">
          <div class="results-ring__value text-high-emphasis">{{ passRate }}%</div>
          <div class="text-body-2 text-medium-emphasis">{{ $t("passed") }}</div>
        </div>
      </VProgressCircular>

      <div class="results-list w-100">
        <div class="results-list__row">
          <span class="d-inline-flex align-center gap-2"><span class="legend-dot bg-success" />{{ $t("Passed") }}</span>
          <span class="font-weight-medium">{{ passed }}</span>
        </div>
        <div class="results-list__row">
          <span class="d-inline-flex align-center gap-2"><span class="legend-dot bg-warning" />{{ $t("Not passed yet") }}</span>
          <span class="font-weight-medium">{{ taken - passed }}</span>
        </div>
        <div class="results-list__row">
          <span>{{ $t("Average score") }}</span>
          <span class="font-weight-medium">{{ stats.average_percent }}%</span>
        </div>
      </div>
    </VCardText>

    <VCardText v-else class="text-body-1 text-medium-emphasis py-10 text-center">
      {{ $t("No quizzes taken yet.") }}
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.results-ring :deep(.v-progress-circular__underlay) {
  opacity: 0.25;
}

.results-ring__value {
  font-size: 1.75rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
}

.results-list__row {
  display: flex;
  justify-content: space-between;
  padding-block: 10px;
  font-variant-numeric: tabular-nums;

  & + & {
    border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }
}

.legend-dot {
  block-size: 8px;
  border-radius: 50%;
  inline-size: 8px;
}
</style>
