<script setup>
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { requiredValidator } from "@/@core/utils/validators";
import AppCard from "@/components/AppCard.vue";
import AppImageUpload from "@/components/AppImageUpload.vue";
import getImageUrl from "@/utils/image/getImageUrl";
import { api } from "@/utils/api";
import { app } from "@/utils/app";
import {
  getBranches,
  getDepartments,
  getPositions,
  getRoles,
  getUsers,
} from "@/services/dataService";
import { onMounted } from "vue";
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";

definePage({
  meta: {
    title: "Employees",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "add-employees",
    navActiveLink: "hr-employees",
  },
});

const formData = ref({
  gender: "male",
});

const gender = [
  { name: "ប្រុស", value: "male" },
  { name: "ស្រី", value: "female" },
];

const isLoading = ref(false);
const branches = ref([]);
const users = ref([]);
const positions = ref([]);
const departments = ref([]);
const positionsByDepartment = ref([]);
const provinces = ref([...app().provinces]);
const districts = ref([]);
const communes = ref([]);
const villages = ref([]);
const route = useRoute();
const router = useRouter();
const isInitializing = ref(false);

const onSubmit = async () => {
  try {
    isLoading.value = true;
    const res = await api.put("employees", formData.value);
    if (res.data.status) {
      // restFormData();
      router.push({ name: "hr-employees" });
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
    const res = await api.get("employees-show", {
      params: { id: route.query.id },
    });
    if (res.data.status) {
      formData.value = res.data.data;
      formData.value.department_id = res.data.data.position.department_id;
      formData.value.choose_branch_id = res.data.data.branch_id;
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
  const [dataBranches, dataPositions, dataUsers, dataDepartments] =
    await Promise.all([
      getBranches(),
      getPositions(),
      getUsers(),
      getDepartments(),
    ]);

  positions.value = dataPositions;
  branches.value = dataBranches;
  users.value = dataUsers;
  departments.value = dataDepartments;

  initData();
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

  const filterPosition = positions.value.filter(
    (v) => v.department_id == data.position.department_id,
  );
  positionsByDepartment.value = filterPosition;
  // console.log(positions.value);

  if (provinceCode) {
    districts.value = app().districts.filter(
      (v) => v.province_code === provinceCode,
    );
    formData.value.province_code = provinceCode;
  }
  if (districtCode) {
    communes.value = app().communes.filter(
      (v) => v.district_code === districtCode,
    );
    formData.value.district_code = districtCode;
  }
  if (communeCode) {
    villages.value = app().villages.filter(
      (v) => v.commune_code === communeCode,
    );
    formData.value.commune_code = communeCode;
  }
  if (villageCode) {
    formData.value.village_code = villageCode;
  }

  // Force re-render to ensure AppAutocomplete picks up the values
  await nextTick();
};

watch(
  () => formData.value.department_id,
  (newVal) => {
    if (newVal && !isInitializing.value) {
      formData.value.position_id = null;
      positionsByDepartment.value = positions.value.filter(
        (v) => v.department_id == newVal,
      );
    }
  },
);
</script>

<template>
  <AppCard
    title="Update Employee"
    title-icon="tabler-users"
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
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField v-model="formData.name_en" label="Name English" />
      </VCol>
      <VCol cols="12" lg="2" md="4" sm="6">
        <AppSelect
          v-model="formData.gender"
          :items="gender"
          item-title="name"
          item-value="value"
          label="Gender"
          :rules="[requiredValidator]"
        />
      </VCol>
      <VCol cols="12" lg="2" md="4" sm="6">
        <AppDateTimePicker
          v-model="formData.dob"
          label="Date of birth"
          :config="{
            allowInput: true,
          }"
        />
      </VCol>
      <VCol cols="12" lg="2" md="4" sm="6">
        <AppTextField v-model="formData.contact" label="Contact" />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField
          v-model="formData.parent_contact"
          label="Parent Contact"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField
          v-model="formData.bank_account_number"
          label="Bank Account"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField
          v-model="formData.national_id_number"
          label="National ID Number"
          hint="9 numbers only"
          maxlength="9"
          numbers-only
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppDateTimePicker
          v-model="formData.national_id_issue_date"
          label="National ID Issue Date"
        />
      </VCol>

      <!--------Address---------->
      <AppLabel title="Address" />
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.province_code"
          label="Provinces"
          :items="provinces"
          item-value="code"
          item-title="name_kh"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.district_code"
          label="Districts"
          :items="districts"
          item-value="code"
          item-title="name_kh"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.commune_code"
          label="Communes"
          :items="communes"
          item-value="code"
          item-title="name_kh"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.village_code"
          label="Villages"
          :items="villages"
          item-value="code"
          item-title="name_kh"
        />
      </VCol>

      <!--------Personal Income---------->
      <AppLabel title="Job Information" />
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.choose_branch_id"
          label="Branch"
          :items="branches"
          item-title="name_kh"
          item-value="id"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.department_id"
          label="Department"
          :items="departments"
          item-title="name_kh"
          item-value="id"
        />
      </VCol>

      <VCol cols="12" lg="3" md="4" sm="6">
        <AppAutocomplete
          v-model="formData.position_id"
          label="Positions"
          :items="positionsByDepartment"
          :item-title="
            (item) => {
              return `${item.name_kh} (${item.abbr})`;
            }
          "
          item-value="id"
        />
      </VCol>

      <VCol cols="12" lg="3" md="4" sm="6">
        <AppDateTimePicker v-model="formData.hired_date" label="Work Date" />
      </VCol>

      <!--------Photo: 4x6, kept at full size (used on the ID card)---------->
      <AppLabel title="Employee Photo (4x6)" />
      <VCol cols="12">
        <div style="block-size: 270px; inline-size: 180px">
          <AppImageUpload
            v-model="formData.image_path"
            icon="tabler-user"
            :label="$t('Employee Photo (4x6)')"
            :is-loading="isLoading"
            :headline="$t('Employee Photo (4x6)')"
            :support-text="$t('PNG or JPG')"
            :url-resolver="getImageUrl"
            :crop-aspect-ratio="4 / 6"
            :max-dimension="3000"
            :output-quality="0.92"
            style="height: 100%"
          />
        </div>
      </VCol>
    </VRow>
  </AppCard>
</template>
