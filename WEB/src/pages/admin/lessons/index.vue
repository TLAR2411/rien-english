<script setup>
import LessonList from "@/views/admin/learning/lessons/LessonList.vue";

definePage({
  meta: {
    title: "Lessons",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-lessons",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <LessonList />
</template>
