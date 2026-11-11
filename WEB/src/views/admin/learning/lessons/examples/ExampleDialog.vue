<script setup>
import AppDialog from "@/components/AppDialog.vue";
import AudioField from "@/components/learning/AudioField.vue";
import { requiredValidator } from "@/@core/utils/validators";

// Add / edit one example sentence. The section saves it (emits "save").
const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
  example: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["update:isDialogVisible", "save"]);

const emptyForm = () => ({ sentence: "", translation_km: "", audio_url: "" });
const form = ref(emptyForm());
const isEdit = computed(() => !!form.value.id);

watch(
  () => [props.isDialogVisible, props.example],
  () => (form.value = { ...emptyForm(), ...props.example }),
  { immediate: true },
);

const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (valid) emit("save", { ...form.value, audio_url: form.value.audio_url || null });
};
</script>

<template>
  <AppDialog
    :title="isEdit ? 'Edit Example' : 'New Example'"
    title-icon="tabler-message-2"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="(v) => emit('update:isDialogVisible', v)"
    :width="600"
    is-footer
    is-submit
    :submit-text="isEdit ? 'Save' : 'Add'"
    submit-icon="tabler-device-floppy"
    submit-color="primary"
    :loading="loading"
    @on-submit="onSubmit"
  >
    <VRow>
      <VCol cols="12">
        <AppTextField v-model="form.sentence" placeholder="I am a student." :rules="[requiredValidator]">
          <template #label>{{ $t("Sentence") }} <span class="text-error">*</span></template>
        </AppTextField>
      </VCol>
      <VCol cols="12">
        <AppTextField v-model="form.translation_km" placeholder="ខ្ញុំជាសិស្ស។">
          <template #label>{{ $t("Khmer translation") }}</template>
        </AppTextField>
      </VCol>
      <VCol cols="12">
        <AudioField v-model="form.audio_url" />
      </VCol>
    </VRow>
  </AppDialog>
</template>
