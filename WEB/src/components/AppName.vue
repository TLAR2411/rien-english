<script setup>
import { computed } from "vue";
import ShowImageDialog from "@/components/ShowImageDialog.vue";
import getImageUrl, { getThumbUrl } from "@/utils/image/getImageUrl";
import avatar1 from "@images/avatars/avatar-7.png";

const props = defineProps({
  title: String,
  subTitle: String,
  image: String,
  subTitleCondition: { type: [Function, Boolean, Number], default: null },
  // "sm" (default): table cell size. "lg": same look, bigger - 44px avatar
  // and larger text (e.g. dialog headers)
  size: { type: String, default: "sm" },
});

const isLg = computed(() => props.size === "lg");
const avatarSize = computed(() => (isLg.value ? 44 : 36));

const isImageDialog = ref(false);
const imageUrlPath = ref(null);
const imageLoadError = ref(false);

// Check if image actually exists (not just a path)
const hasValidImage = computed(() => {
  if (!props.image) return false;
  // You can add additional validation here if needed
  // For example, check if it's not an empty string or invalid path
  return props.image.trim().length > 0;
});

const thumb1x = computed(() =>
  hasValidImage.value
    ? getThumbUrl(props.image, {
        w: avatarSize.value,
        h: avatarSize.value,
        fit: "cover-top",
        fmt: "webp",
        q: 60,
      })
    : null,
);

const thumb2x = computed(() =>
  hasValidImage.value
    ? getThumbUrl(props.image, {
        w: avatarSize.value * 2,
        h: avatarSize.value * 2,
        fit: "cover-top",
        fmt: "webp",
        q: 60,
      })
    : null,
);

const srcset = computed(() =>
  hasValidImage.value ? `${thumb1x.value} 1x, ${thumb2x.value} 2x` : "",
);

const showImage = (image) => {
  imageUrlPath.value = image ? getImageUrl(image) : avatar1;
  isImageDialog.value = true;
};

// Handle image load errors
const onImageError = () => {
  // Optionally handle error - could set hasValidImage to false
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

  <div class="d-flex flex-column">
    <VAvatar
      rounded="lg"
      :size="avatarSize"
      :ripple="false"
      :color="hasValidImage && !imageLoadError ? undefined : 'primary'"
      :class="
        hasValidImage && !imageLoadError
          ? ''
          : 'v-avatar-light-bg primary--text'
      "
      :variant="hasValidImage && !imageLoadError ? undefined : 'tonal'"
      class="cursor-pointer"
      :aria-label="`${title}-va1`"
    >
      <VImg
        v-if="hasValidImage && !imageLoadError"
        :src="thumb1x || avatar1"
        :srcset="srcset"
        :sizes="`${avatarSize}px`"
        :width="avatarSize"
        :height="avatarSize"
        cover
        loading="lazy"
        decoding="async"
        :eager="false"
        :transition="false"
        @click="showImage(image)"
        @error="onImageError"
        :alt="title ?? ''"
      />
      <span v-else :class="{ 'app-name__initial--lg': isLg }">{{
        avatarText(title)
      }}</span>
    </VAvatar>
  </div>

  <div v-if="isLg" class="d-flex flex-column min-w-0 ml-2">
    <span class="app-name__title--lg">{{ title }}</span>
    <span
      v-if="subTitle"
      class="app-name__sub--lg"
      :class="subTitleCondition ? 'text-error' : 'text-primary'"
    >
      {{ subTitle }}
    </span>
  </div>
  <div v-else class="d-flex flex-column ml-2">
    <span>{{ title }}</span>
    <span
      style="
        font-family: &quot;Public Sans&quot;, &quot;kantumruy&quot;, sans-serif;
        font-size: 10px;
      "
      :class="subTitleCondition ? 'text-error' : 'text-primary'"
    >
      {{ subTitle }}
    </span>
  </div>
</template>

<style scoped>
.app-name__initial--lg {
  font-size: 18px;
  font-weight: 500;
}

.app-name__title--lg {
  font-size: 16px;
  font-weight: 500;
  line-height: 1.4;
}

.app-name__sub--lg {
  font-family: "Public Sans", "kantumruy", sans-serif;
  font-size: 12.5px;
}
</style>
