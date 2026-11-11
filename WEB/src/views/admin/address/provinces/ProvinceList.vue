<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import AddEditProvinceDialog from "@/views/admin/address/provinces/AddEditProvinceDialog.vue";
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
  { title: t("Code"), key: "code", visible: true },
  { title: t("Name Khmer"), key: "name_kh", visible: true },
  { title: t("Name English"), key: "name_en", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

const onDelete = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.delete(`provinces/${item.id}`);

    if (res.data.status) {
      dataTableRef.value.reload();
    } else {
      console.error("Error with the response:", res.data);
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    useAppStore().getProvinces();
    isLoading.value = false;
  }
};

const onCreate = async (data, callback) => {
  try {
    isLoading.value = true;

    const res = await api.post("provinces", data);

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
    useAppStore().getProvinces();
    isLoading.value = false;
  }
};

const onEdit = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.get("provinces-show", { params: { id: item.id } });

    if (res.data.status) {
      formData.value = res.data.data;
      // console.log(formData.value)
      isDialogVisible.value = true;
    } else {
      console.error("Error with the response:", res.data);
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};

const onUpdate = async (data, callback) => {
  try {
    isLoading.value = true;

    const res = await api.put("provinces", data);

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
    useAppStore().getProvinces();
    isLoading.value = false;
  }
};

const onDisable = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.patch(`provinces-disable/${item.id}`);

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
    useAppStore().getProvinces();
    isLoading.value = false;
  }
};
</script>

<template>
  <AddEditProvinceDialog
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
    title="Provinces"
    title-icon="tabler-map"
    saveHeaderName="header-provinces-list"
    saveStateName="save-state-provinces-list"
    v-model:loading="isLoading"
    v-model:filters="filter"
    api-url="provinces-list"
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
