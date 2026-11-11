<script setup>
definePage({
  meta: {
    title: "Employee",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});

import { useI18n } from "vue-i18n";
import formatDate from "@/utils/formater/formatDate";
import formatGender from "@/utils/formater/formatGender";
import { useRouter } from "vue-router";
import { api } from "@/utils/api";
import { useDisplay } from "vuetify";
import formatContact from "@/utils/formater/formatContact";
import moment from "moment-timezone";
import EmployeeTerminateContract from "./EmployeeTerminateContract.vue";
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";

const { mdAndUp } = useDisplay();
const { t } = useI18n();
const router = useRouter();
const isLoading = ref(false);
const dataTableRef = ref(null);
const employeeIdSelected = ref(null);
const isTerminateDialogVisible = ref(false);

const filter = ref({
  search: "",
  is_active: null,
  position_id: null,
});

const statusOptions = computed(() => [
  { title: t("Active"), value: 1 },
  { title: t("Inactive"), value: 0 },
]);

const positions = ref([]);
const loadPositions = async () => {
  try {
    const res = await api.get("positions-all");
    positions.value = res.data?.data || [];
  } catch (error) {
    console.error("Failed to load positions:", error);
  }
};
onMounted(loadPositions);

const getAge = (birthDate) => {
  return Math.floor((new Date() - new Date(birthDate).getTime()) / 3.15576e10);
};

// Time worked: hired date → last working date (or today if still working).
// Shows years + months, or days when under a month, e.g. "2 ឆ្នាំ 3 ខែ".
const getServiceLength = (item) => {
  if (!item.hired_date) return "-";
  const start = moment(item.hired_date);
  const end = item.last_working_date
    ? moment(item.last_working_date)
    : moment().tz("Asia/Phnom_Penh");
  if (end.isBefore(start)) return "-";

  const years = end.diff(start, "years");
  const months = end.diff(start.clone().add(years, "years"), "months");
  if (!years && !months) {
    return `${end.diff(start, "days")} ${t("Days")}`;
  }

  const parts = [];
  if (years) parts.push(`${years} ${t("Years")}`);
  if (months) parts.push(`${months} ${t("Months")}`);
  return parts.join(" ");
};

const headers = [
  {
    title: t("Code"),
    key: "code",
    exportValue: (item) => item.code,
    visible: false,
  },
  {
    title: t("Employee"),
    key: "name_kh",
    exportValue: (item) => item.name_kh,
    cellClass: "fixed-first-column",
    headerClass: "fixed-first-column-header",
    visible: true,
    fixed: mdAndUp.value,
  },

  {
    title: t("Branch"),
    key: "branch_id",
    value: (item) => {
      return item.branch.name_kh;
    },
    visible: true,
  },
  {
    title: t("Position"),
    key: "position.name_kh",
    visible: true,
  },

  // { title: t("Name English"), key: "name_en" },
  {
    title: t("Gender"),
    key: "gender",
    value: (items) => formatGender(items.gender),
    visible: true,
  },
  {
    title: t("Date of birth"),
    key: "dob",
    value: (items) => formatDate(items.dob),
    exportValue: (item) => item.dob,
    visible: true,
  },
  {
    title: t("Age"),
    key: "age",
    value: (items) => getAge(items.dob),
    align: "center",
    visible: true,
  },
  {
    title: t("National ID Number"),
    key: "national_id_number",
    visible: true,
  },
  {
    title: t("Contact"),
    key: "contact",
    value: (item) => {
      return `${formatContact(item.contact)}`;
    },
    visible: true,
  },
  {
    title: t("Parent Contact"),
    key: "parent_contact",
    value: (item) => {
      return `${formatContact(item.parent_contact)}`;
    },
    visible: true,
  },
  {
    title: t("Bank Account"),
    key: "bank_account_number",
    visible: true,
  },

  {
    title: t("Work Start Date"),
    key: "hired_date",
    value: (item) => {
      return `${formatDate(item.hired_date)}`;
    },
    visible: true,
  },
  {
    title: t("Last Working Date"),
    key: "last_working_date",
    value: (item) => {
      return `${formatDate(item.last_working_date)}`;
    },
    visible: true,
  },
  {
    title: t("Length of Service"),
    key: "service_length",
    value: (item) => getServiceLength(item),
    sortable: false,
    visible: true,
  },
  {
    title: t("Village"),
    key: "village",
    value: (item) => {
      return item?.village
        ? `
      ${item?.village?.name_kh || ""}`
        : "";
    },
    visible: true,
  },
  {
    title: t("Commune"),
    key: "commune",
    value: (item) => {
      return item?.village
        ? `
      ${item?.village?.commune.name_kh || ""}
        `
        : "";
    },
    visible: true,
  },
  {
    title: t("District"),
    key: "district",
    value: (item) => {
      return item?.village
        ? `
      ${item?.village?.commune?.district?.name_kh || ""}
        `
        : "";
    },
    visible: true,
  },
  {
    title: t("Province"),
    key: "province",
    value: (item) => {
      return item?.village
        ? `
      ${item?.village?.commune?.district?.province?.name_kh || ""}
        `
        : "";
    },
    visible: true,
  },
  {
    title: t("Status"),
    key: "is_active",
    align: "center",
    visible: true,
  },
  {
    title: t("Action"),
    key: "actions",
    align: "center",
    visible: true,
    fixed: mdAndUp.value,
  },
];

const onEdit = (item) => {
  router.push({
    name: "hr-employees-edit",
    query: { id: item.id },
  });
};
const onDelete = async (item) => {
  try {
    // previewFormDialog.value = true;
    isLoading.value = true;

    const res = await api.post("employees-delete", { id: item.id });

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
const onView = (item) => {
  router.push({
    name: "hr-employees-profile",
    query: { id: item.id },
  });
};

const onDisable = async (item) => {
  try {
    // previewFormDialog.value = true;
    isLoading.value = true;

    const res = await api.patch(`employees-disable/${item.id}`);

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

const onTerminate = (item) => {
  employeeIdSelected.value = item.id;
  isTerminateDialogVisible.value = true;
};

</script>

<template>
  <EmployeeTerminateContract
    v-if="isTerminateDialogVisible"
    v-model:is-dialog-visible="isTerminateDialogVisible"
    :item-data="{ employee_id: employeeIdSelected }"
    @onReload="dataTableRef.reload"
  />
  <AppCardTable
    enable-mobile-card
    ref="dataTableRef"
    :api-data="{ with_photo: 1 }"
    create-page="hr-employees-create"
    is-check-branch
    title="List Employees"
    title-icon="tabler-address-book"
    api-url="employees-list"
    saveHeaderName="header-employees-list"
    saveStateName="save-state-employees-list"
    v-model:loading="isLoading"
    v-model:filters="filter"
    :headers="headers"
    is-filter
    is-edit
    is-view
    is-delete
    is-excel
    is-terminate
    save-state
    :is-back="false"
    @onEdit="onEdit"
    @onDelete="onDelete"
    @onView="onView"
    @onDisable="onDisable"
    @onTerminate="onTerminate"
  >
    <template #filter>
      <VRow class="justify-end">
        <!----Filter Input-->
        <VCol cols="12" lg="3" md="3" sm="6">
          <AppAutocomplete
            v-model="filter.position_id"
            :items="positions"
            item-title="name_kh"
            item-value="id"
            prepend-inner-icon="tabler-id-badge-2"
            clearable
            hide-details
            autocomplete="off"
            clear-icon="tabler-x"
          >
            <template #label>{{ t("Position") }}</template>
          </AppAutocomplete>
        </VCol>
        <VCol cols="12" lg="2" md="3" sm="6">
          <VSelect
            v-model="filter.is_active"
            :items="statusOptions"
            :label="t('Status')"
            prepend-inner-icon="tabler-toggle-right"
            clearable
            hide-details
            clear-icon="tabler-x"
          />
        </VCol>
        <VCol cols="12" lg="3" md="3" sm="6">
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

    <template v-slot:item.name_kh="{ item }">
      <div class="d-flex flex-row pt-2 pb-2">
        <AppName
          :title="item.name_kh"
          :sub-title="item.code"
          :image="item.image_path || item.user?.image_path"
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
  </AppCardTable>
</template>
