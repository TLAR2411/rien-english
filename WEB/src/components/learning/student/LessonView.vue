<script setup>
import { useI18n } from "vue-i18n";
import { SKILLS, findOption } from "@/constants/learning";

/**
 * A lesson as students see it. Used by the admin preview now, and by the
 * public lesson page / student system later. `lesson` is the API's
 * StudentLessonResource. Put the quiz (or a "Take the quiz" button) in the default slot.
 *
 * The language follows the language switch in the top bar: Khmer shows
 * `content_km` when the lesson has it.
 *
 * The lesson HTML is shown with v-html: that is safe because the API cleans it
 * when it is saved (API/config/purify.php → lesson).
 */
const props = defineProps({
  lesson: { type: Object, required: true },
});

const slots = useSlots();
const { locale, t } = useI18n();

const skill = computed(() => findOption(SKILLS, props.lesson.skill));
const rawContent = computed(() =>
  locale.value === "km" && props.lesson.content_km ? props.lesson.content_km : props.lesson.content,
);

// Give every <h2> an id so the side outline can jump to it, and count words for the read time
const body = computed(() => {
  const doc = new DOMParser().parseFromString(rawContent.value ?? "", "text/html");
  const sections = [];
  doc.body.querySelectorAll("h2").forEach((heading, index) => {
    const title = heading.textContent.trim();
    if (!title) return;
    heading.id = `lesson-section-${index + 1}`;
    sections.push({ id: heading.id, title });
  });
  const words = doc.body.textContent.split(/\s+/).filter(Boolean).length;

  return { html: doc.body.innerHTML, sections, words };
});

const exampleCount = computed(() => props.lesson.examples?.length ?? 0);
const questionCount = computed(() => props.lesson.quiz?.questions?.length ?? 0);
const minutes = computed(() => Math.max(1, Math.round(body.value.words / 150 + exampleCount.value * 0.25)));

const outline = computed(() => [
  ...body.value.sections,
  ...(exampleCount.value ? [{ id: "lesson-examples", title: t("Examples") }] : []),
  ...(slots.default ? [{ id: "lesson-quiz", title: t("Quiz") }] : []),
]);

// Side outline: jump to a part, and mark the part being read
const activeId = ref(null);
const goTo = (id) => document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });

const updateActive = () => {
  let current = outline.value[0]?.id ?? null;
  for (const item of outline.value) {
    const el = document.getElementById(item.id);
    if (el && el.getBoundingClientRect().top < 140) current = item.id;
  }
  activeId.value = current;
};
// The outline is `position: sticky`. A parent with `overflow: hidden` (cards, tabs)
// stops sticky from working, so switch those parents to `overflow: clip` — it looks
// the same but lets the outline stay on screen. Put back when the page closes.
const outlineEl = ref(null);
const unclipped = [];
const allowSticky = () => {
  for (let el = outlineEl.value?.parentElement; el && el !== document.body; el = el.parentElement) {
    const style = getComputedStyle(el);
    if ([style.overflowX, style.overflowY].includes("hidden")) {
      unclipped.push([el, el.style.overflow]);
      el.style.overflow = "clip";
    }
  }
};
watch(outlineEl, (el) => el && allowSticky());

onMounted(() => {
  window.addEventListener("scroll", updateActive, { passive: true });
  nextTick(updateActive);
});
watch(outline, () => nextTick(updateActive));

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
onBeforeUnmount(() => {
  player?.pause();
  window.removeEventListener("scroll", updateActive);
  unclipped.forEach(([el, overflow]) => (el.style.overflow = overflow));
});
</script>

<template>
  <div class="lesson-view" :class="{ 'lesson-view--no-outline': outline.length < 2 }">
    <!-- Side outline (big screens only) -->
    <aside v-if="outline.length >= 2" ref="outlineEl" class="lesson-outline">
      <p class="lesson-outline__label">{{ $t("In this lesson") }}</p>
      <nav>
        <a
          v-for="item in outline"
          :key="item.id"
          :href="`#${item.id}`"
          class="lesson-outline__link"
          :class="{ 'lesson-outline__link--active': activeId === item.id }"
          @click.prevent="goTo(item.id)"
        >
          {{ item.title }}
        </a>
      </nav>
      <p class="lesson-outline__meta">
        {{ $t("{count} sections", { count: outline.length }) }}
        <template v-if="questionCount"> · {{ $t("{count} questions", { count: questionCount }) }}</template>
      </p>
    </aside>

    <article class="lesson-sheet">
      <header class="lesson-sheet__header">
        <div class="lesson-sheet__tags">
          <span v-if="lesson.course?.level" class="lesson-tag lesson-tag--level">{{ lesson.course.level.code }}</span>
          <span class="lesson-tag">{{ $t(skill?.title ?? lesson.skill) }}</span>
        </div>

        <h1 class="lesson-sheet__title">{{ lesson.title_en }}</h1>
        <p v-if="lesson.title_km" class="lesson-sheet__title-km">{{ lesson.title_km }}</p>
        <p v-if="lesson.summary" class="lesson-sheet__summary">{{ lesson.summary }}</p>

        <ul class="lesson-sheet__facts">
          <li>{{ $t("{count} min read", { count: minutes }) }}</li>
          <li v-if="exampleCount">{{ $t("{count} examples", { count: exampleCount }) }}</li>
          <li v-if="questionCount">{{ $t("{count} questions", { count: questionCount }) }}</li>
        </ul>

        <audio v-if="lesson.audio_url" :src="lesson.audio_url" controls preload="none" class="lesson-sheet__audio" />
      </header>

      <div class="lesson-sheet__body">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="lesson-content" v-html="body.html" />

        <section v-if="exampleCount" id="lesson-examples" class="lesson-sheet__section">
          <h2 class="lesson-sheet__section-title">{{ $t("Examples") }}</h2>
          <ul class="examples">
            <li v-for="example in lesson.examples" :key="example.id" class="examples__item">
              <div class="flex-grow-1">
                <p class="examples__sentence">{{ example.sentence }}</p>
                <p v-if="example.translation_km" class="examples__translation">{{ example.translation_km }}</p>
              </div>
              <VBtn
                v-if="example.audio_url"
                icon
                variant="text"
                color="primary"
                size="40"
                :aria-label="$t('Listen')"
                @click="play(example)"
              >
                <VIcon :icon="playing === example.id ? 'tabler-player-pause' : 'tabler-volume'" size="20" />
              </VBtn>
            </li>
          </ul>
        </section>

        <section v-if="slots.default" id="lesson-quiz" class="lesson-sheet__section lesson-sheet__quiz">
          <slot />
        </section>
      </div>
    </article>
  </div>
</template>

<style lang="scss" scoped>
$muted: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
$line: rgba(var(--v-border-color), var(--v-border-opacity));

.lesson-view {
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  gap: 2.5rem;
  max-width: 1080px;
  margin-inline: auto;

  &--no-outline {
    grid-template-columns: minmax(0, 1fr);
    max-width: 820px;
  }

  @media (max-width: 959px) {
    grid-template-columns: minmax(0, 1fr);
  }
}

// ── Side outline ──
.lesson-outline {
  position: sticky;
  top: 5.5rem;
  align-self: start;
  padding-block-start: 0.5rem;

  @media (max-width: 959px) {
    display: none;
  }
}

.lesson-outline__label {
  margin: 0 0 0.875rem;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: $muted;
}

.lesson-outline nav {
  display: flex;
  flex-direction: column;
  border-inline-start: 2px solid $line;
}

.lesson-outline__link {
  margin-inline-start: -2px;
  padding: 0.4rem 0 0.4rem 1rem;
  border-inline-start: 2px solid transparent;
  font-size: 0.9375rem;
  line-height: 1.5;
  color: $muted;
  text-decoration: none;
  transition: color 0.15s;

  &:hover {
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  }

  &--active {
    border-inline-start-color: rgb(var(--v-theme-primary));
    font-weight: 500;
    color: rgb(var(--v-theme-primary));
  }
}

.lesson-outline__meta {
  margin: 1.25rem 0 0;
  font-size: 0.8125rem;
  color: $muted;
}

// ── The lesson "page" ──
.lesson-sheet {
  overflow: hidden;
  border: 1px solid $line;
  border-radius: 20px;
  background: rgb(var(--v-theme-surface));
}

.lesson-sheet__header {
  padding: clamp(1.5rem, 5vw, 3rem) clamp(1.25rem, 5vw, 3rem) clamp(1.25rem, 4vw, 2rem);
  border-block-end: 1px solid $line;
  background: rgba(var(--v-theme-on-surface), 0.015);
}

.lesson-sheet__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-block-end: 1.25rem;
}

.lesson-tag {
  padding: 0.2rem 0.75rem;
  border: 1px solid $line;
  border-radius: 999px;
  font-size: 0.8125rem;
  font-weight: 500;
  line-height: 1.5;
  color: $muted;

  &--level {
    border-color: transparent;
    font-weight: 600;
    color: rgb(var(--v-theme-primary));
    background: rgba(var(--v-theme-primary), 0.08);
  }
}

.lesson-sheet__title {
  margin: 0;
  font-size: clamp(1.875rem, 5vw, 2.75rem);
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.02em;
}

.lesson-sheet__title-km {
  margin: 0.5rem 0 0;
  font-size: 1.125rem;
  line-height: 1.8;
  color: $muted;
}

.lesson-sheet__summary {
  max-width: 40rem;
  margin: 1rem 0 0;
  font-size: 1.0625rem;
  line-height: 1.7;
  color: $muted;
}

.lesson-sheet__facts {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem 1.5rem;
  margin: 1.5rem 0 0;
  padding: 0;
  list-style: none;
  font-size: 0.875rem;
  color: $muted;
}

.lesson-sheet__audio {
  width: 100%;
  max-width: 420px;
  margin-block-start: 1.25rem;
}

.lesson-sheet__body {
  padding: clamp(1.5rem, 5vw, 2.75rem) clamp(1.25rem, 5vw, 3rem) clamp(1.75rem, 5vw, 3rem);

  // Space above a part when the outline jumps to it (the top bar is sticky)
  :deep(h2),
  .lesson-sheet__section {
    scroll-margin-top: 6rem;
  }
}

.lesson-sheet__section {
  margin-block-start: 3rem;
}

.lesson-sheet__quiz {
  padding-block-start: 2.5rem;
  border-block-start: 1px solid $line;
}

.lesson-sheet__section-title {
  margin: 0 0 1rem;
  font-size: 1.5rem;
  font-weight: 700;
}

// ── Examples ──
.examples {
  margin: 0;
  padding: 0;
  list-style: none;
  border: 1px solid $line;
  border-radius: 12px;
  overflow: hidden;
}

.examples__item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1.125rem;

  & + & {
    border-block-start: 1px solid $line;
  }
}

.examples__sentence {
  margin: 0;
  font-size: 1.0625rem;
  font-weight: 500;
}

.examples__translation {
  margin: 0.125rem 0 0;
  line-height: 1.7;
  color: $muted;
}
</style>
