<script setup>
import HrStatisticsDashboard from "@/views/hr/dashboards/HrStatisticsDashboard.vue";
import TotalEmployeeByPositionDashboard from "@/views/hr/dashboards/TotalEmployeeByPositionDashboard.vue";
import EmployeeGrowthDashboard from "@/views/hr/dashboards/EmployeeGrowthDashboard.vue";

definePage({
  meta: {
    title: "Dashboards",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
  },
});
</script>

<template>
  <VRow>
    <VCol cols="12">
      <HrStatisticsDashboard />
    </VCol>
  </VRow>
  <VRow class="match-height">
    <VCol cols="12" md="6">
      <TotalEmployeeByPositionDashboard />
    </VCol>
    <VCol cols="12" md="6">
      <EmployeeGrowthDashboard />
    </VCol>
  </VRow>
</template>
