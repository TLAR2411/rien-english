<script setup>
/**
 * The 4 key numbers in one calm card, side by side (2 × 2 on phones).
 * `stats` = GET admin/dashboard.
 */
const props = defineProps({
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
});

const { t } = useI18n({ useScope: "global" });

const items = computed(() => {
  const s = props.stats;
  const days = s.recent_days ?? 7;
  const activeShare = s.students ? Math.round(((s.active_students ?? 0) / s.students) * 100) : 0;

  return [
    {
      label: "Students",
      value: s.students ?? 0,
      hint: s.new_students ? t("+{count} new in {days} days", { count: s.new_students, days }) : t("No new sign-ups in {days} days", { days }),
      good: !!s.new_students,
    },
    {
      label: "Active students",
      value: s.active_students ?? 0,
      hint: t("{percent}% of students, last {days} days", { percent: activeShare, days }),
    },
    {
      label: "Quizzes taken",
      value: s.quizzes_taken ?? 0,
      hint: t("{count} in the last {days} days", { count: s.quizzes_taken_recent ?? 0, days }),
    },
    {
      label: "Average score",
      value: s.average_percent == null ? "—" : `${s.average_percent}%`,
      hint: s.quizzes_taken ? t("From {count} quiz tries", { count: s.quizzes_taken }) : t("No quizzes taken yet."),
    },
  ];
});
</script>

<template>
  <VCard class="overview">
    <VRow no-gutters>
      <VCol v-for="item in items" :key="item.label" cols="6" md="3" class="overview__cell">
        <div class="overview__body">
          <div class="text-body-2 text-medium-emphasis">{{ $t(item.label) }}</div>
          <VSkeletonLoader v-if="loading" type="heading" width="80" class="my-1" />
          <div v-else class="overview__value text-high-emphasis">{{ item.value }}</div>
          <div class="text-body-2" :class="item.good ? 'text-success' : 'text-medium-emphasis'">
            {{ loading ? " " : item.hint }}
          </div>
        </div>
      </VCol>
    </VRow>
  </VCard>
</template>

<style lang="scss" scoped>
.overview__body {
  padding: 20px 24px;
}

.overview__cell {
  border-inline-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));

  &:first-child {
    border-inline-start: 0;
  }
}

.overview__value {
  font-size: 2.25rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  line-height: 1.25;
  margin-block: 4px;
}

/* Phones: 2 × 2, lines between the cells */
@media (max-width: 959.98px) {
  .overview__body {
    padding: 16px;
  }

  .overview__cell {
    &:nth-child(odd) {
      border-inline-start: 0;
    }

    &:nth-child(n + 3) {
      border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    }
  }

  .overview__value {
    font-size: 1.75rem;
  }
}
</style>
