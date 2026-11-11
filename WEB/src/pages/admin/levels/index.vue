<script setup>
import LevelList from "@/views/admin/learning/levels/LevelList.vue";

definePage({
  meta: {
    title: "Levels",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-levels",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <LevelList />
</template>
