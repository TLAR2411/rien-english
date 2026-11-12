<script setup>
import { useI18n } from "vue-i18n";
import { useCrud } from "@/composables/useCrud";
import { getLevels } from "@/services/dataService";
import { PUBLISH_STATUSES } from "@/constants/learning";
import StatusChip from "@/components/learning/StatusChip.vue";
import CourseDialog from "@/views/admin/learning/courses/CourseDialog.vue";

const { t } = useI18n();
const tableRef = ref(null);
const isLoading = ref(false);
const isDialogVisible = ref(false);
const editing = ref({});
const levels = ref([]);
const filter = ref({ search: null, level_id: null, status: null });

const { isSaving, save, remove, move } = useCrud("admin/courses", {
  onDone: () => tableRef.value?.reload(),
});

onMounted(async () => (levels.value = await getLevels()));

const headers = [
  { title: t("Level"), key: "level", visible: true },
  { title: t("Title English"), key: "title", visible: true },
  { title: t("Title Khmer"), key: "title_km", visible: true },
  { title: t("Lessons"), key: "lessons_count", visible: true },
  { title: t("Status"), key: "status", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

watch(isDialogVisible, (open) => {
  if (!open) editing.value = {};
});

const onEdit = (course) => {
  editing.value = course;
  isDialogVisible.value = true;
};

const onSave = async (form) => {
  if (await save(form)) isDialogVisible.value = false;
};
</script>

<template>
  <CourseDialog
    v-model:is-dialog-visible="isDialogVisible"
    :course="editing"
    :levels="levels"
    :loading="isSaving"
    @save="onSave"
  />

  <AppCardTable
    ref="tableRef"
    v-model:is-dialog-create-visible="isDialogVisible"
    v-model:loading="isLoading"
    v-model:filters="filter"
    title="Courses"
    title-icon="tabler-books"
    api-url="admin/courses"
    :headers="headers"
    save-header-name="header-courses-list"
    enable-mobile-card
    is-filter
    create-dialog
    can-create="add-courses"
    is-edit
    can-edit="edit-courses"
    is-move-up
    is-move-down
    is-delete
    can-delete="delete-courses"
    :is-back="false"
    @on-edit="onEdit"
    @on-move-up="(item) => move(item, 'up')"
    @on-move-down="(item) => move(item, 'down')"
    @on-delete="remove"
  >
    <template #filter>
      <VRow class="justify-end">
        <VCol cols="12" sm="4" lg="2">
          <AppSelect
            v-model="filter.level_id"
            :items="levels"
            item-title="code"
            item-value="id"
            :placeholder="t('Level')"
            clearable
          />
        </VCol>
        <VCol cols="12" sm="4" lg="2">
          <AppSelect
            v-model="filter.status"
            :items="PUBLISH_STATUSES"
            :item-title="(item) => t(item.title)"
            item-value="value"
            :placeholder="t('Status')"
            clearable
          />
        </VCol>
        <VCol cols="12" sm="4" lg="3">
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

    <template #item.level="{ item }">{{ item.level?.code }}</template>
    <template #item.status="{ item }"><StatusChip :status="item.status" /></template>
  </AppCardTable>
</template>
