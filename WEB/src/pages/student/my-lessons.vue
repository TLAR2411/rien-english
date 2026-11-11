<script setup>
import DoneLessonRow from "@/components/learning/student/DoneLessonRow.vue";
import { getMyLessons } from "@/services/site";
import { lessonsRoute } from "@/utils/siteRoutes";

/**
 * "My lessons": every lesson the student finished, grouped by course
 * (easiest level first), with the date and best quiz score.
 * Phase 9 (paid courses) adds the courses the student bought here.
 */
definePage({
  meta: {
    title: "My lessons",
    layout: "default",
    requiresAuth: true,
  },
});

const { locale } = useI18n({ useScope: "global" });

const courses = ref([]);
const isLoading = ref(true);

onMounted(async () => {
  try {
    courses.value = await getMyLessons();
  } finally {
    isLoading.value = false;
  }
});

const total = computed(() => courses.value.reduce((sum, group) => sum + group.lessons.length, 0));
</script>

<template>
  <section>
    <header class="mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t("My lessons") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-0">
        {{ isLoading ? " " : $t("{count} lessons done", { count: total }) }}
      </p>
    </header>

    <VProgressLinear v-if="isLoading" indeterminate color="primary" />

    <VCard v-else-if="!courses.length">
      <VCardText class="text-center py-10">
        <VIcon icon="tabler-book" size="40" class="text-disabled mb-3" />
        <p class="text-body-1 mb-4">{{ $t("No lessons done yet. Read a lesson, then press “Mark as completed”.") }}</p>
        <VBtn :to="lessonsRoute()">{{ $t("All lessons") }}</VBtn>
      </VCardText>
    </VCard>

    <VCard v-for="group in courses" v-else :key="group.course.id" class="mb-4">
      <VCardItem>
        <template #prepend>
          <VChip v-if="group.level" color="primary" label size="small">{{ group.level.code }}</VChip>
        </template>
        <VCardTitle>{{ locale === "km" && group.course.title_km ? group.course.title_km : group.course.title }}</VCardTitle>
        <template #append>
          <span class="text-body-2 text-medium-emphasis">{{ $t("{count} lessons done", { count: group.lessons.length }) }}</span>
        </template>
      </VCardItem>
      <VList class="pt-0">
        <DoneLessonRow v-for="lesson in group.lessons" :key="lesson.id" :lesson="lesson" />
      </VList>
    </VCard>
  </section>
</template>
