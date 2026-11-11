<script setup>
import { computed, ref } from "vue";
import ShowImageDialog from "@/components/ShowImageDialog.vue";
import getImageUrl, { getThumbUrl } from "@/utils/image/getImageUrl";
import avatar1 from "@images/avatars/avatar-7.png";
import { useDisplay } from "vuetify";

const props = defineProps({
  image: String,
  // Ensure size is treated as a number for calculations
  size: { type: [Number, String], default: 36 },
  // width: { type: [Number, String], default: 36 },
  q: { type: [Number, String], default: 60 },
  isShowFullImage: { type: Boolean, default: true },
  iconHave: { type: String, default: "tabler-check" },
  colorHave: { type: String, default: "success" },

  iconNotHave: { type: String, default: "tabler-x" },
  colorNotHave: { type: String, default: "error" },
});
const { xs } = useDisplay();
const isImageDialog = ref(false);
const imageUrlPath = ref(null);

const emit = defineEmits(["onEdit"]);
const hasValidImage = computed(() => {
  if (!props.image) return false;
  return props.image.trim().length > 0;
});

const showImage = (image) => {
  if (props.isShowFullImage) {
    imageUrlPath.value = image ? getImageUrl(image) : avatar1;
    isImageDialog.value = true;
  }
};
</script>

<template>
  <ShowImageDialog
    v-if="isImageDialog"
    v-model:isDialogVisible="isImageDialog"
    :image="imageUrlPath"
  />

  <div class="d-flex flex-column align-center justify-center">
    <VIcon
      :icon="iconHave"
      :color="colorHave"
      v-if="hasValidImage"
      @click.stop="showImage(image)"
    />
    <VIcon :icon="iconNotHave" :color="colorNotHave" v-else />
  </div>
</template>
