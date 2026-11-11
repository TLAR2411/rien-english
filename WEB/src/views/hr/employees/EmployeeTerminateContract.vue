<script setup>
import { ref, computed, onMounted } from "vue";
import { api } from "@/utils/api.js";
import formatCurrency from "@/utils/formater/formatCurrency.js";
import AppDateTimePicker from "@core/components/app-form-elements/AppDateTimePicker.vue";
import AppTextarea from "@core/components/app-form-elements/AppTextarea.vue";
import { useDialog } from "@/composables/useDialog.js";
import { useI18n } from "vue-i18n";
import moment from "moment-timezone";
import AppDialog from "@/components/AppDialog.vue";

const props = defineProps({
  itemData: {
    type: Object,
    required: false,
    default: () => ({}),
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  // Edit an existing termination (former employees list) instead of
  // terminating a working employee
  isEdit: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["onReload", "update:isDialogVisible"]);

const { t } = useI18n();
const { showDialog } = useDialog();
const isLoading = ref(false);
const employee = ref({});

const today = moment().tz("Asia/Phnom_Penh").format("YYYY-MM-DD");

const formData = ref({
  terminated_date: today,
  last_working_date: today,
  termination_type: "dismissal",
  termination_reason: null,
});

const terminationTypes = [
  { value: "dismissal", title: "Dismissal", icon: "tabler-user-x" },
  { value: "resignation", title: "Resignation", icon: "tabler-door-exit" },
  {
    value: "end_of_contract",
    title: "End of Contract",
    icon: "tabler-calendar-x",
  },
  { value: "retirement", title: "Retirement", icon: "tabler-armchair" },
  {
    value: "mutual_agreement",
    title: "Mutual Agreement",
    icon: "tabler-heart-handshake",
  },
  { value: "other", title: "Other", icon: "tabler-dots" },
];

const isAlreadyTerminated = computed(() => !!employee.value.terminated_date);
// Terminate mode blocks a second termination; edit mode always shows the form
const isLocked = computed(() => !props.isEdit && isAlreadyTerminated.value);

const formatDate = (date) => (date ? moment(date).format("DD-MM-YYYY") : "N/A");

// Length of service from hired date to the termination date
const serviceLength = computed(() => {
  const hired = employee.value.hired_date;
  const end = formData.value.terminated_date;
  if (!hired || !end || moment(end).isBefore(hired)) return "N/A";

  const years = moment(end).diff(hired, "years");
  const months = moment(end).diff(moment(hired).add(years, "years"), "months");
  const parts = [];
  if (years) parts.push(`${years} ${t("Years")}`);
  if (months || !years) parts.push(`${months} ${t("Months")}`);
  return parts.join(" ");
});

const employeeDetails = computed(() => [
  {
    label: "Position",
    icon: "tabler-id-badge-2",
    value: employee.value.position?.name_kh || "N/A",
  },
  {
    label: "Branch",
    icon: "tabler-building",
    value: employee.value.branch?.name_kh || "N/A",
  },
  {
    label: "Hired Date",
    icon: "tabler-calendar-plus",
    value: formatDate(employee.value.hired_date),
  },
  {
    label: "Contract Type",
    icon: "tabler-file-description",
    value: employee.value.contract_type || "N/A",
  },
  {
    label: "Length of Service",
    icon: "tabler-hourglass",
    value: serviceLength.value,
  },
  {
    label: "Gross Salary",
    icon: "tabler-wallet",
    value: employee.value.salary?.gross_salary
      ? formatCurrency(employee.value.salary.gross_salary)
      : "N/A",
  },
]);

// ---------- Validation ----------
const lastWorkingDateRule = (value) => {
  if (!value) return true;
  if (
    formData.value.terminated_date &&
    value > formData.value.terminated_date
  ) {
    return t("Last working date cannot be after the terminate date");
  }
  if (employee.value.hired_date && value < employee.value.hired_date) {
    return t("Last working date cannot be before the hired date");
  }
  return true;
};

// ---------- Data ----------
const loadEmployee = async () => {
  if (!props.itemData?.employee_id) return;
  isLoading.value = true;
  try {
    const res = await api.get("employees-show", {
      params: { id: props.itemData.employee_id },
    });
    if (res.data.status) {
      employee.value = res.data.data;

      // Edit: start from what is saved now
      if (props.isEdit) {
        const e = employee.value;
        formData.value = {
          terminated_date: e.terminated_date || e.last_working_date || today,
          last_working_date: e.last_working_date || e.terminated_date || today,
          termination_type: e.termination_type || "other",
          termination_reason: e.termination_reason || null,
        };
      }
    }
  } catch (error) {
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(loadEmployee);

const onCloseDialog = () => {
  emit("update:isDialogVisible", false);
};

const onFormSubmit = async (validation) => {
  if (isLoading.value || isLocked.value) return;

  const { valid } = (await validation) || {};
  if (!valid) return;

  const confirmed = await showDialog({
    title: props.isEdit
      ? `${t("Do you want to save these changes?")} (${employee.value.name_kh})`
      : `${t("Do you want to terminate this employee?")} (${employee.value.name_kh})`,
    icon: "warning",
    confirmColor: props.isEdit ? "primary" : "error",
  });
  if (!confirmed) return;

  isLoading.value = true;
  try {
    const url = props.isEdit ? "employees-terminate-update" : "employees-terminate";
    const res = await api.patch(
      `${url}/${props.itemData.employee_id}`,
      formData.value,
    );
    if (res.data.status) {
      emit("onReload");
      onCloseDialog();
    }
  } catch (error) {
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <AppDialog
    :title="isEdit ? 'Edit Termination' : 'Terminate'"
    :title-icon="isEdit ? 'tabler-pencil' : 'tabler-file-x'"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="
      (value) => $emit('update:isDialogVisible', value)
    "
    :width="700"
    is-footer
    is-submit
    :submit-text="isEdit ? 'Save' : 'Terminate'"
    :submit-icon="isEdit ? 'tabler-device-floppy' : 'tabler-file-x'"
    :submit-color="isEdit ? 'primary' : 'error'"
    :loading="isLoading"
    @on-submit="onFormSubmit"
  >
    <VRow>
      <!-- Employee summary -->
      <VCol cols="12">
        <div class="border rounded">
          <div class="d-flex align-center gap-3 pa-4">
            <VAvatar color="primary" variant="tonal" size="52" rounded>
              <span class="fs-initial font-weight-bold">
                {{ employee.name_kh?.charAt(0) || "?" }}
              </span>
            </VAvatar>
            <div class="d-flex flex-column">
              <span class="fs-name text-primary">
                {{ employee.name_kh || "..." }}
              </span>
              <span class="fs-caption text-medium-emphasis">
                {{ employee.code || "" }}
                <template v-if="employee.name_en">
                  · {{ employee.name_en }}
                </template>
              </span>
            </div>
            <VSpacer />
            <VChip
              v-if="employee.id"
              size="small"
              :color="isAlreadyTerminated ? 'error' : 'success'"
              variant="tonal"
            >
              {{ isAlreadyTerminated ? $t("Terminated") : $t("Working") }}
            </VChip>
          </div>
          <VDivider />
          <VRow no-gutters class="pa-2">
            <VCol
              v-for="detail in employeeDetails"
              :key="detail.label"
              cols="12"
              sm="6"
              class="pa-2"
            >
              <div class="d-flex align-center gap-2">
                <VIcon :icon="detail.icon" size="18" color="primary" />
                <span class="fs-caption text-medium-emphasis">
                  {{ $t(detail.label) }}
                </span>
                <VSpacer />
                <span class="fs-label">
                  {{ detail.value }}
                </span>
              </div>
            </VCol>
          </VRow>
        </div>
      </VCol>

      <!-- Already terminated: show the existing record instead of the form -->
      <VCol v-if="isLocked" cols="12">
        <VAlert type="error" variant="tonal">
          {{ $t("This employee has already been terminated") }}
          ({{ formatDate(employee.terminated_date) }})
        </VAlert>
      </VCol>

      <template v-else>
        <VCol cols="12" sm="6">
          <AppDateTimePicker
            v-model="formData.terminated_date"
            label="Terminate Date"
            :rules="[requiredValidator]"
            autocomplete="off"
          />
        </VCol>
        <VCol cols="12" sm="6">
          <AppDateTimePicker
            v-model="formData.last_working_date"
            label="Last Working Date"
            :rules="[requiredValidator, lastWorkingDateRule]"
            autocomplete="off"
          />
        </VCol>
        <VCol cols="12">
          <span class="fs-label d-block mb-1">
            {{ $t("Termination Type") }}
          </span>
          <VRadioGroup
            v-model="formData.termination_type"
            :rules="[requiredValidator]"
            hide-details="auto"
            class="termination-types"
          >
            <VRow dense>
              <VCol
                v-for="type in terminationTypes"
                :key="type.value"
                cols="6"
                sm="4"
              >
                <!-- Whole tile is clickable; the real radio is visually hidden -->
                <label
                  class="type-tile d-flex align-center gap-2 rounded px-3 py-2 cursor-pointer"
                  :class="{
                    'type-tile--active':
                      formData.termination_type === type.value,
                  }"
                >
                  <VRadio :value="type.value" class="type-tile__radio" />
                  <VIcon :icon="type.icon" size="20" class="type-tile__icon" />
                  <span class="fs-label">{{ $t(type.title) }}</span>
                  <VIcon
                    v-if="formData.termination_type === type.value"
                    icon="tabler-circle-check-filled"
                    size="18"
                    color="error"
                    class="ms-auto"
                  />
                </label>
              </VCol>
            </VRow>
          </VRadioGroup>
        </VCol>
        <VCol cols="12">
          <AppTextarea
            v-model="formData.termination_reason"
            rows="3"
            counter="1000"
            maxlength="1000"
          >
            <template #label>{{ $t("Reason") }}</template>
          </AppTextarea>
        </VCol>
      </template>
    </VRow>
  </AppDialog>
</template>

<style scoped>
/* Termination type tiles */
.type-tile {
  position: relative;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  min-block-size: 44px;
  transition:
    border-color 0.15s ease,
    background-color 0.15s ease;
}
.type-tile:hover {
  border-color: rgba(var(--v-theme-error), 0.5);
}
.type-tile__icon {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}
.type-tile--active {
  border-color: rgb(var(--v-theme-error));
  background: rgba(var(--v-theme-error), 0.06);
}
.type-tile--active .type-tile__icon {
  color: rgb(var(--v-theme-error));
}
/* Keep the radio in the DOM for the form value and keyboard, but hide it */
.type-tile__radio {
  position: absolute;
  inline-size: 1px;
  block-size: 1px;
  opacity: 0;
  overflow: hidden;
  pointer-events: none;
}

/* Sizes only — keep the app font so Khmer renders with kantumruy */
.fs-initial {
  font-size: 1.5rem;
}
.fs-name {
  font-size: 1.125rem;
}
.fs-label {
  font-size: 0.875rem;
}
.fs-caption {
  font-size: 0.8125rem;
}
</style>
