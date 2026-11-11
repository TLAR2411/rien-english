<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import formatDate from "@/utils/formater/formatDate";
import formatContact from "@/utils/formater/formatContact";
import formatGender from "@/utils/formater/formatGender";
import moment from "moment-timezone";
import { useDialog } from "@/composables/useDialog";
import UserRequestApproveDialog from "@/views/admin/users/UserRequestApproveDialog.vue";

/**
 * Requests to make a login account for a new employee. HR raises one when it
 * creates an employee; here an admin creates the user from the employee's
 * details (which closes the request) or dismisses it.
 */
definePage({
  meta: {
    title: "User Requests",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "add-users",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});

const { t } = useI18n();
const { showDialog } = useDialog();

const isLoading = ref(false);
const dataTableRef = ref(null);
const filter = ref({ search: "" });

// How many are waiting (all pages), shown beside the title
const waitingCount = ref(0);
const onResponse = (res) => {
  waitingCount.value = res?.data?.meta?.total ?? 0;
};

// Days a request has been waiting; older ones stand out
const waitingDays = (item) =>
  Math.max(0, moment().startOf("day").diff(moment(item.created_at).startOf("day"), "days"));
const waitingColor = (item) => {
  const days = waitingDays(item);
  if (days >= 7) return "error";
  if (days >= 3) return "warning";
  return "success";
};

const headers = [
  { title: t("Employee"), key: "employee.name_kh", visible: true },
  { title: t("Name English"), key: "employee.name_en", visible: true },
  {
    title: t("Gender"),
    key: "gender",
    value: (item) => formatGender(item.employee?.gender),
    visible: true,
  },
  {
    title: t("Branch"),
    key: "branch",
    value: (item) => item.employee?.branch?.name_kh,
    visible: true,
  },
  {
    title: t("Position"),
    key: "position",
    value: (item) => item.employee?.position?.name_kh,
    visible: true,
  },
  {
    title: t("Contact"),
    key: "contact",
    value: (item) => formatContact(item.employee?.contact),
    visible: true,
  },
  {
    title: t("Work Start Date"),
    key: "hired_date",
    value: (item) => formatDate(item.employee?.hired_date),
    visible: true,
  },
  {
    title: t("Requested Date"),
    key: "created_at",
    value: (item) => formatDate(item.created_at),
    visible: true,
  },
  {
    title: t("Action"),
    key: "actions",
    align: "center",
    width: 230,
    visible: true,
  },
];

// Approve: a short dialog asks for the role and access; the rest of the
// account comes from the employee. Saving it closes the request.
const isApproveDialogVisible = ref(false);
const requestSelected = ref(null);
const onApprove = (item) => {
  requestSelected.value = item;
  isApproveDialogVisible.value = true;
};

const onDismiss = async (item) => {
  const confirmed = await showDialog({
    title: t("Dismiss this user request?"),
    icon: "warning",
    confirmColor: "error",
  });
  if (!confirmed) return;

  try {
    isLoading.value = true;
    const res = await api.post("user-access-requests-dismiss", { id: item.id });
    if (res.data.status) dataTableRef.value.reload();
  } catch (error) {
    console.error("Failed to dismiss user request:", error);
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <UserRequestApproveDialog
    v-if="isApproveDialogVisible"
    v-model:is-dialog-visible="isApproveDialogVisible"
    :item-data="requestSelected"
    @onReload="dataTableRef.reload"
  />
  <AppCardTable
    enable-mobile-card
    ref="dataTableRef"
    title="User Requests"
    title-icon="tabler-user-plus"
    api-url="user-access-requests-list"
    saveHeaderName="header-user-requests-list"
    saveStateName="save-state-user-requests-list"
    v-model:loading="isLoading"
    v-model:filters="filter"
    :headers="headers"
    is-filter
    is-reload
    save-state
    :is-back="false"
    :is-more-action="false"
    :is-double-click-action="false"
    @update:response="onResponse"
  >
    <!-- Waiting count beside the toolbar icons -->
    <template #card-header>
      <VChip
        size="small"
        :color="waitingCount ? 'warning' : 'success'"
        variant="tonal"
        :prepend-icon="waitingCount ? 'tabler-hourglass' : 'tabler-check'"
        class="mr-2"
      >
        {{ waitingCount }} {{ $t("Waiting") }}
      </VChip>
    </template>

    <template #no-data>
      <div class="text-center py-10">
        <VAvatar color="success" variant="tonal" size="56" class="mb-3">
          <VIcon icon="tabler-user-check" size="30" />
        </VAvatar>
        <p class="text-body-1 font-weight-medium mb-1">
          {{ $t("No user requests waiting") }}
        </p>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ $t("A request appears here when HR creates a new employee.") }}
        </p>
      </div>
    </template>
    <template #filter>
      <VRow class="justify-end">
        <VCol cols="12" lg="3" md="4" sm="6">
          <VTextField
            v-model="filter.search"
            :label="t('Search')"
            prepend-inner-icon="tabler-search"
            clearable
            hide-details
            autocomplete="off"
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>
    </template>

    <template #item.employee.name_kh="{ item }">
      <div class="d-flex flex-row pt-2 pb-2">
        <AppName
          :title="item.employee?.name_kh"
          :sub-title="item.employee?.code"
          :image="item.employee?.image_path"
        />
      </div>
    </template>

    <template #item.branch="{ value }">
      <span class="d-inline-flex align-center ga-1">
        <VIcon icon="tabler-building" size="16" class="text-medium-emphasis" />
        {{ value || "-" }}
      </span>
    </template>

    <template #item.position="{ value }">
      <VChip v-if="value" size="small" label color="primary" variant="tonal">
        {{ value }}
      </VChip>
      <span v-else class="text-disabled">-</span>
    </template>

    <!-- Date, with how long the request has been waiting -->
    <template #item.created_at="{ item, value }">
      <div class="d-flex align-center ga-2">
        <span>{{ value }}</span>
        <VChip size="x-small" :color="waitingColor(item)" variant="tonal">
          {{
            waitingDays(item)
              ? `${waitingDays(item)} ${$t("Days")}`
              : $t("Today")
          }}
        </VChip>
      </div>
    </template>

    <template #actions-prepend="{ item }">
      <div class="d-inline-flex align-center ga-2">
        <VBtn
          size="small"
          color="primary"
          variant="flat"
          prepend-icon="tabler-user-check"
          @click="onApprove(item)"
        >
          {{ $t("Approve") }}
        </VBtn>
        <VBtn
          size="small"
          color="error"
          variant="tonal"
          prepend-icon="tabler-x"
          @click="onDismiss(item)"
        >
          {{ $t("Dismiss") }}
        </VBtn>
      </div>
    </template>
  </AppCardTable>
</template>
