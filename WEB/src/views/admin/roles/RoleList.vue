<script setup>
import AddEditRoleDialog from "@/views/admin/roles/AddEditRoleDialog.vue";
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import { onMounted } from "vue";
import { getPermissions } from "@/services/dataService";

definePage({
  meta: {
    title: "Roles",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-roles",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});

const { t } = useI18n();
const formData = ref({});
const dataTableRef = ref(null);
const isDialogVisible = ref(false);
const isLoading = ref(true);
const permissions = ref([]);
const rolePermissions = ref([]);

const filter = ref({
  search: null,
});

const headers = [
  // {title: t("ID"), sortable: false``, key: "id"},
  { title: t("Name"), key: "name", visible: true },
  { title: t("Display Name"), key: "display_name", visible: true },
  { title: t("Abbr"), key: "abbr", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

const onDelete = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.delete(`roles/${item.id}`);

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

    const res = await api.post("roles", data);

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

    const res = await api.get("roles-show", { params: { id: item.id } });

    if (res.data.status) {
      formData.value = res.data.data.role_data;
      rolePermissions.value = res.data.data.role_permission;
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

    const res = await api.put("roles", data);

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
  const dataPermissions = await getPermissions();
  permissions.value = dataPermissions;
});
</script>

<template>
  <AddEditRoleDialog
    v-model:isDialogVisible="isDialogVisible"
    v-model:loading="isLoading"
    :role-data="formData"
    :permissions="permissions"
    :role-permissions="rolePermissions"
    @on-create="onCreate"
    @on-update="onUpdate"
  />

  <AppCardTable
    enable-mobile-card
    v-model:isDialogCreateVisible="isDialogVisible"
    ref="dataTableRef"
    title="Roles"
    title-icon="tabler-user-cog"
    saveHeaderName="header-roles-list"
    saveStateName="save-state-roles-list"
    v-model:loading="isLoading"
    v-model:filters="filter"
    api-url="roles-list"
    :headers="headers"
    is-filter
    is-excel
    is-edit
    is-delete
    create-dialog
    save-state
    @on-delete="onDelete"
    @on-edit="onEdit"
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
