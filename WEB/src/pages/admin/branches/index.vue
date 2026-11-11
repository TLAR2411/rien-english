<script setup>
import BranchList from "@/views/admin/branches/BranchList.vue";

definePage({
  meta: {
    title: "Branches",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-branches",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <BranchList />
</template>
