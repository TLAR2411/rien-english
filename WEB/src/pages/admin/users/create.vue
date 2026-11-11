<script setup>
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { requiredValidator } from "@/@core/utils/validators";
import AppCard from "@/components/AppCard.vue";
import { api } from "@/utils/api";
import { app } from "@/utils/app";
import {
  getBranches,
  getPositions,
  getRoles,
  // getUnderUsers,
  getUsers,
} from "@/services/dataService";
import { computed, onMounted, watch } from "vue";
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import formatContact from "@/utils/formater/formatContact";
import { auth } from "@/utils/auth";
import { useSettingStore } from "@/stores/settingStore";

definePage({
  meta: {
    title: "Users",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "add-users",
    navActiveLink: "admin-users",
  },
});

const formData = ref({
  manage_branch: 1,
  gender: "male",
  default_part: "admin",
  email_type: "@gmail.com",
});

const restFormData = () =>
  (formData.value = {
    manage_branch: 1,
    gender: "male",
    default_part: "admin",
    email_type: "@gmail.com",
  });

const gender = [
  { name: "ប្រុស", value: "male" },
  { name: "ស្រី", value: "female" },
];

// System the user lands on after login (users.default_part)
const defaultParts = [
  { name: "HR System", value: "hr" },
  { name: "Admin System", value: "admin" },
];

const email = [
  { name: "@gmal.com", value: "@gmal.com" },
  { name: "@outlook.com", value: "@outlook.com" },
  { name: "@hotmail.com", value: "@hotmail.com" },
];

const manageBranch = [
  { name: "មួយសាខា", value: 1 },
  { name: "ច្រើនសាខា", value: 2 },
  { name: "គ្រប់សាខា", value: 3 },
  { name: "លើកលែងសាខា", value: 4 },
];

const route = useRoute();
const router = useRouter();
// Opened from a user request: the account is made for this employee
const requestEmployee = ref(null);

// The new user's branch: the employee's when made from a request, otherwise
// the one picked in the navbar
const settingStore = useSettingStore();
const userBranchId = computed(
  () => requestEmployee.value?.branch_id ?? settingStore.branch_id,
);

const isLoading = ref(false);
const branches = ref([]);
const roles = ref([]);
const users = ref([]);
const positions = ref([]);
const provinces = ref([...app().provinces]);
const districts = ref([]);
const communes = ref([]);
const villages = ref([]);

const onSubmit = async () => {
  try {
    isLoading.value = true;
    const res = await api.post("users", formData.value);
    if (res.data.status) {
      // From a user request: it is closed now, go back to the others
      if (requestEmployee.value) {
        router.push({ name: "admin-user-requests" });
        return;
      }
      restFormData();
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  const [dataBranches, dataRoles, dataPositions] =
    await Promise.all([
      getBranches(),
      getRoles(),
      getPositions(),
    ]);

  roles.value = dataRoles;
  positions.value = dataPositions;
  branches.value = dataBranches;
  // users.value = dataUsers;

  await fillFromEmployee();
});

// ?employee_id=…: start the form from the employee's details and send the
// id along, so the new account is linked to the employee and the request closed
const fillFromEmployee = async () => {
  const employeeId = route.query.employee_id;
  if (!employeeId) return;

  try {
    isLoading.value = true;
    const res = await api.get("user-access-requests-employee", {
      params: { employee_id: employeeId },
    });
    const employee = res.data?.data;
    if (!res.data?.status || !employee) return;

    requestEmployee.value = employee;
    formData.value = {
      ...formData.value,
      employee_id: employee.id,
      name_kh: employee.name_kh,
      name_en: employee.name_en,
      gender: employee.gender || "male",
      dob: employee.dob,
      contact: employee.contact,
      national_id_number: employee.national_id_number,
      national_id_issue_date: employee.national_id_issue_date,
      join_date: employee.hired_date,
      position_id: employee.position_id,
    };
  } catch (error) {
    console.error("Failed to load the employee:", error);
  } finally {
    isLoading.value = false;
  }
};

const getUnderUsers = async () => {
  try {
    const res = await api.get("users-all-under-users", {
      params: {
        choose_branch_id: userBranchId.value,
      },
    });

    if (res.data.status) {
      users.value = res.data.data;
    }
  } catch (error) {}
};

watch(
  userBranchId,
  (newVal) => {
    if (newVal) {
      getUnderUsers();
    }
  },
  { immediate: true },
);

// Address Watch
watch(
  () => formData.value.province_code,
  (newVal, oldValue) => {
    if (newVal) {
      formData.value.district_code = null;
      formData.value.commune_code = null;
      formData.value.village_code = null;

      districts.value = app().districts.filter(
        (v) => v.province_code === parseInt(newVal),
      );
      communes.value = [];
      villages.value = [];
    }
  },
);

watch(
  () => formData.value.district_code,
  (newVal, oldValue) => {
    if (newVal) {
      formData.value.commune_code = null;
      formData.value.village_code = null;

      communes.value = app().communes.filter(
        (v) => v.district_code === parseInt(newVal),
      );
      villages.value = [];
    }
  },
);

watch(
  () => formData.value.commune_code,
  (newVal, oldValue) => {
    if (newVal) {
      formData.value.village_code = null;

      villages.value = app().villages.filter(
        (v) => v.commune_code === parseInt(newVal),
      );
    }
  },
);

watch(
  () => formData.value.village_code,
  (newVal, oldValue) => {
    if (newVal) {
      if (formData.value.province_code == null) {
        const communeCode = app().villages.find(
          (v) => v.code === parseInt(newVal),
        ).commune_code;
        const districtCode = app().communes.find(
          (v) => v.code === parseInt(communeCode),
        ).district_code;
        const provinceCode = app().districts.find(
          (v) => v.code === parseInt(districtCode),
        ).province_code;

        formData.value.province_code = provinceCode;
        formData.value.district_code = districtCode;
        formData.value.commune_code = communeCode;
        formData.value.village_code = newVal;
      }
    }
  },
);
</script>

<template>
  <AppCard
    title="Create User"
    title-icon="tabler-user-circle"
    is-submit
    :is-check-branch="!requestEmployee"
    :loading="isLoading"
    @on-submit="onSubmit"
  >
    <!-- Made for an employee (from a user request) -->
    <VAlert
      v-if="requestEmployee"
      type="info"
      variant="tonal"
      density="compact"
      icon="tabler-user-plus"
      class="mb-4"
    >
      {{ $t("Creating a user for employee") }}:
      <b>{{ requestEmployee.name_kh }}</b> ({{ requestEmployee.code }})
    </VAlert>
    <VRow>
      <!--------Personal Information---------->
      <AppLabel title="Personal Information" />
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField
          v-model="formData.name_kh"
          label="Name Khmer"
          :rules="[requiredValidator]"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField
          v-model="formData.name_en"
          label="Name English"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppSelect
          v-model="formData.gender"
          :items="gender"
          item-title="name"
          item-value="value"
          label="Gender"
          :rules="[requiredValidator]"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppDateTimePicker
          v-model="formData.dob"
          label="Date of birth"
          :config="{
            allowInput: true,
          }"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField
          v-model="formData.contact"
          label="Contact"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField
          v-model="formData.national_id_number"
          label="National ID Number"
          hint="9 numbers only"
          maxlength="9"
          numbers-only
          autocomplete="off"
        />
      </VCol>
      <!-- <VCol cols="12" lg="3" md="4" sm="6">
        <AppDateTimePicker
          v-model="formData.national_id_issue_date"
          label="National ID Issue Date"
          autocomplete="off"
          :config="{
            allowInput: true,
          }"
        />
      </VCol> -->

      <!--------Address---------->
      <AppLabel title="Address" />
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.province_code"
          label="Provinces"
          :items="provinces"
          item-value="code"
          item-title="name_kh"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.district_code"
          label="Districts"
          :items="districts"
          item-value="code"
          item-title="name_kh"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.commune_code"
          label="Communes"
          :items="communes"
          item-value="code"
          item-title="name_kh"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.village_code"
          label="Villages"
          :items="villages"
          item-value="code"
          item-title="name_kh"
          autocomplete="off"
        />
      </VCol>

      <!--------Personal Income---------->
      <AppLabel title="Job Information" />
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.role_id"
          label="Roles"
          :items="roles"
          item-title="display_name"
          item-value="id"
          autocomplete="off"
        >
          <template #item="{ props, item }">
            <VListItem
              v-bind="props"
              :disabled="item.raw.id === 1 && auth().user.is_super == false"
            />
          </template>
        </AppAutocomplete>
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppSelect
          v-model="formData.default_part"
          :items="defaultParts"
          :item-title="(item) => $t(item.name)"
          item-value="value"
          label="Default System"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.position_id"
          label="Positions"
          :items="positions"
          :item-title="
            (item) => {
              return `${item.name_kh} (${item.abbr})`;
            }
          "
          item-value="id"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.under_user_id"
          label="Under User"
          :items="users"
          :item-title="
            (item) => {
              return item.role_abbr
                ? `${item.name_kh} (${item.role_abbr})`
                : item.name_kh;
            }
          "
          item-value="id"
          autocomplete="off"
          :disabled="!userBranchId"
        >
          <template #item="{ props, item }">
            <VListItem
              v-bind="props"
              :title="`${item?.raw?.name_kh}  (${item?.raw?.role_abbr})`"
              :subtitle="`${formatContact(item?.raw.contact)}`"
            >
              <template #prepend>
                <AppAvatar
                  :title="item?.raw?.name_kh"
                  :image="item?.raw?.image_path"
                  :size="40"
                  :is-show-full-image="false"
                />
                &nbsp;&nbsp;
              </template>
            </VListItem>
          </template>
        </AppAutocomplete>
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppDateTimePicker
          v-model="formData.join_date"
          label="Work Date"
          :config="{
            allowInput: true,
          }"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.manage_branch"
          label="Manage Branch"
          :items="manageBranch"
          item-title="name"
          item-value="value"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData._branch_id"
          label="Choose Branches"
          :items="branches"
          :disabled="formData.manage_branch != 2 && formData.manage_branch != 4"
          item-title="name_kh"
          item-value="id"
          multiple
          eager
          closable-chips
          chips
          autocomplete="off"
        />
      </VCol>
    </VRow>
  </AppCard>
</template>

<style scoped></style>
