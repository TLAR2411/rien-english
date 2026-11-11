<script setup>
import { ref } from "vue";
import { useBuildVersion } from "@/composables/useBuildVersion";

const { hasNewBuild, forceUpdate } = useBuildVersion();
const isUpdating = ref(false);

const handleUpdate = async () => {
  isUpdating.value = true;
  try {
    await forceUpdate();
  } catch (error) {
    console.error("Failed to update app:", error);
    window.location.reload();
  }
};
</script>

<template>
  <VDialog
    :model-value="hasNewBuild"
    persistent
    max-width="360"
    no-click-animation
  >
    <VCard class="update-card">
      <div class="update-inner">
        <div class="update-icon-wrap">
          <VIcon icon="tabler-circle-arrow-down-filled" size="34" />
        </div>

        <h3 class="update-title">{{ $t("New update available") }}</h3>
        <p class="update-message">
          {{
            $t(
              "A new version of the app is ready. Please update now to continue.",
            )
          }}
        </p>

        <VBtn
          color="primary"
          variant="flat"
          block
          class="update-btn"
          :loading="isUpdating"
          :disabled="isUpdating"
          @click="handleUpdate"
        >
          {{ $t("Get New Update") }}
        </VBtn>
      </div>
    </VCard>
  </VDialog>
</template>

<style scoped>
.update-card {
  border-radius: 12px !important;
  overflow: hidden;
}
.update-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 32px 24px 26px;
}
.update-icon-wrap {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  min-width: 64px;
  border-radius: 50%;
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.12);
  margin-bottom: 16px;
}
.update-title {
  margin: 0 0 8px;
  font-size: 19px;
  font-weight: 600;
  line-height: 1.3;
  font-family: "inter", "kantumruy", sans-serif;
}
.update-message {
  margin: 0 0 24px;
  font-family: inter, kantumruy, sans-serif;
  font-size: 14px;
  line-height: 1.5;
  opacity: 0.7;
}
.update-btn {
  height: 42px !important;
  border-radius: 8px !important;
  text-transform: none;
  font-weight: 500;
}
</style>
