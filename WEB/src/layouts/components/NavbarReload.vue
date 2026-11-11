<script setup>
import { useAppStore } from "@/stores/appStore";
const isLoading = ref(false);
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const reload = async () => {
  isLoading.value = true;
  await useAppStore().clearAllAppStore();
  await sleep(1000); // Delay for 500ms
  await useAppStore().getAllAppStore();
  isLoading.value = false;
};
</script>

<template>
  <IconBtn :loading="isLoading" @click="reload">
    <VIcon icon="tabler-reload" />
  </IconBtn>
</template>
