<script setup>
import { uploadFile } from "@/services/uploads";
import { UPLOAD_ACCEPT } from "@/constants/learning";

// Audio link with an upload button and a player. v-model is the URL (or "").
const props = defineProps({
  modelValue: { type: String, default: "" },
  label: { type: String, default: "Audio" },
});

const emit = defineEmits(["update:modelValue"]);

const fileInput = ref(null);
const isUploading = ref(false);

const onPicked = async (event) => {
  const file = event.target.files?.[0];
  event.target.value = "";
  if (!file) return;
  isUploading.value = true;
  try {
    const url = await uploadFile(file, "audio");
    if (url) emit("update:modelValue", url);
  } finally {
    isUploading.value = false;
  }
};
</script>

<template>
  <div class="audio-field">
    <div class="text-body-2 mb-1">{{ $t(label) }}</div>
    <div class="d-flex align-center flex-wrap gap-2">
      <audio v-if="modelValue" :src="modelValue" controls preload="none" class="audio-field__player" />
      <span v-else class="text-body-2 text-medium-emphasis">{{ $t("No audio yet") }}</span>

      <VBtn
        size="small"
        variant="tonal"
        prepend-icon="tabler-upload"
        :loading="isUploading"
        @click="fileInput?.click()"
      >
        {{ modelValue ? $t("Replace") : $t("Upload mp3") }}
      </VBtn>
      <IconBtn
        v-if="modelValue"
        size="small"
        :aria-label="$t('Remove audio')"
        :title="$t('Remove audio')"
        @click="emit('update:modelValue', '')"
      >
        <VIcon icon="tabler-x" />
      </IconBtn>
      <input ref="fileInput" type="file" :accept="UPLOAD_ACCEPT.audio" hidden @change="onPicked" />
    </div>
  </div>
</template>

<style scoped>
.audio-field__player {
  height: 36px;
  max-width: 100%;
}
</style>
