<script setup>
import { SKILLS, findOption } from "@/constants/learning";

/**
 * A lesson as students see it. Used by the admin preview now, and by the
 * public lesson page / student system later. `lesson` is the API's
 * StudentLessonResource. Put the quiz (or a "Take the quiz" button) in the default slot.
 *
 * The lesson HTML is shown with v-html: that is safe because the API cleans it
 * when it is saved (API/config/purify.php → lesson).
 */
const props = defineProps({
  lesson: { type: Object, required: true },
});

const language = ref("en");
const hasKhmer = computed(() => !!props.lesson.content_km);
const content = computed(() => (language.value === "km" && hasKhmer.value ? props.lesson.content_km : props.lesson.content));
const skill = computed(() => findOption(SKILLS, props.lesson.skill));

// One shared player, so starting an example stops the one before
let player = null;
const playing = ref(null);
const play = (example) => {
  player?.pause();
  if (playing.value === example.id) return (playing.value = null);
  player = new Audio(example.audio_url);
  player.onended = () => (playing.value = null);
  player.play();
  playing.value = example.id;
};
onBeforeUnmount(() => player?.pause());
</script>

<template>
  <article class="lesson-view">
    <header class="lesson-view__header">
      <p class="lesson-view__meta">
        <template v-if="lesson.course?.level">{{ lesson.course.level.code }} · </template>{{ $t(skill?.title ?? lesson.skill) }}
      </p>
      <h1 class="lesson-view__title">{{ lesson.title_en }}</h1>
      <p v-if="lesson.title_km" class="lesson-view__title-km">{{ lesson.title_km }}</p>
      <p v-if="lesson.summary" class="lesson-view__summary">{{ lesson.summary }}</p>
      <audio v-if="lesson.audio_url" :src="lesson.audio_url" controls preload="none" class="lesson-view__audio" />
    </header>

    <VBtnToggle v-if="hasKhmer" v-model="language" mandatory density="comfortable" class="mb-6" color="primary" variant="outlined" divided>
      <VBtn value="en">English</VBtn>
      <VBtn value="km">ខ្មែរ</VBtn>
    </VBtnToggle>

    <!-- eslint-disable-next-line vue/no-v-html -->
    <div class="lesson-content" v-html="content" />

    <section v-if="lesson.examples?.length" class="lesson-view__section">
      <h2 class="lesson-view__section-title">{{ $t("Examples") }}</h2>
      <ul class="examples">
        <li v-for="example in lesson.examples" :key="example.id" class="examples__item">
          <div class="flex-grow-1">
            <p class="examples__sentence">{{ example.sentence }}</p>
            <p v-if="example.translation_km" class="examples__translation">{{ example.translation_km }}</p>
          </div>
          <VBtn
            v-if="example.audio_url"
            icon
            variant="tonal"
            color="primary"
            size="44"
            :aria-label="$t('Listen')"
            @click="play(example)"
          >
            <VIcon :icon="playing === example.id ? 'tabler-player-pause' : 'tabler-volume'" />
          </VBtn>
        </li>
      </ul>
    </section>

    <slot />
  </article>
</template>

<style lang="scss" scoped>
.lesson-view {
  max-width: 760px;
  margin-inline: auto;
}

.lesson-view__header {
  margin-block-end: 2rem;
}

.lesson-view__meta {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.lesson-view__title {
  margin: 0;
  font-size: clamp(1.75rem, 5vw, 2.5rem);
  font-weight: 700;
  line-height: 1.2;
}

.lesson-view__title-km {
  margin: 0.25rem 0 0;
  font-size: 1.25rem;
  line-height: 1.7;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.lesson-view__summary {
  margin: 1rem 0 0;
  font-size: 1.125rem;
  line-height: 1.6;
}

.lesson-view__audio {
  width: 100%;
  max-width: 420px;
  margin-block-start: 1rem;
}

.lesson-view__section {
  margin-block-start: 3rem;
}

.lesson-view__section-title {
  margin: 0 0 1rem;
  font-size: 1.5rem;
  font-weight: 700;
}

.examples {
  margin: 0;
  padding: 0;
  list-style: none;
}

.examples__item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 0;
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.examples__sentence {
  margin: 0;
  font-size: 1.0625rem;
  font-weight: 500;
}

.examples__translation {
  margin: 0.125rem 0 0;
  line-height: 1.7;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}
</style>
