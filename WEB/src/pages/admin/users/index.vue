<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import formatDate from "@/utils/formater/formatDate";
import { onMounted, ref } from "vue";
import { getPermissions, getRoles } from "@/services/dataService";
import formatGender from "@/utils/formater/formatGender";
import { useRouter } from "vue-router";
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import PasswordDialog from "@/views/admin/users/PasswordDialog.vue";
import UsernameDialog from "@/views/admin/users/UsernameDialog.vue";
import PermissionDialog from "@/views/admin/users/PermissionDialog.vue";
import LinkUserDialog from "@/views/admin/users/LinkUserDialog.vue";
import formatContact from "@/utils/formater/formatContact";
import moment from "moment-timezone";
import { useDisplay } from "vuetify";

const { t, locale } = useI18n();
definePage({
  meta: {
    title: "Users",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-users",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});

const today = moment().tz("Asia/Phnom_Penh").format("YYYY-MM-DD");
const formData = ref({});
const router = useRouter();
const isSnackbarTopVisible = ref(false);
const isDialogVisible = ref(false);
const isLoading = ref(true);
const dataTableRef = ref(null);
const roles = ref([]);
const isPasswordDialog = ref(false);
const isUsernameDialog = ref(false);
const isLinkUserDialog = ref(false);
const isPermissionDialog = ref(false);
const userIdSelected = ref(null);
const { mdAndUp } = useDisplay();
const permissions = ref([]);
const userPermissions = ref([]);

const filter = ref({
  search: null,
  is_active: null,
  role_id: null,
  gender: null,
  join_from: null,
  join_to: null,
  last_login: null,
});

const statusOptions = [
  { title: t("Active"), value: "1" },
  { title: t("Inactive"), value: "0" },
];
const genderOptions = [
  { title: t("Male"), value: "male" },
  { title: t("Female"), value: "female" },
];
const lastLoginOptions = [
  { title: t("Today"), value: "today" },
  { title: t("Last 7 days"), value: "7" },
  { title: t("Last 30 days"), value: "30" },
  { title: t("Not logged in for 30 days"), value: "inactive_30" },
  { title: t("Never logged in"), value: "never" },
];

const headers = [
  {
    title: t("User"),
    key: "code",
    visible: true,
    cellClass: "fixed-first-column",
    headerClass: "fixed-first-column-header",
    fixed: mdAndUp.value,
  },
  { title: t("Name"), key: "name_kh", visible: false },
  { title: t("Username"), key: "username", visible: true },
  // { title: t("Email"), key: "email", visible: true },
  {
    title: t("Gender"),
    key: "gender",
    value: (items) => formatGender(items.gender),
    visible: true,
  },
  {
    title: t("DOB"),
    key: "dob",
    value: (items) => formatDate(items.dob),
    visible: true,
  },
  {
    title: t("Contact"),
    key: "contact",
    value: (items) => formatContact(items.contact),
    visible: true,
  },
  {
    title: t("National ID Number"),
    key: "national_id_number",
    visible: true,
  },
  // {
  //   title: t("National ID Issue Date"),
  //   key: "national_id_issue_date",
  //   value: (items) => formatDate(items.national_id_issue_date),
  //   visible: true,
  // },
  {
    title: t("Branch"),
    key: locale.value === "en" ? "branch.name_en" : "branch.name_kh" || null,
    visible: true,
  },

  {
    title: t("Role"),
    key: "role.display_name",
    visible: true,
  },
  {
    title: t("Join Date"),
    key: "join_date",
    value: (items) => formatDate(items.join_date),
    visible: true,
  },
  { title: t("Last Login"), key: "last_login", align: "center", visible: true },
  { title: t("Status"), key: "is_active", align: "center", visible: true },
  {
    title: t("Action"),
    key: "actions",
    align: "center",
    visible: true,
    fixed: true,
  },
];

const onDelete = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.delete(`users/${item.id}`);

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

const onEdit = async (item) => {
  router.push({ name: "admin-users-edit", query: { id: item.id } });
};

// The user's learning: numbers, quiz scores, every quiz try
const onView = (item) => {
  router.push({ name: "admin-users-view-id", params: { id: item.id } });
};

const onDisable = async (item) => {
  try {
    isLoading.value = true;

    const res = await api.patch(`users-disable/${item.id}`);

    if (res.data.status) {
      dataTableRef.value.reload();
      isDialogVisible.value = false;
    } else {
      console.error("Error with the response:", res.data);
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text);
  isSnackbarTopVisible.value = true;
};

onMounted(async () => {
  const [dataRoles, dataPermissions] = await Promise.all([
    getRoles(),
    getPermissions(),
  ]);

  roles.value = dataRoles;
  permissions.value = dataPermissions;
});

const onPassword = (item) => {
  userIdSelected.value = item.id;
  isPasswordDialog.value = true;
};

const onUsername = (item) => {
  userIdSelected.value = item.id;
  isUsernameDialog.value = true;
};

const onLinkUser = (item) => {
  userIdSelected.value = item.id;
  isLinkUserDialog.value = true;
};

const onPermission = async (item) => {
  const res = await api.get("users-show", { params: { id: item.id } });

  if (res.data.status) {
    userPermissions.value = res.data.data.user_permission;
    userIdSelected.value = item.id;
    isPermissionDialog.value = true;
  }
};

const onUpdatePermission = async (data, callback) => {
  // console.log(data);

  try {
    isLoading.value = true;

    const res = await api.post("users-permission", data);

    if (res.data.status) {
      dataTableRef.value.reload();
      isPermissionDialog.value = false;
    } else {
      console.error("Error with the response:", res.data);
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <VSnackbar v-model="isSnackbarTopVisible" location="top">
    <VIcon>tabler-copy</VIcon> Copied to clipboard!
  </VSnackbar>

  <PasswordDialog
    v-if="isPasswordDialog"
    v-model:is-dialog-visible="isPasswordDialog"
    :item-data="{ user_id: userIdSelected }"
    @onReload="dataTableRef.reload()"
  />
  <UsernameDialog
    v-if="isUsernameDialog"
    v-model:is-dialog-visible="isUsernameDialog"
    :item-data="{ user_id: userIdSelected }"
    @onReload="dataTableRef.reload()"
  />
  <LinkUserDialog
    v-if="isLinkUserDialog"
    v-model:is-dialog-visible="isLinkUserDialog"
    :item-data="{ user_id: userIdSelected }"
    @onReload="dataTableRef.reload()"
  />

  <PermissionDialog
    v-if="isPermissionDialog"
    v-model:is-dialog-visible="isPermissionDialog"
    :item-data="{ user_id: userIdSelected }"
    :user-permissions="userPermissions"
    :permissions="permissions"
    @on-update="onUpdatePermission"
  />

  <AppCardTable
    enable-mobile-card
    ref="dataTableRef"
    title="List Users"
    title-icon="tabler-users"
    saveHeaderName="header-list-users"
    saveStateName="save-state-list-users"
    v-model:loading="isLoading"
    v-model:filters="filter"
    api-url="users-list"
    :headers="headers"
    can-create="add-users"
    create-page="admin-users-create"
    is-check-branch
    is-filter
    is-view
    can-view="view-users"
    is-edit
    is-delete
    is-disable
    is-password
    is-username
    is-permission
    is-link-user
    is-excel
    save-state
    :is-back="false"
    @on-delete="onDelete"
    @on-view="onView"
    @on-edit="onEdit"
    @on-disable="onDisable"
    @on-password="onPassword"
    @on-username="onUsername"
    @on-link-user="onLinkUser"
    @on-permission="onPermission"
  >
    <template #filter>
      <VRow class="justify-end">
        <VCol cols="6" sm="4" md="3" lg="2">
          <VSelect
            v-model="filter.is_active"
            :items="statusOptions"
            :label="t('Status')"
            prepend-inner-icon="tabler-circle-check"
            clearable
            hide-details
            clear-icon="tabler-x"
          />
        </VCol>
        <VCol cols="6" sm="4" md="3" lg="2">
          <VSelect
            v-model="filter.gender"
            :items="genderOptions"
            :label="t('Gender')"
            prepend-inner-icon="tabler-gender-bigender"
            clearable
            hide-details
            clear-icon="tabler-x"
          />
        </VCol>
        <VCol cols="12" sm="4" md="3" lg="2">
          <AppAutocomplete
            v-model="filter.role_id"
            :items="roles"
            item-title="display_name"
            item-value="id"
            prepend-inner-icon="tabler-shield"
            clearable
            hide-details
            autocomplete="off"
            clear-icon="tabler-x"
          >
            <template #label>{{ t("Role") }}</template>
          </AppAutocomplete>
        </VCol>
        <VCol cols="12" sm="4" md="3" lg="2">
          <VSelect
            v-model="filter.last_login"
            :items="lastLoginOptions"
            :label="t('Last Login')"
            prepend-inner-icon="tabler-login"
            clearable
            hide-details
            clear-icon="tabler-x"
          />
        </VCol>
        <VCol cols="12" sm="8" md="6" lg="4">
          <AppInputGroup stack-on="xs">
            <AppDateTimePicker
              v-model="filter.join_from"
              clearable
              hide-details
              autocomplete="off"
              prepend-inner-icon="tabler-calendar-due"
            >
              <template #label>{{ t("Join Date") }} ({{ t("From") }})</template>
            </AppDateTimePicker>
            <AppDateTimePicker
              v-model="filter.join_to"
              clearable
              hide-details
              autocomplete="off"
              prepend-inner-icon="tabler-calendar-due"
            >
              <template #label>{{ t("To") }}</template>
            </AppDateTimePicker>
          </AppInputGroup>
        </VCol>
        <VCol cols="12" sm="4" md="3" lg="2">
          <VTextField
            v-model="filter.search"
            :label="t('Search')"
            :placeholder="t('Name, code, phone, ID card')"
            prepend-inner-icon="tabler-search"
            clearable
            hide-details
            autocomplete="off"
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>
    </template>

    <template v-slot:item.code="{ item }">
      <div class="d-flex flex-row pt-2 pb-2">
        <AppName
          :title="item.name_kh"
          :sub-title="item.code"
          :image="item.image_path"
          :subTitleCondition="!item.is_active"
        />
      </div>
    </template>
    <template v-slot:item.is_active="{ item }">
      <VChip color="success" size="small" v-if="item.is_active == true">
        {{ $t("Active") }}
      </VChip>
      <VChip color="error" size="small" v-if="item.is_active == false">
        {{ $t("Inactive") }}
      </VChip>
    </template>

    <template v-slot:item.last_login="{ item }">
      <span
        v-if="item.last_login"
        :style="`color: ${formatDate(item.last_login) == formatDate(today) ? 'green' : 'inherit'} `"
      >
        {{ moment(item.last_login).format("DD-MM-YYYY HH:mm:ss") }}
      </span>
    </template>
  </AppCardTable>
</template>
