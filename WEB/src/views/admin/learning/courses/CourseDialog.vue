<script setup>
import AppDialog from "@/components/AppDialog.vue";
import { requiredValidator } from "@/@core/utils/validators";
import { PUBLISH_STATUSES } from "@/constants/learning";

// Create / edit a course. The list saves it (emits "save").
const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
  course: { type: Object, default: () => ({}) },
  levels: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["update:isDialogVisible", "save"]);

const emptyForm = () => ({
  level_id: null,
  title: "",
  title_km: "",
  description: "",
  slug: "",
  status: "draft",
});
const form = ref(emptyForm());
const isEdit = computed(() => !!form.value.id);

watch(
  () => [props.isDialogVisible, props.course],
  () => (form.value = { ...emptyForm(), ...props.course }),
  { immediate: true },
);

const levelTitle = (level) => `${level.code} — ${level.name}`;

const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (valid) emit("save", { ...form.value });
};
</script>

<template>
  <AppDialog
    :title="isEdit ? 'Edit Course' : 'New Course'"
    title-icon="tabler-books"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="(v) => emit('update:isDialogVisible', v)"
    :width="640"
    is-footer
    is-submit
    :submit-text="isEdit ? 'Save' : 'Create'"
    submit-icon="tabler-device-floppy"
    submit-color="primary"
    :loading="loading"
    @on-submit="onSubmit"
  >
    <VRow>
      <VCol cols="12" sm="6">
        <AppSelect
          v-model="form.level_id"
          label="Level"
          :items="levels"
          :item-title="levelTitle"
          item-value="id"
          :rules="[requiredValidator]"
        />
      </VCol>
      <VCol cols="12" sm="6">
        <AppSelect
          v-model="form.status"
          label="Status"
          :items="PUBLISH_STATUSES"
          :item-title="(item) => $t(item.title)"
          item-value="value"
          :rules="[requiredValidator]"
        />
      </VCol>
      <VCol cols="12" sm="6">
        <AppTextField v-model="form.title" label="Title English" placeholder="Grammar A1" :rules="[requiredValidator]" />
      </VCol>
      <VCol cols="12" sm="6">
        <AppTextField v-model="form.title_km" label="Title Khmer" placeholder="វេយ្យាករណ៍ A1" />
      </VCol>
      <VCol cols="12">
        <AppTextarea v-model="form.description" label="Description" rows="3" auto-grow />
      </VCol>
      <VCol cols="12">
        <AppTextField
          v-model="form.slug"
          label="Slug"
          placeholder="grammar-a1"
          :hint="$t('Web address name. Leave empty to make it from the title.')"
          persistent-hint
        />
      </VCol>
    </VRow>
  </AppDialog>
</template>
