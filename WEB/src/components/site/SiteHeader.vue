<script setup>
import I18n from "@core/components/I18n.vue";
import { themeConfig } from "@themeConfig";
import RienLogo from "@/components/site/RienLogo.vue";
import { useAuthStore } from "@/stores/authStore";
import { homePathFor } from "@/utils/homePath";
import {
  homeRoute,
  lessonsRoute,
  loginRoute,
  signUpRoute,
} from "@/utils/siteRoutes";

/** Top bar of the public website: logo, Lessons, language, Log in, Sign up. */
const auth = useAuthStore();
const route = useRoute();

const isLoggedIn = computed(() => auth.isAuthenticated && Boolean(auth.user));
const dashboard = computed(() => homePathFor(auth.user?.default_part));

// Come back to this page after logging in
const redirect = computed(() => (route.path === "/" ? null : route.fullPath));

// At the top of the page the bar is plain white; after scrolling it gets a line
const { y: scrollY } = useWindowScroll();
const isScrolled = computed(() => scrollY.value > 8);

const isMenuOpen = ref(false);

// Students stay on the public page they are reading after logging out
const logout = async () => {
  isMenuOpen.value = false;
  await auth.logout();
};
watch(
  () => route.fullPath,
  () => (isMenuOpen.value = false),
);
</script>

<template>
  <header class="site-header" :class="{ 'site-header--raised': isScrolled || isMenuOpen }">
    <div class="site-container site-header__bar">
      <RouterLink
        :to="homeRoute()"
        class="site-header__logo"
        aria-label="Rien English — home"
      >
        <RienLogo :height="28" />
      </RouterLink>

      <div class="site-header__actions">
        <!-- Same flag menu as the dashboard -->
        <I18n :languages="themeConfig.app.i18n.langConfig" />
        <span class="site-header__divider site-header__desktop" aria-hidden="true" />

        <template v-if="isLoggedIn">
          <button
            type="button"
            class="site-header__link site-header__desktop"
            @click="logout"
          >
            {{ $t("Log out") }}
          </button>
          <RouterLink :to="dashboard" class="site-btn site-btn--small">{{
            $t("My dashboard")
          }}</RouterLink>
        </template>
        <template v-else>
          <RouterLink
            :to="loginRoute(redirect)"
            class="site-header__link site-header__desktop"
            >{{ $t("Log in") }}</RouterLink
          >
          <RouterLink
            :to="signUpRoute(redirect)"
            class="site-btn site-btn--small site-header__desktop"
            >{{ $t("Sign up free") }}</RouterLink
          >
        </template>

        <button
          type="button"
          class="site-header__menu-btn"
          :aria-expanded="isMenuOpen"
          :aria-label="$t('Main menu')"
          @click="isMenuOpen = !isMenuOpen"
        >
          <VIcon :icon="isMenuOpen ? 'tabler-x' : 'tabler-menu-2'" size="24" />
        </button>
      </div>
    </div>

    <!-- Phone menu -->
    <Transition name="site-fade">
      <div v-if="isMenuOpen" class="site-header__panel">
        <div class="site-container">
          <RouterLink :to="lessonsRoute()" class="site-header__panel-link">{{
            $t("Lessons")
          }}</RouterLink>
          <template v-if="!isLoggedIn">
            <RouterLink
              :to="loginRoute(redirect)"
              class="site-header__panel-link"
              >{{ $t("Log in") }}</RouterLink
            >
            <RouterLink
              :to="signUpRoute(redirect)"
              class="site-btn site-header__panel-btn"
              >{{ $t("Sign up free") }}</RouterLink
            >
          </template>
          <button
            v-else
            type="button"
            class="site-header__panel-link"
            @click="logout"
          >
            {{ $t("Log out") }}
          </button>
        </div>
      </div>
    </Transition>
  </header>
</template>

<style lang="scss" scoped>
.site-header {
  position: sticky;
  z-index: 10;
  inset-block-start: 0;
  border-block-end: 1px solid transparent;
  background: #fff;
  transition: border-color 0.2s, background-color 0.2s;

  // After scrolling: a thin line and a light see-through white
  &--raised {
    border-block-end-color: var(--rien-line);
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: saturate(180%) blur(14px);
  }
}

.site-header__bar {
  display: flex;
  align-items: center;
  height: var(--rien-header-height);
}

.site-header__logo {
  display: inline-flex;
  padding-block: 6px;
}

.site-header__actions {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-inline-start: auto;
}

// Thin line between the language and the account buttons
.site-header__divider {
  width: 1px;
  height: 20px;
  margin-inline: -4px;
  background: var(--rien-line);
}

.site-header__link {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  padding: 0;
  border: 0;
  background: none;
  color: var(--rien-ink);
  font: inherit;
  font-size: 15px;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;

  &:hover,
  &.router-link-active {
    color: var(--rien-blue);
  }
}

.site-header__menu-btn {
  display: none;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  margin-inline-end: -10px;
  border: 0;
  background: none;
  color: var(--rien-ink);
  cursor: pointer;
}

.site-header__panel {
  border-block-start: 1px solid var(--rien-line);
  background: #fff;
  padding-block: 8px 20px;

  .site-container {
    display: flex;
    flex-direction: column;
  }
}

.site-header__panel-link {
  display: flex;
  align-items: center;
  width: 100%;
  min-height: 52px;
  padding: 0;
  border: 0;
  border-block-end: 1px solid var(--rien-line);
  background: none;
  color: inherit;
  font: inherit;
  cursor: pointer;
  font-size: 17px;
  font-weight: 500;
  text-decoration: none;
}

.site-header__panel-btn {
  margin-block-start: 20px;
}

@media (max-width: 767px) {
  .site-header__actions {
    gap: 8px;
  }

  .site-header__desktop {
    display: none;
  }

  .site-header__menu-btn {
    display: inline-flex;
  }
}
</style>
