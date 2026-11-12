<script setup>
import LessonView from "@/components/learning/student/LessonView.vue";
import WritingTask from "@/components/learning/student/WritingTask.vue";
import { useLessonReader } from "@/composables/useLessonReader";
import { quizRoute } from "@/utils/siteRoutes";
import { studentLessonsRoute } from "@/utils/studentRoutes";

/**
 * Read a lesson inside the student system (side menu stays): Khmer/English
 * switch, examples with sound, "Take the quiz" and "Mark as completed".
 * Shared logic: composables/useLessonReader.js.
 */
definePage({
  meta: {
    title: "Lesson",
    layout: "default",
    requiresAuth: true,
  },
});

const route = useRoute();
const { lesson, notFound, language, hasKhmer, isStudent, isSaving, markCompleted, completedDate } =
  useLessonReader(() => route.params.slug);
</script>

<template>
  <section>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <VBtn :to="studentLessonsRoute()" variant="text" prepend-icon="tabler-arrow-left" class="ms-n3">
        {{ $t("All lessons") }}
      </VBtn>

      <!-- Khmer explanation switch -->
      <VBtnToggle
        v-if="hasKhmer"
        v-model="language"
        mandatory
        density="comfortable"
        color="primary"
        variant="outlined"
        divided
        :aria-label="$t('Explanation language')"
      >
        <VBtn value="en">English</VBtn>
        <VBtn value="km">ខ្មែរ</VBtn>
      </VBtnToggle>
    </div>

    <LessonView v-if="lesson" :lesson="lesson" :language="language" :fit-screen="false" :show-transcript="lesson.quiz_taken">
      <template v-if="lesson.quiz || isStudent" #default>
        <!-- Writing lesson: write and send to the teacher -->
        <WritingTask v-if="lesson.skill === 'writing'" :lesson="lesson" class="mb-8" />
        <VCard v-if="lesson.quiz" variant="tonal" color="primary" class="mb-6">
          <VCardText>
            <h2 class="text-h5 font-weight-bold text-high-emphasis mb-1">{{ $t("Check what you learned") }}</h2>
            <p class="text-body-1 text-medium-emphasis mb-4">
              {{ $t("{count} questions", { count: lesson.quiz.questions_count ?? 0 }) }}
              · {{ $t("Pass score {score}%", { score: lesson.quiz.pass_score }) }}
            </p>
            <VBtn :to="quizRoute(lesson.slug)" color="primary" variant="flat">{{ $t("Take the quiz") }}</VBtn>
          </VCardText>
        </VCard>

        <template v-if="isStudent">
          <p v-if="lesson.completed_at" class="d-inline-flex align-center gap-2 text-success font-weight-medium mb-0">
            <VIcon icon="tabler-circle-check-filled" size="22" />
            {{ $t("Completed on {date}", { date: completedDate }) }}
          </p>
          <VBtn v-else variant="outlined" color="success" prepend-icon="tabler-check" :loading="isSaving" @click="markCompleted">
            {{ $t("Mark as completed") }}
          </VBtn>
        </template>
      </template>
    </LessonView>

    <VCard v-else-if="notFound">
      <VCardText class="text-center py-10">
        <h1 class="text-h5 font-weight-bold mb-2">{{ $t("Lesson not found") }}</h1>
        <p class="text-body-1 text-medium-emphasis mb-6">{{ $t("This lesson does not exist or is not published yet.") }}</p>
        <VBtn :to="studentLessonsRoute()">{{ $t("See all lessons") }}</VBtn>
      </VCardText>
    </VCard>

    <VProgressLinear v-else indeterminate color="primary" />
  </section>
</template>
