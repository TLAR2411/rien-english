<script setup>
import { useI18n } from "vue-i18n";
import { useDialog } from "@/composables/useDialog";
import { api } from "@/utils/api";
import { useCrud } from "@/composables/useCrud";
import { RECOMMENDED_EXAMPLES } from "@/constants/learning";
import ExampleDialog from "@/views/admin/learning/lessons/examples/ExampleDialog.vue";

// Example sentences of one lesson: add, edit, reorder, delete.
const props = defineProps({
  lessonId: { type: [Number, String], required: true },
});

const { t } = useI18n();
const { showDialog } = useDialog();
const examples = ref([]);
const isLoading = ref(false);
const isDialogVisible = ref(false);
const editing = ref({});

const load = async () => {
  isLoading.value = true;
  try {
    const res = await api.get(`admin/lessons/${props.lessonId}/examples`);
    examples.value = res.data.data;
  } finally {
    isLoading.value = false;
  }
};
watch(() => props.lessonId, load, { immediate: true });

// New examples are created under the lesson; changes go to /examples/{id}
const { isSaving, save, remove, move } = useCrud("admin/examples", {
  onDone: load,
  createUrl: `admin/lessons/${props.lessonId}/examples`,
});

const openNew = () => {
  editing.value = {};
  isDialogVisible.value = true;
};
const openEdit = (example) => {
  editing.value = example;
  isDialogVisible.value = true;
};

const onSave = async (form) => {
  if (await save(form)) isDialogVisible.value = false;
};

const onDelete = async (example) => {
  const ok = await showDialog({ title: t("Delete Item?"), icon: "delete", confirmColor: "error", confirmText: "Delete" });
  if (ok) remove(example);
};

const countHint = computed(() =>
  t("{count} of {min}–{max} recommended", { count: examples.value.length, ...RECOMMENDED_EXAMPLES }),
);
const hasEnough = computed(() => examples.value.length >= RECOMMENDED_EXAMPLES.min);
</script>

<template>
  <VCard class="mt-6" :loading="isLoading">
    <VCardItem :title="t('Examples')">
      <VCardSubtitle :class="hasEnough ? 'text-success' : ''">{{ countHint }}</VCardSubtitle>
      <template #append>
        <VBtn variant="tonal" prepend-icon="tabler-plus" @click="openNew">{{ t("Add example") }}</VBtn>
      </template>
    </VCardItem>

    <VList v-if="examples.length" lines="two" class="py-0">
      <template v-for="(example, index) in examples" :key="example.id">
        <VDivider v-if="index" />
        <VListItem class="py-3">
          <VListItemTitle class="text-wrap font-weight-medium">{{ example.sentence }}</VListItemTitle>
          <VListItemSubtitle class="text-wrap">{{ example.translation_km }}</VListItemSubtitle>
          <audio v-if="example.audio_url" :src="example.audio_url" controls preload="none" class="example-audio mt-2" />

          <template #append>
            <div class="d-flex align-center">
              <IconBtn size="small" :disabled="index === 0" :title="t('Move up')" @click="move(example, 'up')">
                <VIcon icon="tabler-arrow-up" />
              </IconBtn>
              <IconBtn size="small" :disabled="index === examples.length - 1" :title="t('Move down')" @click="move(example, 'down')">
                <VIcon icon="tabler-arrow-down" />
              </IconBtn>
              <IconBtn size="small" :title="t('Edit')" @click="openEdit(example)">
                <VIcon icon="tabler-pencil" />
              </IconBtn>
              <IconBtn size="small" color="error" :title="t('Delete')" @click="onDelete(example)">
                <VIcon icon="tabler-trash" />
              </IconBtn>
            </div>
          </template>
        </VListItem>
      </template>
    </VList>
    <VCardText v-else-if="!isLoading" class="text-medium-emphasis">
      {{ t("No examples yet. Add the first one.") }}
    </VCardText>

    <ExampleDialog
      v-model:is-dialog-visible="isDialogVisible"
      :example="editing"
      :loading="isSaving"
      @save="onSave"
    />
  </VCard>
</template>

<style scoped>
.example-audio {
  height: 32px;
  max-width: 100%;
}
</style>
