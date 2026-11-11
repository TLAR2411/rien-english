<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { api } from "@/utils/api";
import { auth } from "@/utils/auth";
import { requiredValidator } from "@/@core/utils/validators";
import { getBranches, getRoles } from "@/services/dataService";
import formatContact from "@/utils/formater/formatContact";
import AppDialog from "@/components/AppDialog.vue";
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";

/**
 * Approve a user request: the account is made from the employee's own details
 * (names, gender, birth date, contact, national ID, branch, position, work
 * start date), so only what the employee record cannot answer is asked here -
 * role, default system, who they report to and which branches they manage.
 */
const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
  // The request row, with its employee (branch and position loaded)
  itemData: { type: Object, default: () => ({}) },
});
const emit = defineEmits(["update:isDialogVisible", "onReload"]);

const employee = computed(() => props.itemData?.employee || {});

const isLoading = ref(false);
const roles = ref([]);
const branches = ref([]);
const users = ref([]);

const formData = ref({
  role_id: null,
  default_part: "admin",
  under_user_id: null,
  manage_branch: 1,
  _branch_id: [],
  // Asked only when the employee record has none (both are required for a user)
  name_en: null,
  dob: null,
});

// System the user lands on after login (users.default_part)
const defaultParts = [
  { name: "HR System", value: "hr" },
  { name: "Admin System", value: "admin" },
];

const manageBranch = [
  { name: "មួយសាខា", value: 1 },
  { name: "ច្រើនសាខា", value: 2 },
  { name: "គ្រប់សាខា", value: 3 },
  { name: "លើកលែងសាខា", value: 4 },
];

const needsBranches = computed(
  () => formData.value.manage_branch == 2 || formData.value.manage_branch == 4,
);
watch(needsBranches, (needed) => {
  if (!needed) formData.value._branch_id = [];
});

onMounted(async () => {
  try {
    isLoading.value = true;
    const [dataRoles, dataBranches, resUsers] = await Promise.all([
      getRoles(),
      getBranches(),
      // People this user can report to, in the employee's branch
      api.get("users-all-under-users", {
        params: { choose_branch_id: employee.value.branch_id },
      }),
    ]);
    roles.value = dataRoles;
    branches.value = dataBranches;
    users.value = resUsers.data?.data || [];
  } catch (error) {
    console.error("Failed to load the approve form:", error);
  } finally {
    isLoading.value = false;
  }
});

const onSubmit = async (refForm) => {
  if (isLoading.value) return;
  const { valid } = (await refForm) || {};
  if (!valid) return;

  try {
    isLoading.value = true;
    const res = await api.post("users", {
      // From the employee
      employee_id: employee.value.id,
      name_kh: employee.value.name_kh,
      name_en: employee.value.name_en || formData.value.name_en,
      gender: employee.value.gender || "male",
      dob: employee.value.dob || formData.value.dob,
      contact: employee.value.contact,
      national_id_number: employee.value.national_id_number,
      national_id_issue_date: employee.value.national_id_issue_date,
      join_date: employee.value.hired_date,
      position_id: employee.value.position_id,
      // Chosen here
      role_id: formData.value.role_id,
      default_part: formData.value.default_part,
      under_user_id: formData.value.under_user_id,
      manage_branch: formData.value.manage_branch,
      _branch_id: formData.value._branch_id,
    });

    if (res.data.status) {
      emit("onReload");
      emit("update:isDialogVisible", false);
    }
  } catch (error) {
    console.error("Failed to create the user:", error);
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <AppDialog
    title="Approve User Request"
    title-icon="tabler-user-check"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="(v) => $emit('update:isDialogVisible', v)"
    :width="620"
    :loading="isLoading"
    is-footer
    is-submit
    submit-text="Approve"
    submit-icon="tabler-user-check"
    @on-submit="onSubmit"
  >
    <!-- Who the account is for; these details come from the employee record -->
    <div class="employee">
      <div class="d-flex align-center">
        <AppName
          size="lg"
          :title="employee.name_kh"
          :sub-title="employee.code"
          :image="employee.image_path"
        />
      </div>
      <div class="employee__facts">
        <span v-if="employee.name_en">
          <VIcon icon="tabler-user" size="15" />{{ employee.name_en }}
        </span>
        <span>
          <VIcon icon="tabler-building" size="15" />
          {{ employee.branch?.name_kh || "-" }}
        </span>
        <span>
          <VIcon icon="tabler-id-badge-2" size="15" />
          {{ employee.position?.name_kh || "-" }}
        </span>
        <span v-if="employee.contact">
          <VIcon icon="tabler-phone" size="15" />
          {{ formatContact(employee.contact) }}
        </span>
      </div>
    </div>

    <VRow class="mt-1">
      <!-- A user needs these two; asked only when the employee has none -->
      <VCol v-if="!employee.name_en" cols="12" sm="6">
        <AppTextField
          v-model="formData.name_en"
          label="Name English"
          :rules="[requiredValidator]"
          autocomplete="off"
        />
      </VCol>
      <VCol v-if="!employee.dob" cols="12" sm="6">
        <AppDateTimePicker
          v-model="formData.dob"
          label="Date of birth"
          :rules="[requiredValidator]"
          :config="{ allowInput: true }"
          autocomplete="off"
        />
      </VCol>

      <VCol cols="12" sm="6">
        <AppAutocomplete
          v-model="formData.role_id"
          label="Roles"
          :items="roles"
          item-title="display_name"
          item-value="id"
          :rules="[requiredValidator]"
          autocomplete="off"
        >
          <template #item="{ props: itemProps, item }">
            <VListItem
              v-bind="itemProps"
              :disabled="item.raw.id === 1 && auth().user.is_super == false"
            />
          </template>
        </AppAutocomplete>
      </VCol>
      <VCol cols="12" sm="6">
        <AppSelect
          v-model="formData.default_part"
          :items="defaultParts"
          :item-title="(item) => $t(item.name)"
          item-value="value"
          label="Default System"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" sm="6">
        <AppAutocomplete
          v-model="formData.under_user_id"
          label="Under User"
          :items="users"
          :item-title="
            (item) =>
              item.role_abbr
                ? `${item.name_kh} (${item.role_abbr})`
                : item.name_kh
          "
          item-value="id"
          clearable
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" sm="6">
        <AppAutocomplete
          v-model="formData.manage_branch"
          label="Manage Branch"
          :items="manageBranch"
          item-title="name"
          item-value="value"
          autocomplete="off"
        />
      </VCol>
      <VCol v-if="needsBranches" cols="12">
        <AppAutocomplete
          v-model="formData._branch_id"
          label="Choose Branches"
          :items="branches"
          item-title="name_kh"
          item-value="id"
          :rules="[(v) => (v && v.length > 0) || $t('This field is required')]"
          multiple
          closable-chips
          chips
          autocomplete="off"
        />
      </VCol>
    </VRow>
  </AppDialog>
</template>

<style scoped>
.employee {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  background: rgba(var(--v-theme-primary), 0.05);
  padding: 12px 14px;
}

.employee__facts {
  display: flex;
  flex-wrap: wrap;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 13px;
  gap: 4px 16px;
  margin-block-start: 10px;
}

.employee__facts span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
</style>
