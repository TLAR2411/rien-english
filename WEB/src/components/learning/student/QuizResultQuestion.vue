<script setup>
/**
 * One question on the result page: ✓ / ✗, the question, the student's answer,
 * the correct answer and the explanation. `question` = one item of the API's
 * QuizResultResource.questions. `language` ("en" / "km") picks the explanation.
 */
const props = defineProps({
  question: { type: Object, required: true },
  number: { type: Number, required: true },
  language: { type: String, default: "en" },
});

const optionText = (id) => props.question.options?.find((option) => option.id === id)?.text ?? null;

// Answers as text, whatever the question type
const yourAnswer = computed(() => {
  const answer = props.question.your_answer;
  if (answer == null) return null;
  if (props.question.options) return optionText(answer);
  return Array.isArray(answer) ? answer.join(" ") : answer;
});

const correctAnswer = computed(() =>
  props.question.options ? optionText(props.question.correct_option_id) : props.question.correct_answers.join("  /  "),
);

const explanation = computed(() =>
  props.language === "km" && props.question.explanation_km ? props.question.explanation_km : props.question.explanation,
);

// Find the error: show the whole sentence (its parts are the options)
const prompt = computed(() =>
  props.question.type === "find_error"
    ? props.question.options.map((option) => option.text).join(" ")
    : props.question.prompt,
);
</script>

<template>
  <li class="result-question" :class="question.is_correct ? 'result-question--right' : 'result-question--wrong'">
    <div class="d-flex align-start gap-3">
      <VIcon
        :icon="question.is_correct ? 'tabler-circle-check-filled' : 'tabler-circle-x-filled'"
        :color="question.is_correct ? 'success' : 'error'"
        size="24"
        class="flex-shrink-0 mt-1"
      />
      <div class="flex-grow-1 min-w-0">
        <p class="result-question__number">{{ $t("Question {number}", { number }) }}</p>
        <p class="result-question__prompt">
          <template v-if="question.type === 'find_error'">{{ $t("Find the mistake in this sentence:") }} </template>
          {{ prompt }}
        </p>

        <dl class="result-question__answers">
          <div>
            <dt>{{ $t("Your answer") }}</dt>
            <dd :class="question.is_correct ? 'text-success' : 'text-error'">
              {{ yourAnswer ?? $t("No answer") }}
            </dd>
          </div>
          <div v-if="!question.is_correct">
            <dt>{{ $t("Correct answer") }}</dt>
            <dd class="text-success">{{ correctAnswer }}</dd>
          </div>
        </dl>

        <p v-if="explanation" class="result-question__explanation">{{ explanation }}</p>
      </div>
    </div>
  </li>
</template>

<style lang="scss" scoped>
$muted: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));

.result-question {
  padding-block: 1.25rem;

  & + & {
    border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }
}

.result-question__number {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 500;
  color: $muted;
}

.result-question__prompt {
  margin: 0.125rem 0 0.75rem;
  font-size: 1.0625rem;
  font-weight: 500;
  line-height: 1.6;
}

.result-question__answers {
  display: grid;
  gap: 0.375rem;
  margin: 0;

  div {
    display: flex;
    flex-wrap: wrap;
    gap: 0 0.5rem;
  }

  dt {
    min-width: 8rem;
    color: $muted;
  }

  dd {
    margin: 0;
    font-weight: 500;
  }
}

.result-question__explanation {
  margin: 0.75rem 0 0;
  padding: 0.625rem 0.875rem;
  border-radius: 8px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  line-height: 1.7;
}
</style>
