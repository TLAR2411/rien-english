<script setup>
import EmployeeList from "@/views/hr/employees/EmployeeList.vue";

definePage({
  meta: {
    title: "Employee",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <EmployeeList />
</template>
