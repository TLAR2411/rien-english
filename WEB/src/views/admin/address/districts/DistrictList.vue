<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import AddEditDistrictDialog from "@/views/admin/address/districts/AddEditDistrictDialog.vue";
import { useAppStore } from "@/stores/appStore.js";

const { t } = useI18n();
const formData = ref({});
const dataTableRef = ref(null);
const isDialogVisible = ref(false);

// Forget the edited item when the dialog closes, so "Create" opens empty
watch(isDialogVisible, (open) => {
  if (!open) formData.value = {};
});
const isLoading = ref(true);

const filter = ref({
  search: null,
});

const headers = [
  // { title: t("ID"), sortable: false, key: "id" },
  { title: t("Code"), key: "code", visible: true },
  { title: t("Name Khmer"), key: "name_kh", visible: true },
  { title: t("Name English"), key: "name_en", visible: true },
  { title: t("Province"), key: "province.name_kh", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

const onDelete = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.post("districts-delete", { id: item.id });

    dataTableRef.value.reload();
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    useAppStore().getDistricts();
    isLoading.value = false;
  }
};

const onCreate = async (data, callback) => {
  try {
    isLoading.value = true;

    const res = await api.post("districts", data);

    dataTableRef.value.reload();
    isDialogVisible.value = false;
    callback(res.data.status);
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    useAppStore().getDistricts();
    isLoading.value = false;
  }
};

const onEdit = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.get("districts-show", { params: { id: item.id } });

    formData.value = res.data.data;
    // console.log(formData.value)
    isDialogVisible.value = true;
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};

const onUpdate = async (data, callback) => {
  try {
    isLoading.value = true;

    const res = await api.put("districts", data);

    if (res.data.status) {
      dataTableRef.value.reload();
      isDialogVisible.value = false;
    } else {
      console.error("Error with the response:", res.data);
    }
    callback(res.data.status);
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    useAppStore().getDistricts();
    isLoading.value = false;
  }
};

const onDisable = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.patch(`districts-disable/${item.id}`);

    if (res.data.status) {
      dataTableRef.value.reload();
      isDialogVisible.value = false;
    } else {
      console.error("Error with the response:", res.data);
    }
    callback(res.data.status);
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    useAppStore().getDistricts();
    isLoading.value = false;
  }
};
</script>

<template>
  <AddEditDistrictDialog
    v-model:isDialogVisible="isDialogVisible"
    :item-data="formData"
    :loading="isLoading"
    @on-create="onCreate"
    @on-update="onUpdate"
  />

  <AppCardTable
    enable-mobile-card
    v-model:isDialogCreateVisible="isDialogVisible"
    ref="dataTableRef"
    title="Districts"
    title-icon="tabler-map"
    saveHeaderName="header-districts-list"
    saveStateName="save-state-districts-list"
    v-model:loading="isLoading"
    v-model:filters="filter"
    api-url="districts-list"
    :headers="headers"
    is-filter
    is-excel
    is-edit
    is-delete
    create-dialog
    save-state
    @onEdit="onEdit"
    @onDelete="onDelete"
  >
    <template #filter>
      <VRow class="justify-end">
        <!----Filter Input-->

        <VCol cols="12" sm="6" md="4" lg="2">
          <VTextField
            v-model="filter.search"
            :label="t('Search')"
            prepend-inner-icon="tabler-search"
            clearable
            hide-details
            autocomlete="off"
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>
    </template>
  </AppCardTable>
</template>
