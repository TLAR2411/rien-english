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
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

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
  { name: "@admin.com", value: "@admin.com" },
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
const coordinates = ref(null);
const error = ref(null);
const route = useRoute();
const router = useRouter();
const isInitializing = ref(false);

const getLocation = () => {
  isLoading.value = true;
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        formData.value.location = `${position.coords.latitude},${position.coords.longitude}`;
        error.value = null;
      },
      (err) => {
        error.value = handleGeoError(err);
        coordinates.value = null;
      },
    );
  } else {
    error.value = "Geolocation is not supported by this browser.";
  }
  isLoading.value = false;
};

const handleGeoError = (err) => {
  switch (err.code) {
    case err.PERMISSION_DENIED:
      return "User denied the request for Geolocation.";
    case err.POSITION_UNAVAILABLE:
      return "Location information is unavailable.";
    case err.TIMEOUT:
      return "The request to get user location timed out.";
    default:
      return "An unknown error occurred.";
  }
};
const onSubmit = async () => {
  try {
    isLoading.value = true;
    const res = await api.put("users", formData.value);
    if (res.data.status) {
      // restFormData();
      router.push({ name: "admin-users" });
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};

const initData = async () => {
  try {
    isLoading.value = true;
    isInitializing.value = true;
    const res = await api.get("users-show", { params: { id: route.query.id } });
    if (res.data.status) {
      formData.value = res.data.data;

      if (res.data.data.email) {
        const email = res.data.data.email.split("@");
        formData.value.email = email[0];
        formData.value.email_type = `@${email[1]}`;
      }

      // console.log(res.data.data.email.split("@"));
      formData.value.dob = res.data.data?.dob;

      formData.value.gender = res.data.data?.gender;
      formData.value.default_part = res.data.data?.default_part || "admin";
      formData.value.contact = res.data.data?.contact;
      formData.value.national_id_number = res.data.data?.national_id_number;
      formData.value.national_id_issue_date =
        res.data.data?.national_id_issue_date;
      formData.value.join_date = res.data.data?.join_date;
      formData.value.choose_branch_id = res.data.data.branch_id;
      // formData.value.village_code = res.data.data?.village_code;
      await initializeAddressFields(res.data.data);
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
    isInitializing.value = false;
  }
};

onMounted(async () => {
  initData();
  const [dataBranches, dataRoles] = await Promise.all([
    getBranches(),
    getRoles(),
  ]);

  roles.value = dataRoles;
  branches.value = dataBranches;
});

// Watchers for cascading location fields
watch(
  () => formData.value.province_code,
  (newVal) => {
    if (newVal && !isInitializing.value) {
      districts.value = app().districts.filter(
        (v) => v.province_code === newVal,
      );
      formData.value.district_code = undefined;
      formData.value.commune_code = undefined;
      formData.value.village_code = undefined;
      communes.value = [];
      villages.value = [];
    }
  },
);

watch(
  () => formData.value.district_code,
  (newVal) => {
    if (newVal && !isInitializing.value) {
      communes.value = app().communes.filter((v) => v.district_code === newVal);
      formData.value.commune_code = undefined;
      formData.value.village_code = undefined;
      villages.value = [];
    }
  },
);

watch(
  () => formData.value.commune_code,
  (newVal) => {
    if (newVal && !isInitializing.value) {
      villages.value = app().villages.filter((v) => v.commune_code === newVal);
      formData.value.village_code = undefined;
    }
  },
);

const initializeAddressFields = async (data) => {
  // Convert codes to numbers to match expected type
  const provinceCode = data.province_code
    ? Number(data.province_code)
    : undefined;
  const districtCode = data.district_code
    ? Number(data.district_code)
    : undefined;
  const communeCode = data.commune_code ? Number(data.commune_code) : undefined;
  const villageCode = data.village_code ? Number(data.village_code) : undefined;

  console.log("Initializing address fields with:", {
    provinceCode,
    districtCode,
    communeCode,
    villageCode,
  });

  if (provinceCode) {
    districts.value = app().districts.filter(
      (v) => v.province_code === provinceCode,
    );
    console.log("Districts after filtering:", districts.value);
    formData.value.province_code = provinceCode;
  }
  if (districtCode) {
    communes.value = app().communes.filter(
      (v) => v.district_code === districtCode,
    );
    console.log("Communes after filtering:", communes.value);
    formData.value.district_code = districtCode;
  }
  if (communeCode) {
    villages.value = app().villages.filter(
      (v) => v.commune_code === communeCode,
    );
    console.log("Villages after filtering:", villages.value);
    formData.value.commune_code = communeCode;
  }
  if (villageCode) {
    formData.value.village_code = villageCode;
  }

  // Force re-render to ensure AppAutocomplete picks up the values
  await nextTick();
};
</script>

<template>
  <AppCard
    title="Edit User"
    title-icon="tabler-user-circle"
    is-submit
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
          :rules="[requiredValidator]"
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
          v-model="formData.branch_id"
          label="Branch"
          :items="branches"
          item-title="name_kh"
          item-value="id"
          autocomplete="off"
        />
      </VCol>

      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.role_id"
          label="Roles"
          :items="roles"
          item-title="display_name"
          item-value="id"
          autocomplete="off"
        />
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
          autocomplete="off"
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
