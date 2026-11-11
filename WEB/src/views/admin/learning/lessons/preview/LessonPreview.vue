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
  <!-- The lesson sits on a grey canvas, so it reads as the student screen -->
  <div class="preview-page">
    <VProgressLinear
      v-if="isLoading"
      indeterminate
      color="primary"
      class="mb-4"
    />

    <LessonView v-if="lesson" :lesson="lesson">
      <QuizView v-if="lesson.quiz?.questions?.length" :quiz="lesson.quiz" preview />
    </LessonView>
  </div>
</template>

<style scoped>
.preview-page {
  margin: -0.5rem -0.25rem -0.75rem;
  padding: 0.75rem clamp(0rem, 2vw, 1.5rem);
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.03);
}
</style>
