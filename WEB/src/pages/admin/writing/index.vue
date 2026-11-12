<script setup>
import { useI18n } from "vue-i18n";
import WritingReviewList from "@/views/admin/learning/writing/WritingReviewList.vue";

definePage({
  meta: {
    title: "Writing Review",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-writing",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});

// Students' writings: Waiting (oldest first) · Reviewed · All. The open tab is kept in ?tab=.
const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const tabs = [
  { value: "pending", title: "Waiting", icon: "tabler-clock-hour-4" },
  { value: "reviewed", title: "Reviewed", icon: "tabler-circle-check" },
  { value: "all", title: "All", icon: "tabler-list" },
];

const tab = computed({
  get: () => route.query.tab || "pending",
  set: (value) => router.replace({ query: { ...route.query, tab: value === "pending" ? undefined : value } }),
});
</script>

<template>
  <!-- One frame: full-width tabs on top, the list below fills the rest of the screen -->
  <VCard flat border class="writing-page">
    <VTabs v-model="tab" grow stacked class="v-tabs--fixed flex-0-0">
      <VTab v-for="item in tabs" :key="item.value" :value="item.value">
        <VIcon :icon="item.icon" class="mb-2" />
        <span>{{ t(item.title) }}</span>
      </VTab>
    </VTabs>
    <VDivider />

    <div class="writing-page__body">
      <WritingReviewList :key="tab" :status="tab === 'all' ? null : tab" />
    </div>
  </VCard>
</template>

<style scoped>
.writing-page {
  display: flex;
  flex-direction: column;
  height: calc(100dvh - 85px);
}

.writing-page__body {
  flex: 1;
  min-height: 0;
}

.writing-page__body :deep(.list-tab-height) {
  height: 100%;
}
</style>
