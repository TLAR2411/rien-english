<script setup>
import LessonEditor from "@/views/admin/learning/lessons/LessonEditor.vue";
import LessonExamples from "@/views/admin/learning/lessons/examples/LessonExamples.vue";
import LessonQuiz from "@/views/admin/learning/lessons/quiz/LessonQuiz.vue";

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

// Tabs next to "Lesson" (each saves on its own)
const tabs = [
  { value: "examples", title: "Examples", icon: "tabler-message-2" },
  { value: "quiz", title: "Quiz", icon: "tabler-checklist" },
];
</script>

<template>
  <LessonEditor :lesson-id="route.params.id" :tabs="tabs">
    <template #tab-examples="{ lesson }">
      <LessonExamples :lesson-id="lesson.id" />
    </template>
    <template #tab-quiz="{ lesson }">
      <LessonQuiz :lesson="lesson" />
    </template>
  </LessonEditor>
</template>
