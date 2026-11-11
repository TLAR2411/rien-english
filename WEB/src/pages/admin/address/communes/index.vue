<script setup>
import CommuneList from "@/views/admin/address/communes/CommuneList.vue";

definePage({
  meta: {
    title: "Communes",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-communes",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <CommuneList />
</template>
