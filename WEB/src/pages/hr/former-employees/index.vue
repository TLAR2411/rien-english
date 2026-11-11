<script setup>
import FormerEmployeeList from "@/views/hr/employees/FormerEmployeeList.vue";

definePage({
  meta: {
    title: "Former Employees",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-employees",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <FormerEmployeeList />
</template>
