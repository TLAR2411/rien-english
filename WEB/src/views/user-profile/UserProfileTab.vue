<script setup>
import { ref, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import Account from "@/views/user-profile/AccountTab.vue";
import Security from "@/views/user-profile/SecurityTab.vue";
import BrowserData from "@/views/user-profile/BrowserDataTab.vue";
import hasPermission from "@/utils/hasPermission";
import { usePartStore } from "@/stores/partStore";
import { userProfileTabs } from "@/constants/user-profile/userProfileTabs";

definePage({
  meta: {
    title: "User Profile",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
  },
});

const route = useRoute();
const router = useRouter();
const systemPart = usePartStore()?.system_part;

const tabs = userProfileTabs.find((v) => v.tab === "user-profile")?.sub || [];

// Find first accessible tab
const getFirstAccessibleTab = () => {
  const first = tabs.find(
    (item) => !item.permission || hasPermission(item.permission),
  );
  return first?.tab || "list"; // Fallback to 'list' instead of 'address'
};

// Initialize activeTab
const defaultTab = getFirstAccessibleTab();
const activeTab = ref(route.params.tab || defaultTab);

// Redirect if the current route tab is not accessible
if (
  route.params.tab &&
  !tabs.some(
    (t) =>
      t.tab === route.params.tab &&
      (!t.permission || hasPermission(t.permission)),
  )
) {
  router.replace({
    name: `${systemPart.value}-user-profile-tab`,
    params: { tab: defaultTab },
  });
}

// Watch route changes to sync activeTab
watch(
  () => route.params.tab,
  (newTab) => {
    if (newTab && tabs.some((t) => t.tab === newTab)) {
      activeTab.value = newTab;
    } else {
      activeTab.value = defaultTab;
      router.replace({
        name: `${systemPart.value}-user-profile-tab`,
        params: { tab: defaultTab },
      });
    }
  },
);

// Prevent scroll interference
const preventScrollInterference = (event) => {
  if (event.target.closest(".v-window__container")) {
    event.stopPropagation();
  }
};

// Add scroll event listener to prevent interference
onMounted(() => {
  const windowContainer = document.querySelector(".v-window");
  if (windowContainer) {
    windowContainer.addEventListener("wheel", preventScrollInterference, {
      passive: false,
    });
    windowContainer.addEventListener("touchmove", preventScrollInterference, {
      passive: false,
    });
  }
});
</script>

<template>
  <AppCard title="Settings" title-icon="tabler-settings" :is-header="false">
    <VRow>
      <div class="d-flex flex-column w-100 h-100">
        <!-- Tabs -->
        <VTabs v-model="activeTab" grow stacked class="v-tabs--fixed">
          <template v-for="item in tabs">
            <VTab
              v-if="!item?.permission || hasPermission(item.permission)"
              :key="item.icon"
              :value="item.tab"
              :to="{
                name: `${systemPart}-user-profile-tab`,
                params: { tab: item.tab },
              }"
            >
              <VIcon :icon="item.icon" class="mb-2" />
              <span>{{ $t(item.title) }}</span>
            </VTab>
          </template>
        </VTabs>

        <!-- Content -->
        <VWindow
          v-model="activeTab"
          class="disable-tab-transition"
          :touch="false"
        >
          <VWindowItem value="account">
            <Account v-if="activeTab === 'account'" :key="activeTab" />
          </VWindowItem>
          <VWindowItem value="security">
            <Security v-if="activeTab === 'security'" :key="activeTab" />
          </VWindowItem>
          <VWindowItem value="browser-data">
            <BrowserData v-if="activeTab === 'browser-data'" :key="activeTab" />
          </VWindowItem>
        </VWindow>
      </div>
    </VRow>
  </AppCard>
</template>

<style scoped>
/* Ensure content area handles scrolling correctly */
.v-window__container {
  max-height: calc(100vh - 150px); /* Adjust based on your layout */
  overflow-y: auto;
  -webkit-overflow-scrolling: touch; /* Smooth scrolling on touch devices */
}

/* Prevent VWindow from intercepting scroll events */
.v-window {
  overflow: hidden !important;
}
</style>
