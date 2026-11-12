<script setup>
import ProgressRow from "@/components/learning/student/ProgressRow.vue";
import ScoreHistoryChart from "@/components/learning/student/ScoreHistoryChart.vue";
import QuizScoreTable from "@/components/learning/QuizScoreTable.vue";
import { SKILLS, findOption } from "@/constants/learning";
import { getMyProgress } from "@/services/site";
import { quizResultRoute, studentLessonRoute, studentLessonsRoute } from "@/utils/studentRoutes";

/** "My Progress": scores chart, topics to review, % of lessons done per skill and level, every quiz score. */
definePage({
  meta: {
    title: "My Progress",
    layout: "default",
    requiresAuth: true,
  },
});

const { locale } = useI18n({ useScope: "global" });

const data = ref(null);
const isLoading = ref(true);

onMounted(async () => {
  try {
    data.value = await getMyProgress();
  } finally {
    isLoading.value = false;
  }
});

const skills = computed(() => data.value?.skills ?? []);
const levels = computed(() => data.value?.levels ?? []);
const quizzes = computed(() => data.value?.quizzes ?? []);
const history = computed(() => data.value?.history ?? []);
const toReview = computed(() => data.value?.to_review ?? []);

const skillTitle = (skill) => findOption(SKILLS, skill)?.title ?? skill;
const skillIcon = (skill) => findOption(SKILLS, skill)?.icon;
const levelName = (level) => (locale.value === "km" && level.name_km ? level.name_km : level.name);
const khmer = (lesson) => (locale.value === "km" ? lesson?.title_km : null);
</script>

<template>
  <section>
    <header class="mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t("My Progress") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-0">{{ $t("What you have learned so far.") }}</p>
    </header>

    <VProgressLinear v-if="isLoading" indeterminate color="primary" />

    <VRow v-else>
      <!-- Scores over time -->
      <VCol cols="12" md="8">
        <VCard class="h-100">
          <VCardItem>
            <VCardTitle>{{ $t("Scores over time") }}</VCardTitle>
            <VCardSubtitle v-if="history.length">{{ $t("Your last {count} quiz tries", { count: history.length }) }}</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <ScoreHistoryChart v-if="history.length" :attempts="history" />
            <p v-else class="text-body-1 text-medium-emphasis py-8 text-center mb-0">
              {{ $t("Take a quiz to see your scores here.") }}
            </p>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Topics to review: best score still under the pass score -->
      <VCol cols="12" md="4">
        <VCard class="h-100">
          <VCardItem>
            <VCardTitle>{{ $t("Topics to review") }}</VCardTitle>
            <VCardSubtitle v-if="toReview.length">{{ $t("Read the lesson again, then retry the quiz.") }}</VCardSubtitle>
          </VCardItem>

          <VList v-if="toReview.length" class="pt-0" lines="two">
            <VListItem
              v-for="topic in toReview"
              :key="topic.quiz_id"
              :to="topic.lesson ? studentLessonRoute(topic.lesson.slug) : undefined"
              class="review-row"
            >
              <VListItemTitle class="font-weight-medium">{{ topic.lesson?.title_en ?? topic.quiz_title }}</VListItemTitle>
              <VListItemSubtitle>
                <span v-if="khmer(topic.lesson)">{{ khmer(topic.lesson) }} · </span>{{ $t("Pass score {score}%", { score: topic.pass_score }) }}
              </VListItemSubtitle>
              <template #append>
                <VChip size="small" color="warning" variant="tonal" :title="$t('Best score')">{{ topic.best_percent }}%</VChip>
              </template>
            </VListItem>
          </VList>

          <VCardText v-else class="d-flex align-center gap-3 text-body-1 text-medium-emphasis">
            <template v-if="quizzes.length">
              <VIcon icon="tabler-circle-check-filled" color="success" size="24" />
              {{ $t("Nothing to review. You passed every quiz you took.") }}
            </template>
            <template v-else>{{ $t("Quizzes you do not pass yet will show here.") }}</template>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Lessons done per skill -->
      <VCol cols="12" md="6">
        <VCard class="h-100">
          <VCardItem>
            <VCardTitle>{{ $t("By skill") }}</VCardTitle>
          </VCardItem>
          <VCardText class="d-flex flex-column gap-4">
            <ProgressRow
              v-for="item in skills"
              :key="item.skill"
              :item="item"
              :title="$t(skillTitle(item.skill))"
              :icon="skillIcon(item.skill)"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- Lessons done per level -->
      <VCol cols="12" md="6">
        <VCard class="h-100">
          <VCardItem>
            <VCardTitle>{{ $t("By level") }}</VCardTitle>
          </VCardItem>
          <VCardText class="d-flex flex-column gap-4">
            <ProgressRow
              v-for="level in levels"
              :key="level.id"
              :item="level"
              :title="levelName(level)"
              :badge="level.code"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- Quiz scores -->
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>{{ $t("Quiz scores") }}</VCardTitle>
            <VCardSubtitle v-if="quizzes.length">{{ $t("Tap a quiz to see your last result.") }}</VCardSubtitle>
          </VCardItem>

          <QuizScoreTable v-if="quizzes.length" :rows="quizzes" :row-link="(row) => quizResultRoute(row.last_attempt_id)" />

          <VCardText v-else class="text-center py-8">
            <p class="text-body-1 text-medium-emphasis mb-4">
              {{ $t("No quizzes yet. Read a lesson, then take its quiz.") }}
            </p>
            <VBtn :to="studentLessonsRoute()">{{ $t("All lessons") }}</VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style scoped>
.review-row {
  min-block-size: 60px;
}
</style>
