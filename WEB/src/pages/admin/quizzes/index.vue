<script setup>
import QuizList from "@/views/admin/learning/quizzes/QuizList.vue";

definePage({
  meta: {
    title: "Quizzes",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-quizzes",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <QuizList />
</template>
