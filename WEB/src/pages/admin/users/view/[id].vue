<script setup>
import AppStatTile from "@/components/AppStatTile.vue";
import ProgressRow from "@/components/learning/student/ProgressRow.vue";
import QuizScoreTable from "@/components/learning/QuizScoreTable.vue";
import { SKILLS, findOption } from "@/constants/learning";
import { getUserLearning, userQuizAttemptsUrl } from "@/services/learningAdmin";
import { formatLearningDate } from "@/utils/learningDate";

/**
 * One user's learning (opened with "View" in the users list): who they are,
 * numbers, lessons done per skill, scores per quiz and every quiz try.
 */
definePage({
  meta: {
    title: "User",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-users",
  },
});

const route = useRoute();
const { t, locale } = useI18n({ useScope: "global" });

const data = ref(null);
const isLoading = ref(true);
const notFound = ref(false);
const isTableLoading = ref(false);

onMounted(async () => {
  try {
    data.value = await getUserLearning(route.params.id);
  } catch {
    notFound.value = true;
  } finally {
    isLoading.value = false;
  }
});

const user = computed(() => data.value?.user);
const stats = computed(() => data.value?.stats ?? {});
const quizzes = computed(() => data.value?.quizzes ?? []);
const skills = computed(() => (data.value?.skills ?? []).filter((item) => item.total));

const name = computed(() => user.value?.name_en || user.value?.name_kh || user.value?.username);
const date = (value) => formatLearningDate(value, locale.value);
const skillTitle = (skill) => t(findOption(SKILLS, skill)?.title ?? skill);
const lessonTitle = (lesson) => (locale.value === "km" && lesson?.title_km) || lesson?.title_en;

const TILES = [
  { key: "lessons_done", title: "Lessons done", icon: "tabler-circle-check", color: "success" },
  { key: "quizzes_taken", title: "Quizzes taken", icon: "tabler-checklist" },
  { key: "quizzes_passed", title: "Quizzes passed", icon: "tabler-trophy", color: "success" },
  { key: "average_percent", title: "Average score", icon: "tabler-percentage", suffix: "%" },
];
const tileValue = (tile) => (stats.value[tile.key] == null ? "—" : `${stats.value[tile.key]}${tile.suffix ?? ""}`);

const headers = [
  { title: t("Lesson"), key: "lesson", visible: true },
  { title: t("Score"), key: "percent", align: "center", visible: true },
  { title: t("Points"), key: "score", align: "center", visible: true },
  { title: t("Result"), key: "passed", align: "center", visible: true },
  { title: t("Date"), key: "finished_at", visible: true },
];
</script>

<template>
  <AppCard title="User" title-icon="tabler-user" :loading="isLoading">
    <VAlert v-if="notFound" type="warning" variant="tonal" class="ma-4">
      {{ $t("This user was not found.") }}
    </VAlert>

    <div v-else-if="user" class="d-flex flex-column gap-6 pa-4">
      <!-- Who -->
      <div class="d-flex flex-wrap align-center gap-4">
        <VAvatar color="primary" variant="tonal" size="56" class="text-h5 font-weight-bold">
          {{ name?.charAt(0)?.toUpperCase() }}
        </VAvatar>
        <div class="flex-grow-1">
          <div class="d-flex flex-wrap align-center gap-2">
            <h2 class="text-h5 font-weight-bold mb-0">{{ name }}</h2>
            <VChip v-if="user.role" size="small" label>{{ user.role.display_name }}</VChip>
            <VChip size="small" :color="user.is_active ? 'success' : 'error'" variant="tonal">
              {{ $t(user.is_active ? "Active" : "Inactive") }}
            </VChip>
            <VChip v-if="user.level" size="small" color="primary" label>{{ user.level.code }}</VChip>
          </div>
          <p class="text-body-2 text-medium-emphasis mb-0 mt-1">
            <span v-if="user.email">{{ user.email }} · </span>
            {{ $t("Joined {date}", { date: date(user.created_at) }) }} ·
            {{ user.last_login ? $t("Last login {date}", { date: date(user.last_login) }) : $t("Never logged in") }}
          </p>
        </div>
      </div>

      <!-- Numbers -->
      <VRow>
        <VCol v-for="tile in TILES" :key="tile.key" cols="6" md="3">
          <AppStatTile :title="tile.title" :icon="tile.icon" :color="tile.color" :value="tileValue(tile)" dense />
        </VCol>
      </VRow>

      <!-- Lessons done per skill (skills that have lessons) -->
      <VCard v-if="skills.length" border flat>
        <VCardItem>
          <VCardTitle>{{ $t("Lessons by skill") }}</VCardTitle>
        </VCardItem>
        <VCardText>
          <VRow>
            <VCol v-for="item in skills" :key="item.skill" cols="12" md="6">
              <ProgressRow
                :item="item"
                :title="skillTitle(item.skill)"
                :icon="findOption(SKILLS, item.skill)?.icon"
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- Best / last score per quiz -->
      <VCard border flat>
        <VCardItem>
          <VCardTitle>{{ $t("Quiz scores") }}</VCardTitle>
        </VCardItem>
        <QuizScoreTable v-if="quizzes.length" :rows="quizzes" />
        <VCardText v-else class="text-medium-emphasis">{{ $t("No quizzes taken yet.") }}</VCardText>
      </VCard>

      <!-- Every quiz try -->
      <AppCardTable
        v-if="stats.quizzes_taken"
        v-model:loading="isTableLoading"
        title="All quiz tries"
        title-icon="tabler-history"
        :api-url="userQuizAttemptsUrl(user.id)"
        :headers="headers"
        :is-full-height="false"
        :is-back="false"
        save-header-name="header-user-quiz-attempts"
        enable-mobile-card
      >
        <template #item.lesson="{ item }">
          <span class="font-weight-medium">{{ lessonTitle(item.lesson) ?? item.quiz_title }}</span>
        </template>
        <template #item.percent="{ item }">
          <VChip size="small" :color="item.passed ? 'success' : 'warning'" variant="tonal">{{ item.percent }}%</VChip>
        </template>
        <template #item.score="{ item }">{{ item.score }} / {{ item.total }}</template>
        <template #item.passed="{ item }">
          <span :class="item.passed ? 'text-success' : 'text-medium-emphasis'">
            {{ $t(item.passed ? "Passed" : "Not passed yet") }}
          </span>
        </template>
        <template #item.finished_at="{ item }">{{ date(item.finished_at) }}</template>
      </AppCardTable>
    </div>
  </AppCard>
</template>
