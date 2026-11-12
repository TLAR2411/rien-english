<script setup>
import LessonEditor from "@/views/admin/learning/lessons/LessonEditor.vue";

definePage({
  meta: {
    title: "New Lesson",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "add-lessons",
  },
});
</script>

<template>
  <LessonEditor />
</template>
