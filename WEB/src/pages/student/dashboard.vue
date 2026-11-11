<script setup>
import AppStatTile from "@/components/AppStatTile.vue";
import DoneLessonRow from "@/components/learning/student/DoneLessonRow.vue";
import { getStudentDashboard } from "@/services/site";
import { useAuthStore } from "@/stores/authStore";
import { useStudentLevelStore } from "@/stores/studentLevelStore";
import { myLessonsRoute, studentLessonRoute, studentLessonsRoute } from "@/utils/studentRoutes";

/** Student home page: what to learn next, numbers, recently finished lessons. */
definePage({
  meta: {
    title: "Dashboard",
    layout: "default",
    requiresAuth: true,
  },
});

const auth = useAuthStore();
const { locale } = useI18n({ useScope: "global" });

const data = ref(null);
const isLoading = ref(true);

// "Continue learning" and the numbers follow the level chosen in the top bar
const load = async () => {
  isLoading.value = true;
  try {
    data.value = await getStudentDashboard();
  } finally {
    isLoading.value = false;
  }
};
onMounted(load);
watch(() => useStudentLevelStore().levelId, load);

const name = computed(() => auth.user?.name_en || auth.user?.name_kh || "");
const next = computed(() => data.value?.next_lesson);
const stats = computed(() => data.value?.stats ?? {});
const recent = computed(() => data.value?.recent ?? []);
const percentDone = computed(() =>
  stats.value.lessons_total ? Math.round((stats.value.lessons_done / stats.value.lessons_total) * 100) : 0,
);
</script>

<template>
  <section>
    <header class="mb-6">
      <h1 class="text-h4 font-weight-bold">
        {{ $t("Hello") }}<template v-if="name">, {{ name }}</template>
      </h1>
      <p class="text-body-1 text-medium-emphasis mb-0">{{ $t("Keep learning, one lesson at a time.") }}</p>
    </header>

    <VRow>
      <!-- Continue learning -->
      <VCol cols="12" md="6">
        <VCard class="h-100" :loading="isLoading">
          <VCardText class="d-flex flex-column h-100">
            <p class="text-overline text-medium-emphasis mb-2">{{ $t("Continue learning") }}</p>

            <template v-if="next">
              <div class="d-flex align-center gap-2 mb-1">
                <VChip v-if="next.level" size="small" color="primary" label>{{ next.level.code }}</VChip>
              </div>
              <h2 class="text-h5 font-weight-bold mb-1">{{ next.title_en }}</h2>
              <p v-if="locale === 'km' && next.title_km" class="text-body-1 mb-1">{{ next.title_km }}</p>
              <p v-if="next.summary" class="text-body-2 text-medium-emphasis mb-4">{{ next.summary }}</p>
              <div class="mt-auto">
                <VBtn :to="studentLessonRoute(next.slug)" append-icon="tabler-arrow-right">{{ $t("Read lesson") }}</VBtn>
              </div>
            </template>

            <template v-else-if="!isLoading">
              <h2 class="text-h5 font-weight-bold mb-1">{{ $t("You finished every lesson.") }}</h2>
              <p class="text-body-2 text-medium-emphasis mb-4">{{ $t("New lessons come soon. You can read your lessons again.") }}</p>
              <div class="mt-auto">
                <VBtn :to="studentLessonsRoute()" variant="tonal">{{ $t("All lessons") }}</VBtn>
              </div>
            </template>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Numbers -->
      <VCol cols="12" md="6">
        <VRow>
          <VCol cols="6" md="12">
            <AppStatTile
              title="Lessons done"
              icon="tabler-circle-check"
              color="success"
              :value="`${stats.lessons_done ?? 0} / ${stats.lessons_total ?? 0}`"
              :progress="percentDone"
              :caption="data?.level ? $t('Level {code}', { code: data.level.code }) : null"
              :loading="isLoading"
              dense
            />
          </VCol>
          <VCol cols="6" md="12">
            <AppStatTile
              title="Quizzes passed"
              icon="tabler-trophy"
              :value="stats.quizzes_passed ?? 0"
              :loading="isLoading"
              dense
            />
          </VCol>
        </VRow>
      </VCol>

      <!-- Recently finished -->
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>{{ $t("Recently finished") }}</VCardTitle>
            <template #append>
              <VBtn v-if="recent.length" :to="myLessonsRoute()" variant="text" size="small">
                {{ $t("See all") }}
              </VBtn>
            </template>
          </VCardItem>

          <VList v-if="recent.length" class="pt-0">
            <DoneLessonRow v-for="lesson in recent" :key="lesson.id" :lesson="lesson" show-level />
          </VList>
          <VCardText v-else-if="!isLoading" class="text-medium-emphasis">
            {{ $t("No lessons done yet. Read a lesson, then press “Mark as completed”.") }}
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>
