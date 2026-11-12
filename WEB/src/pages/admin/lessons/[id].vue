<script setup>
import LessonEditor from "@/views/admin/learning/lessons/LessonEditor.vue";
import LessonExamples from "@/views/admin/learning/lessons/examples/LessonExamples.vue";
import LessonWords from "@/views/admin/learning/lessons/words/LessonWords.vue";
import LessonQuiz from "@/views/admin/learning/lessons/quiz/LessonQuiz.vue";
import LessonPreview from "@/views/admin/learning/lessons/preview/LessonPreview.vue";

definePage({
  meta: {
    title: "Edit Lesson",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "edit-lessons",
  },
});

const route = useRoute();
</script>

<template>
  <!-- One page, tabs: Lesson · Words (reading / vocabulary) · Examples · Quiz · Preview -->
  <LessonEditor :lesson-id="route.params.id">
    <template #tab-words="{ lesson }">
      <LessonWords :lesson-id="lesson.id" />
    </template>
    <template #tab-examples="{ lesson }">
      <LessonExamples :lesson-id="lesson.id" />
    </template>
    <template #tab-quiz="{ lesson }">
      <LessonQuiz :lesson="lesson" />
    </template>
    <template #tab-preview="{ lesson }">
      <AppCard title="Preview" title-icon="tabler-eye" border="" :is-back="false">
        <LessonPreview :lesson-id="lesson.id" />
      </AppCard>
    </template>
  </LessonEditor>
</template>
