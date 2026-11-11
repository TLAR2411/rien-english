<script setup>
import AppDialog from "@/components/AppDialog.vue";
import { requiredValidator } from "@/@core/utils/validators";

// Create / edit a level. The list saves it (emits "save").
const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
  level: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["update:isDialogVisible", "save"]);

const emptyForm = () => ({ code: "", name: "", name_km: "" });
const form = ref(emptyForm());
const isEdit = computed(() => !!form.value.id);

watch(
  () => [props.isDialogVisible, props.level],
  () => (form.value = { ...emptyForm(), ...props.level }),
  { immediate: true },
);

const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (valid) emit("save", { ...form.value, code: form.value.code.toUpperCase() });
};
</script>

<template>
  <AppDialog
    :title="isEdit ? 'Edit Level' : 'New Level'"
    title-icon="tabler-stairs"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="(v) => emit('update:isDialogVisible', v)"
    :width="520"
    is-footer
    is-submit
    :submit-text="isEdit ? 'Save' : 'Create'"
    submit-icon="tabler-device-floppy"
    submit-color="primary"
    :loading="loading"
    @on-submit="onSubmit"
  >
    <VRow>
      <VCol cols="12" sm="4">
        <AppTextField v-model="form.code" placeholder="A1" maxlength="10" :rules="[requiredValidator]">
          <template #label>{{ $t("Code") }} <span class="text-error">*</span></template>
        </AppTextField>
      </VCol>
      <VCol cols="12" sm="8">
        <AppTextField v-model="form.name" placeholder="Beginner" :rules="[requiredValidator]">
          <template #label>{{ $t("Name English") }} <span class="text-error">*</span></template>
        </AppTextField>
      </VCol>
      <VCol cols="12">
        <AppTextField v-model="form.name_km" placeholder="កម្រិតដំបូង">
          <template #label>{{ $t("Name Khmer") }}</template>
        </AppTextField>
      </VCol>
    </VRow>
  </AppDialog>
</template>
