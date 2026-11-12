<script setup>
import LearningDashboard from "@/views/admin/dashboards/LearningDashboard.vue";

definePage({
  meta: {
    title: "Dashboard",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
  },
});
</script>

<template>
  <LearningDashboard />
</template>
