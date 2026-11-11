<script setup>
import QuizResultQuestion from "@/components/learning/student/QuizResultQuestion.vue";
import { getQuizResult } from "@/services/site";
import { formatLearningDate } from "@/utils/learningDate";
import { myQuizzesRoute, studentLessonRoute, studentQuizRoute } from "@/utils/studentRoutes";

/**
 * A quiz result (right after submit, or opened from My Quizzes): score, %,
 * pass / not yet, stars, then every question with the student's answer,
 * the correct answer and the explanation (English / ខ្មែរ).
 */
definePage({
  meta: {
    title: "Quiz result",
    layout: "default",
    requiresAuth: true,
  },
});

const route = useRoute();
const { locale } = useI18n({ useScope: "global" });

const result = ref(null);
const notFound = ref(false);

const load = async () => {
  result.value = null;
  notFound.value = false;
  try {
    result.value = await getQuizResult(route.params.id);
    document.title = `${result.value.title} — Rien English`;
  } catch {
    notFound.value = true;
  }
};
watch(() => route.params.id, load, { immediate: true });

const language = ref(locale.value === "km" ? "km" : "en");
const hasKhmer = computed(() => result.value?.questions.some((question) => question.explanation_km));
const date = computed(() => formatLearningDate(result.value?.finished_at, locale.value));
const rightCount = computed(() => result.value?.questions.filter((question) => question.is_correct).length ?? 0);
</script>

<template>
  <section class="quiz-result">
    <VBtn :to="myQuizzesRoute()" variant="text" prepend-icon="tabler-arrow-left" class="ms-n3 mb-4">
      {{ $t("My Quizzes") }}
    </VBtn>

    <template v-if="result">
      <!-- Score -->
      <VCard class="mb-4">
        <VCardText class="text-center pa-6 pa-sm-8">
          <p class="text-body-2 text-medium-emphasis mb-1">{{ result.lesson?.title_en }}</p>
          <h1 class="text-h5 font-weight-bold mb-5">{{ result.title }}</h1>

          <div class="quiz-result__stars" :aria-label="$t('{count} of 3 stars', { count: result.stars })">
            <VIcon
              v-for="n in 3"
              :key="n"
              :icon="n <= result.stars ? 'tabler-star-filled' : 'tabler-star'"
              :class="n <= result.stars ? 'quiz-result__star--on' : 'text-disabled'"
              size="36"
            />
          </div>

          <p class="quiz-result__percent">{{ result.percent }}%</p>
          <VChip :color="result.passed ? 'success' : 'warning'" variant="tonal" class="mb-3">
            {{ result.passed ? $t("Passed") : $t("Not passed yet — you need {score}%", { score: result.pass_score }) }}
          </VChip>
          <p class="text-body-1 text-medium-emphasis mb-6">
            {{ $t("{right} of {total} questions right", { right: rightCount, total: result.questions.length }) }} ·
            {{ $t("{score} of {total} points", { score: result.score, total: result.total }) }}
            <br />
            <span class="text-body-2">{{ date }}</span>
          </p>

          <div class="d-flex flex-wrap justify-center gap-3">
            <VBtn v-if="result.lesson" :to="studentQuizRoute(result.lesson.slug)" variant="outlined" prepend-icon="tabler-refresh">
              {{ $t("Retry") }}
            </VBtn>
            <VBtn v-if="result.lesson" :to="studentLessonRoute(result.lesson.slug)" variant="outlined" prepend-icon="tabler-book">
              {{ $t("Back to the lesson") }}
            </VBtn>
            <VBtn
              v-if="result.next_lesson"
              :to="studentLessonRoute(result.next_lesson.slug)"
              color="primary"
              append-icon="tabler-arrow-right"
            >
              {{ $t("Next lesson") }}
            </VBtn>
          </div>
        </VCardText>
      </VCard>

      <!-- Every question -->
      <VCard>
        <VCardItem>
          <VCardTitle>{{ $t("Your answers") }}</VCardTitle>
          <template #append>
            <VBtnToggle
              v-if="hasKhmer"
              v-model="language"
              mandatory
              density="compact"
              color="primary"
              variant="outlined"
              divided
              :aria-label="$t('Explanation language')"
            >
              <VBtn value="en">English</VBtn>
              <VBtn value="km">ខ្មែរ</VBtn>
            </VBtnToggle>
          </template>
        </VCardItem>
        <VCardText class="pt-0">
          <ol class="quiz-result__questions">
            <QuizResultQuestion
              v-for="(question, index) in result.questions"
              :key="question.id"
              :question="question"
              :number="index + 1"
              :language="language"
            />
          </ol>
        </VCardText>
      </VCard>
    </template>

    <VCard v-else-if="notFound">
      <VCardText class="text-center py-10">
        <h1 class="text-h5 font-weight-bold mb-2">{{ $t("Result not found") }}</h1>
        <VBtn :to="myQuizzesRoute()" class="mt-4">{{ $t("My Quizzes") }}</VBtn>
      </VCardText>
    </VCard>

    <VProgressLinear v-else indeterminate color="primary" />
  </section>
</template>

<style lang="scss" scoped>
.quiz-result {
  max-width: 760px;
  margin-inline: auto;
}

.quiz-result__stars {
  display: flex;
  justify-content: center;
  gap: 0.25rem;
  margin-block-end: 0.5rem;
}

.quiz-result__star--on {
  color: #f5a524;
}

.quiz-result__percent {
  margin: 0 0 0.5rem;
  font-size: 3.5rem;
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: -0.02em;
}

.quiz-result__questions {
  margin: 0;
  padding: 0;
  list-style: none;
}
</style>
