<script setup>
import ChoiceQuestion from "@/components/learning/student/questions/ChoiceQuestion.vue";
import WordOrderQuestion from "@/components/learning/student/questions/WordOrderQuestion.vue";

/**
 * A quiz as students take it. `quiz` is the API's StudentQuizResource
 * (no correct answers in it — grading happens on the server).
 *
 * Answers are kept by question id, in the shape the API grades:
 *   multiple_choice, true_false, find_error → option id
 *   fill_blank → typed text
 *   word_order → list of words
 *
 * Emits "submit" with { [questionId]: answer }. `preview` = admin preview: answering
 * works, but the button only says that answers are not checked.
 */
const props = defineProps({
  quiz: { type: Object, required: true },
  preview: { type: Boolean, default: false },
  submitting: { type: Boolean, default: false },
});

const emit = defineEmits(["submit"]);

const answers = ref({});
const answeredCount = computed(
  () => props.quiz.questions.filter((question) => isAnswered(answers.value[question.id])).length,
);
const isAnswered = (answer) => (Array.isArray(answer) ? answer.length > 0 : answer != null && String(answer).trim() !== "");

const reset = () => (answers.value = {});

// "She ___ a teacher." → ["She ", " a teacher."], to put an input in the gap
const blankParts = (prompt) => {
  const index = prompt.indexOf("___");
  return index === -1 ? null : [prompt.slice(0, index), prompt.slice(index).replace(/^_+/, "")];
};
</script>

<template>
  <section class="quiz-view">
    <header class="mb-8">
      <h2 class="quiz-view__title">{{ quiz.title }}</h2>
      <p class="quiz-view__meta">
        {{ $t("{count} questions", { count: quiz.questions.length }) }} ·
        {{ $t("Pass with {score}%", { score: quiz.pass_score }) }}
        <template v-if="quiz.time_limit_minutes"> · {{ $t("{minutes} minutes", { minutes: quiz.time_limit_minutes }) }}</template>
      </p>
    </header>

    <ol class="quiz-view__questions">
      <li v-for="(question, index) in quiz.questions" :key="question.id" class="question">
        <p class="question__number">{{ $t("Question {number}", { number: index + 1 }) }}</p>

        <!-- Fill in the blank: the input sits in the sentence -->
        <template v-if="question.type === 'fill_blank'">
          <p class="question__prompt">
            <template v-if="blankParts(question.prompt)">
              {{ blankParts(question.prompt)[0] }}
              <input
                v-model="answers[question.id]"
                class="question__blank"
                :aria-label="$t('Your answer')"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
              />
              {{ blankParts(question.prompt)[1] }}
            </template>
            <template v-else>{{ question.prompt }}</template>
          </p>
          <AppTextField
            v-if="!blankParts(question.prompt)"
            v-model="answers[question.id]"
            :placeholder="$t('Your answer')"
            autocomplete="off"
          />
        </template>

        <template v-else>
          <p class="question__prompt">
            {{ question.type === "find_error" ? $t("Find the mistake in this sentence:") : question.prompt }}
          </p>
          <ChoiceQuestion
            v-if="question.options"
            v-model="answers[question.id]"
            :question="question"
            :inline="question.type === 'find_error' || question.type === 'true_false'"
          />
          <WordOrderQuestion v-else-if="question.words" v-model="answers[question.id]" :question="question" />
        </template>
      </li>
    </ol>

    <footer class="quiz-view__footer">
      <p class="quiz-view__progress">
        {{ $t("{answered} of {total} answered", { answered: answeredCount, total: quiz.questions.length }) }}
      </p>
      <p v-if="preview" class="text-body-2 text-medium-emphasis mb-3">
        {{ $t("Preview: answers are not checked or saved.") }}
      </p>
      <div class="d-flex flex-wrap align-center gap-4">
        <VBtn
          color="primary"
          size="large"
          :disabled="preview || !answeredCount"
          :loading="submitting"
          @click="emit('submit', { ...answers })"
        >
          {{ $t("Check my answers") }}
        </VBtn>
        <VBtn v-if="answeredCount" variant="text" @click="reset">{{ $t("Start again") }}</VBtn>
      </div>
    </footer>
  </section>
</template>

<style lang="scss" scoped>
.quiz-view {
  max-width: 760px;
  margin-inline: auto;
}

.quiz-view__title {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.3;
}

.quiz-view__meta,
.quiz-view__progress,
.question__number {
  margin: 0.25rem 0 0;
  font-size: 0.9375rem;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.quiz-view__questions {
  margin: 0;
  padding: 0;
  list-style: none;
}

.question {
  padding-block: 1.5rem;
  border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.question__number {
  margin: 0 0 0.25rem;
  font-size: 0.8125rem;
  font-weight: 500;
}

.question__prompt {
  margin: 0 0 1rem;
  font-size: 1.125rem;
  font-weight: 500;
  line-height: 1.7;
}

.question__blank {
  width: 9rem;
  max-width: 100%;
  min-height: 40px;
  padding: 0.25rem 0.5rem;
  border: 0;
  border-block-end: 2px solid rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.05);
  color: inherit;
  font: inherit;
  text-align: center;

  &:focus {
    outline: none;
    background: rgba(var(--v-theme-primary), 0.1);
  }
}

.quiz-view__footer {
  padding-block-start: 1.5rem;
  border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.quiz-view__progress {
  margin: 0 0 1rem;
}
</style>
