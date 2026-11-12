<script setup>
import { lessonPreviewRoute } from "@/utils/lessonRoutes";

/**
 * Lessons most students finished, with a thin bar to compare them.
 * `lessons` = popular_lessons from GET admin/dashboard.
 */
const props = defineProps({
  lessons: { type: Array, required: true },
});

const { locale } = useI18n({ useScope: "global" });

// Bars compare "finished"; when nobody finished yet, they compare quiz tries
const metric = computed(() => (props.lessons.some((lesson) => lesson.finished_count) ? "finished_count" : "attempts_count"));
const max = computed(() => Math.max(1, ...props.lessons.map((lesson) => lesson[metric.value])));
const share = (lesson) => Math.round((lesson[metric.value] / max.value) * 100);
const title = (lesson) => (locale.value === "km" && lesson.title_km) || lesson.title_en;
</script>

<template>
  <VCard>
    <VCardItem>
      <VCardTitle>{{ $t("Most popular lessons") }}</VCardTitle>
      <VCardSubtitle>{{ $t("Lessons most students finished.") }}</VCardSubtitle>
    </VCardItem>

    <VList v-if="lessons.length" class="pt-0">
      <VListItem v-for="(lesson, index) in lessons" :key="lesson.id" :to="lessonPreviewRoute(lesson.id)" class="popular-row">
        <div class="d-flex align-center gap-4">
          <span class="popular-row__rank text-body-1 text-disabled">{{ index + 1 }}</span>

          <div class="flex-grow-1 overflow-hidden">
            <div class="d-flex align-center justify-space-between gap-3">
              <span class="text-body-1 font-weight-medium text-truncate">
                <VChip v-if="lesson.level_code" size="x-small" color="primary" label class="me-1">{{ lesson.level_code }}</VChip>
                {{ title(lesson) }}
              </span>
              <span class="text-body-2 text-medium-emphasis text-no-wrap">
                {{ $t("{count} finished", { count: lesson.finished_count }) }} ·
                {{ $t("{count} quiz tries", { count: lesson.attempts_count }) }}
              </span>
            </div>
            <VProgressLinear
              :model-value="share(lesson)"
              color="primary"
              bg-color="on-surface"
              bg-opacity="0.06"
              height="4"
              rounded
              class="mt-2"
            />
          </div>

          <VIcon icon="tabler-chevron-right" size="18" class="text-disabled" />
        </div>
      </VListItem>
    </VList>

    <VCardText v-else class="text-body-1 text-medium-emphasis">
      {{ $t("No students have finished a lesson yet.") }}
    </VCardText>
  </VCard>
</template>

<style scoped>
.popular-row {
  padding-block: 12px !important;
}

.popular-row__rank {
  font-variant-numeric: tabular-nums;
  inline-size: 1rem;
  text-align: center;
}

/* Phones: numbers under the title */
@media (max-width: 599.98px) {
  .popular-row .justify-space-between {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 2px !important;
  }
}
</style>
