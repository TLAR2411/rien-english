<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import AddEditPositionDialog from "@/views/admin/positions/AddEditPositionDialog.vue";
import { onMounted } from "vue";
import { getDepartments } from "@/services/dataService";
import formatCurrency from "@/utils/formater/formatCurrency";

definePage({
  meta: {
    title: "Positions",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-positions",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});

const { t, locale } = useI18n();
const formData = ref({});
const dataTableRef = ref(null);
const isDialogVisible = ref(false);

// Forget the edited position when the dialog closes, so "Create" opens empty
watch(isDialogVisible, (open) => {
  if (!open) formData.value = {};
});
const isLoading = ref(true);
const departments = ref([]);

const filter = ref({
  search: null,
});

const headers = [
  // { title: t("ID"), sortable: false, key: "id" },
  { title: t("Name Khmer"), key: "name_kh", visible: true },
  { title: t("Name English"), key: "name_en", visible: true },
  { title: t("Abbr"), key: "abbr", visible: true },
  { title: t("Level"), key: "level", visible: true },
  {
    title: t("Department"),
    key:
      locale.value === "en"
        ? "department.name_en"
        : "department.name_kh" || null,
    visible: true,
  },
  {
    title: t("Insurance Amount"),
    key: "insurance_amount",
    value: (item) => {
      return formatCurrency(item.insurance_amount);
    },
    align: "end",
    visible: true,
  },
  {
    title: t("Position Fee"),
    key: "position_fee",
    value: (item) => {
      return formatCurrency(item.position_fee);
    },
    align: "end",
    visible: true,
  },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

const onDelete = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.delete(`positions/${item.id}`);

    if (res.data.status) {
      dataTableRef.value.reload();
    } else {
      console.error("Error with the response:", res.data);
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};

const onCreate = async (data, callback) => {
  try {
    isLoading.value = true;

    const res = await api.post("positions", data);

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
    isLoading.value = false;
  }
};

const onEdit = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.get("positions-show", { params: { id: item.id } });

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

    const res = await api.put("positions", data);

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
    isLoading.value = false;
  }
};

const onDisable = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.post("positions-disable", { id: item.id });

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
    isLoading.value = false;
  }
};

onMounted(async () => {
  const dataDepartments = await getDepartments();
  departments.value = dataDepartments;
});
</script>

<template>
  <AddEditPositionDialog
    v-model:isDialogVisible="isDialogVisible"
    :item-data="formData"
    :departments="departments"
    :loading="isLoading"
    @on-create="onCreate"
    @on-update="onUpdate"
  />

  <AppCardTable
    enable-mobile-card
    v-model:isDialogCreateVisible="isDialogVisible"
    ref="dataTableRef"
    title="Positions"
    title-icon="tabler-briefcase-2"
    saveHeaderName="header-positions-list"
    saveStateName="save-state-positions-list"
    v-model:loading="isLoading"
    v-model:filters="filter"
    api-url="positions-list"
    :headers="headers"
    is-filter
    is-excel
    is-edit
    is-delete
    is-disable
    create-dialog
    save-state
    @on-delete="onDelete"
    @on-edit="onEdit"
    @on-disable="onDisable"
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
