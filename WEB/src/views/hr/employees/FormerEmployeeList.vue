<script setup>
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import moment from "moment-timezone";
import { api } from "@/utils/api";
import formatDate from "@/utils/formater/formatDate";
import formatContact from "@/utils/formater/formatContact";
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import AppDateTimePicker from "@core/components/app-form-elements/AppDateTimePicker.vue";
import EmployeeTerminateContract from "./EmployeeTerminateContract.vue";
import { useDisplay } from "vuetify";

/**
 * Employees whose contract was terminated: when they left, why, and how long
 * they worked. Same endpoint as the employee list, with employment = former.
 */
const { t } = useI18n();
const router = useRouter();
const { mdAndUp } = useDisplay();
const isLoading = ref(false);
const dataTableRef = ref(null);
const filter = ref({
  search: "",
  position_id: null,
  termination_type: null,
  left_from: null,
  left_to: null,
});

// Same reasons and icons as the terminate form
const TERMINATION = {
  dismissal: { title: "Dismissal", icon: "tabler-user-x", color: "error" },
  resignation: {
    title: "Resignation",
    icon: "tabler-door-exit",
    color: "info",
  },

  end_of_contract: {
    title: "End of Contract",
    icon: "tabler-calendar-x",
    color: "warning",
  },
  retirement: {
    title: "Retirement",
    icon: "tabler-armchair",
    color: "secondary",
  },
  mutual_agreement: {
    title: "Mutual Agreement",
    icon: "tabler-heart-handshake",
    color: "success",
  },
  other: { title: "Other", icon: "tabler-dots", color: "secondary" },
};

const terminationOptions = computed(() =>
  Object.entries(TERMINATION).map(([value, v]) => ({
    value,
    title: t(v.title),
  })),
);

const positions = ref([]);
onMounted(async () => {
  try {
    const res = await api.get("positions-all");
    positions.value = res.data?.data || [];
  } catch (error) {
    console.error(error);
  }
});

// Hired date → last working date, e.g. "2 ឆ្នាំ 3 ខែ"
const serviceLength = (item) => {
  if (!item.hired_date || !item.last_working_date) return "-";
  const start = moment(item.hired_date);
  const end = moment(item.last_working_date);
  if (end.isBefore(start)) return "-";
  const years = end.diff(start, "years");
  const months = end.diff(start.clone().add(years, "years"), "months");
  if (!years && !months) return `${end.diff(start, "days")} ${t("Days")}`;
  return [
    years && `${years} ${t("Years")}`,
    months && `${months} ${t("Months")}`,
  ]
    .filter(Boolean)
    .join(" ");
};

const headers = [
  { title: t("Code"), key: "code", visible: false },
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
    value: (item) => item.branch?.name_kh,
    visible: true,
  },
  { title: t("Position"), key: "position.name_kh", visible: true },
  {
    title: t("Work Start Date"),
    key: "hired_date",
    value: (item) => formatDate(item.hired_date),
    exportValue: (item) => item.hired_date,
    visible: true,
  },
  {
    title: t("Last Working Date"),
    key: "last_working_date",
    value: (item) => formatDate(item.last_working_date),
    exportValue: (item) => item.last_working_date,
    visible: true,
  },
  {
    title: t("Length of Service"),
    key: "service_length",
    value: (item) => serviceLength(item),
    sortable: false,
    visible: true,
  },
  {
    title: t("Reason for leaving"),
    key: "termination_type",
    exportValue: (item) =>
      t(
        TERMINATION[item.termination_type]?.title ||
          item.termination_type ||
          "",
      ),
    visible: true,
  },
  {
    title: t("Note"),
    key: "termination_reason",
    value: (item) => item.termination_reason || "-",
    visible: true,
  },
  {
    title: t("Terminated Date"),
    key: "terminated_date",
    value: (item) => formatDate(item.terminated_date),
    exportValue: (item) => item.terminated_date,
    visible: false,
  },
  {
    title: t("Contact"),
    key: "contact",
    value: (item) => formatContact(item.contact),
    visible: false,
  },
  {
    title: t("Action"),
    key: "actions",
    align: "center",
    visible: true,
    fixed: mdAndUp.value,
  },
];

const onView = (item) =>
  router.push({ name: "hr-employees-profile", query: { id: item.id } });

// Edit the termination details (date, type, reason)
const isEditDialogVisible = ref(false);
const editEmployeeId = ref(null);
const onEdit = (item) => {
  editEmployeeId.value = item.id;
  isEditDialogVisible.value = true;
};
</script>

<template>
  <EmployeeTerminateContract
    v-if="isEditDialogVisible"
    v-model:is-dialog-visible="isEditDialogVisible"
    :item-data="{ employee_id: editEmployeeId }"
    is-edit
    @onReload="dataTableRef?.reload()"
  />

  <AppCardTable
    enable-mobile-card
    ref="dataTableRef"
    title="Former Employees"
    title-icon="tabler-user-off"
    api-url="employees-list"
    :api-data="{ employment: 'former' }"
    saveHeaderName="header-former-employees-list"
    saveStateName="save-state-former-employees-list"
    v-model:loading="isLoading"
    v-model:filters="filter"
    :headers="headers"
    is-filter
    is-view
    is-edit
    can-edit="edit-employees"
    is-excel
    save-state
    :is-back="false"
    @onView="onView"
    @onEdit="onEdit"
  >
    <template #filter>
      <VRow class="justify-end">
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
            v-model="filter.termination_type"
            :items="terminationOptions"
            :label="t('Reason for leaving')"
            prepend-inner-icon="tabler-door-exit"
            clearable
            hide-details
            clear-icon="tabler-x"
          />
        </VCol>
        <VCol cols="6" lg="2" md="3" sm="3">
          <AppDateTimePicker
            v-model="filter.left_from"
            clearable
            hide-details
            autocomplete="off"
          >
            <template #label>{{ $t("Left from") }}</template>
          </AppDateTimePicker>
        </VCol>
        <VCol cols="6" lg="2" md="3" sm="3">
          <AppDateTimePicker
            v-model="filter.left_to"
            clearable
            hide-details
            autocomplete="off"
          >
            <template #label>{{ $t("Left to") }}</template>
          </AppDateTimePicker>
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

    <template #item.name_kh="{ item }">
      <div class="d-flex flex-row pt-2 pb-2">
        <AppName
          :title="item.name_kh"
          :sub-title="item.code"
          :image="item.image_path"
        />
      </div>
    </template>

    <template #item.termination_type="{ item }">
      <VChip
        v-if="item.termination_type"
        size="small"
        label
        :color="TERMINATION[item.termination_type]?.color"
        :prepend-icon="TERMINATION[item.termination_type]?.icon"
      >
        {{
          $t(TERMINATION[item.termination_type]?.title || item.termination_type)
        }}
      </VChip>
      <span v-else class="text-disabled">-</span>
    </template>

    <template #item.termination_reason="{ item }">
      <span class="reason" :title="item.termination_reason">{{
        item.termination_reason || "-"
      }}</span>
    </template>
  </AppCardTable>
</template>

<style scoped>
/* Long notes: two lines, full text on hover */
/* .reason {
  display: -webkit-box;
  max-width: 460px;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  white-space: normal;
} */
</style>
