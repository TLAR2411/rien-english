<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import { useCrud } from "@/composables/useCrud";
import hasPermission from "@/utils/hasPermission";
import { QUESTION_TYPES, RECOMMENDED_QUESTIONS, findOption } from "@/constants/learning";
import QuestionDialog from "@/views/admin/learning/lessons/quiz/QuestionDialog.vue";

// The quiz of one lesson: its settings (AppCard) and its questions (AppCardTable).
// `isBack`: show a Back button (when it is a page of its own).
// Slot `actions`: extra buttons in the settings header.
const props = defineProps({
  lesson: { type: Object, required: true },
  isBack: { type: Boolean, default: false },
});

const { t } = useI18n();
const canEdit = hasPermission("edit-quizzes");

const quiz = ref(null);
const settings = ref({});
const isLoading = ref(false);
const isSavingSettings = ref(false);
const quizUrl = computed(() => `admin/lessons/${props.lesson.id}/quiz`);

const fillSettings = (data) => {
  settings.value = {
    title: data?.title ?? `${props.lesson.title_en} — Quiz`,
    pass_score: data?.pass_score ?? 70,
    time_limit_minutes: data?.time_limit_minutes ?? null,
  };
};

const load = async () => {
  isLoading.value = true;
  try {
    const res = await api.get(quizUrl.value);
    quiz.value = res.data.data ?? null;
    fillSettings(quiz.value);
  } finally {
    isLoading.value = false;
  }
};
watch(() => props.lesson.id, load, { immediate: true });

// Creates the quiz the first time, then updates its settings
const onSaveSettings = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (!valid) return;
  isSavingSettings.value = true;
  try {
    const res = await api.put(quizUrl.value, {
      ...settings.value,
      time_limit_minutes: settings.value.time_limit_minutes || null,
    });
    quiz.value = res.data.data;
  } catch {
    // shown by utils/api.js
  } finally {
    isSavingSettings.value = false;
  }
};

// ---- Questions ----
const tableRef = ref(null);
const isTableLoading = ref(false);
const isDialogVisible = ref(false);
const editing = ref({});
const totals = ref({ count: 0 });

const { isSaving, save, remove, move } = useCrud("admin/questions", {
  onDone: () => tableRef.value?.reload(),
  createUrl: () => `admin/quizzes/${quiz.value.id}/questions`,
});

const headers = [
  { title: "#", key: "number", visible: true, width: 48 },
  { title: t("Question"), key: "prompt", visible: true },
  { title: t("Question type"), key: "type", visible: true },
  { title: t("Correct answer"), key: "answer", visible: true },
  { title: t("Points"), key: "points", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
].filter((header) => canEdit || header.key !== "actions");

watch(isDialogVisible, (open) => {
  if (!open) editing.value = {};
});

const onEdit = (question) => {
  editing.value = question;
  isDialogVisible.value = true;
};
const onSave = async (form) => {
  if (await save(form)) isDialogVisible.value = false;
};

const typeOf = (question) => findOption(QUESTION_TYPES, question.type);

// Short "right answer" text for the table
const answerSummary = (question) => {
  const correct = (question.options ?? []).filter((option) => option.is_correct).map((option) => option.text);
  return typeOf(question)?.answer === "typed" ? correct.join(" / ") : correct[0];
};

const countHint = computed(() =>
  t("{count} of {min}–{max} recommended", { count: totals.value.count, ...RECOMMENDED_QUESTIONS }),
);
</script>

<template>
  <div class="d-flex flex-column">
    <!-- Settings -->
    <AppCard
      :title="quiz ? 'Quiz settings' : 'This lesson has no quiz yet'"
      border=""
      title-icon="tabler-settings"
      :is-back="isBack"
      :is-submit="canEdit"
      :submit-text="quiz ? 'Save settings' : 'Create quiz'"
      :submit-icon="quiz ? 'tabler-device-floppy' : 'tabler-plus'"
      :loading="isLoading || isSavingSettings"
      :disabled="!canEdit"
      @on-submit="onSaveSettings"
    >
      <template #card-header>
        <slot name="actions" :quiz="quiz" />
      </template>
      <VRow>
        <VCol cols="12" md="6">
          <AppTextField v-model="settings.title" label="Quiz title" :rules="[(v) => !!v || t('Required')]" />
        </VCol>
        <VCol cols="6" md="3">
          <AppTextField v-model="settings.pass_score" label="Pass score" type="number" min="1" max="100" suffix="%" />
        </VCol>
        <VCol cols="6" md="3">
          <AppTextField
            v-model="settings.time_limit_minutes"
            label="Time limit"
            type="number"
            min="1"
            :suffix="t('min')"
            :placeholder="t('No limit')"
          />
        </VCol>
      </VRow>
    </AppCard>

    <!-- Questions -->
    <template v-if="quiz">
      <VDivider />
      <QuestionDialog
        v-model:is-dialog-visible="isDialogVisible"
        :question="editing"
        :loading="isSaving"
        @save="onSave"
      />

      <AppCardTable
        ref="tableRef"
        v-model:is-dialog-create-visible="isDialogVisible"
        v-model:loading="isTableLoading"
        title="Questions"
        border=""
        title-icon="tabler-checklist"
        :api-url="`admin/quizzes/${quiz.id}/questions`"
        :headers="headers"
        :limit="50"
        :is-full-height="false"
        save-header-name="header-quiz-questions"
        enable-mobile-card
        :create-dialog="canEdit"
        :is-edit="canEdit"
        :is-move-up="canEdit"
        :is-move-down="canEdit"
        :is-delete="canEdit"
        :is-back="false"
        @update:response="(res) => (totals.count = res?.data?.meta?.total ?? 0)"
        @on-edit="onEdit"
        @on-move-up="(item) => move(item, 'up')"
        @on-move-down="(item) => move(item, 'down')"
        @on-delete="remove"
      >
        <template #card-header>
          <span
            class="text-body-2 text-no-wrap me-3"
            :class="totals.count >= RECOMMENDED_QUESTIONS.min ? 'text-success' : 'text-medium-emphasis'"
          >
            {{ countHint }}
          </span>
        </template>

        <template #item.number="{ item }">{{ item.sort_order }}</template>
        <template #item.prompt="{ item }"><span class="text-wrap">{{ item.prompt }}</span></template>
        <template #item.type="{ item }">
          <VChip size="small" label :prepend-icon="typeOf(item)?.icon">{{ t(typeOf(item)?.title ?? item.type) }}</VChip>
        </template>
        <template #item.answer="{ item }">
          <span class="text-success">{{ answerSummary(item) }}</span>
        </template>
      </AppCardTable>
    </template>
  </div>
</template>
