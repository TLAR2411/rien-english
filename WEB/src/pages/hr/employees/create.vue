<script setup>
import EmployeeCreate from "@/views/hr/employees/EmployeeCreate.vue";

definePage({
  meta: {
    title: "Create Employee",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    navActiveLink: "hr-employees",
  },
});
</script>

<template>
  <EmployeeCreate />
</template>
