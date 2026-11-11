<script setup>
import VillageList from "@/views/admin/address/villages/VillageList.vue";

definePage({
  meta: {
    title: "Villages",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-villages",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <VillageList />
</template>
