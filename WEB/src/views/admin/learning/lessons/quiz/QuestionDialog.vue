<script setup>
import { useI18n } from "vue-i18n";
import AppDialog from "@/components/AppDialog.vue";
import { requiredValidator } from "@/@core/utils/validators";
import { QUESTION_TYPES, findOption } from "@/constants/learning";
import AcceptedAnswers from "@/views/admin/learning/lessons/quiz/answers/AcceptedAnswers.vue";
import ChoiceAnswers from "@/views/admin/learning/lessons/quiz/answers/ChoiceAnswers.vue";

// Add / edit one question with its answers. The quiz section saves it (emits "save").
const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
  question: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["update:isDialogVisible", "save"]);
const { t } = useI18n();

const emptyForm = (type = "multiple_choice") => ({
  type,
  prompt: "",
  explanation: "",
  explanation_km: "",
  points: 1,
  options: findOption(QUESTION_TYPES, type).newOptions(),
});

const form = ref(emptyForm());
const answerError = ref("");
const isEdit = computed(() => !!form.value.id);
const type = computed(() => findOption(QUESTION_TYPES, form.value.type));

watch(
  () => [props.isDialogVisible, props.question],
  () => {
    answerError.value = "";
    form.value = props.question?.id
      ? { ...emptyForm(props.question.type), ...structuredClone(toRaw(props.question)) }
      : emptyForm(form.value.type);
  },
  { immediate: true },
);

// A new type needs new answers
const changeType = (value) => {
  if (!value || value === form.value.type) return;
  form.value = { ...form.value, type: value, options: findOption(QUESTION_TYPES, value).newOptions() };
  answerError.value = "";
};

// "Find the error": one part per word of the sentence
const splitPrompt = () => {
  const words = form.value.prompt.trim().split(/\s+/).filter(Boolean);
  form.value.options = words.map((text) => ({ text, is_correct: false }));
};

const checkAnswers = () => {
  const { options } = form.value;
  const [min] = type.value.limits;
  if (options.length < min) return t("Add at least {min} answers.", { min });
  if (type.value.answer === "choice" && options.filter((o) => o.is_correct).length !== 1)
    return t("Mark exactly one correct answer.");
  return "";
};

const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  answerError.value = checkAnswers();
  if (valid && !answerError.value) emit("save", { ...form.value, points: Number(form.value.points) || 1 });
};
</script>

<template>
  <AppDialog
    :title="isEdit ? 'Edit Question' : 'New Question'"
    title-icon="tabler-help-circle"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="(v) => emit('update:isDialogVisible', v)"
    :width="720"
    is-footer
    is-submit
    :submit-text="isEdit ? 'Save' : 'Add'"
    submit-icon="tabler-device-floppy"
    submit-color="primary"
    :loading="loading"
    @on-submit="onSubmit"
  >
    <div class="question-form">
      <!-- Type -->
      <div>
        <div class="text-body-2 mb-2">{{ t("Question type") }}</div>
        <VChipGroup :model-value="form.type" mandatory selected-class="text-primary" column @update:model-value="changeType">
          <VChip v-for="item in QUESTION_TYPES" :key="item.value" :value="item.value" :prepend-icon="item.icon" variant="outlined" filter>
            {{ t(item.title) }}
          </VChip>
        </VChipGroup>
        <p class="text-body-2 text-medium-emphasis mt-2 mb-0">{{ t(type.help) }}</p>
      </div>

      <!-- Question -->
      <AppTextarea v-model="form.prompt" rows="2" auto-grow :placeholder="type.promptHint" :rules="[requiredValidator]">
        <template #label>{{ t("Question") }} <span class="text-error">*</span></template>
      </AppTextarea>

      <!-- Answers -->
      <div>
        <div class="d-flex align-center justify-space-between mb-2">
          <span class="text-body-2">
            {{ type.answer === "typed" ? t("Accepted answers") : t("Answers — mark the correct one") }}
          </span>
          <VBtn
            v-if="form.type === 'find_error'"
            variant="text"
            size="small"
            prepend-icon="tabler-cut"
            :disabled="!form.prompt.trim()"
            @click="splitPrompt"
          >
            {{ t("Make parts from the sentence") }}
          </VBtn>
        </div>

        <AcceptedAnswers v-if="type.answer === 'typed'" v-model="form.options" :limits="type.limits" />
        <ChoiceAnswers
          v-else
          v-model="form.options"
          :limits="type.limits"
          :fixed="type.fixed"
          :placeholder="form.type === 'find_error' ? 'Part of the sentence' : 'Answer'"
        />
        <p v-if="answerError" class="text-error text-body-2 mt-1 mb-0">{{ answerError }}</p>
      </div>

      <!-- Explanation -->
      <VRow>
        <VCol cols="12" md="6">
          <AppTextarea v-model="form.explanation" rows="2" auto-grow :placeholder="t('Why is this the answer?')">
            <template #label>{{ t("Explanation (English)") }}</template>
          </AppTextarea>
        </VCol>
        <VCol cols="12" md="6">
          <AppTextarea v-model="form.explanation_km" rows="2" auto-grow>
            <template #label>{{ t("Explanation (Khmer)") }}</template>
          </AppTextarea>
        </VCol>
        <VCol cols="6" sm="3">
          <AppTextField v-model="form.points" type="number" min="1" max="100">
            <template #label>{{ t("Points") }}</template>
          </AppTextField>
        </VCol>
      </VRow>
    </div>
  </AppDialog>
</template>

<style scoped>
.question-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
</style>
