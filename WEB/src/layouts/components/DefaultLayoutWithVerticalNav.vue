<script setup>
import { useLayoutConfigStore } from "@layouts/stores/config";

// Components
import Footer from "@/layouts/components/Footer.vue";
import NavbarBranches from "./NavbarBranches.vue";
import NavBarI18n from "@core/components/I18n.vue";
import { themeConfig } from "@themeConfig";

// @layouts plugin
import { VerticalNavLayout } from "@layouts";
import { useRoute } from "vue-router";
import NavbarClearAppDataButton from "./NavbarClearAppDataButton.vue";
import { usePartStore } from "@/stores/partStore";
import { SYSTEM_PART } from "@/composables/useSystemParts";
const currentNavItems = useSystemPartNavItems();

// Students have one branch: no branch select for them
const showBranches = computed(() => usePartStore().system_part !== SYSTEM_PART.STUDENT);
const configStore = useLayoutConfigStore();

const isMobileNav = ref(window.innerWidth < 1280);
const updateMobileNav = () => {
  isMobileNav.value = window.innerWidth < 1280;
};
const route = useRoute();

onMounted(() => {
  window.addEventListener("resize", updateMobileNav);
});

onUnmounted(() => {
  window.removeEventListener("resize", updateMobileNav);
});
</script>

<template>
  <VerticalNavLayout :nav-items="currentNavItems">
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <!--Navbar -->

        <IconBtn
          v-if="isMobileNav"
          id="vertical-nav-toggle-btn"
          class="ms-n3 d-lg-none"
          aria-label="Open navigation menu"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon size="26" icon="tabler-menu-2" />
        </IconBtn>

        <IconBtn
          v-else
          id="vertical-nav-toggle-btn"
          class="ms-n3"
          :aria-label="
            configStore.isVerticalNavCollapsed
              ? 'Expand navigation menu'
              : 'Collapse navigation menu'
          "
          @click="
            configStore.isVerticalNavCollapsed =
              !configStore.isVerticalNavCollapsed
          "
        >
          <VIcon size="26" icon="tabler-menu-2" />
        </IconBtn>

        <VSpacer />

        <!-- Branch select shrinks first; the icons keep their size -->
        <NavbarBranches v-if="showBranches" class="me-2" />
        <div class="navbar-actions">
          <NavBarI18n
            v-if="
              themeConfig.app.i18n.enable &&
              themeConfig.app.i18n.langConfig?.length
            "
            :languages="themeConfig.app.i18n.langConfig"
          />
          <NavbarClearAppDataButton />
        </div>
      </div>
    </template>

    <!-- 👉 Pages -->
    <slot />

    <!-- 👉 Footer -->
    <template #footer>
      <Footer />
    </template>

    <!-- 👉 Customizer -->
    <!-- <TheCustomizer /> -->
  </VerticalNavLayout>
</template>

<style scoped>
.navbar-actions {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  gap: 6px;
  margin-inline-end: -12px;
}

/* Phones: tighter icons so the branch name keeps more room */
@media (max-width: 599px) {
  .navbar-actions {
    gap: 0;
    margin-inline-end: -8px;
  }
}
</style>
