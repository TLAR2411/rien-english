<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import moment from "moment-timezone"
import formatContact from "@/utils/formater/formatContact"
import formatGender from "@/utils/formater/formatGender"

/** Employee profile "Information" tab: grouped detail cards. */
const props = defineProps({
  itemData: { type: Object, default: () => ({}) },
})

const { t } = useI18n()
const employee = computed(() => props.itemData || {})

const date = value => (value ? moment(value).format("DD-MM-YYYY") : "-")
const age = dob => (dob ? moment().diff(moment(dob), "years") : null)

const maritalLabels = {
  single: "Single",
  married: "Married",
  divorced: "Divorced",
  widowed: "Widowed",
}

const terminationLabels = {
  resignation: "Resignation",
  dismissal: "Dismissal",
  end_of_contract: "End of Contract",
  retirement: "Retirement",
  mutual_agreement: "Mutual Agreement",
  other: "Other",
}

const personal = computed(() => [
  { label: "Gender", value: formatGender(employee.value.gender) },
  {
    label: "Date of birth",
    value: employee.value.dob
      ? `${date(employee.value.dob)} (${age(employee.value.dob)} ${t("Years")})`
      : "-",
  },
  {
    label: "Marital Status",
    value: maritalLabels[employee.value.marital_status] ? t(maritalLabels[employee.value.marital_status]) : "-",
  },
  {
    label: "Dependents",
    value: `${employee.value.dependent_children || 0} ${t("Children")}${
      employee.value.spouse_is_dependent ? ` + ${t("Spouse")}` : ""
    }`,
  },
  {
    label: "National ID Number",
    value: employee.value.national_id_number || "-",
  },
  {
    label: "National ID Issue Date",
    value: date(employee.value.national_id_issue_date),
  },
  {
    label: "Contact",
    value: employee.value.contact ? formatContact(employee.value.contact) : "-",
  },
  {
    label: "Parent Contact",
    value: employee.value.parent_contact
      ? formatContact(employee.value.parent_contact)
      : "-",
  },
])

const employment = computed(() => [
  { label: "Branch", value: employee.value.branch?.name_kh || "-" },
  {
    label: "Department",
    value: employee.value.position?.department?.name_kh || "-",
  },
  { label: "Position", value: employee.value.position?.name_kh || "-" },
  { label: "Hired Date", value: date(employee.value.hired_date) },
  {
    label: "Probation End Date",
    value: date(employee.value.probation_end_date),
  },
  {
    label: "Employee Type",
    value: employee.value.employee_type ? t(employee.value.employee_type) : "-",
  },
  { label: "Contract Type", value: employee.value.contract_type || "-" },
  { label: "Work Email", value: employee.value.work_email || "-" },
])

const address = computed(() => {
  const village = employee.value.village
  
  return [
    employee.value.house_no && `${t("House No")} ${employee.value.house_no}`,
    employee.value.street && `${t("Street")} ${employee.value.street}`,
    village?.name_kh,
    village?.commune?.name_kh,
    village?.commune?.district?.name_kh,
    village?.commune?.district?.province?.name_kh,
  ]
    .filter(Boolean)
    .join(", ")
})

const isTerminated = computed(() => !!employee.value.terminated_date)
</script>

<template>
  <VRow>
    <!-- Personal -->
    <VCol
      cols="12"
      md="6"
      lg="4"
    >
      <VCard class="h-100">
        <VCardItem>
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-user"
              color="primary"
            />
            <span class="fs-title font-weight-bold">
              {{ $t("Personal Information") }}
            </span>
          </div>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-0">
          <div
            v-for="row in personal"
            :key="row.label"
            class="info-row"
          >
            <span class="text-medium-emphasis">{{ $t(row.label) }}</span>
            <span class="font-weight-medium text-end">{{ row.value }}</span>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Employment -->
    <VCol
      cols="12"
      md="6"
      lg="4"
    >
      <VCard class="h-100">
        <VCardItem>
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-briefcase"
              color="primary"
            />
            <span class="fs-title font-weight-bold">
              {{ $t("Employment") }}
            </span>
          </div>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-0">
          <div
            v-for="row in employment"
            :key="row.label"
            class="info-row"
          >
            <span class="text-medium-emphasis">{{ $t(row.label) }}</span>
            <span class="font-weight-medium text-end">{{ row.value }}</span>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Address -->
    <VCol
      cols="12"
      lg="4"
      class="d-flex flex-column gap-6"
    >
      <VCard class="flex-grow-1">
        <VCardItem>
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-map-pin"
              color="primary"
            />
            <span class="fs-title font-weight-bold">{{ $t("Address") }}</span>
          </div>
        </VCardItem>
        <VDivider />
        <VCardText class="fs-label">
          {{ address || "-" }}
        </VCardText>
      </VCard>
    </VCol>

    <!-- Termination (only once the contract has ended) -->
    <VCol
      v-if="isTerminated"
      cols="12"
    >
      <VCard class="termination">
        <VCardText class="d-flex align-center flex-wrap gap-6">
          <div class="d-flex align-center gap-2">
            <VAvatar
              color="error"
              variant="tonal"
              size="40"
              rounded
            >
              <VIcon
                icon="tabler-file-x"
                size="22"
              />
            </VAvatar>
            <span class="fs-title font-weight-bold text-error">
              {{ $t("Terminated") }}
            </span>
          </div>
          <div class="d-flex flex-column">
            <span class="fs-caption text-medium-emphasis">
              {{ $t("Terminate Date") }}
            </span>
            <span class="font-weight-bold">
              {{ date(employee.terminated_date) }}
            </span>
          </div>
          <div class="d-flex flex-column">
            <span class="fs-caption text-medium-emphasis">
              {{ $t("Last Working Date") }}
            </span>
            <span class="font-weight-bold">
              {{ date(employee.last_working_date) }}
            </span>
          </div>
          <div class="d-flex flex-column">
            <span class="fs-caption text-medium-emphasis">
              {{ $t("Termination Type") }}
            </span>
            <span class="font-weight-bold">
              {{ terminationLabels[employee.termination_type] ? $t(terminationLabels[employee.termination_type]) : "-" }}
            </span>
          </div>
          <div
            v-if="employee.termination_reason"
            class="d-flex flex-column flex-grow-1"
          >
            <span class="fs-caption text-medium-emphasis">
              {{ $t("Reason") }}
            </span>
            <span>{{ employee.termination_reason }}</span>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.value-blur {
  cursor: not-allowed;
  filter: blur(5px);
  user-select: none;
}
.info-row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 10px 20px;
  font-size: 0.875rem;
}
.info-row + .info-row {
  border-block-start: 1px solid
    rgba(var(--v-border-color), var(--v-border-opacity));
}
.info-row--total {
  background: rgba(var(--v-theme-primary), 0.06);
}
.termination {
  border-inline-start: 4px solid rgb(var(--v-theme-error));
}

/* Sizes only — keep the app font so Khmer renders with kantumruy */
.fs-title {
  font-size: 1rem;
}
.fs-total {
  font-size: 1.125rem;
}
.fs-label {
  font-size: 0.875rem;
}
.fs-caption {
  font-size: 0.8125rem;
}
</style>
