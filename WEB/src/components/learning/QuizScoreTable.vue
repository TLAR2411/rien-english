<script setup>
import { useDisplay } from "vuetify";
import { SKILLS, findOption } from "@/constants/learning";
import { formatLearningDate } from "@/utils/learningDate";

/**
 * Quiz scores, one row per quiz: lesson, best %, last %, tries, last date.
 * `rows` = ProgressReport::quizScores() from the API. Used by the student's
 * "My Progress" page and by the admin user page.
 * `rowLink(row)` → a route to open when a row is clicked (optional).
 */
const props = defineProps({
  rows: { type: Array, required: true },
  rowLink: { type: Function, default: null },
});

const { locale } = useI18n({ useScope: "global" });
const { mdAndUp } = useDisplay();

const date = (value) => formatLearningDate(value, locale.value);
const khmer = (lesson) => (locale.value === "km" ? lesson?.title_km : null);
const skillIcon = (lesson) => findOption(SKILLS, lesson?.skill)?.icon ?? "tabler-checklist";
const link = (row) => props.rowLink?.(row) ?? undefined;
const scoreColor = (row) => (row.passed ? "success" : "warning");
</script>

<template>
  <!-- Big screens: a table -->
  <VTable v-if="mdAndUp" class="quiz-score-table">
    <thead>
      <tr>
        <th>{{ $t("Lesson") }}</th>
        <th class="text-center">{{ $t("Best score") }}</th>
        <th class="text-center">{{ $t("Last score") }}</th>
        <th class="text-center">{{ $t("Tries") }}</th>
        <th>{{ $t("Last taken") }}</th>
        <th v-if="rowLink" class="quiz-score-table__go" />
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="row in rows"
        :key="row.quiz_id"
        :class="{ 'quiz-score-table__link': rowLink }"
        @click="rowLink && $router.push(link(row))"
      >
        <td>
          <div class="d-flex align-center gap-3 py-2">
            <VIcon :icon="skillIcon(row.lesson)" size="20" class="text-medium-emphasis" />
            <div>
              <div class="font-weight-medium">
                <VChip v-if="row.lesson?.level_code" size="x-small" color="primary" label class="me-1">
                  {{ row.lesson.level_code }}
                </VChip>
                {{ row.lesson?.title_en ?? row.quiz_title }}
              </div>
              <div v-if="khmer(row.lesson)" class="text-body-2 text-medium-emphasis">{{ khmer(row.lesson) }}</div>
            </div>
          </div>
        </td>
        <td class="text-center">
          <VChip size="small" :color="scoreColor(row)" variant="tonal">{{ row.best_percent }}%</VChip>
        </td>
        <td class="text-center tabular">{{ row.last_percent }}%</td>
        <td class="text-center tabular">{{ row.attempts }}</td>
        <td class="text-no-wrap">{{ date(row.last_at) }}</td>
        <td v-if="rowLink" class="quiz-score-table__go">
          <VIcon icon="tabler-chevron-right" size="18" class="text-disabled" />
        </td>
      </tr>
    </tbody>
  </VTable>

  <!-- Phones: a list -->
  <VList v-else class="py-0" lines="two">
    <template v-for="(row, index) in rows" :key="row.quiz_id">
      <VDivider v-if="index" />
      <VListItem :to="link(row)" class="quiz-score-row">
        <VListItemTitle class="font-weight-medium">
          <VChip v-if="row.lesson?.level_code" size="x-small" color="primary" label class="me-1">
            {{ row.lesson.level_code }}
          </VChip>
          {{ row.lesson?.title_en ?? row.quiz_title }}
        </VListItemTitle>
        <VListItemSubtitle>
          {{ $t("Last {percent}%", { percent: row.last_percent }) }} ·
          {{ $t("{count} tries", { count: row.attempts }) }} · {{ date(row.last_at) }}
        </VListItemSubtitle>
        <template #append>
          <VChip size="small" :color="scoreColor(row)" variant="tonal" :title="$t('Best score')">
            {{ row.best_percent }}%
          </VChip>
        </template>
      </VListItem>
    </template>
  </VList>
</template>

<style scoped>
.tabular {
  font-variant-numeric: tabular-nums;
}

.quiz-score-table__link {
  cursor: pointer;
}

.quiz-score-table__go {
  inline-size: 40px;
  padding-inline: 8px !important;
}

.quiz-score-row {
  min-block-size: 64px;
}
</style>
