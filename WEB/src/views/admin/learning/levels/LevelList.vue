<script setup>
import { useI18n } from "vue-i18n";
import { useCrud } from "@/composables/useCrud";
import LevelDialog from "@/views/admin/learning/levels/LevelDialog.vue";

const { t } = useI18n();
const tableRef = ref(null);
const isLoading = ref(false);
const isDialogVisible = ref(false);
const editing = ref({});
const filter = ref({ search: null });

const { isSaving, save, remove, move } = useCrud("admin/levels", {
  onDone: () => tableRef.value?.reload(),
});

const headers = [
  { title: t("Code"), key: "code", visible: true },
  { title: t("Name English"), key: "name", visible: true },
  { title: t("Name Khmer"), key: "name_km", visible: true },
  { title: t("Courses"), key: "courses_count", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

// "New" opens an empty form
watch(isDialogVisible, (open) => {
  if (!open) editing.value = {};
});

const onEdit = (level) => {
  editing.value = level;
  isDialogVisible.value = true;
};

const onSave = async (form) => {
  if (await save(form)) isDialogVisible.value = false;
};
</script>

<template>
  <LevelDialog
    v-model:is-dialog-visible="isDialogVisible"
    :level="editing"
    :loading="isSaving"
    @save="onSave"
  />

  <AppCardTable
    ref="tableRef"
    v-model:is-dialog-create-visible="isDialogVisible"
    v-model:loading="isLoading"
    v-model:filters="filter"
    title="Levels"
    title-icon="tabler-stairs"
    api-url="admin/levels"
    :headers="headers"
    save-header-name="header-levels-list"
    enable-mobile-card
    is-filter
    create-dialog
    can-create="add-levels"
    is-edit
    can-edit="edit-levels"
    is-move-up
    is-move-down
    is-delete
    can-delete="delete-levels"
    :is-back="false"
    @on-edit="onEdit"
    @on-move-up="(item) => move(item, 'up')"
    @on-move-down="(item) => move(item, 'down')"
    @on-delete="remove"
  >
    <template #filter>
      <VRow class="justify-end">
        <VCol cols="12" sm="6" md="4" lg="3">
          <VTextField
            v-model="filter.search"
            :label="t('Search')"
            prepend-inner-icon="tabler-search"
            clearable
            hide-details
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>
    </template>
  </AppCardTable>
</template>
