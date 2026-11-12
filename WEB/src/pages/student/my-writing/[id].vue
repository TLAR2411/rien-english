<script setup>
import { getMyWritingOne } from "@/services/site";
import { formatLearningDate } from "@/utils/learningDate";
import { myWritingRoute, studentLessonRoute } from "@/utils/studentRoutes";
import { wordTargetText } from "@/utils/wordCount";
import WritingStatusChip from "@/components/learning/WritingStatusChip.vue";

/** One of my writings: the teacher's score + feedback, my text, and the sample answer. */
definePage({
  meta: {
    title: "My Writing",
    layout: "default",
    requiresAuth: true,
  },
});

const route = useRoute();
const { t, locale } = useI18n({ useScope: "global" });

const writing = ref(null);
const notFound = ref(false);

watch(
  () => route.params.id,
  async (id) => {
    writing.value = null;
    notFound.value = false;
    try {
      writing.value = await getMyWritingOne(id);
    } catch {
      notFound.value = true;
    }
  },
  { immediate: true },
);

const task = computed(() => writing.value?.task ?? {});
const target = computed(() => wordTargetText(t, task.value.min_words, task.value.max_words));
const title = (lesson) => (locale.value === "km" && lesson?.title_km) || lesson?.title_en;
const date = (value) => formatLearningDate(value, locale.value);
</script>

<template>
  <section class="my-writing">
    <VBtn :to="myWritingRoute()" variant="text" prepend-icon="tabler-arrow-left" class="ms-n3 mb-4">
      {{ $t("My Writing") }}
    </VBtn>

    <VAlert v-if="notFound" type="warning" variant="tonal">{{ $t("This writing was not found.") }}</VAlert>
    <VProgressLinear v-else-if="!writing" indeterminate color="primary" />

    <template v-else>
      <header class="d-flex flex-wrap align-start justify-space-between gap-3 mb-6">
        <div>
          <p class="text-body-2 text-primary font-weight-bold mb-1">
            <template v-if="writing.lesson?.level">{{ writing.lesson.level }} · </template>{{ $t("Writing") }}
          </p>
          <h1 class="text-h4 font-weight-bold mb-1">{{ title(writing.lesson) }}</h1>
          <p class="text-body-1 text-medium-emphasis mb-0">
            {{ $t("Sent on {date}", { date: date(writing.created_at) }) }}
          </p>
        </div>
        <WritingStatusChip :status="writing.status" :score="writing.score" size="default" />
      </header>

      <!-- Teacher's feedback, or "waiting" -->
      <VCard v-if="writing.status === 'reviewed'" flat border class="mb-6">
        <VCardText class="d-flex flex-wrap gap-5 pa-6">
          <div class="score-ring">
            <strong>{{ writing.score }}</strong>
            <span>/ 100</span>
          </div>
          <div class="feedback-body">
            <h2 class="text-h5 font-weight-bold mb-2">{{ $t("Your teacher's feedback") }}</h2>
            <p class="feedback mb-2">{{ writing.feedback }}</p>
            <p class="text-body-2 text-medium-emphasis mb-0">{{ $t("Reviewed on {date}", { date: date(writing.reviewed_at) }) }}</p>
          </div>
        </VCardText>
      </VCard>
      <VAlert v-else color="primary" variant="tonal" icon="tabler-clock-hour-4" class="mb-6">
        <strong>{{ $t("Waiting for review") }}</strong> —
        {{ $t("Your teacher will read it and give you a score and feedback.") }}
      </VAlert>

      <!-- My text -->
      <h2 class="section-label">
        {{ $t("Your writing") }} · {{ $t("{count} words", { count: writing.word_count }) }}
        <template v-if="target"> ({{ target }})</template>
      </h2>
      <p class="my-text">{{ writing.text }}</p>

      <!-- Sample answer -->
      <template v-if="task.sample_answer">
        <h2 class="section-label mt-8">{{ $t("Sample answer") }}</h2>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="sample lesson-content" v-html="task.sample_answer" />
      </template>

      <VBtn
        v-if="task.is_open"
        :to="studentLessonRoute(writing.lesson.slug)"
        variant="tonal"
        color="primary"
        prepend-icon="tabler-book"
        class="mt-8"
      >
        {{ $t(writing.status === "reviewed" ? "Open the lesson and write again" : "Open the lesson") }}
      </VBtn>
    </template>
  </section>
</template>

<style lang="scss" scoped>
$line: rgba(var(--v-border-color), var(--v-border-opacity));

.my-writing {
  max-width: 820px;
}

.score-ring {
  display: flex;
  flex: 0 0 auto;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 96px;
  height: 96px;
  border-radius: 50%;
  background: rgba(var(--v-theme-success), 0.1);
  color: rgb(var(--v-theme-success));

  strong {
    font-size: 2rem;
    line-height: 1;
  }

  span {
    margin-block-start: 0.25rem;
    font-size: 0.8125rem;
  }
}

.feedback-body {
  flex: 1 1 240px;
  min-inline-size: 0;
}

.feedback {
  font-size: 1.0625rem;
  line-height: 1.8;
  white-space: pre-line;
}

.section-label {
  margin: 0 0 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.my-text {
  margin: 0;
  padding: 1.25rem 1.5rem;
  border: 1px solid $line;
  border-radius: 8px;
  background: rgb(var(--v-theme-surface));
  font-size: 1.0625rem;
  line-height: 1.8;
  white-space: pre-line;
}

.sample {
  padding: 1.25rem 1.5rem;
  border-inline-start: 3px solid rgb(var(--v-theme-success));
  background: rgba(var(--v-theme-success), 0.04);
}
</style>
