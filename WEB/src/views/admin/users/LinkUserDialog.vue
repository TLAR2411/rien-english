<script setup>
import { ref, watch, nextTick } from "vue";
import { debounce } from "lodash";
import AppAddEditDialog from "@/components/AppAddEditDialog.vue";
import { api } from "@/utils/api.js";
import avatar1 from "@images/avatars/avatar-7.png";
import AppTextField from "@core/components/app-form-elements/AppTextField.vue";
import getImageUrl from "@/utils/image/getImageUrl.js";
import { useDialog } from "@/composables/useDialog.js";
import { useI18n } from "vue-i18n";
import CustomCheckboxes from "@/@core/components/app-form-elements/CustomCheckboxes.vue";
import {
  getBranches,
  getPermissions,
  getRoles,
} from "@/services/dataService";
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";

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
  loading: {
    type: Boolean,
    required: false,
    skipCheck: true,
    default: undefined,
  },
});

const { t } = useI18n();
const { showDialog } = useDialog();
const isLoading = ref(false);
const userInfo = ref({});
const roles = ref([]);

const manageBranch = [
  { name: "មួយសាខា", value: 1 },
  { name: "ច្រើនសាខា", value: 2 },
  { name: "គ្រប់សាខា", value: 3 },
  { name: "លើកលែងសាខា", value: 4 },
];
const branches = ref([]);
const inintFormData = () => ({
  username: null,
  email: null,
});

const formData = ref(inintFormData());

const emit = defineEmits([
  "onCreate",
  "onUpdate",
  "onReload",
  "update:isDialogVisible",
]);

const itemData = ref({ ...props.itemData });

watch(
  () => props.itemData,
  (newData) => {
    itemData.value = { ...newData };
  },
  { deep: true },
);

const resetData = () => {
  inintFormData();
};

const initData = async () => {
  const res = await api.get("users-show", {
    params: {
      id: itemData.value.user_id,
    },
  });
  if (res.data.status) {
    userInfo.value = res.data.data;
    formData.value = res.data.data;
  }
};

onMounted(async () => {
  isLoading.value = true;
  await initData();
  isLoading.value = false;

  const [dataRoles, dataBranches] = await Promise.all([
    getRoles(),
    getBranches(),
  ]);

  roles.value = dataRoles;
  branches.value = dataBranches;
});

const onFormSubmit = debounce(async (refForm) => {
  const { valid } = await refForm;
  if (valid) {
    let result = await showDialog({
      title: t("Do you want to create link user?"),
      icon: "warning",
      confirmColor: "error",
    });

    if (result) {
      try {
        isLoading.value = true;

        const res = await api.post("users-link-user", formData.value);

        if (res.data.status) {
          resetData();
          initData();
          emit("onReload");
          onCloseDialog();
        } else {
          console.error("Error with the response:", res.data);
        }
      } catch (error) {
        console.error("Failed to fetch data:", error);
      } finally {
        isLoading.value = false;
      }
    }
  }
}, 500);

const onCloseDialog = () => {
  resetData();
  emit("update:isDialogVisible", false);
};
</script>

<template>
  <AppAddEditDialog
    title="Link User"
    icon="tabler-link"
    :is-dialog-visible="isDialogVisible"
    :loading="isLoading"
    @on-close-dialog="onCloseDialog"
    @on-submit="onFormSubmit"
  >
    <VRow>
      <VCol cols="12" sm="6" class="pt-0">
        <AppAutocomplete
          label="Role"
          v-model="formData.role_id"
          :items="roles"
          item-title="display_name"
          item-value="id"
        />
      </VCol>

      <VCol cols="12" sm="6" class="pt-0">
        <AppAutocomplete
          label="Branch"
          v-model="formData.branch_id"
          :items="branches"
          item-title="name_kh"
          item-value="id"
        />
      </VCol>
      <VCol cols="12" sm="6" class="pt-0">
        <AppSelect
          v-model="formData.manage_branch"
          label="Manage Branch"
          :items="manageBranch"
          item-title="name"
          item-value="value"
          autocomplete="off"
        />
      </VCol>
      <VCol cols="12" class="pt-0">
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
  </AppAddEditDialog>
</template>
