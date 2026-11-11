<script setup>
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { requiredValidator } from "@/@core/utils/validators";
import AppCard from "@/components/AppCard.vue";
import AppImageUpload from "@/components/AppImageUpload.vue";
import getImageUrl from "@/utils/image/getImageUrl";
import AppGoogleMap from "@/components/AppGoogleMap.vue";
import { api } from "@/utils/api";
import { app } from "@/utils/app";
import {
  getDepartments,
  getPositions,
  getUsers,
} from "@/services/dataService";
import { onMounted, watch } from "vue";
import { ref } from "vue";

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

const restFormData = () =>
  (formData.value = {
    gender: "male",
  });

const gender = [
  { name: "ប្រុស", value: "male" },
  { name: "ស្រី", value: "female" },
];

const isLoading = ref(false);
const users = ref([]);
const positions = ref([]);
const positionsByDepartment = ref([]);
const provinces = ref([...app().provinces]);
const departments = ref([]);
const districts = ref([]);
const communes = ref([]);
const villages = ref([]);

const onSubmit = async () => {
  try {
    isLoading.value = true;
    const res = await api.post("employees", formData.value);
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
  const [dataPositions, dataUsers, dataDepartments] =
    await Promise.all([
      getPositions(),
      getUsers(),
      getDepartments(),
    ]);

  positions.value = dataPositions;
  users.value = dataUsers;
  departments.value = dataDepartments;
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

watch(
  () => formData.value.department_id,
  (newVal) => {
    if (newVal) {
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
    title="Create Employee"
    title-icon="tabler-users"
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
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="2" md="4" sm="6">
        <AppDateTimePicker
          v-model="formData.dob"
          label="Date of birth"
          :config="{
            allowInput: true,
          }"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="2" md="4" sm="6">
        <AppTextField
          v-model="formData.contact"
          label="Contact"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField
          v-model="formData.parent_contact"
          label="Parent Contact"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppTextField
          v-model="formData.bank_account_number"
          label="Bank Account"
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
      <VCol cols="12" lg="3" md="4" sm="6">
        <AppDateTimePicker
          v-model="formData.national_id_issue_date"
          label="National ID Issue Date"
          autocomplete="off"
        />
      </VCol>

      <!--------Address---------->
      <AppLabel title="Address" />
      <VCol cols="12" lg="3" md="3" sm="6">
        <AppAutocomplete
          v-model="formData.province_code"
          label="Provinces"
          :items="provinces"
          item-value="code"
          item-title="name_kh"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="3" sm="6">
        <AppAutocomplete
          v-model="formData.district_code"
          label="Districts"
          :items="districts"
          item-value="code"
          item-title="name_kh"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="3" sm="6">
        <AppAutocomplete
          v-model="formData.commune_code"
          label="Communes"
          :items="communes"
          item-value="code"
          item-title="name_kh"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="3" sm="6">
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
      <VCol cols="12" lg="3" md="3" sm="6">
        <AppAutocomplete
          v-model="formData.department_id"
          label="Department"
          :items="departments"
          item-title="name_kh"
          item-value="id"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" lg="3" md="3" sm="6">
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
          autocomplete="off"
        />
      </VCol>
      <!-- <VCol cols="12" lg="3" md="3" sm="6">
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
        />
      </VCol> -->
      <VCol cols="12" lg="3" md="3" sm="6">
        <AppDateTimePicker
          v-model="formData.hired_date"
          label="Hired Date"
          autocomplete="off"
        />
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
