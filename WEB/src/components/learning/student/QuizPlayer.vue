<script setup>
import AppDialog from "@/components/AppDialog.vue";
import QuizQuestion from "@/components/learning/student/questions/QuizQuestion.vue";
import { useQuizClock } from "@/composables/useQuizClock";
import { SKILLS } from "@/constants/learning";
import { isAnswered } from "@/utils/quiz";

/**
 * A student takes a quiz: one question at a time.
 * - Top: title, clock, progress (one bar part per question).
 * - Phone: a bar at the bottom (Back, all questions, Next). "All questions" opens a sheet.
 * - Desktop: Back, question numbers, Next.
 * "Submit" asks first. When the time limit ends, the answers are sent.
 *
 * `quiz` is the API's StudentQuizResource + lesson (no correct answers — grading is on the server).
 * v-model:answers = { [questionId]: answer } (the page keeps it, so it can save a draft).
 */
const props = defineProps({
  quiz: { type: Object, required: true },
  startedAt: { type: String, default: null },
  closeTo: { type: [String, Object], default: null },
  submitting: { type: Boolean, default: false },
});

const answers = defineModel("answers", { type: Object, default: () => ({}) });
const emit = defineEmits(["submit"]);

const index = ref(0);
const questions = computed(() => props.quiz.questions);
const total = computed(() => questions.value.length);
const question = computed(() => questions.value[index.value]);
const isLast = computed(() => index.value === total.value - 1);

const answered = (q) => isAnswered(answers.value[q.id]);
const answeredCount = computed(() => questions.value.filter(answered).length);
const unanswered = computed(() => total.value - answeredCount.value);

const skill = computed(() =>
  SKILLS.find((item) => item.value === props.quiz.lesson?.skill),
);

const setAnswer = (value) =>
  (answers.value = { ...answers.value, [question.value.id]: value });

const isSheetOpen = ref(false);
const go = (to) => {
  index.value = Math.min(Math.max(to, 0), total.value - 1);
  isSheetOpen.value = false;
  window.scrollTo({ top: 0, behavior: "smooth" });
};

// Desktop page numbers: up to 5 around the current one, plus the first and last ("…" between)
const pages = computed(() => {
  const last = total.value - 1;
  if (total.value <= 7) return questions.value.map((_, i) => i);

  const start = Math.min(Math.max(index.value - 2, 0), last - 5);
  const shown = [
    ...new Set([0, ...Array.from({ length: 5 }, (_, i) => start + i), last]),
  ];
  return shown.flatMap((page, i) =>
    i && page - shown[i - 1] > 1 ? ["gap", page] : [page],
  );
});

// Clock: counts down when the quiz has a time limit
const clock = useQuizClock(
  () => props.startedAt,
  () => props.quiz.time_limit_minutes,
);

// Check before sending: the student cannot change answers after
const isConfirmOpen = ref(false);
const askSubmit = () => {
  isSheetOpen.value = false;
  isConfirmOpen.value = true;
};
const confirmSubmit = () => {
  isConfirmOpen.value = false;
  emit("submit");
};

watch(
  clock.isOver,
  (over) => {
    if (over && !props.submitting) {
      isConfirmOpen.value = false;
      emit("submit");
    }
  },
  { immediate: true },
);
</script>

<template>
  <section class="quiz-player">
    <!-- Title + clock -->
    <header class="quiz-head">
      <VBtn
        v-if="closeTo"
        :to="closeTo"
        icon="tabler-x"
        variant="outlined"
        class="quiz-head__close d-md-none"
        :aria-label="$t('Back to the lesson')"
      />

      <div class="quiz-head__titles">
        <span v-if="quiz.lesson" class="quiz-head__tag d-none d-md-inline-flex">
          <template v-if="skill">{{ $t(skill.title) }} · </template
          >{{ quiz.lesson.title_en }}
        </span>
        <h1 class="quiz-head__title">{{ quiz.title }}</h1>
        <p class="quiz-head__meta">
          <span class="d-none d-md-inline-flex align-center gap-1">
            <VIcon icon="tabler-list-numbers" size="18" />
            {{ $t("{count} questions", { count: total }) }}
          </span>
          <span class="d-inline-flex align-center gap-1">
            <VIcon
              icon="tabler-target"
              size="18"
              class="d-none d-md-inline-flex"
            />
            {{ $t("Pass with {score}%", { score: quiz.pass_score }) }}
          </span>
        </p>
      </div>

      <!-- <div
        class="quiz-clock"
        :class="{ 'quiz-clock--low': clock.isLow.value }"
        role="timer"
        :aria-label="clock.hasLimit.value ? $t('Time left') : $t('Time')"
      >
        <VIcon icon="tabler-clock" size="20" />
        <span>{{ clock.label.value }}</span>
      </div> -->
    </header>

    <!-- Progress: one part per question -->
    <div class="quiz-progress">
      <div class="quiz-progress__text">
        <span>{{
          $t("Question {number} of {total}", { number: index + 1, total })
        }}</span>
        <span>{{
          $t("{answered} of {total} answered", {
            answered: answeredCount,
            total,
          })
        }}</span>
      </div>
      <div
        class="quiz-progress__bar"
        role="progressbar"
        :aria-label="$t('Quiz progress')"
        :aria-valuenow="index + 1"
        aria-valuemin="1"
        :aria-valuemax="total"
      >
        <span
          v-for="(item, i) in questions"
          :key="item.id"
          class="quiz-progress__part"
          :class="{
            'quiz-progress__part--done': answered(item),
            'quiz-progress__part--current': i === index,
          }"
        />
      </div>
    </div>

    <!-- key: a fresh question part for each question -->
    <QuizQuestion
      :key="question.id"
      :question="question"
      :model-value="answers[question.id]"
      :disabled="submitting"
      class="quiz-player__question"
      @update:model-value="setAnswer"
    />

    <!-- Desktop: Back · numbers · Next -->
    <footer class="quiz-pager d-none d-md-flex">
      <VBtn
        variant="outlined"
        size="large"
        prepend-icon="tabler-arrow-left"
        :disabled="index === 0 || submitting"
        @click="go(index - 1)"
      >
        {{ $t("Back") }}
      </VBtn>

      <nav class="quiz-pager__numbers" :aria-label="$t('Questions')">
        <template v-for="(page, i) in pages" :key="`${page}-${i}`">
          <button
            v-if="page === 'gap'"
            type="button"
            class="quiz-number quiz-number--gap"
            :aria-label="$t('All questions')"
            @click="isSheetOpen = true"
          >
            …
          </button>
          <button
            v-else
            type="button"
            class="quiz-number"
            :class="{
              'quiz-number--answered': answered(questions[page]),
              'quiz-number--current': page === index,
            }"
            :aria-label="$t('Question {number}', { number: page + 1 })"
            :aria-current="page === index ? 'step' : undefined"
            :disabled="submitting"
            @click="go(page)"
          >
            {{ page + 1 }}
          </button>
        </template>
      </nav>

      <VBtn
        v-if="!isLast"
        color="primary"
        size="large"
        append-icon="tabler-arrow-right"
        @click="go(index + 1)"
      >
        {{ $t("Next") }}
      </VBtn>
      <VBtn
        v-else
        color="primary"
        size="large"
        append-icon="tabler-send"
        :loading="submitting"
        @click="askSubmit"
      >
        {{ $t("Submit") }}
      </VBtn>
    </footer>

    <!-- Phone: a bar fixed at the bottom of the screen -->
    <footer class="quiz-bar d-md-none">
      <VBtn
        variant="outlined"
        size="x-large"
        icon="tabler-arrow-left"
        rounded="lg"
        :disabled="index === 0 || submitting"
        :aria-label="$t('Back')"
        @click="go(index - 1)"
      />
      <VBtn
        variant="outlined"
        size="x-large"
        icon="tabler-grid-dots"
        rounded="lg"
        :aria-label="$t('All questions')"
        @click="isSheetOpen = true"
      />
      <VBtn
        v-if="!isLast"
        color="primary"
        size="x-large"
        class="flex-grow-1"
        append-icon="tabler-arrow-right"
        @click="go(index + 1)"
      >
        {{ $t("Next") }}
      </VBtn>
      <VBtn
        v-else
        color="primary"
        size="x-large"
        class="flex-grow-1"
        append-icon="tabler-send"
        :loading="submitting"
        @click="askSubmit"
      >
        {{ $t("Submit") }}
      </VBtn>
    </footer>

    <!-- All questions: jump to any one, or submit -->
    <VBottomSheet v-model="isSheetOpen" max-width="560">
      <VCard class="quiz-sheet">
        <div class="quiz-sheet__handle" aria-hidden="true" />
        <div class="d-flex align-center justify-space-between gap-3 mb-5">
          <h2 class="quiz-sheet__title">{{ $t("All questions") }}</h2>
          <span class="quiz-sheet__count">
            {{
              $t("{answered} of {total} answered", {
                answered: answeredCount,
                total,
              })
            }}
          </span>
        </div>

        <nav class="quiz-sheet__grid" :aria-label="$t('Questions')">
          <button
            v-for="(item, i) in questions"
            :key="item.id"
            type="button"
            class="quiz-number quiz-number--tile"
            :class="{
              'quiz-number--answered': answered(item),
              'quiz-number--current': i === index,
            }"
            :aria-label="$t('Question {number}', { number: i + 1 })"
            :aria-current="i === index ? 'step' : undefined"
            :disabled="submitting"
            @click="go(i)"
          >
            {{ i + 1 }}
          </button>
        </nav>

        <ul class="quiz-sheet__legend">
          <li>
            <span class="quiz-key quiz-key--answered" />{{ $t("Answered") }}
          </li>
          <li>
            <span class="quiz-key quiz-key--current" />{{ $t("Current") }}
          </li>
          <li><span class="quiz-key" />{{ $t("Not yet") }}</li>
        </ul>

        <VBtn
          block
          variant="outlined"
          size="x-large"
          :loading="submitting"
          @click="askSubmit"
        >
          {{ $t("Submit quiz") }}
        </VBtn>
      </VCard>
    </VBottomSheet>

    <AppDialog
      v-model:is-dialog-visible="isConfirmOpen"
      title="Submit your answers?"
      title-icon="tabler-send"
      width="440"
      is-footer
      is-submit
      submit-text="Yes, submit"
      submit-icon="tabler-send"
      submit-color="primary"
      @on-submit="confirmSubmit"
    >
      <p class="text-body-1 mb-2">
        {{
          $t("You answered {answered} of {total} questions.", {
            answered: answeredCount,
            total,
          })
        }}
      </p>
      <p v-if="unanswered" class="text-body-2 text-warning mb-2">
        {{ $t("Questions without an answer count as wrong.") }}
      </p>
      <p class="text-body-2 text-medium-emphasis mb-0">
        {{ $t("You cannot change your answers after you submit.") }}
      </p>
    </AppDialog>
  </section>
</template>

<style lang="scss" scoped>
$primary: rgb(var(--v-theme-primary));
$muted: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
$line: rgba(var(--v-border-color), var(--v-border-opacity));
$soft: rgba(var(--v-theme-on-surface), 0.05);

.quiz-player {
  max-width: 100%;
  // margin-inline: auto;
  border-radius: 2px;
}

// ---- Header ----
.quiz-head {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-block-end: 1.5rem;
}

.quiz-head__close {
  flex: none;
  border-color: $line;
}

.quiz-head__titles {
  flex: 1 1 auto;
  min-width: 0;
  text-align: center;
}

.quiz-head__tag {
  margin-block-end: 0.75rem;
  padding: 0.25rem 0.75rem;
  border-radius: 8px;
  background: rgba(var(--v-theme-primary), 0.12);
  color: $primary;
  font-size: 0.875rem;
  font-weight: 500;
}

.quiz-head__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  line-height: 1.3;
}

.quiz-head__meta {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.25rem 1.25rem;
  margin: 0.125rem 0 0;
  font-size: 0.875rem;
  color: $muted;
}

.quiz-clock {
  display: inline-flex;
  flex: none;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid $line;
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
  font-size: 1rem;
  font-variant-numeric: tabular-nums;

  &--low {
    border-color: rgba(var(--v-theme-error), 0.4);
    color: rgb(var(--v-theme-error));
  }
}

// ---- Progress ----
.quiz-progress {
  margin-block-end: 2rem;
}

.quiz-progress__text {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  margin-block-end: 0.625rem;
  font-size: 0.9375rem;
  color: $muted;
}

.quiz-progress__bar {
  display: flex;
  gap: 6px;
}

.quiz-progress__part {
  flex: 1 1 0;
  height: 6px;
  border-radius: 6px;
  background: rgba(var(--v-theme-on-surface), 0.1);
  transition: background-color 0.2s ease;

  &--done {
    background: rgba(var(--v-theme-primary), 0.4);
  }

  &--current {
    background: $primary;
  }
}

.quiz-player__question {
  min-height: 260px;
  padding-block-end: 2rem;
}

// ---- Question numbers (desktop pager + sheet) ----
.quiz-number {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border: 1.5px solid $line;
  border-radius: 50%;
  background: transparent;
  color: $muted;
  font: inherit;
  font-size: 0.9375rem;
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    background-color 0.15s ease;

  &:hover:not(:disabled) {
    border-color: rgba(var(--v-theme-primary), 0.5);
  }

  &:focus-visible {
    outline: 2px solid $primary;
    outline-offset: 2px;
  }

  &--answered {
    border-color: transparent;
    background: rgba(var(--v-theme-primary), 0.12);
    color: $primary;
  }

  &--current {
    border-color: rgba(var(--v-theme-primary), 0.6);
    color: $primary;
    font-weight: 600;
  }

  &--gap {
    width: auto;
    padding-inline: 0.25rem;
    border: 0;
  }

  // Sheet: big rounded squares; answered = filled blue
  &--tile {
    width: 100%;
    height: 56px;
    border-radius: 14px;
    font-size: 1.125rem;
    color: rgb(var(--v-theme-on-surface));

    &.quiz-number--answered {
      background: $primary;
      color: rgb(var(--v-theme-on-primary));
    }

    &.quiz-number--current {
      border-color: rgba(var(--v-theme-primary), 0.6);
      background: rgb(var(--v-theme-surface));
      color: $primary;
    }
  }
}

.quiz-pager {
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-block-start: 1.5rem;
  border-block-start: 1px solid $line;

  > .v-btn {
    min-width: 8.5rem;
  }
}

.quiz-pager__numbers {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.5rem;
}

// ---- Phone bottom bar ----
.quiz-bar {
  position: sticky;
  z-index: 2;
  inset-block-end: 0;
  display: flex;
  gap: 0.75rem;
  padding-block: 0.875rem calc(0.875rem + env(safe-area-inset-bottom));
  border-block-start: 1px solid $line;
  background: rgb(var(--v-theme-background));

  .v-btn--icon {
    border-color: $line;
  }
}

// ---- All questions sheet ----
.quiz-sheet {
  padding: 0.75rem 1.5rem 1.5rem;
  border-radius: 24px 24px 0 0 !important;
}

.quiz-sheet__handle {
  width: 40px;
  height: 5px;
  margin: 0 auto 1.25rem;
  border-radius: 5px;
  background: rgba(var(--v-theme-on-surface), 0.15);
}

.quiz-sheet__title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.quiz-sheet__count {
  font-size: 0.9375rem;
  color: $muted;
}

.quiz-sheet__grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.75rem;
}

.quiz-sheet__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1.25rem;
  margin: 1.25rem 0 1.5rem;
  padding: 0;
  list-style: none;
  font-size: 0.9375rem;
  color: $muted;

  li {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }
}

.quiz-key {
  width: 12px;
  height: 12px;
  border: 1.5px solid $line;
  border-radius: 3px;

  &--answered {
    border-color: $primary;
    background: $primary;
  }

  &--current {
    border-color: rgba(var(--v-theme-primary), 0.6);
  }
}

// ---- Desktop: the quiz sits in one card, bigger title on the left ----
@media (min-width: 960px) {
  .quiz-player {
    padding: 2.5rem;
    border: 1px solid $line;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
  }

  .quiz-head {
    align-items: flex-start;
    margin-block-end: 2rem;
  }

  .quiz-head__titles {
    text-align: start;
  }

  .quiz-head__title {
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  .quiz-head__meta {
    justify-content: flex-start;
    margin-block-start: 0.375rem;
    font-size: 0.9375rem;
  }

  .quiz-clock {
    border-color: transparent;
    background: $soft;
  }
}
</style>
