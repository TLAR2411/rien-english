<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import { useCrud } from "@/composables/useCrud";
import { useDialog } from "@/composables/useDialog";
import hasPermission from "@/utils/hasPermission";
import { QUESTION_TYPES, RECOMMENDED_QUESTIONS, findOption } from "@/constants/learning";
import QuestionDialog from "@/views/admin/learning/lessons/quiz/QuestionDialog.vue";

// The quiz of one lesson: its settings and its questions.
const props = defineProps({
  lesson: { type: Object, required: true },
});

const { t } = useI18n();
const { showDialog } = useDialog();

const quiz = ref(null);
const settings = ref({});
const isLoading = ref(false);
const isSavingSettings = ref(false);
const isDialogVisible = ref(false);
const editing = ref({});
const canEdit = hasPermission("edit-quizzes");

const quizUrl = computed(() => `admin/lessons/${props.lesson.id}/quiz`);
const questions = computed(() => quiz.value?.questions ?? []);

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
const saveSettings = async () => {
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
const { isSaving, save, remove, move } = useCrud("admin/questions", {
  onDone: load,
  createUrl: () => `admin/quizzes/${quiz.value.id}/questions`,
});

const openNew = () => {
  editing.value = {};
  isDialogVisible.value = true;
};
const openEdit = (question) => {
  editing.value = question;
  isDialogVisible.value = true;
};
const onSave = async (form) => {
  if (await save(form)) isDialogVisible.value = false;
};
const onDelete = async (question) => {
  const ok = await showDialog({ title: t("Delete Item?"), icon: "delete", confirmColor: "error", confirmText: "Delete" });
  if (ok) remove(question);
};

const typeOf = (question) => findOption(QUESTION_TYPES, question.type);

// Short "right answer" line under each question
const answerSummary = (question) => {
  const correct = question.options.filter((option) => option.is_correct).map((option) => option.text);
  return typeOf(question)?.answer === "typed" ? correct.join(" / ") : correct[0];
};

const countHint = computed(() =>
  t("{count} of {min}–{max} recommended", { count: questions.value.length, ...RECOMMENDED_QUESTIONS }),
);
const totalPoints = computed(() => questions.value.reduce((sum, question) => sum + question.points, 0));
</script>

<template>
  <div>
    <!-- Settings -->
    <VCard class="mb-6" :loading="isLoading">
      <VCardItem :title="quiz ? t('Quiz settings') : t('This lesson has no quiz yet')" />
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <AppTextField v-model="settings.title" :readonly="!canEdit">
              <template #label>{{ t("Quiz title") }}</template>
            </AppTextField>
          </VCol>
          <VCol cols="6" md="3">
            <AppTextField v-model="settings.pass_score" type="number" min="1" max="100" suffix="%" :readonly="!canEdit">
              <template #label>{{ t("Pass score") }}</template>
            </AppTextField>
          </VCol>
          <VCol cols="6" md="3">
            <AppTextField
              v-model="settings.time_limit_minutes"
              type="number"
              min="1"
              :suffix="t('min')"
              :placeholder="t('No limit')"
              :readonly="!canEdit"
            >
              <template #label>{{ t("Time limit") }}</template>
            </AppTextField>
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions v-if="canEdit" class="px-6 pb-4 pt-0">
        <VBtn
          :variant="quiz ? 'tonal' : 'flat'"
          color="primary"
          :prepend-icon="quiz ? 'tabler-device-floppy' : 'tabler-plus'"
          :loading="isSavingSettings"
          @click="saveSettings"
        >
          {{ quiz ? t("Save settings") : t("Create quiz") }}
        </VBtn>
      </VCardActions>
    </VCard>

    <!-- Questions -->
    <VCard v-if="quiz">
      <VCardItem :title="t('Questions')">
        <VCardSubtitle>
          <span :class="{ 'text-success': questions.length >= RECOMMENDED_QUESTIONS.min }">{{ countHint }}</span>
          · {{ t("{points} points", { points: totalPoints }) }}
        </VCardSubtitle>
        <template #append>
          <VBtn v-if="canEdit" variant="tonal" prepend-icon="tabler-plus" @click="openNew">{{ t("Add question") }}</VBtn>
        </template>
      </VCardItem>

      <VList v-if="questions.length" class="py-0">
        <template v-for="(question, index) in questions" :key="question.id">
          <VDivider />
          <VListItem class="py-3">
            <template #prepend>
              <span class="question-number">{{ index + 1 }}</span>
            </template>

            <div class="d-flex align-center flex-wrap gap-2 mb-1">
              <VChip size="x-small" label :prepend-icon="typeOf(question)?.icon">{{ t(typeOf(question)?.title ?? question.type) }}</VChip>
              <span class="text-caption text-medium-emphasis">{{ t("{points} points", { points: question.points }) }}</span>
            </div>
            <VListItemTitle class="text-wrap font-weight-medium">{{ question.prompt }}</VListItemTitle>
            <VListItemSubtitle class="text-wrap">
              <VIcon icon="tabler-check" size="14" color="success" /> {{ answerSummary(question) }}
            </VListItemSubtitle>

            <template v-if="canEdit" #append>
              <div class="d-flex align-center">
                <IconBtn size="small" :disabled="index === 0" :title="t('Move up')" @click="move(question, 'up')">
                  <VIcon icon="tabler-arrow-up" />
                </IconBtn>
                <IconBtn size="small" :disabled="index === questions.length - 1" :title="t('Move down')" @click="move(question, 'down')">
                  <VIcon icon="tabler-arrow-down" />
                </IconBtn>
                <IconBtn size="small" :title="t('Edit')" @click="openEdit(question)">
                  <VIcon icon="tabler-pencil" />
                </IconBtn>
                <IconBtn size="small" color="error" :title="t('Delete')" @click="onDelete(question)">
                  <VIcon icon="tabler-trash" />
                </IconBtn>
              </div>
            </template>
          </VListItem>
        </template>
      </VList>
      <VCardText v-else class="text-medium-emphasis">{{ t("No questions yet. Add the first one.") }}</VCardText>
    </VCard>

    <QuestionDialog
      v-model:is-dialog-visible="isDialogVisible"
      :question="editing"
      :loading="isSaving"
      @save="onSave"
    />
  </div>
</template>

<style scoped>
.question-number {
  display: inline-grid;
  place-items: center;
  width: 28px;
  height: 28px;
  margin-inline-end: 12px;
  border-radius: 50%;
  font-size: 13px;
  font-weight: 600;
  background: rgba(var(--v-theme-primary), 0.1);
  color: rgb(var(--v-theme-primary));
}
</style>
