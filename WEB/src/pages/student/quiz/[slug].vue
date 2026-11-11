<script setup>
import { lessonRoute } from "@/utils/siteRoutes";

/**
 * A lesson's quiz. Needs login: a visitor is sent to log in / sign up and comes
 * back here after. For now it only says the quiz is coming —
 * T25 (PLAN.md) replaces this page with the real quiz.
 */
definePage({
  meta: {
    title: "Quiz",
    layout: "public",
    requiresAuth: true,
  },
});

const route = useRoute();
</script>

<template>
  <div class="site-section">
    <div class="site-container quiz-soon">
      <h1 class="site-h2">{{ $t("Quizzes open soon") }}</h1>
      <p class="site-lead">{{ $t("You are logged in. The quiz for this lesson will be ready very soon.") }}</p>
      <RouterLink :to="lessonRoute(route.params.slug)" class="site-btn quiz-soon__btn">{{ $t("Back to the lesson") }}</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.quiz-soon {
  max-width: 560px;
  text-align: center;
}

.quiz-soon__btn {
  margin-block-start: 28px;
}
</style>
