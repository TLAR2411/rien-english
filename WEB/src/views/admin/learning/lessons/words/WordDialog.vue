<script setup>
import AppDialog from "@/components/AppDialog.vue";
import AudioField from "@/components/learning/AudioField.vue";
import { requiredValidator } from "@/@core/utils/validators";

// Add / edit one new word. The section saves it (emits "save").
const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
  word: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["update:isDialogVisible", "save"]);

const emptyForm = () => ({ word: "", meaning_en: "", meaning_km: "", example: "", audio_url: "" });
const form = ref(emptyForm());
const isEdit = computed(() => !!form.value.id);

watch(
  () => [props.isDialogVisible, props.word],
  () => (form.value = { ...emptyForm(), ...props.word }),
  { immediate: true },
);

const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (valid) emit("save", { ...form.value, audio_url: form.value.audio_url || null });
};
</script>

<template>
  <AppDialog
    :title="isEdit ? 'Edit Word' : 'New Word'"
    title-icon="tabler-vocabulary"
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
      <VCol cols="12" md="6">
        <AppTextField v-model="form.word" label="Word" placeholder="rice field" :rules="[requiredValidator]" />
      </VCol>
      <VCol cols="12" md="6">
        <AppTextField v-model="form.meaning_km" label="Khmer meaning" placeholder="វាលស្រែ" />
      </VCol>
      <VCol cols="12">
        <AppTextField v-model="form.meaning_en" label="Meaning in simple English" placeholder="land where rice grows" />
      </VCol>
      <VCol cols="12">
        <AppTextField
          v-model="form.example"
          label="Example sentence"
          placeholder="He works in the rice field every day."
        />
      </VCol>
      <VCol cols="12">
        <AudioField v-model="form.audio_url" />
      </VCol>
    </VRow>
  </AppDialog>
</template>
