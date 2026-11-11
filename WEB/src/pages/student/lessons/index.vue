<script setup>
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import StudentLessonCard from "@/components/learning/student/StudentLessonCard.vue";
import { SKILLS } from "@/constants/learning";
import { useLessonList } from "@/composables/useLessonList";
import { useStudentLevelStore } from "@/stores/studentLevelStore";
import { studentLessonsRoute } from "@/utils/studentRoutes";

/**
 * All lessons, inside the student system (side menu stays).
 * Only the level the student chose in the top bar (NavbarStudentLevel.vue);
 * every level until they choose one. Same skill tabs and search as the public
 * list (composables/useLessonList.js), with a ✓ Done on finished lessons.
 */
definePage({
  meta: {
    title: "All lessons",
    layout: "default",
    requiresAuth: true,
  },
});

const levelStore = useStudentLevelStore();
await levelStore.loadLevels().catch(() => {}); // know the level's code before the first load

const { filters, setFilters, lessons, meta, isLoading, searchText, applySearch, hasFilters, clearFilters, goToPage } =
  useLessonList(studentLessonsRoute, { fixedLevel: () => levelStore.level?.code });

const skillTabs = computed(() => [{ value: null, title: "All skills" }, ...SKILLS]);
</script>

<template>
  <section>
    <header class="mb-4">
      <h1 class="text-h4 font-weight-bold">{{ $t("All lessons") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-0">
        <template v-if="levelStore.level">{{ $t("Level {code} lessons. Change your level in the top bar.", { code: levelStore.level.code }) }}</template>
        <template v-else>{{ $t("Pick a lesson. Finished lessons have a ✓.") }}</template>
      </p>
    </header>

    <VAlert v-if="!levelStore.level" type="info" variant="tonal" class="mb-4">
      {{ $t("Choose your level in the top bar to see only the lessons for you.") }}
    </VAlert>

    <VCard class="mb-4">
      <VTabs :model-value="filters.skill" show-arrows @update:model-value="(skill) => setFilters({ skill })">
        <VTab v-for="tab in skillTabs" :key="tab.value ?? 'all'" :value="tab.value">{{ $t(tab.title) }}</VTab>
      </VTabs>
      <VDivider />
      <VCardText class="d-flex flex-wrap align-center gap-3">

        <AppTextField
          v-model="searchText"
          class="lessons-search"
          prepend-inner-icon="tabler-search"
          :placeholder="$t('Search lessons')"
          clearable
          hide-details
          @update:model-value="applySearch"
        />
      </VCardText>
    </VCard>

    <VProgressLinear v-if="isLoading" indeterminate color="primary" class="mb-4" />

    <template v-if="lessons.length">
      <p class="text-body-2 text-medium-emphasis mb-3">
        {{ $t("{count} lessons", { count: meta?.total ?? lessons.length }, meta?.total ?? lessons.length) }}
      </p>
      <VRow>
        <VCol v-for="lesson in lessons" :key="lesson.id" cols="12" sm="6" lg="4">
          <StudentLessonCard :lesson="lesson" />
        </VCol>
      </VRow>

      <VPagination
        v-if="meta && meta.last_page > 1"
        :model-value="meta.current_page"
        :length="meta.last_page"
        class="mt-6"
        @update:model-value="goToPage"
      />
    </template>

    <VCard v-else-if="!isLoading">
      <VCardText class="text-center py-10">
        <p class="text-body-1 mb-4">{{ hasFilters ? $t("No lessons found.") : $t("No lessons yet. Please come back soon.") }}</p>
        <VBtn v-if="hasFilters" variant="tonal" @click="clearFilters">{{ $t("Show all lessons") }}</VBtn>
      </VCardText>
    </VCard>
  </section>
</template>

<style scoped>
.lessons-search {
  flex: 1 1 240px;
  max-width: 420px;
}
</style>
