<script setup>
import { api } from "@/utils/api";
import LessonView from "@/components/learning/student/LessonView.vue";
import QuizView from "@/components/learning/student/QuizView.vue";
import StatusChip from "@/components/learning/StatusChip.vue";

definePage({
  meta: {
    title: "Preview",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-lessons",
  },
});

// The lesson and its quiz exactly as a student gets them (also drafts).
const route = useRoute();
const lesson = ref(null);
const status = ref(null);
const isLoading = ref(true);

onMounted(async () => {
  try {
    const [preview, saved] = await Promise.all([
      api.get(`admin/lessons/${route.params.id}/preview`),
      api.get(`admin/lessons/${route.params.id}`),
    ]);
    lesson.value = preview.data.data;
    status.value = saved.data.data.status;
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <AppCard title="Preview" title-icon="tabler-eye" :loading="isLoading">
    <template #card-header>
      <StatusChip v-if="status" :status="status" class="me-2" />
      <IconBtn
        rounded
        size="30"
        class="mr-1"
        :aria-label="$t('Back to editing')"
        :to="{ name: 'admin-lessons-id', params: { id: route.params.id } }"
      >
        <VIcon icon="tabler-pencil" size="20" />
        <VTooltip location="top" activator="parent">{{ $t("Back to editing") }}</VTooltip>
      </IconBtn>
    </template>

    <VAlert type="info" variant="tonal" density="compact" icon="tabler-eye" class="mb-6">
      {{ $t("Preview — this is what students see.") }}
    </VAlert>

    <div v-if="lesson" class="preview-page__body">
      <LessonView :lesson="lesson">
        <div v-if="lesson.quiz?.questions?.length" class="mt-12">
          <QuizView :quiz="lesson.quiz" preview />
        </div>
      </LessonView>
    </div>
  </AppCard>
</template>

<style scoped>
.preview-page__body {
  padding: clamp(0.5rem, 4vw, 2.5rem) clamp(0rem, 3vw, 1.5rem);
}
</style>
