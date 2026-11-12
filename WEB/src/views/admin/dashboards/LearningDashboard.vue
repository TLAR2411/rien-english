<script setup>
import ActivityChart from "@/views/admin/dashboards/ActivityChart.vue";
import DashboardOverview from "@/views/admin/dashboards/DashboardOverview.vue";
import PopularLessons from "@/views/admin/dashboards/PopularLessons.vue";
import QuizResultsCard from "@/views/admin/dashboards/QuizResultsCard.vue";
import { getAdminDashboard } from "@/services/learningAdmin";
import { auth } from "@/utils/auth";

/**
 * Admin home: key numbers, activity per day, quiz results, popular lessons.
 * Everything comes from GET admin/dashboard (API: Services/Learning/LearningStats).
 */
const data = ref({});
const isLoading = ref(true);

onMounted(async () => {
  try {
    data.value = (await getAdminDashboard()) ?? {};
  } catch (error) {
    console.error("Failed to load the dashboard:", error);
  } finally {
    isLoading.value = false;
  }
});

const userName = computed(() => auth()?.user?.name_en || auth()?.user?.name_kh || "");
</script>

<template>
  <section class="learning-dashboard">
    <header class="mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t("Dashboard") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-0">
        {{ $t("Welcome back") }}<template v-if="userName">, {{ userName }}</template>
      </p>
    </header>

    <VRow>
      <VCol cols="12">
        <DashboardOverview :stats="data" :loading="isLoading" />
      </VCol>

      <template v-if="!isLoading">
        <VCol cols="12" md="8">
          <ActivityChart :days="data.activity ?? []" />
        </VCol>
        <VCol cols="12" md="4">
          <QuizResultsCard :stats="data" />
        </VCol>
        <VCol cols="12">
          <PopularLessons :lessons="data.popular_lessons ?? []" />
        </VCol>
      </template>
    </VRow>
  </section>
</template>
