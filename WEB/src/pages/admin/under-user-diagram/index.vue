<script setup>
import UnderUserDiagram from "@/views/admin/users/UnderUserDiagram.vue";

definePage({
  meta: {
    title: "Team Structure",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-users",
  },
});
</script>

<template>
  <UnderUserDiagram />
</template>
