<script setup>
import { api } from "@/utils/api";
import EmployeeBio from "@/views/hr/employees/EmployeeBio.vue";
import EmployeeOverview from "@/views/hr/employees/EmployeeOverview.vue";
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";

definePage({
  meta: {
    title: "Employee Profile",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    navActiveLink: "hr-employees",
  },
});

const route = useRoute();
const isLoading = ref(false);
const employee = ref({});
const tab = ref("overview");

const tabs = computed(() =>
  [
    { value: "overview", icon: "tabler-user", label: "Information" },
  ].filter(Boolean),
);

const loadEmployee = async () => {
  try {
    isLoading.value = true;
    const res = await api.get("employees-show", {
      params: { id: route.query.id },
    });
    if (res.data.status) {
      employee.value = res.data.data;
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(loadEmployee);

// Opening another employee's profile from here reloads the page data
watch(
  () => route.query.id,
  (id) => {
    if (id) {
      tab.value = "overview";
      loadEmployee();
    }
  },
);
</script>

<template>
  <VRow>
    <VCol cols="12">
      <EmployeeBio :loading="isLoading" :item-data="employee" />
    </VCol>

    <VCol cols="12">
      <VTabs v-model="tab" class="profile-tabs">
        <VTab v-for="item in tabs" :key="item.value" :value="item.value">
          <VIcon :icon="item.icon" size="18" start />
          {{ $t(item.label) }}
        </VTab>
      </VTabs>
    </VCol>

    <VCol cols="12">
      <VWindow v-model="tab" :touch="false">
        <VWindowItem value="overview">
          <EmployeeOverview :item-data="employee" />
        </VWindowItem>
      </VWindow>
    </VCol>
  </VRow>
</template>
