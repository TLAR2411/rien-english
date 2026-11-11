<script setup>
import { computed, ref } from "vue";
import ShowImageDialog from "@/components/ShowImageDialog.vue";
// Make sure avatarText is imported if it's in a utils file, otherwise define it
import { avatarText } from "@core/utils/formatters";
import getImageUrl, { getThumbUrl } from "@/utils/image/getImageUrl";
import avatar1 from "@images/avatars/avatar-7.png";
import { useDisplay } from "vuetify";

const props = defineProps({
  title: String,
  image: String,
  // Ensure size is treated as a number for calculations
  size: { type: [Number, String], default: 36 },
  // width: { type: [Number, String], default: 36 },
  q: { type: [Number, String], default: 60 },
  isShowFullImage: { type: Boolean, default: true },
  color: { type: String, default: "primary" },

  rounded: { type: String, default: "lg" },
  isEdit: { type: Boolean, default: false },
});
const { xs } = useDisplay();
const isImageDialog = ref(false);
const imageUrlPath = ref(null);
const imageLoadError = ref(false);

const emit = defineEmits(["onEdit"]);

// Convert prop to number safely (in case "45" string is passed)
const sizeNumeric = computed(() => Number(props.size) || 36);

const hasValidImage = computed(() => {
  if (!props.image) return false;
  return props.image.trim().length > 0;
});

// --- CHANGED: Uses props.size for dynamic width/height ---
const thumb1x = computed(() =>
  hasValidImage.value
    ? getThumbUrl(props.image, {
        w: sizeNumeric.value, // Dynamic width
        h: sizeNumeric.value, // Dynamic height
        fit: "cover-top",
        fmt: "webp",
        q: props.q,
      })
    : null,
);

// --- CHANGED: Uses props.size * 2 for Retina displays ---
const thumb2x = computed(() =>
  hasValidImage.value
    ? getThumbUrl(props.image, {
        w: sizeNumeric.value * 2, // Dynamic 2x width
        h: sizeNumeric.value * 2, // Dynamic 2x height
        fit: "cover-top",
        fmt: "webp",
        q: props.q,
      })
    : null,
);

const srcset = computed(() =>
  hasValidImage.value ? `${thumb1x.value} 1x, ${thumb2x.value} 2x` : "",
);

// const showImage = (image) => {
//   if (props.isShowFullImage) {
//     imageUrlPath.value = image
//       ? getThumbUrl(image, {
//           w: 500,
//           h: 500,
//           fit: "cover",
//           fmt: "webp",
//           q: 100,
//         })
//       : avatar1;
//     isImageDialog.value = true;
//   }
// };

const showImage = (image) => {
  if (props.isShowFullImage) {
    imageUrlPath.value = image ? getImageUrl(image) : avatar1;
    isImageDialog.value = true;
  }
};

const onImageError = () => {
  imageLoadError.value = true;
  // console.warn("Failed to load image:", props.image);
};
</script>

<template>
  <ShowImageDialog
    v-if="isImageDialog"
    v-model:isDialogVisible="isImageDialog"
    :image="imageUrlPath"
  />

  <div class="d-flex flex-column align-center justify-center">
    <VBadge
      v-if="isEdit"
      class="small-badge"
      color="warning"
      location="bottom right"
      offset-x="8"
      offset-y="8"
      bordered
      @click="
        () => {
          emit('onEdit');
        }
      "
    >
      <template #badge>
        <VIcon icon="tabler-pencil" size="18" />
      </template>

      <VAvatar
        :rounded="rounded"
        :size="size"
        :ripple="false"
        :color="hasValidImage && !imageLoadError ? undefined : color"
        :class="
          hasValidImage && !imageLoadError
            ? ''
            : `v-avatar-light-bg ${color}--text`
        "
        :variant="hasValidImage && !imageLoadError ? undefined : 'tonal'"
        class="cursor-pointer"
        :aria-label="`${title}-va1`"
      >
        <VImg
          v-if="hasValidImage && !imageLoadError"
          :src="thumb1x || avatar1"
          :srcset="srcset"
          :sizes="`${size}px`"
          :width="size"
          :height="size"
          cover
          @click.stop="showImage(image)"
          @error="onImageError"
          :alt="title ?? ''"
        />

        <span v-else :style="{ fontSize: sizeNumeric * 0.4 + 'px' }">
          {{ avatarText(title) }}
        </span>
      </VAvatar>
    </VBadge>
    <VAvatar
      v-else
      :rounded="rounded"
      :size="size"
      :ripple="false"
      :color="hasValidImage && !imageLoadError ? undefined : color"
      :class="
        hasValidImage && !imageLoadError
          ? ''
          : `v-avatar-light-bg ${color}--text`
      "
      :variant="hasValidImage && !imageLoadError ? undefined : 'tonal'"
      class="cursor-pointer"
      :aria-label="`${title}-av2`"
    >
      <VImg
        v-if="hasValidImage && !imageLoadError"
        :src="thumb1x || avatar1"
        :srcset="srcset"
        :sizes="`${size}px`"
        :width="size"
        :height="size"
        cover
        @click.stop="showImage(image)"
        @error="onImageError"
        :alt="title ?? ''"
      />

      <span v-else :style="{ fontSize: sizeNumeric * 0.4 + 'px' }">
        {{ avatarText(title) }}
      </span>
    </VAvatar>
  </div>
</template>
<style lang="scss" scoped>
.small-badge :deep(.v-badge__badge) {
  /* Reduce padding */
  padding: 2px;

  /* Override Vuetify's default min-width */
  min-width: 25px;
  height: 25px;

  /* Ensure the icon inside is also small */
  font-size: 12px;

  /* ADD THIS LINE */
  cursor: pointer;
}
</style>
