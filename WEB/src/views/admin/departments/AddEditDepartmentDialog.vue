<script setup>
import { computed, ref, watch } from "vue";
import AppDialog from "@/components/AppDialog.vue";
import { requiredValidator } from "@/@core/utils/validators";

/**
 * Create / edit a department. Saving is done by the list (onCreate / onUpdate
 * events, same as before); the list closes the dialog on success.
 */
const props = defineProps({
  itemData: { type: Object, default: () => ({}) },
  isDialogVisible: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["onCreate", "onUpdate", "update:isDialogVisible"]);

const emptyForm = () => ({ name_kh: "", name_en: "" });

const form = ref({ ...emptyForm(), ...props.itemData });
const isEdit = computed(() => !!form.value.id);

// Re-fill whenever the list hands over another department (or an empty one)
watch(
  () => props.itemData,
  (data) => {
    form.value = { ...emptyForm(), ...data };
  },
);

const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (!valid || props.loading) return;

  emit(isEdit.value ? "onUpdate" : "onCreate", {
    ...form.value,
    name_kh: form.value.name_kh.trim(),
    name_en: form.value.name_en.trim(),
  }, () => {});
};
</script>

<template>
  <AppDialog
    :title="isEdit ? 'Update Department' : 'Create Department'"
    title-icon="tabler-hierarchy-3"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="(v) => $emit('update:isDialogVisible', v)"
    :width="560"
    is-footer
    is-submit
    :submit-text="isEdit ? 'Update' : 'Create'"
    :submit-icon="isEdit ? 'tabler-device-floppy' : 'tabler-plus'"
    :submit-color="isEdit ? 'warning' : 'primary'"
    :loading="loading"
    @on-submit="onSubmit"
  >
    <VRow>
      <VCol cols="12" sm="6">
        <AppTextField v-model="form.name_kh" placeholder="នាយកដ្ឋានប្រតិបត្តិការ" :rules="[requiredValidator]" autofocus>
          <template #label>{{ $t("Name Khmer") }} <span class="text-error">*</span></template>
        </AppTextField>
      </VCol>
      <VCol cols="12" sm="6">
        <AppTextField v-model="form.name_en" placeholder="Operations Department" :rules="[requiredValidator]">
          <template #label>{{ $t("Name English") }} <span class="text-error">*</span></template>
        </AppTextField>
      </VCol>
    </VRow>
  </AppDialog>
</template>
