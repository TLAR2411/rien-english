<script setup>
import { usePwaInstall } from "@/composables/usePwaInstall";

const { canInstall, needsIosInstructions, promptInstall } = usePwaInstall();
const isIosDialogOpen = ref(false);
</script>

<template>
  <VBtn
    v-if="canInstall"
    variant="tonal"
    color="secondary"
    size="small"
    block
    prepend-icon="tabler-download"
    @click="promptInstall"
  >
    {{ $t("Install App") }}
  </VBtn>

  <template v-else-if="needsIosInstructions">
    <VBtn
      variant="tonal"
      color="secondary"
      size="small"
      block
      prepend-icon="tabler-download"
      @click="isIosDialogOpen = true"
    >
      {{ $t("Install App") }}
    </VBtn>

    <VDialog v-model="isIosDialogOpen" max-width="340">
      <VCard>
        <VCardTitle class="font-weight-bold">
          {{ $t("Install App") }}
        </VCardTitle>
        <VCardText>
          <!-- Step 1: the actual Share icon shape (a square with an arrow out
               the top) — matches the icon in the browser's own toolbar. -->
          <div class="d-flex align-center ga-3 mb-4">
            <VAvatar size="26" color="primary" variant="flat" class="flex-shrink-0">
              <span class="text-caption font-weight-bold">1</span>
            </VAvatar>
            <div class="d-flex align-center ga-2">
              <VIcon icon="tabler-arrow-up-square" size="18" />
              <span class="font-weight-medium">
                {{ $t("Tap the Share icon in your browser") }}
              </span>
            </div>
          </div>

          <!-- Step 2: mocked up as a real menu row, so it's recognizable
               the moment the actual menu opens. -->
          <div class="d-flex align-start ga-3">
            <VAvatar size="26" color="primary" variant="flat" class="flex-shrink-0">
              <span class="text-caption font-weight-bold">2</span>
            </VAvatar>
            <div class="flex-grow-1">
              <div class="pwa-menu-row d-flex align-center ga-2 pa-2 rounded-lg">
                <VIcon icon="tabler-square-rounded-plus" size="20" />
                <span class="font-weight-medium">{{ $t("Add to Home Screen") }}</span>
              </div>
              <p class="text-caption text-medium-emphasis mt-1 mb-0">
                {{ $t("Scroll down the menu if you don't see it right away") }}
              </p>
            </div>
          </div>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="text" @click="isIosDialogOpen = false">
            {{ $t("OK") }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </template>
</template>

<style scoped>
/* Mimics a native menu row so it's instantly recognizable when the real
   Share menu opens with the same icon + label. */
.pwa-menu-row {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgba(var(--v-theme-on-surface), 0.04);
}
</style>
