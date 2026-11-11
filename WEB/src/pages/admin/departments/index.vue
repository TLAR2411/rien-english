<script setup>
import DepartmentList from "@/views/admin/departments/DepartmentList.vue";

definePage({
  meta: {
    title: "Departments",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-departments",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <DepartmentList />
</template>
