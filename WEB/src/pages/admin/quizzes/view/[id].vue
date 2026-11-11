<script setup>
import { api } from "@/utils/api";
import LessonQuiz from "@/views/admin/learning/lessons/quiz/LessonQuiz.vue";

definePage({
  meta: {
    title: "Quiz",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-quizzes",
  },
});

// One quiz on its own page: settings + questions (add, edit, reorder, delete
// for people with "edit-quizzes"). `id` is the lesson id (one quiz per lesson).
const route = useRoute();
const lesson = ref(null);

onMounted(async () => {
  const res = await api.get(`admin/lessons/${route.params.id}`);
  lesson.value = res.data.data;
});
</script>

<template>
  <VCard v-if="lesson" flat border>
    <LessonQuiz :lesson="lesson" is-back />
  </VCard>
</template>
