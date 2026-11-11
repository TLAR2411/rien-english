<script setup>
import { SKILLS, findOption } from "@/constants/learning";
import { studentLessonRoute } from "@/utils/studentRoutes";

/** A lesson in the student system's list. `lesson` = API LessonCardResource (with completed_at). */
const props = defineProps({
  lesson: { type: Object, required: true },
});

const skill = computed(() => findOption(SKILLS, props.lesson.skill));
const { locale } = useI18n({ useScope: "global" });
</script>

<template>
  <VCard :to="studentLessonRoute(lesson.slug)" class="h-100 d-flex flex-column">
    <VCardText class="d-flex flex-column flex-grow-1">
      <div class="d-flex align-center flex-wrap gap-2 mb-3">
        <VChip v-if="lesson.level" size="small" color="primary" label>{{ lesson.level.code }}</VChip>
        <VChip size="small" label>{{ $t(skill?.title ?? lesson.skill) }}</VChip>
        <VChip v-if="lesson.completed_at" size="small" color="success" variant="tonal" prepend-icon="tabler-circle-check-filled" class="ms-auto">
          {{ $t("Done") }}
        </VChip>
      </div>

      <h3 class="text-h6 font-weight-bold mb-1">{{ lesson.title_en }}</h3>
      <p v-if="locale === 'km' && lesson.title_km" class="text-body-1 mb-1">{{ lesson.title_km }}</p>
      <p v-if="lesson.summary" class="text-body-2 text-medium-emphasis mb-4">{{ lesson.summary }}</p>

      <span class="mt-auto text-primary font-weight-medium d-inline-flex align-center gap-1">
        {{ lesson.completed_at ? $t("Read again") : $t("Read lesson") }}
        <VIcon icon="tabler-arrow-right" size="18" />
      </span>
    </VCardText>
  </VCard>
</template>
