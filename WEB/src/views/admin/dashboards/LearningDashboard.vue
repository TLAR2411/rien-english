<script setup>
import AppStatTile from "@/components/AppStatTile.vue";
import { getAdminDashboard } from "@/services/learningAdmin";
import { auth } from "@/utils/auth";
import { lessonPreviewRoute } from "@/utils/lessonRoutes";

// One tile per number from GET admin/dashboard. Add a tile here when the API adds a number.
// `recent` = the number counts the last `recent_days` days (shown under the number).
const TILES = [
  { key: "students", title: "Students", icon: "tabler-school" },
  { key: "new_students", title: "New sign-ups", icon: "tabler-user-plus", color: "success", recent: true },
  { key: "active_students", title: "Active students", icon: "tabler-activity", color: "success", recent: true },
  { key: "quizzes_taken", title: "Quizzes taken", icon: "tabler-checklist", recentKey: "quizzes_taken_recent" },
  { key: "average_percent", title: "Average score", icon: "tabler-percentage", suffix: "%" },
  { key: "teachers", title: "Teachers", icon: "tabler-chalkboard", color: "secondary" },
];

const { t, locale } = useI18n({ useScope: "global" });

const data = ref({});
const isLoading = ref(true);

onMounted(async () => {
  try {
    data.value = (await getAdminDashboard()) ?? {};
  } catch (error) {
    console.error("Failed to load the dashboard:", error);
  } finally {
    isLoading.value = false;
  }
});

const userName = computed(() => auth()?.user?.name_en || auth()?.user?.name_kh || "");
const days = computed(() => data.value.recent_days ?? 7);
const popular = computed(() => data.value.popular_lessons ?? []);

const value = (tile) => (data.value[tile.key] == null ? "—" : `${data.value[tile.key]}${tile.suffix ?? ""}`);
const caption = (tile) => {
  if (tile.recent) return t("Last {days} days", { days: days.value });
  if (tile.recentKey) return t("{count} in the last {days} days", { count: data.value[tile.recentKey] ?? 0, days: days.value });

  return null;
};
const lessonTitle = (lesson) => (locale.value === "km" && lesson.title_km) || lesson.title_en;
</script>

<template>
  <section>
    <header class="mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t("Dashboard") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-0">
        {{ $t("Welcome back") }}<template v-if="userName">, {{ userName }}</template>
      </p>
    </header>

    <VRow>
      <VCol v-for="tile in TILES" :key="tile.key" cols="6" md="4" lg="2">
        <AppStatTile
          :title="tile.title"
          :icon="tile.icon"
          :color="tile.color"
          :value="value(tile)"
          :caption="caption(tile)"
          :loading="isLoading"
          dense
        />
      </VCol>

      <!-- Lessons most students finished -->
      <VCol cols="12">
        <VCard :loading="isLoading">
          <VCardItem>
            <VCardTitle>{{ $t("Most popular lessons") }}</VCardTitle>
            <VCardSubtitle>{{ $t("Lessons most students finished.") }}</VCardSubtitle>
          </VCardItem>

          <VList v-if="popular.length" class="pt-0" lines="two">
            <template v-for="(lesson, index) in popular" :key="lesson.id">
              <VDivider v-if="index" />
              <VListItem :to="lessonPreviewRoute(lesson.id)" class="popular-row">
                <template #prepend>
                  <span class="popular-row__rank text-h6 text-disabled me-4">{{ index + 1 }}</span>
                </template>
                <VListItemTitle class="font-weight-medium">
                  <VChip v-if="lesson.level_code" size="x-small" color="primary" label class="me-1">
                    {{ lesson.level_code }}
                  </VChip>
                  {{ lessonTitle(lesson) }}
                </VListItemTitle>
                <VListItemSubtitle>
                  {{ $t("{count} finished", { count: lesson.finished_count }) }} ·
                  {{ $t("{count} quiz tries", { count: lesson.attempts_count }) }}
                </VListItemSubtitle>
                <template #append>
                  <VIcon icon="tabler-chevron-right" size="18" class="text-disabled" />
                </template>
              </VListItem>
            </template>
          </VList>
          <VCardText v-else-if="!isLoading" class="text-medium-emphasis">
            {{ $t("No students have finished a lesson yet.") }}
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style scoped>
.popular-row {
  min-block-size: 60px;
}

.popular-row__rank {
  font-variant-numeric: tabular-nums;
  inline-size: 1.25rem;
  text-align: center;
}
</style>
