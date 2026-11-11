<script setup>
const props = defineProps({
  title: {
    type: String,
    default: null,
  },
  icon: {
    type: String,
    default: null,
  },
  isDialogVisible: {
    type: Boolean,
    default: true,
  },
  isUpdate: {
    type: Boolean,
    default: false,
  },
  isSubmit: {
    type: Boolean,
    default: true,
  },
  maxWidth: {
    type: String,
    default: "600px",
  },
  loading: {
    type: Boolean,
    required: false,
    skipCheck: true,
    default: undefined,
  },
});
const emit = defineEmits([
  "update:isDialogVisible",
  "onSubmit",
  "onCloseDialog",
]);

const refForm = ref();

const onCloseDialog = () => {
  emit("onCloseDialog");
  emit("update:isDialogVisible", false);
};
const onFormSubmit = async () => {
  emit("onSubmit", refForm.value?.validate());
};

const _loading = ref(false);

const $loading = computed({
  get() {
    return props.loading !== undefined ? props.loading : _loading.value;
  },
  set(value) {
    props.loading !== undefined
      ? emit("update:loading", value)
      : (_loading.value = value);
  },
});
</script>

<template>
  <VDialog
    :model-value="isDialogVisible"
    @update:model-value="(value) => $emit('update:isDialogVisible', value)"
    :max-width="maxWidth"
    persistent
  >
    <div class="dialog-content" style="max-height: 100vh; overflow-y: auto">
      <!-- Ensure this div can scroll -->
      <DialogCloseBtn @click="onCloseDialog" />
      <VCard>
        <VCardItem style="padding-top: 12px; padding-bottom: 12px">
          <span style="font-size: 18px">
            <template v-if="icon">
              <VIcon>{{ icon }}</VIcon>
            </template>
            <template v-else>
              <VIcon v-if="isUpdate">tabler-pencil</VIcon>
              <VIcon v-else>tabler-plus</VIcon>
            </template>
            {{ $t(title) }}
          </span>
        </VCardItem>
        <VDivider />

        <VCardText>
          <VForm ref="refForm" @submit.prevent="onFormSubmit">
            <slot />
          </VForm>
        </VCardText>
        <VDivider />

        <VCardText
          class="d-flex justify-space-between flex-wrap gap-3"
          style="padding-top: 12px; padding-bottom: 12px"
        >
          <VBtn variant="tonal" color="secondary" @click="onCloseDialog">
            <VIcon start icon="tabler-arrow-left" />
            {{ $t("Close") }}
          </VBtn>
          <VBtn
            v-if="isSubmit"
            @click="onFormSubmit"
            :loading="loading"
            :color="isUpdate ? 'warning' : 'success'"
          >
            <VIcon start :icon="isUpdate ? 'tabler-pencil' : 'tabler-check'" />
            {{ isUpdate == true ? $t("Update") : $t("Create") }}
          </VBtn>
        </VCardText>

        <VOverlay
          v-model="$loading"
          contained
          persistent
          scroll-strategy="none"
          class="align-center justify-center"
        >
          <VProgressCircular
            indeterminate
            aria-label="Loading progress App Add Edit Dialog"
            role="progressbar"
          />
        </VOverlay>
      </VCard>
    </div>
  </VDialog>
</template>
