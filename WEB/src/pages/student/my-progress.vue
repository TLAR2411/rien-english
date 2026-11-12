<script setup>
import ProgressRow from "@/components/learning/student/ProgressRow.vue";
import QuizScoreTable from "@/components/learning/QuizScoreTable.vue";
import { SKILLS, findOption } from "@/constants/learning";
import { getMyProgress } from "@/services/site";
import { quizResultRoute, studentLessonsRoute } from "@/utils/studentRoutes";

/** "My Progress": % of lessons done per skill and per level, and every quiz score. */
definePage({
  meta: {
    title: "My Progress",
    layout: "default",
    requiresAuth: true,
  },
});

const { locale } = useI18n({ useScope: "global" });

const data = ref(null);
const isLoading = ref(true);

onMounted(async () => {
  try {
    data.value = await getMyProgress();
  } finally {
    isLoading.value = false;
  }
});

const skills = computed(() => data.value?.skills ?? []);
const levels = computed(() => data.value?.levels ?? []);
const quizzes = computed(() => data.value?.quizzes ?? []);

const skillTitle = (skill) => findOption(SKILLS, skill)?.title ?? skill;
const skillIcon = (skill) => findOption(SKILLS, skill)?.icon;
const levelName = (level) => (locale.value === "km" && level.name_km ? level.name_km : level.name);
</script>

<template>
  <section>
    <header class="mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t("My Progress") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-0">{{ $t("What you have learned so far.") }}</p>
    </header>

    <VProgressLinear v-if="isLoading" indeterminate color="primary" />

    <VRow v-else>
      <!-- Lessons done per skill -->
      <VCol cols="12" md="6">
        <VCard class="h-100">
          <VCardItem>
            <VCardTitle>{{ $t("By skill") }}</VCardTitle>
          </VCardItem>
          <VCardText class="d-flex flex-column gap-4">
            <ProgressRow
              v-for="item in skills"
              :key="item.skill"
              :item="item"
              :title="$t(skillTitle(item.skill))"
              :icon="skillIcon(item.skill)"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- Lessons done per level -->
      <VCol cols="12" md="6">
        <VCard class="h-100">
          <VCardItem>
            <VCardTitle>{{ $t("By level") }}</VCardTitle>
          </VCardItem>
          <VCardText class="d-flex flex-column gap-4">
            <ProgressRow
              v-for="level in levels"
              :key="level.id"
              :item="level"
              :title="levelName(level)"
              :badge="level.code"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- Quiz scores -->
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>{{ $t("Quiz scores") }}</VCardTitle>
            <VCardSubtitle v-if="quizzes.length">{{ $t("Tap a quiz to see your last result.") }}</VCardSubtitle>
          </VCardItem>

          <QuizScoreTable v-if="quizzes.length" :rows="quizzes" :row-link="(row) => quizResultRoute(row.last_attempt_id)" />

          <VCardText v-else class="text-center py-8">
            <p class="text-body-1 text-medium-emphasis mb-4">
              {{ $t("No quizzes yet. Read a lesson, then take its quiz.") }}
            </p>
            <VBtn :to="studentLessonsRoute()">{{ $t("All lessons") }}</VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>
