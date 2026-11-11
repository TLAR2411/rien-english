<script setup>
import LessonEditor from "@/views/admin/learning/lessons/LessonEditor.vue";
import LessonExamples from "@/views/admin/learning/lessons/examples/LessonExamples.vue";

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
  <LessonEditor :lesson-id="route.params.id">
    <template #extra="{ lesson }">
      <LessonExamples :lesson-id="lesson.id" />
    </template>
  </LessonEditor>
</template>
