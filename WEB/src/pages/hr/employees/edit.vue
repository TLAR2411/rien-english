<script setup>
import EmployeeEdit from "@/views/hr/employees/EmployeeEdit.vue";

definePage({
  meta: {
    title: "Edit Employee",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    navActiveLink: "hr-employees",
  },
});
</script>

<template>
  <EmployeeEdit />
</template>
