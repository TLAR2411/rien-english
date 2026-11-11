<script setup>
import PositionList from "@/views/admin/positions/PositionList.vue";

definePage({
  meta: {
    title: "Positions",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-positions",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <PositionList />
</template>
