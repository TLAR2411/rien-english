<script setup>
import LessonEditor from "@/views/admin/learning/lessons/LessonEditor.vue";

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
  <LessonEditor :lesson-id="route.params.id" />
</template>
