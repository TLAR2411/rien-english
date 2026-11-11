<script setup>
import { useI18n } from "vue-i18n";
import hasPermission from "@/utils/hasPermission";
import LessonList from "@/views/admin/learning/lessons/LessonList.vue";
import QuizList from "@/views/admin/learning/quizzes/QuizList.vue";

definePage({
  meta: {
    title: "Lessons",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-lessons",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});

// Lessons and their quizzes on one page. The open tab is kept in ?tab=.
const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const tabs = [
  { value: "lessons", title: "Lessons", icon: "tabler-notebook", permission: "view-lessons" },
  { value: "quizzes", title: "Quizzes", icon: "tabler-checklist", permission: "view-quizzes" },
].filter((item) => hasPermission(item.permission));

const tab = computed({
  get: () => route.query.tab || "lessons",
  set: (value) => router.replace({ query: { ...route.query, tab: value === "lessons" ? undefined : value } }),
});
</script>

<template>
  <!-- One frame: full-width tabs on top, the list below fills the rest of the screen -->
  <VCard flat border class="lessons-page">
    <VTabs v-model="tab" grow stacked class="v-tabs--fixed flex-shrink-0">
      <VTab v-for="item in tabs" :key="item.value" :value="item.value">
        <VIcon :icon="item.icon" class="mb-2" />
        <span>{{ t(item.title) }}</span>
      </VTab>
    </VTabs>
    <VDivider />

    <div class="lessons-page__body">
      <LessonList v-if="tab === 'lessons'" />
      <QuizList v-else-if="tab === 'quizzes'" />
    </div>
  </VCard>
</template>

<style scoped>
.lessons-page {
  display: flex;
  flex-direction: column;
  height: calc(100dvh - 85px);
}

.lessons-page__body {
  flex: 1;
  min-height: 0;
}

/* the list fills the space under the tabs */
.lessons-page__body :deep(.list-tab-height) {
  height: 100%;
}
</style>
