<script setup>
import { SKILLS, findOption } from "@/constants/learning";
import { lessonRoute } from "@/utils/siteRoutes";

/** A lesson in a public list. `lesson` is the API's LessonCardResource. */
const props = defineProps({
  lesson: { type: Object, required: true },
});

const skill = computed(() => findOption(SKILLS, props.lesson.skill));

// English mode shows no Khmer text
const { locale } = useI18n({ useScope: "global" });
const isKhmer = computed(() => locale.value === "km");
</script>

<template>
  <RouterLink :to="lessonRoute(lesson.slug)" class="lesson-card">
    <div class="lesson-card__tags">
      <span v-if="lesson.level" class="site-tag site-tag--level">{{ lesson.level.code }}</span>
      <span class="site-tag">{{ $t(skill?.title ?? lesson.skill) }}</span>
    </div>

    <h3 class="lesson-card__title">{{ lesson.title_en }}</h3>
    <p v-if="isKhmer && lesson.title_km" class="lesson-card__title-km site-km">{{ lesson.title_km }}</p>
    <p v-if="lesson.summary" class="lesson-card__summary">{{ lesson.summary }}</p>

    <span class="lesson-card__more">
      {{ $t("Read lesson") }}
      <VIcon icon="tabler-arrow-right" size="18" />
    </span>
  </RouterLink>
</template>

<style lang="scss" scoped>
.lesson-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 20px 22px;
  border: 1px solid var(--rien-line);
  border-radius: var(--rien-radius);
  background: #fff;
  text-decoration: none;
  transition: border-color 0.15s;

  &:hover {
    border-color: var(--rien-blue);

    .lesson-card__more {
      text-decoration: underline;
    }
  }
}

.lesson-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-block-end: 12px;
}

.lesson-card__title {
  font-size: 19px;
  font-weight: 600;
  line-height: 1.3;
}

.lesson-card__title-km {
  margin-block-start: 2px;
  color: var(--rien-grey);
  font-size: 15px;
  line-height: 1.5;
}

.lesson-card__summary {
  display: -webkit-box;
  overflow: hidden;
  margin-block-start: 8px;
  line-height: 1.55;
  color: var(--rien-grey);
  font-size: 15px;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}

.lesson-card__more {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-block-start: auto;
  padding-block-start: 14px;
  color: var(--rien-blue);
  font-weight: 500;
}
</style>
