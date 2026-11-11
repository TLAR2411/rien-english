<script setup>
import GeneralSettings from "@/views/admin/settings/GeneralSettings.vue";

definePage({
  meta: {
    title: "Settings",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-settings",
  },
});
</script>

<template>
  <GeneralSettings />
</template>
