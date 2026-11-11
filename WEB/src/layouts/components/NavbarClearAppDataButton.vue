<!-- components/ClearAppDataButton.vue -->
<script setup>
import { ref } from "vue";
import { useBuildVersion } from "@/composables/useBuildVersion";

const isClearing = ref(false);
const { hasNewBuild } = useBuildVersion();
const isLoading = ref(false);

const handleForceReset = async () => {
  isLoading.value = true;
  isClearing.value = true;

  try {
    // 1. Unregister all Service Workers
    if ("serviceWorker" in navigator) {
      const registrations = await navigator.serviceWorker.getRegistrations();
      for (const registration of registrations) {
        await registration.unregister();
      }
    }

    // 2. Clear all CacheStorage (where Workbox & PWA store static build assets)
    if ("caches" in window) {
      const cacheNames = await caches.keys();

      await Promise.all(cacheNames.map((name) => caches.delete(name)));
    }

    // 3. Clear Session Storage (Optional: clear localStorage if you want a full reset)
    sessionStorage.clear();

    // 4. Force hard reload bypassing browser disk cache with a timestamp query
    const url = new URL(window.location.href);

    url.searchParams.set("reload", Date.now().toString());
    window.location.href = url.toString();
  } catch (error) {
    console.error("Failed to clear app storage:", error);
    window.location.reload();
  } finally {
    isClearing.value = false;
  }
  isLoading.value = false;
};
</script>

<template>
  <IconBtn
    :loading="isLoading"
    :disabled="isClearing"
    @click="handleForceReset"
    :aria-label="
      hasNewBuild
        ? $t('New version available — tap to update')
        : $t('Force check for updates')
    "
    class="jusitify-end"
  >
    <VBadge
      :model-value="hasNewBuild"
      color="error"
      dot
      offset-x="2"
      offset-y="3"
    >
      <VIcon
        size="26"
        :icon="
          hasNewBuild ? 'tabler-circle-arrow-down-filled' : 'tabler-refresh'
        "
        :class="{ 'refresh-spin': isClearing }"
        :color="hasNewBuild ? 'success' : 'secondary'"
      />
    </VBadge>

    <VTooltip
      activator="parent"
      open-delay="500"
      scroll-strategy="close"
      :aria-label="
        hasNewBuild
          ? $t('New version available — tap to update')
          : $t('Force check for updates')
      "
    >
      <span>{{
        hasNewBuild
          ? $t("New version available — tap to update")
          : $t("Force check for updates")
      }}</span>
    </VTooltip>
  </IconBtn>
</template>

<style scoped>

</style>
