<template>
  <div style="position: relative; height: 90%">
    <!-- A <label> tied to the input: tapping it opens the picker natively.
         Some phones (older iOS Safari, Android WebViews / installed PWA)
         ignore a script calling .click() on a display:none input. -->
    <label
      ref="uploadBox"
      :for="inputId"
      @dragover.prevent="handleDragOver"
      @dragleave.prevent="handleDragLeave"
      @drop.prevent="handleDrop"
      :style="{
        width: '100%',
        height: '100%',
        border: `1.5px dashed ${isDragging ? '#1976d2' : borderColor}`,
        borderRadius: '8px',
        padding: '16px',
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center',
        cursor: 'pointer',
        backgroundColor: isDragging ? '#f0f6ff' : '#fafafa',
        transition: 'all 0.3s',
        position: 'relative',
      }"
      @mouseover="handleMouseOver"
      @mouseleave="handleMouseLeave"
    >
      <!-- Preview -->
      <img
        v-if="previewUrl"
        :src="previewUrl"
        :alt="label"
        style="max-height: 100%; max-width: 100%; object-fit: contain"
      />

      <!-- Empty state -->
      <div v-else style="text-align: center; color: #999">
        <div
          style="
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 12px;
          "
        >
          <VIcon
            :icon="icon"
            size="22"
            :color="iconColor"
            style="transition: color 0.3s"
          />
        </div>

        <p
          style="
            font-weight: 600;
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 4px;
          "
        >
          {{ headline || $t("Drop your file here") }}
        </p>

        <p style="font-size: 0.75rem; color: #999; margin-bottom: 12px">
          {{ supportText || $t("Support for image files up to 10MB") }}
        </p>

        <!-- a <span>, not a <button>: a button inside a label swallows the
             tap, so the label (and the picker) would never get it -->
        <VBtn tag="span" size="small" variant="outlined" color="default">
          {{ $t("Browse Files") }}
        </VBtn>
      </div>

      <!-- Processing overlay (while downscaling / encoding) -->
      <div
        v-if="isProcessing"
        style="
          position: absolute;
          inset: 0;
          display: flex;
          justify-content: center;
          align-items: center;
          background-color: rgba(255, 255, 255, 0.7);
          border-radius: 8px;
        "
      >
        <VProgressCircular indeterminate color="primary" />
      </div>

      <!-- Re-crop button (only for freshly uploaded images, avoids CORS taint) -->
      <VBtn
        v-if="previewUrl && enableCrop && rawImage"
        color="primary"
        icon="tabler-crop"
        size="x-small"
        rounded
        style="position: absolute; top: 10px; right: 44px"
        @click.stop.prevent="reopenCropper"
      />

      <!-- Delete button -->
      <VBtn
        v-if="previewUrl"
        :loading="isLoading"
        color="error"
        icon="tabler-x"
        size="x-small"
        rounded
        style="position: absolute; top: 10px; right: 10px"
        @click.stop.prevent="handleClear"
      />
    </label>

    <!-- Invisible but NOT display:none (iOS won't open a picker for that), and
         moved to <body> so it still works when a parent hides this component
         with v-show="false" and opens it through openPicker(). -->
    <Teleport to="body">
      <input
        :id="inputId"
        ref="fileInput"
        type="file"
        accept="image/*"
        class="app-image-upload__input"
        tabindex="-1"
        aria-hidden="true"
        @change="handleFileChange"
      />
    </Teleport>

    <!-- Crop dialog -->
    <AppDialog
      title="Crop Image"
      title-icon="tabler-crop"
      :is-dialog-visible="cropDialog"
      @update:is-dialog-visible="(value) => cancelCrop()"
      width="600"
      :is-padding="false"
    >
      <VCardText class="ma-0 pa-4">
        <Cropper
          ref="cropper"
          :src="rawImage"
          :stencil-props="
            cropAspectRatio ? { aspectRatio: cropAspectRatio } : {}
          "
          :stencil-component="circleStencil ? CircleStencil : RectangleStencil"
          class="cropper"
          style="max-height: 400px; background: #1e1e1e"
        />
      </VCardText>

      <VDivider />

      <VCardActions class="px-4 pb-4 pt-4">
        <VBtn
          color="primary"
          variant="flat"
          :loading="isProcessing"
          @click="confirmCrop"
          class="w-100"
        >
          <VIcon icon="tabler-crop" start size="24" />
          {{ $t("Crop & Save") }}
        </VBtn>
      </VCardActions>
    </AppDialog>
  </div>
</template>

<script setup>
import { ref, computed, useId } from "vue";
import { Cropper, CircleStencil, RectangleStencil } from "vue-advanced-cropper";
import "vue-advanced-cropper/dist/style.css";
import { useDialog } from "@/composables/useDialog";

const { showDialog } = useDialog();
const { t } = useI18n();
// links the <label> to the (teleported) <input>; unique per instance, since a
// page like ClientCreate has four of these
const inputId = `app-image-upload-${useId()}`;

const props = defineProps({
  modelValue: {
    type: String,
    default: null,
  },
  label: {
    type: String,
    default: "",
  },
  icon: {
    type: String,
    default: "tabler-cloud-upload",
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  headline: {
    type: String,
    default: null,
  },
  supportText: {
    type: String,
    default: null,
  },
  // Cropping options
  enableCrop: {
    type: Boolean,
    default: true,
  },
  cropAspectRatio: {
    type: Number,
    default: null, // e.g. 1 for square, 16/9 for wide. null = free crop
  },
  circleStencil: {
    type: Boolean,
    default: false,
  },
  // How to turn a server path into a full URL (used on edit)
  urlResolver: {
    type: Function,
    default: null,
  },
  // Performance: largest dimension fed into the cropper / emitted output.
  // Big mobile photos get downscaled to this before any heavy work happens.
  maxDimension: {
    type: Number,
    default: 1600,
  },
  // JPEG quality for the encoded output (0..1)
  outputQuality: {
    type: Number,
    default: 0.85,
  },
});

const emit = defineEmits(["update:modelValue", "change", "clear"]);

const uploadBox = ref(null);
const fileInput = ref(null);
const cropper = ref(null);
const iconColor = ref("#999");
const borderColor = ref("#ddd");
const selectedFile = ref(null);
const isDragging = ref(false);
const isProcessing = ref(false);

const cropDialog = ref(false);
const rawImage = ref(null); // downscaled base64 of a NEW upload, kept for re-cropping

// Is the current value a freshly uploaded base64 string?
const isDataUrl = (val) => typeof val === "string" && val.startsWith("data:");

// What the <img> actually shows: base64 as-is, or server path resolved to URL
const previewUrl = computed(() => {
  const val = props.modelValue;
  if (!val) return null;
  if (isDataUrl(val)) return val;
  return props.urlResolver ? props.urlResolver(val) : val;
});

const readAsDataURL = (file) =>
  new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => resolve(e.target.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });

const loadImage = (src) =>
  new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = reject;
    img.src = src;
  });

// Shrink a data URL so its largest side is <= maxDimension.
// Returns a (smaller) data URL — used as the cropper source.
const downscaleDataUrl = async (dataUrl) => {
  const img = await loadImage(dataUrl);
  const max = Math.max(img.width, img.height);
  if (max <= props.maxDimension) return dataUrl; // already small enough

  const scale = props.maxDimension / max;
  const canvas = document.createElement("canvas");
  canvas.width = Math.round(img.width * scale);
  canvas.height = Math.round(img.height * scale);
  const ctx = canvas.getContext("2d");
  ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
  return canvas.toDataURL("image/jpeg", 0.92);
};

// Some Android gallery apps hand over a photo with an empty file.type, so
// fall back to the file name before deciding it is not an image.
const IMAGE_EXT = /\.(jpe?g|png|gif|webp|bmp|heic|heif|avif)$/i;
const isImageFile = (file) =>
  file.type ? file.type.startsWith("image/") : IMAGE_EXT.test(file.name ?? "");

const processFile = async (file, event = null) => {
  if (!file) return;
  if (!isImageFile(file)) return; // ignore non-image drops

  isProcessing.value = true;
  try {
    selectedFile.value = file;
    const original = await readAsDataURL(file);
    // Downscale FIRST so nothing downstream ever touches the full-size photo.
    const downscaled = await downscaleDataUrl(original);

    if (props.enableCrop) {
      rawImage.value = downscaled;
      cropDialog.value = true; // open cropper instead of emitting immediately
    } else {
      // No crop: re-encode at output quality and emit as base64.
      const img = await loadImage(downscaled);
      const canvas = document.createElement("canvas");
      canvas.width = img.width;
      canvas.height = img.height;
      canvas.getContext("2d").drawImage(img, 0, 0);
      emit(
        "update:modelValue",
        canvas.toDataURL("image/jpeg", props.outputQuality),
      );
    }

    emit("change", { event, file });
  } catch (error) {
    // e.g. an iPhone HEIC photo on a browser that can't decode it — before,
    // this failed silently and looked like the picker did nothing
    console.error("Failed to read image:", error);
    showDialog({
      icon: "error",
      title: t("Cannot read this image. Please choose a JPG or PNG photo."),
      isConfirm: false,
    });
  } finally {
    isProcessing.value = false;
  }
};

const handleFileChange = (event) => {
  const file = event.target.files?.[0];
  // clear it right away, so picking the same photo again still fires change
  event.target.value = "";
  processFile(file, event);
};

const handleDrop = (event) => {
  isDragging.value = false;
  processFile(event.dataTransfer.files[0], event);
};

const confirmCrop = () => {
  // Cap the cropper output dimensions too, in case the user zoomed way out.
  const result = cropper.value?.getResult({
    maxWidth: props.maxDimension,
    maxHeight: props.maxDimension,
  });
  if (result?.canvas) {
    emit(
      "update:modelValue",
      result.canvas.toDataURL("image/jpeg", props.outputQuality),
    );
  }
  cropDialog.value = false;
};

const cancelCrop = () => {
  cropDialog.value = false;
  // If nothing was previously saved, discard the pending upload
  if (!props.modelValue || !isDataUrl(props.modelValue)) {
    rawImage.value = null;
    selectedFile.value = null;
    if (fileInput.value) fileInput.value.value = "";
  }
};

const reopenCropper = () => {
  if (rawImage.value) cropDialog.value = true;
};

const handleDragOver = () => {
  isDragging.value = true;
};

const handleDragLeave = () => {
  isDragging.value = false;
};

const handleClear = () => {
  selectedFile.value = null;
  rawImage.value = null;
  if (fileInput.value) fileInput.value.value = "";
  emit("update:modelValue", null);
  emit("clear");
};

const handleMouseOver = () => {
  borderColor.value = "#1976d2";
  iconColor.value = "#1976d2";
};

const handleMouseLeave = () => {
  borderColor.value = "#ddd";
  iconColor.value = "#999";
};

// Lets a parent open the file picker (and crop dialog) from its own button,
// e.g. a camera icon on a round avatar, while this component stays hidden
defineExpose({
  openPicker: () => fileInput.value?.click(),
  clear: handleClear,
});
</script>

<style>
/* not scoped: the input is teleported to <body> */
.app-image-upload__input {
  position: fixed;
  overflow: hidden;
  width: 1px;
  height: 1px;
  clip: rect(0 0 0 0);
  clip-path: inset(50%);
  opacity: 0;
  pointer-events: none;
  inset-block-start: 0;
  inset-inline-start: 0;
}
</style>

<style scoped>
.cropper {
  border-radius: 6px;
  overflow: hidden;
}
</style>
