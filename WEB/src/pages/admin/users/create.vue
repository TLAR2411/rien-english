<script setup>
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { requiredValidator } from "@/@core/utils/validators";
import AppCard from "@/components/AppCard.vue";
import { api } from "@/utils/api";
import { app } from "@/utils/app";
import { getBranches, getRoles } from "@/services/dataService";
import { onMounted, ref, watch } from "vue";
import { auth } from "@/utils/auth";

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

const isLoading = ref(false);
const branches = ref([]);
const roles = ref([]);
const provinces = ref([...app().provinces]);
const districts = ref([]);
const communes = ref([]);
const villages = ref([]);

const onSubmit = async () => {
  try {
    isLoading.value = true;
    const res = await api.post("users", formData.value);
    if (res.data.status) {
      restFormData();
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  const [dataBranches, dataRoles] = await Promise.all([
    getBranches(),
    getRoles(),
  ]);

  roles.value = dataRoles;
  branches.value = dataBranches;
});

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
    is-check-branch
    :loading="isLoading"
    @on-submit="onSubmit"
  >
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
