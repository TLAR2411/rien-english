<script setup>
import ProvinceList from "@/views/admin/address/provinces/ProvinceList.vue";

definePage({
  meta: {
    title: "Provinces",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-provinces",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});
</script>

<template>
  <ProvinceList />
</template>
