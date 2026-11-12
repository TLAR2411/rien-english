<script setup>
import ChoiceQuestion from "@/components/learning/student/questions/ChoiceQuestion.vue";
import WordOrderQuestion from "@/components/learning/student/questions/WordOrderQuestion.vue";

/**
 * One quiz question to answer: a short instruction, the sentence, then the answer part.
 * `question` is the API's StudentQuestionResource.
 * v-model is the answer in the shape the API grades:
 *   multiple_choice, true_false, find_error → option id
 *   fill_blank → typed text
 *   word_order → list of words
 */
const props = defineProps({
  question: { type: Object, required: true },
  modelValue: { type: [Number, String, Array], default: null },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const answer = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
});

const type = computed(() => props.question.type);

// "She ___ a teacher." → ["She ", " a teacher."], to show the gap in the sentence
const blank = computed(() => {
  const prompt = props.question.prompt ?? "";
  const index = prompt.indexOf("___");
  return index === -1 ? null : [prompt.slice(0, index), prompt.slice(index).replace(/^_+/, "")];
});

// Multiple choice with a gap: the picked word appears in the gap
const pickedText = computed(() => props.question.options?.find((option) => option.id === props.modelValue)?.text);

const instruction = computed(
  () =>
    ({
      multiple_choice: blank.value ? "Choose the correct word" : "Choose the correct answer",
      true_false: "True or false?",
      fill_blank: "Type the missing word",
      word_order: "Put the words in order",
      find_error: "Find the mistake",
    })[type.value],
);

const layout = computed(
  () => ({ true_false: "pair", find_error: "inline" })[type.value] ?? "list",
);
</script>

<template>
  <div class="quiz-question">
    <div class="quiz-question__card">
      <p class="quiz-question__instruction">{{ $t(instruction) }}</p>

      <!-- Fill in the blank: the input sits in the sentence -->
      <p v-if="type === 'fill_blank' && blank" class="quiz-question__prompt">
        {{ blank[0] }}
        <input
          v-model="answer"
          class="quiz-question__gap quiz-question__gap--input"
          :aria-label="$t('Your answer')"
          :disabled="disabled"
          autocomplete="off"
          autocapitalize="off"
          spellcheck="false"
        />
        {{ blank[1] }}
      </p>

      <!-- Multiple choice with a gap: show the picked word in it -->
      <p v-else-if="type === 'multiple_choice' && blank" class="quiz-question__prompt">
        {{ blank[0] }}
        <span class="quiz-question__gap" :class="{ 'quiz-question__gap--filled': pickedText }">{{ pickedText }}</span>
        {{ blank[1] }}
      </p>

      <!-- Find the error: the sentence is made of the options below -->
      <p v-else-if="type !== 'find_error'" class="quiz-question__prompt">{{ question.prompt }}</p>
    </div>

    <AppTextField
      v-if="type === 'fill_blank' && !blank"
      v-model="answer"
      :placeholder="$t('Your answer')"
      :disabled="disabled"
      autocomplete="off"
    />
    <ChoiceQuestion
      v-else-if="question.options"
      v-model="answer"
      :question="question"
      :layout="layout"
      :disabled="disabled"
    />
    <WordOrderQuestion v-else-if="question.words" v-model="answer" :question="question" :disabled="disabled" />
  </div>
</template>

<style lang="scss" scoped>
$primary: rgb(var(--v-theme-primary));

.quiz-question__card {
  margin-block-end: 1.25rem;
}

.quiz-question__instruction {
  margin: 0 0 0.5rem;
  font-size: 0.9375rem;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.quiz-question__prompt {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  line-height: 1.6;
  letter-spacing: -0.01em;
}

// The gap in the sentence: a dashed line, filled with the answer
.quiz-question__gap {
  display: inline-block;
  min-width: 5.5rem;
  padding-inline: 0.5rem;
  border-block-end: 2px dashed rgba(var(--v-theme-primary), 0.45);
  color: $primary;
  line-height: 1.4;
  text-align: center;
  vertical-align: baseline;
}

.quiz-question__gap--filled {
  border-block-end-color: rgba(var(--v-theme-primary), 0.7);
}

.quiz-question__gap--input {
  width: 8rem;
  max-width: 100%;
  border-radius: 6px 6px 0 0;
  border-block-start: 0;
  border-inline: 0;
  background: rgba(var(--v-theme-primary), 0.05);
  font: inherit;

  &:focus {
    outline: none;
    border-block-end-style: solid;
    background: rgba(var(--v-theme-primary), 0.1);
  }
}

// Desktop: the sentence sits in a soft box (like the lesson page)
@media (min-width: 960px) {
  .quiz-question__card {
    padding: 1.75rem 2rem;
    border-radius: 16px;
    background: rgba(var(--v-theme-on-surface), 0.025);
  }
}
</style>
