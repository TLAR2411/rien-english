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
});

const onFormSubmit = debounce(async (refForm) => {
  const { valid } = await refForm;
  if (valid) {
    let result = await showDialog({
      title: t("Do you want to change this user username?"),
      icon: "warning",
      confirmColor: "error",
    });

    if (result) {
      try {
        isLoading.value = true;

        const res = await api.post(
          "users-change-username-email",
          formData.value,
        );

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
    title="Username"
    icon="tabler-user"
    :is-dialog-visible="isDialogVisible"
    isUpdate
    :loading="isLoading"
    @on-close-dialog="onCloseDialog"
    @on-submit="onFormSubmit"
  >
    <VRow>
      <VCol cols="12">
        <div class="border pa-3 rounded">
          <div class="d-flex flex-row align-center">
            <VAvatar rounded :size="50" border>
              <VImg
                v-if="userInfo?.image_path"
                :src="getImageUrl(userInfo?.image_path)"
                @click="showImage(null)"
              />
              <VImg v-else :src="avatar1" @click="showImage(null)" />
            </VAvatar>
            <div class="d-flex flex-column ml-5 align-start">
              <span style="font-size: 14px"
                >{{ userInfo?.name_kh }}
                <template v-if="userInfo?.village?.name_kh">
                  <VChip size="small">
                    {{ userInfo?.village?.name_kh }}
                  </VChip></template
                ></span
              >
              <span class="text-primary" style="font-size: 14px">{{
                userInfo.code
              }}</span>
            </div>
          </div>
        </div>
      </VCol>
      <VCol cols="12" sm="6" class="pt-0">
        <AppTextField label="Username" v-model="formData.username" />
      </VCol>
      <VCol cols="12" sm="6" class="pt-0">
        <AppTextField label="Email" v-model="formData.email" />
      </VCol>
    </VRow>
  </AppAddEditDialog>
</template>
