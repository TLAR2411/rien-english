<script setup>
import { useI18n } from "vue-i18n";
import { useCrud } from "@/composables/useCrud";
import { useFitToScreen } from "@/composables/useFitToScreen";
import { RECOMMENDED_EXAMPLES } from "@/constants/learning";
import ExampleDialog from "@/views/admin/learning/lessons/examples/ExampleDialog.vue";

// Example sentences of one lesson: add, edit, reorder, delete.
const props = defineProps({
  lessonId: { type: [Number, String], required: true },
});

const { t } = useI18n();
const tableRef = ref(null);
const isLoading = ref(false);
const isDialogVisible = ref(false);
const editing = ref({});
const total = ref(0);

// New examples are created under the lesson; changes go to /examples/{id}
const { isSaving, save, remove, move } = useCrud("admin/examples", {
  onDone: () => tableRef.value?.reload(),
  createUrl: () => `admin/lessons/${props.lessonId}/examples`,
});

const headers = [
  { title: t("Sentence"), key: "sentence", visible: true },
  { title: t("Khmer translation"), key: "translation_km", visible: true },
  { title: t("Audio"), key: "audio_url", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

watch(isDialogVisible, (open) => {
  if (!open) editing.value = {};
});

const onEdit = (example) => {
  editing.value = example;
  isDialogVisible.value = true;
};

const onSave = async (form) => {
  if (await save(form)) isDialogVisible.value = false;
};

// Big screens: the tabs stay still, only the examples table scrolls
const { box: tableBox, isFit, height: tableHeight } = useFitToScreen();

const countHint = computed(() =>
  t("{count} of {min}–{max} recommended", {
    count: total.value,
    ...RECOMMENDED_EXAMPLES,
  }),
);
</script>

<template>
  <ExampleDialog
    v-model:is-dialog-visible="isDialogVisible"
    :example="editing"
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
      title="Examples"
      border=""
      title-icon="tabler-message-2"
      :api-url="`admin/lessons/${lessonId}/examples`"
      :headers="headers"
      :is-full-height="isFit"
      :is-full-height-tab="isFit"
      save-header-name="header-lesson-examples"
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
          :class="
            total >= RECOMMENDED_EXAMPLES.min
              ? 'text-success'
              : 'text-medium-emphasis'
          "
        >
          {{ countHint }}
        </span>
      </template>

      <template #item.audio_url="{ item }">
        <audio
          v-if="item.audio_url"
          :src="item.audio_url"
          controls
          preload="none"
          class="example-audio"
        />
        <span v-else class="text-disabled">—</span>
      </template>
    </AppCardTable>
  </div>
</template>

<style scoped>
.example-audio {
  height: 32px;
  max-width: 220px;
}
</style>
