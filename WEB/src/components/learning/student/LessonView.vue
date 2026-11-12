<script setup>
import { useI18n } from "vue-i18n";
import { useSpeech } from "@/composables/useSpeech";
import { SKILLS, findOption } from "@/constants/learning";
import { youtubeEmbedUrl } from "@/utils/youtube";
import ListeningPlayer from "@/components/learning/student/ListeningPlayer.vue";
import { wordTargetText } from "@/utils/wordCount";

/**
 * A lesson as students see it. Used by the admin preview now, and by the
 * public lesson page / student system later. `lesson` is the API's
 * StudentLessonResource. Put the quiz (or a "Take the quiz" button) in the default slot.
 *
 * The lesson text language: `language` ("en" / "km") when given, otherwise the
 * language switch in the top bar. Khmer shows `content_km` when the lesson has it.
 *
 * `fitScreen` (admin preview): on big screens the lesson card fills the screen
 * and scrolls inside. The public website sets it off and the page scrolls.
 *
 * Listening lessons: the audio player comes after the text ("Before you listen"),
 * the transcript stays closed until "Show transcript" — or `showTranscript`
 * (the page sets it once the student has done the quiz).
 *
 * The lesson HTML is shown with v-html: that is safe because the API cleans it
 * when it is saved (API/config/purify.php → lesson).
 */
const props = defineProps({
  lesson: { type: Object, required: true },
  language: { type: String, default: null },
  fitScreen: { type: Boolean, default: true },
  showTranscript: { type: Boolean, default: false },
});

const slots = useSlots();
const { locale, t } = useI18n();

const skill = computed(() => findOption(SKILLS, props.lesson.skill));
const isListening = computed(() => props.lesson.skill === "listening");
const hasPlayer = computed(() => isListening.value && Boolean(props.lesson.audio_url));

// Listening: try first without reading
const transcriptOpen = ref(props.showTranscript);
watch(
  () => props.showTranscript,
  (open) => open && (transcriptOpen.value = true),
);
// Khmer text (content_km, Khmer title, example translations) only in Khmer;
// English shows English only
const isKhmer = computed(() => (props.language ?? locale.value) === "km");
const rawContent = computed(() =>
  isKhmer.value && props.lesson.content_km ? props.lesson.content_km : props.lesson.content,
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
const wordCount = computed(() => props.lesson.words?.length ?? 0);
// Writing: "50–100 words"
const wordTarget = computed(() => wordTargetText(t, props.lesson.min_words, props.lesson.max_words));

// "Words to know": the meaning follows the language (Khmer, else simple English).
// `sentence` + a "word-" id let useSpeech read the word without mixing it up with examples.
const words = computed(() =>
  (props.lesson.words ?? []).map((word) => ({
    ...word,
    id: `word-${word.id}`,
    sentence: word.word,
    meaning: (isKhmer.value && word.meaning_km) || word.meaning_en,
  })),
);
const questionCount = computed(() => props.lesson.quiz?.questions?.length ?? props.lesson.quiz?.questions_count ?? 0);
const minutes = computed(() =>
  Math.max(1, Math.round(body.value.words / 150 + exampleCount.value * 0.25 + wordCount.value * 0.15)),
);

const outline = computed(() => [
  ...(props.lesson.youtube_id ? [{ id: "lesson-video", title: t("Video") }] : []),
  ...body.value.sections,
  ...(hasPlayer.value ? [{ id: "lesson-listen", title: t("Listen") }] : []),
  ...(props.lesson.transcript ? [{ id: "lesson-transcript", title: t("Transcript") }] : []),
  ...(wordCount.value ? [{ id: "lesson-words", title: t("Words to know") }] : []),
  ...(exampleCount.value ? [{ id: "lesson-examples", title: t("Examples") }] : []),
  ...(slots.default
    ? [{ id: "lesson-quiz", title: props.lesson.skill === "writing" ? t("Your writing") : t("Quiz") }]
    : []),
]);

// Big screens: the outline stays still and only the lesson card scrolls.
// The card fills the screen from where it starts down to the bottom.
const viewEl = ref(null);
const sheetEl = ref(null);
const height = ref(null);
const fitToScreen = () => {
  if (!props.fitScreen || !viewEl.value) return;
  const rect = viewEl.value.getBoundingClientRect();
  const top = rect.top + window.scrollY;
  // Whatever the page still shows under the lesson (card padding, footer…)
  const below = Math.max(0, document.documentElement.scrollHeight - (rect.bottom + window.scrollY));
  height.value = `${Math.max(420, window.innerHeight - top - below)}px`;
};

// Side outline: jump to a part, and mark the part being read
const activeId = ref(null);
const goTo = (id) => document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });

const updateActive = () => {
  // Where reading starts: the card's top, or below the site top bar when the page scrolls
  const sheetTop = props.fitScreen ? Math.max(sheetEl.value?.getBoundingClientRect().top ?? 0, 0) : 88;
  let current = outline.value[0]?.id ?? null;
  for (const item of outline.value) {
    const el = document.getElementById(item.id);
    if (el && el.getBoundingClientRect().top - sheetTop < 80) current = item.id;
  }
  activeId.value = current;
};

onMounted(() => {
  window.addEventListener("scroll", updateActive, { passive: true });
  window.addEventListener("resize", fitToScreen);
  nextTick(() => {
    fitToScreen();
    updateActive();
  });
});
watch(outline, () => nextTick(updateActive));

// Uploaded audio, or the browser reads the sentence (one at a time)
const speech = useSpeech();
onBeforeUnmount(() => {
  window.removeEventListener("scroll", updateActive);
  window.removeEventListener("resize", fitToScreen);
});
</script>

<template>
  <div
    ref="viewEl"
    class="lesson-view"
    :class="{ 'lesson-view--no-outline': outline.length < 2, 'lesson-view--flow': !fitScreen }"
    :style="{ '--lesson-view-height': height }"
  >
    <!-- Side outline (big screens only) -->
    <aside v-if="outline.length >= 2" class="lesson-outline">
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

    <article ref="sheetEl" class="lesson-sheet" @scroll.passive="updateActive">
      <header class="lesson-sheet__header">
        <div class="lesson-sheet__tags">
          <span v-if="lesson.course?.level" class="lesson-tag lesson-tag--level">{{ lesson.course.level.code }}</span>
          <span class="lesson-tag">{{ $t(skill?.title ?? lesson.skill) }}</span>
        </div>

        <h1 class="lesson-sheet__title">{{ lesson.title_en }}</h1>
        <p v-if="isKhmer && lesson.title_km" class="lesson-sheet__title-km">{{ lesson.title_km }}</p>
        <p v-if="lesson.summary" class="lesson-sheet__summary">{{ lesson.summary }}</p>

        <ul class="lesson-sheet__facts">
          <li>{{ $t("{count} min read", { count: minutes }) }}</li>
          <li v-if="wordTarget">{{ $t("Write {target}", { target: wordTarget }) }}</li>
          <li v-if="wordCount">{{ $t("{count} new words", { count: wordCount }) }}</li>
          <li v-if="exampleCount">{{ $t("{count} examples", { count: exampleCount }) }}</li>
          <li v-if="questionCount">{{ $t("{count} questions", { count: questionCount }) }}</li>
        </ul>

        <audio
          v-if="lesson.audio_url && !hasPlayer"
          :src="lesson.audio_url"
          controls
          preload="none"
          class="lesson-sheet__audio"
        />
      </header>

      <div class="lesson-sheet__body">
        <!-- YouTube video (the teacher's link) -->
        <section v-if="lesson.youtube_id" id="lesson-video" class="lesson-video">
          <iframe
            :src="youtubeEmbedUrl(lesson.youtube_id)"
            :title="lesson.title_en"
            loading="lazy"
            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
          />
        </section>

        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="lesson-content" v-html="body.html" />

        <!-- Listening: the audio player -->
        <section v-if="hasPlayer" id="lesson-listen" class="lesson-sheet__section">
          <h2 class="lesson-sheet__section-title">{{ $t("Listen") }}</h2>
          <ListeningPlayer :src="lesson.audio_url" :title="lesson.title_en" />
        </section>

        <!-- Listening: the transcript, closed at first -->
        <section v-if="lesson.transcript" id="lesson-transcript" class="lesson-sheet__section">
          <div class="d-flex align-center justify-space-between gap-3 mb-4">
            <h2 class="lesson-sheet__section-title mb-0">{{ $t("Transcript") }}</h2>
            <VBtn
              v-if="transcriptOpen"
              variant="text"
              size="small"
              prepend-icon="tabler-eye-off"
              @click="transcriptOpen = false"
            >
              {{ $t("Hide") }}
            </VBtn>
          </div>
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div v-if="transcriptOpen" class="lesson-content transcript" v-html="lesson.transcript" />
          <div v-else class="transcript-closed">
            <p>{{ $t("Listen first without reading. Then open the transcript to check.") }}</p>
            <VBtn variant="tonal" color="primary" prepend-icon="tabler-file-text" @click="transcriptOpen = true">
              {{ $t("Show transcript") }}
            </VBtn>
          </div>
        </section>

        <!-- New words of the text (reading) -->
        <section v-if="wordCount" id="lesson-words" class="lesson-sheet__section">
          <h2 class="lesson-sheet__section-title">{{ $t("Words to know") }}</h2>
          <ul class="examples">
            <li v-for="word in words" :key="word.id" class="examples__item">
              <div class="flex-grow-1">
                <p class="examples__sentence">
                  {{ word.word }}
                  <span v-if="word.meaning" class="words__meaning">— {{ word.meaning }}</span>
                </p>
                <p v-if="word.example" class="words__example">{{ word.example }}</p>
              </div>
              <VBtn
                v-if="speech.canPlay(word)"
                icon
                variant="text"
                color="primary"
                size="44"
                :aria-label="$t('Listen')"
                @click="speech.toggle(word)"
              >
                <VIcon :icon="speech.isPlaying(word) ? 'tabler-player-stop' : 'tabler-volume'" size="22" />
              </VBtn>
            </li>
          </ul>
        </section>

        <section v-if="exampleCount" id="lesson-examples" class="lesson-sheet__section">
          <h2 class="lesson-sheet__section-title">{{ $t("Examples") }}</h2>
          <ul class="examples">
            <li v-for="example in lesson.examples" :key="example.id" class="examples__item">
              <div class="flex-grow-1">
                <p class="examples__sentence">{{ example.sentence }}</p>
                <p v-if="isKhmer && example.translation_km" class="examples__translation">{{ example.translation_km }}</p>
              </div>
              <VBtn
                v-if="speech.canPlay(example)"
                icon
                variant="text"
                color="primary"
                size="44"
                :aria-label="$t('Listen')"
                @click="speech.toggle(example)"
              >
                <VIcon :icon="speech.isPlaying(example) ? 'tabler-player-stop' : 'tabler-volume'" size="22" />
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

  // Big screens: fixed height, the lesson card scrolls inside
  @media (min-width: 960px) {
    &:not(.lesson-view--no-outline):not(.lesson-view--flow) {
      height: var(--lesson-view-height, calc(100dvh - 14rem));

      .lesson-sheet {
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-width: thin;
      }
    }
  }
}

// ── Side outline ──
.lesson-outline {
  align-self: start;
  padding-block-start: 0.5rem;

  // Page scrolls: the outline stays in view below the site top bar
  .lesson-view--flow & {
    position: sticky;
    inset-block-start: calc(var(--rien-header-height, 64px) + 1.5rem);
  }

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
  border-radius: 4px;
  background: rgb(var(--v-theme-surface));
}

.lesson-sheet__header {
  padding: clamp(1.25rem, 4vw, 2.25rem) clamp(1.25rem, 5vw, 3rem) clamp(1rem, 3vw, 1.5rem);
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

  // Space above a part when the outline jumps to it
  :deep(h2),
  .lesson-video,
  .lesson-sheet__section {
    scroll-margin-top: 1.5rem;
  }

  .lesson-view--flow & :deep(h2),
  .lesson-view--flow & .lesson-video,
  .lesson-view--flow & .lesson-sheet__section {
    scroll-margin-top: calc(var(--rien-header-height, 64px) + 1.5rem);
  }
}

.lesson-sheet__section {
  margin-block-start: 3rem;
}

// 16:9 video box, full width of the lesson text
.lesson-video {
  position: relative;
  margin-block-end: 2rem;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  border-radius: 4px;
  background: #000;

  iframe {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
  }
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
  border-radius: 4px;
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

// ── Transcript ──
.transcript {
  padding: 1.25rem 1.5rem;
  border-inline-start: 3px solid rgba(var(--v-theme-primary), 0.35);
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.transcript-closed {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border: 1px dashed $line;
  border-radius: 4px;

  p {
    margin: 0;
    color: $muted;
  }
}

// ── Words to know ──
.words__meaning {
  font-weight: 400;
  line-height: 1.7;
  color: $muted;
}

.words__example {
  margin: 0.125rem 0 0;
  font-style: italic;
  color: $muted;
}
</style>
