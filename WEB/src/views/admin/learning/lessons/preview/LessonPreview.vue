<script setup>
import { api } from "@/utils/api";
import LessonView from "@/components/learning/student/LessonView.vue";
import QuizView from "@/components/learning/student/QuizView.vue";

// The saved lesson and its quiz exactly as a student gets them (also drafts).
const props = defineProps({
  lessonId: { type: [Number, String], required: true },
});

const emit = defineEmits(["loaded"]);

const lesson = ref(null);
const isLoading = ref(true);

const load = async () => {
  isLoading.value = true;
  try {
    const [preview, saved] = await Promise.all([
      api.get(`admin/lessons/${props.lessonId}/preview`),
      api.get(`admin/lessons/${props.lessonId}`),
    ]);
    lesson.value = preview.data.data;
    emit("loaded", saved.data.data);
  } finally {
    isLoading.value = false;
  }
};
watch(() => props.lessonId, load, { immediate: true });
</script>

<template>
  <!-- The lesson sits on a "page" on a grey canvas, so it reads as the student screen -->
  <div class="preview-page">
    <p class="preview-page__note">
      <VIcon icon="tabler-eye" size="16" />
      {{ $t("Preview — this is what students see.") }}
    </p>

    <VProgressLinear v-if="isLoading" indeterminate color="primary" class="mb-4" />

    <div v-if="lesson" class="preview-page__sheet">
      <LessonView :lesson="lesson">
        <div v-if="lesson.quiz?.questions?.length" class="preview-page__quiz">
          <QuizView :quiz="lesson.quiz" preview />
        </div>
      </LessonView>
    </div>
  </div>
</template>

<style scoped>
.preview-page {
  margin: -0.5rem -0.25rem 0;
  padding: clamp(0.75rem, 3vw, 2rem) clamp(0rem, 3vw, 2rem) clamp(1rem, 4vw, 3rem);
  border-radius: 12px;
  background: rgba(var(--v-theme-on-surface), 0.03);
}

.preview-page__note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  margin: 0 0 1rem;
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.preview-page__sheet {
  max-width: 860px;
  margin-inline: auto;
  padding: clamp(1.25rem, 5vw, 3.5rem) clamp(1rem, 5vw, 3.5rem);
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 16px;
  background: rgb(var(--v-theme-surface));
}

.preview-page__quiz {
  margin-block-start: 3rem;
  padding-block-start: 2.5rem;
  border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
