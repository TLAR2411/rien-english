<script setup>
import CourseList from "@/views/admin/learning/courses/CourseList.vue";

definePage({
  meta: {
    title: "Courses",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-courses",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <CourseList />
</template>
