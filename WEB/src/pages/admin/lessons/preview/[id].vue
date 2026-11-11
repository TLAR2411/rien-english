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
  <div>
    <VAlert type="info" variant="tonal" density="compact" class="mb-6" icon="tabler-eye">
      <div class="d-flex align-center flex-wrap gap-3">
        <span class="flex-grow-1">{{ $t("Preview — this is what students see.") }}</span>
        <StatusChip v-if="status" :status="status" />
        <VBtn
          size="small"
          variant="text"
          prepend-icon="tabler-pencil"
          :to="{ name: 'admin-lessons-id', params: { id: route.params.id } }"
        >
          {{ $t("Back to editing") }}
        </VBtn>
      </div>
    </VAlert>

    <VCard class="preview-page" :loading="isLoading">
      <VCardText v-if="lesson" class="preview-page__body">
        <LessonView :lesson="lesson">
          <div v-if="lesson.quiz?.questions?.length" class="mt-12">
            <QuizView :quiz="lesson.quiz" preview />
          </div>
        </LessonView>
      </VCardText>
    </VCard>
  </div>
</template>

<style scoped>
.preview-page__body {
  padding: clamp(1.25rem, 5vw, 3.5rem) clamp(1rem, 4vw, 2rem);
}
</style>
