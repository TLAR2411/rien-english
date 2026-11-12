<script setup>
import { useI18n } from "vue-i18n";
import { useCrud } from "@/composables/useCrud";
import { useFitToScreen } from "@/composables/useFitToScreen";
import { RECOMMENDED_WORDS } from "@/constants/learning";
import WordDialog from "@/views/admin/learning/lessons/words/WordDialog.vue";

// New words of one lesson ("Words to know"): add, edit, reorder, delete.
const props = defineProps({
  lessonId: { type: [Number, String], required: true },
});

const { t } = useI18n();
const tableRef = ref(null);
const isLoading = ref(false);
const isDialogVisible = ref(false);
const editing = ref({});
const total = ref(0);

// New words are created under the lesson; changes go to /words/{id}
const { isSaving, save, remove, move } = useCrud("admin/words", {
  onDone: () => tableRef.value?.reload(),
  createUrl: () => `admin/lessons/${props.lessonId}/words`,
});

const headers = [
  { title: t("Word"), key: "word", visible: true },
  { title: t("Khmer meaning"), key: "meaning_km", visible: true },
  { title: t("Meaning in simple English"), key: "meaning_en", visible: true },
  { title: t("Example sentence"), key: "example", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

watch(isDialogVisible, (open) => {
  if (!open) editing.value = {};
});

const onEdit = (word) => {
  editing.value = word;
  isDialogVisible.value = true;
};

const onSave = async (form) => {
  if (await save(form)) isDialogVisible.value = false;
};

// Big screens: the tabs stay still, only the words table scrolls
const { box: tableBox, isFit, height: tableHeight } = useFitToScreen();

const countHint = computed(() =>
  t("{count} of {min}–{max} recommended", {
    count: total.value,
    ...RECOMMENDED_WORDS,
  }),
);
</script>

<template>
  <WordDialog
    v-model:is-dialog-visible="isDialogVisible"
    :word="editing"
    :loading="isSaving"
    @save="onSave"
  />

  <div
    ref="tableBox"
    :class="{ 'fit-table': isFit }"
    :style="{ height: isFit ? tableHeight : null }"
  >
    <AppCardTable
      ref="tableRef"
      v-model:is-dialog-create-visible="isDialogVisible"
      v-model:loading="isLoading"
      title="Words to know"
      border=""
      title-icon="tabler-vocabulary"
      :api-url="`admin/lessons/${lessonId}/words`"
      :headers="headers"
      :is-full-height="isFit"
      :is-full-height-tab="isFit"
      save-header-name="header-lesson-words"
      enable-mobile-card
      create-dialog
      can-create="edit-lessons"
      is-edit
      can-edit="edit-lessons"
      is-move-up
      is-move-down
      is-delete
      can-delete="edit-lessons"
      :is-back="false"
      @update:response="(res) => (total = res?.data?.meta?.total ?? 0)"
      @on-edit="onEdit"
      @on-move-up="(item) => move(item, 'up')"
      @on-move-down="(item) => move(item, 'down')"
      @on-delete="remove"
    >
      <template #card-header>
        <span
          class="text-body-2 text-no-wrap me-3"
          :class="total >= RECOMMENDED_WORDS.min ? 'text-success' : 'text-medium-emphasis'"
        >
          {{ countHint }}
        </span>
      </template>

      <template #item.word="{ item }">
        <span class="font-weight-medium">{{ item.word }}</span>
      </template>
    </AppCardTable>
  </div>
</template>
