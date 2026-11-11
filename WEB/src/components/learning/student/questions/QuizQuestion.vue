<script setup>
import ChoiceQuestion from "@/components/learning/student/questions/ChoiceQuestion.vue";
import WordOrderQuestion from "@/components/learning/student/questions/WordOrderQuestion.vue";

/**
 * One quiz question to answer. `question` is the API's StudentQuestionResource.
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

// "She ___ a teacher." → ["She ", " a teacher."], to put an input in the gap
const blank = computed(() => {
  const prompt = props.question.prompt ?? "";
  const index = prompt.indexOf("___");
  return index === -1 ? null : [prompt.slice(0, index), prompt.slice(index).replace(/^_+/, "")];
});
</script>

<template>
  <div class="quiz-question">
    <!-- Fill in the blank: the input sits in the sentence -->
    <template v-if="question.type === 'fill_blank'">
      <p class="quiz-question__prompt">
        <template v-if="blank">
          {{ blank[0] }}
          <input
            v-model="answer"
            class="quiz-question__blank"
            :aria-label="$t('Your answer')"
            :disabled="disabled"
            autocomplete="off"
            autocapitalize="off"
            spellcheck="false"
          />
          {{ blank[1] }}
        </template>
        <template v-else>{{ question.prompt }}</template>
      </p>
      <AppTextField
        v-if="!blank"
        v-model="answer"
        :placeholder="$t('Your answer')"
        :disabled="disabled"
        autocomplete="off"
      />
    </template>

    <template v-else>
      <p class="quiz-question__prompt">
        {{ question.type === "find_error" ? $t("Find the mistake in this sentence:") : question.prompt }}
      </p>
      <ChoiceQuestion
        v-if="question.options"
        v-model="answer"
        :question="question"
        :disabled="disabled"
        :inline="question.type === 'find_error' || question.type === 'true_false'"
      />
      <WordOrderQuestion v-else-if="question.words" v-model="answer" :question="question" :disabled="disabled" />
    </template>
  </div>
</template>

<style lang="scss" scoped>
.quiz-question__prompt {
  margin: 0 0 1rem;
  font-size: 1.125rem;
  font-weight: 500;
  line-height: 1.7;
}

.quiz-question__blank {
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
</style>
