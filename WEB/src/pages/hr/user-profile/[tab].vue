<script setup>
import UserProfileTab from "@/views/user-profile/UserProfileTab.vue";

definePage({
  meta: {
    title: "User Profile",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
  },
});
</script>

<template>
  <UserProfileTab />
</template>
