<script setup>
import { studentLessonRoute } from "@/utils/studentRoutes";
import { formatLearningDate } from "@/utils/learningDate";

/** One finished lesson: ✓, title, date, best quiz score. `lesson` = API DoneLessonResource. */
const props = defineProps({
  lesson: { type: Object, required: true },
  showLevel: { type: Boolean, default: false },
});

const { locale } = useI18n({ useScope: "global" });
const date = computed(() => formatLearningDate(props.lesson.completed_at, locale.value));
const khmerTitle = computed(() => (locale.value === "km" ? props.lesson.title_km : null));
</script>

<template>
  <VListItem :to="studentLessonRoute(lesson.slug)" class="done-row">
    <template #prepend>
      <VIcon icon="tabler-circle-check-filled" color="success" size="24" class="me-3" />
    </template>

    <VListItemTitle class="font-weight-medium">
      <VChip v-if="showLevel && lesson.level" size="x-small" color="primary" label class="me-2">
        {{ lesson.level.code }}
      </VChip>
      {{ lesson.title_en }}
    </VListItemTitle>
    <VListItemSubtitle>
      <span v-if="khmerTitle">{{ khmerTitle }} · </span>{{ $t("Done on {date}", { date }) }}
    </VListItemSubtitle>

    <template #append>
      <VChip
        v-if="lesson.best_percent !== null"
        size="small"
        :color="lesson.passed ? 'success' : 'warning'"
        variant="tonal"
      >
        {{ lesson.best_percent }}%
      </VChip>
      <span v-else class="text-disabled" :title="$t('No quiz score yet')">—</span>
    </template>
  </VListItem>
</template>

<style scoped>
.done-row {
  min-height: 60px;
}
</style>
