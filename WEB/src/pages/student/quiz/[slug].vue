<script setup>
import { studentLessonRoute } from "@/utils/studentRoutes";

/**
 * A lesson's quiz, inside the student system. Needs login: a visitor is sent
 * to log in / sign up and comes back here after. For now it only says the quiz
 * is coming — T25 (PLAN.md) replaces this page with the real quiz.
 */
definePage({
  meta: {
    title: "Quiz",
    layout: "default",
    requiresAuth: true,
  },
});

const route = useRoute();
</script>

<template>
  <VCard class="mx-auto" max-width="560">
    <VCardText class="text-center py-10">
      <VIcon icon="tabler-clock" size="40" color="primary" class="mb-4" />
      <h1 class="text-h5 font-weight-bold mb-2">{{ $t("Quizzes open soon") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-6">
        {{ $t("You are logged in. The quiz for this lesson will be ready very soon.") }}
      </p>
      <VBtn :to="studentLessonRoute(route.params.slug)" prepend-icon="tabler-arrow-left">
        {{ $t("Back to the lesson") }}
      </VBtn>
    </VCardText>
  </VCard>
</template>
