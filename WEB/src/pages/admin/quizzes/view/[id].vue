<script setup>
import { api } from "@/utils/api";
import hasPermission from "@/utils/hasPermission";
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

// Look at one quiz: settings + questions, read-only.
// `id` is the lesson id (each lesson has one quiz).
const route = useRoute();
const lesson = ref(null);
const canEdit = hasPermission("edit-quizzes");

onMounted(async () => {
  const res = await api.get(`admin/lessons/${route.params.id}`);
  lesson.value = res.data.data;
});
</script>

<template>
  <LessonQuiz v-if="lesson" :lesson="lesson" readonly>
    <template #actions>
      <IconBtn
        v-if="canEdit"
        rounded
        size="30"
        class="mr-1"
        :aria-label="$t('Edit')"
        :to="{ name: 'admin-lessons-id', params: { id: lesson.id }, query: { tab: 'quiz' } }"
      >
        <VIcon icon="tabler-pencil" size="20" />
        <VTooltip location="top" activator="parent">{{ $t("Edit") }}</VTooltip>
      </IconBtn>
    </template>
  </LessonQuiz>
</template>
