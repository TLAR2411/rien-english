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

// Icon cards for the type picker (AppTowRadioIcon)
const typeCards = QUESTION_TYPES.map((item) => ({
  value: item.value,
  title: item.title,
  icon: { icon: item.icon, size: 24 },
}));

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
    <VRow>
      <!-- Type -->
      <AppLabel title="Question type" />
      <VCol cols="12">
        <AppTowRadioIcon
          :selected-radio="form.type"
          :radio-content="typeCards"
          :grid-column="{ cols: 6, sm: 4, md: true }"
          @update:selected-radio="changeType"
        />
        <p class="text-body-2 text-medium-emphasis mt-2 mb-0">{{ t(type.help) }}</p>
      </VCol>

      <!-- Question -->
      <AppLabel title="Question" />
      <VCol cols="12">
        <AppTextarea v-model="form.prompt" rows="2" auto-grow :placeholder="type.promptHint" :rules="[requiredValidator]" />
      </VCol>

      <!-- Answers -->
      <AppLabel :title="type.answer === 'typed' ? 'Accepted answers' : 'Answers — mark the correct one'" />
      <VCol cols="12">
        <VBtn
          v-if="form.type === 'find_error'"
          variant="tonal"
          size="small"
          prepend-icon="tabler-cut"
          class="mb-3"
          :disabled="!form.prompt.trim()"
          @click="splitPrompt"
        >
          {{ t("Make parts from the sentence") }}
        </VBtn>
        <AcceptedAnswers v-if="type.answer === 'typed'" v-model="form.options" :limits="type.limits" />
        <ChoiceAnswers
          v-else
          v-model="form.options"
          :limits="type.limits"
          :fixed="type.fixed"
          :placeholder="form.type === 'find_error' ? 'Part of the sentence' : 'Answer'"
        />
        <p v-if="answerError" class="text-error text-body-2 mt-1 mb-0">{{ answerError }}</p>
      </VCol>

      <!-- Explanation -->
      <AppLabel title="Explanation" />
      <VCol cols="12" md="6">
        <AppTextarea
          v-model="form.explanation"
          label="Explanation (English)"
          rows="2"
          auto-grow
          :placeholder="t('Why is this the answer?')"
        />
      </VCol>
      <VCol cols="12" md="6">
        <AppTextarea v-model="form.explanation_km" label="Explanation (Khmer)" rows="2" auto-grow />
      </VCol>
      <VCol cols="6" sm="3">
        <AppTextField v-model="form.points" label="Points" type="number" min="1" max="100" />
      </VCol>
    </VRow>
  </AppDialog>
</template>
