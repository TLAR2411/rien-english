<script setup>
import { getQuizAttempts } from "@/services/site";
import { formatLearningDate } from "@/utils/learningDate";
import { quizResultRoute, studentLessonsRoute } from "@/utils/studentRoutes";

/** "My Quizzes": every quiz the student took, newest first. Open one to see the full result. */
definePage({
  meta: {
    title: "My Quizzes",
    layout: "default",
    requiresAuth: true,
  },
});

const { locale } = useI18n({ useScope: "global" });

const attempts = ref([]);
const meta = ref(null);
const page = ref(1);
const isLoading = ref(true);

const load = async () => {
  isLoading.value = true;
  try {
    const res = await getQuizAttempts({ page: page.value });
    attempts.value = res.data;
    meta.value = res.meta;
  } finally {
    isLoading.value = false;
  }
};
watch(page, load, { immediate: true });

const date = (value) => formatLearningDate(value, locale.value);
const khmer = (lesson) => (locale.value === "km" ? lesson?.title_km : null);
</script>

<template>
  <section>
    <header class="mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t("My Quizzes") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-0">
        {{ meta ? $t("{count} quizzes taken", { count: meta.total }) : " " }}
      </p>
    </header>

    <VProgressLinear v-if="isLoading && !attempts.length" indeterminate color="primary" />

    <VCard v-else-if="!attempts.length">
      <VCardText class="text-center py-10">
        <VIcon icon="tabler-checklist" size="40" class="text-disabled mb-3" />
        <p class="text-body-1 mb-4">{{ $t("No quizzes yet. Read a lesson, then take its quiz.") }}</p>
        <VBtn :to="studentLessonsRoute()">{{ $t("All lessons") }}</VBtn>
      </VCardText>
    </VCard>

    <template v-else>
      <VCard>
        <VList class="py-0" lines="two">
          <template v-for="(attempt, index) in attempts" :key="attempt.id">
            <VDivider v-if="index" />
            <VListItem :to="quizResultRoute(attempt.id)" class="attempt-row">
              <template #prepend>
                <VIcon
                  :icon="attempt.passed ? 'tabler-circle-check-filled' : 'tabler-circle-dashed'"
                  :color="attempt.passed ? 'success' : 'warning'"
                  size="24"
                  class="me-3"
                />
              </template>
              <VListItemTitle class="font-weight-medium">{{ attempt.lesson?.title_en ?? attempt.quiz_title }}</VListItemTitle>
              <VListItemSubtitle>
                <span v-if="khmer(attempt.lesson)">{{ khmer(attempt.lesson) }} · </span>{{ date(attempt.finished_at) }} ·
                {{ $t("{score} of {total} points", { score: attempt.score, total: attempt.total }) }}
              </VListItemSubtitle>
              <template #append>
                <VChip size="small" :color="attempt.passed ? 'success' : 'warning'" variant="tonal" class="me-1">
                  {{ attempt.percent }}%
                </VChip>
                <VIcon icon="tabler-chevron-right" size="18" class="text-disabled" />
              </template>
            </VListItem>
          </template>
        </VList>
      </VCard>

      <VPagination
        v-if="meta?.last_page > 1"
        v-model="page"
        :length="meta.last_page"
        :total-visible="5"
        density="comfortable"
        class="mt-4"
      />
    </template>
  </section>
</template>

<style scoped>
.attempt-row {
  min-height: 64px;
}
</style>
