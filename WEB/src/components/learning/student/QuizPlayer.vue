<script setup>
import AppDialog from "@/components/AppDialog.vue";
import QuizQuestion from "@/components/learning/student/questions/QuizQuestion.vue";
import { isAnswered } from "@/utils/quiz";

/**
 * A student takes a quiz: one question at a time, progress bar, Back / Next,
 * "Submit" on the last question with a check first. `quiz` is the API's
 * StudentQuizResource (no correct answers — grading is on the server).
 *
 * v-model:answers = { [questionId]: answer } (the page keeps it, so it can save a draft).
 * Emits "submit" when the student confirms.
 */
const props = defineProps({
  quiz: { type: Object, required: true },
  submitting: { type: Boolean, default: false },
});

const answers = defineModel("answers", { type: Object, default: () => ({}) });
const emit = defineEmits(["submit"]);

const index = ref(0);
const questions = computed(() => props.quiz.questions);
const question = computed(() => questions.value[index.value]);
const isLast = computed(() => index.value === questions.value.length - 1);

const answeredCount = computed(() => questions.value.filter((q) => isAnswered(answers.value[q.id])).length);
const unanswered = computed(() => questions.value.length - answeredCount.value);
const progress = computed(() => ((index.value + 1) / questions.value.length) * 100);

const setAnswer = (value) => (answers.value = { ...answers.value, [question.value.id]: value });

const go = (to) => {
  index.value = Math.min(Math.max(to, 0), questions.value.length - 1);
  window.scrollTo({ top: 0, behavior: "smooth" });
};

// Check before sending: the student cannot change answers after
const isConfirmOpen = ref(false);
const confirmSubmit = () => {
  isConfirmOpen.value = false;
  emit("submit");
};
</script>

<template>
  <section class="quiz-player">
    <header class="quiz-player__header">
      <div class="d-flex align-center justify-space-between gap-3 mb-2">
        <p class="quiz-player__count">
          {{ $t("Question {number} of {total}", { number: index + 1, total: questions.length }) }}
        </p>
        <p class="quiz-player__count">
          {{ $t("{answered} of {total} answered", { answered: answeredCount, total: questions.length }) }}
        </p>
      </div>
      <VProgressLinear :model-value="progress" color="primary" height="6" rounded :aria-label="$t('Quiz progress')" />
    </header>

    <!-- key: a fresh question part for each question -->
    <QuizQuestion
      :key="question.id"
      :question="question"
      :model-value="answers[question.id]"
      :disabled="submitting"
      class="quiz-player__question"
      @update:model-value="setAnswer"
    />

    <footer class="quiz-player__footer">
      <VBtn
        variant="outlined"
        size="large"
        prepend-icon="tabler-arrow-left"
        :disabled="index === 0 || submitting"
        @click="go(index - 1)"
      >
        {{ $t("Back") }}
      </VBtn>

      <VBtn v-if="!isLast" color="primary" size="large" append-icon="tabler-arrow-right" @click="go(index + 1)">
        {{ $t("Next") }}
      </VBtn>
      <VBtn v-else color="primary" size="large" append-icon="tabler-send" :loading="submitting" @click="isConfirmOpen = true">
        {{ $t("Submit") }}
      </VBtn>
    </footer>

    <!-- Jump to any question; answered ones are filled in -->
    <nav class="quiz-player__dots" :aria-label="$t('Questions')">
      <button
        v-for="(item, i) in questions"
        :key="item.id"
        type="button"
        class="quiz-dot"
        :class="{ 'quiz-dot--answered': isAnswered(answers[item.id]), 'quiz-dot--current': i === index }"
        :aria-label="$t('Question {number}', { number: i + 1 })"
        :aria-current="i === index ? 'step' : undefined"
        :disabled="submitting"
        @click="go(i)"
      >
        {{ i + 1 }}
      </button>
    </nav>

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
        {{ $t("You answered {answered} of {total} questions.", { answered: answeredCount, total: questions.length }) }}
      </p>
      <p v-if="unanswered" class="text-body-2 text-warning mb-2">
        {{ $t("Questions without an answer count as wrong.") }}
      </p>
      <p class="text-body-2 text-medium-emphasis mb-0">{{ $t("You cannot change your answers after you submit.") }}</p>
    </AppDialog>
  </section>
</template>

<style lang="scss" scoped>
$muted: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
$line: rgba(var(--v-border-color), var(--v-border-opacity));

.quiz-player {
  max-width: 680px;
  margin-inline: auto;
}

.quiz-player__count {
  margin: 0;
  font-size: 0.875rem;
  color: $muted;
}

.quiz-player__question {
  min-height: 220px;
  padding-block: 2rem 1.5rem;
}

.quiz-player__footer {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  padding-block-start: 1.25rem;
  border-block-start: 1px solid $line;

  .v-btn {
    flex: 1 1 0;
    max-width: 12rem;
  }
}

.quiz-player__dots {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.5rem;
  margin-block-start: 2rem;
}

.quiz-dot {
  width: 36px;
  height: 36px;
  border: 1.5px solid $line;
  border-radius: 50%;
  background: transparent;
  color: $muted;
  font: inherit;
  font-size: 0.8125rem;
  cursor: pointer;

  &--answered {
    border-color: transparent;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
  }

  &--current {
    border-color: rgb(var(--v-theme-primary));
    font-weight: 600;
    color: rgb(var(--v-theme-primary));
  }

  &:focus-visible {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
  }
}
</style>
