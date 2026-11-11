<script setup>
import RoleList from "@/views/admin/roles/RoleList.vue";

definePage({
  meta: {
    title: "Roles",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-roles",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <RoleList />
</template>
