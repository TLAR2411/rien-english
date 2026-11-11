<script setup>
import DistrictList from "@/views/admin/address/districts/DistrictList.vue";

definePage({
  meta: {
    title: "Districts",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-districts",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <DistrictList />
</template>
