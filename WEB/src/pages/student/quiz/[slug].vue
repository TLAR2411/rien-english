<script setup>
import QuizPlayer from "@/components/learning/student/QuizPlayer.vue";
import { useQuizDraft } from "@/composables/useQuizDraft";
import { getStudentQuiz, submitQuiz } from "@/services/site";
import { toAnswerList } from "@/utils/quiz";
import { studentLessonRoute, studentLessonsRoute } from "@/utils/studentRoutes";

/**
 * Take a lesson's quiz (students, inside the student system). A visitor is sent
 * to log in / sign up and comes back here after. Answers are graded by the API.
 */
definePage({
  meta: {
    title: "Quiz",
    layout: "default",
    requiresAuth: true,
  },
});

const route = useRoute();
const slug = computed(() => route.params.slug);

const quiz = ref(null);
const notFound = ref(false);
const answers = ref({});
const startedAt = ref(null);
const result = ref(null);
const isSubmitting = ref(false);

const draft = useQuizDraft(() => quiz.value?.id);

const load = async () => {
  quiz.value = null;
  result.value = null;
  notFound.value = false;
  try {
    quiz.value = await getStudentQuiz(slug.value);
    document.title = `${quiz.value.lesson.title_en} — Quiz — Rien English`;
    const saved = draft.read();
    answers.value = saved?.answers ?? {};
    startedAt.value = saved?.started_at ?? new Date().toISOString();
  } catch {
    notFound.value = true;
  }
};
watch(slug, load, { immediate: true });

watch(answers, (value) => quiz.value && draft.write(value, startedAt.value), { deep: true });

const submit = async () => {
  isSubmitting.value = true;
  try {
    result.value = await submitQuiz(quiz.value.id, {
      answers: toAnswerList(answers.value),
      started_at: startedAt.value,
    });
    draft.clear();
    window.scrollTo({ top: 0 });
  } finally {
    isSubmitting.value = false;
  }
};

const retry = () => {
  draft.clear();
  load();
};
</script>

<template>
  <section>
    <VBtn :to="studentLessonRoute(slug)" variant="text" prepend-icon="tabler-arrow-left" class="ms-n3 mb-4">
      {{ $t("Back to the lesson") }}
    </VBtn>

    <template v-if="quiz">
      <VCard>
        <VCardText class="pa-5 pa-sm-8">
          <header class="text-center mb-6">
            <p class="text-body-2 text-medium-emphasis mb-1">{{ quiz.lesson.title_en }}</p>
            <h1 class="text-h4 font-weight-bold mb-1">{{ quiz.title }}</h1>
            <p class="text-body-2 text-medium-emphasis mb-0">
              {{ $t("Pass with {score}%", { score: quiz.pass_score }) }}
            </p>
          </header>

          <!-- Short result (T26 adds the full result page) -->
          <div v-if="result" class="text-center py-6">
            <VIcon
              :icon="result.passed ? 'tabler-circle-check-filled' : 'tabler-circle-x'"
              :color="result.passed ? 'success' : 'warning'"
              size="56"
              class="mb-3"
            />
            <p class="text-h3 font-weight-bold mb-1">{{ result.percent }}%</p>
            <p class="text-body-1 text-medium-emphasis mb-6">
              {{ $t("{score} of {total} points", { score: result.score, total: result.total }) }} ·
              {{ result.passed ? $t("Passed") : $t("Not passed yet") }}
            </p>
            <div class="d-flex flex-wrap justify-center gap-3">
              <VBtn variant="outlined" prepend-icon="tabler-refresh" @click="retry">{{ $t("Try again") }}</VBtn>
              <VBtn :to="studentLessonRoute(slug)" color="primary">{{ $t("Back to the lesson") }}</VBtn>
            </div>
          </div>

          <QuizPlayer v-else v-model:answers="answers" :quiz="quiz" :submitting="isSubmitting" @submit="submit" />
        </VCardText>
      </VCard>
    </template>

    <VCard v-else-if="notFound">
      <VCardText class="text-center py-10">
        <h1 class="text-h5 font-weight-bold mb-2">{{ $t("Quiz not found") }}</h1>
        <p class="text-body-1 text-medium-emphasis mb-6">{{ $t("This lesson has no quiz yet, or it is not published.") }}</p>
        <VBtn :to="studentLessonsRoute()">{{ $t("See all lessons") }}</VBtn>
      </VCardText>
    </VCard>

    <VProgressLinear v-else indeterminate color="primary" />
  </section>
</template>
