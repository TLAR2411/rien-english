<script setup>
import RienLogo from "@/components/site/RienLogo.vue";
import { useAuthStore } from "@/stores/authStore";
import { lessonsRoute, loginRoute, signUpRoute } from "@/utils/siteRoutes";

const auth = useAuthStore();
const isLoggedIn = computed(() => auth.isAuthenticated && Boolean(auth.user));

const year = new Date().getFullYear();
</script>

<template>
  <footer class="site-footer">
    <div class="site-container site-footer__inner">
      <div>
        <RienLogo :height="24" />
        <p class="site-footer__tagline">{{ $t("Learn English, step by step.") }}</p>
      </div>

      <nav class="site-footer__links" :aria-label="$t('Footer')">
        <RouterLink :to="lessonsRoute()">{{ $t("Lessons") }}</RouterLink>
        <template v-if="!isLoggedIn">
          <RouterLink :to="signUpRoute()">{{ $t("Sign up free") }}</RouterLink>
          <RouterLink :to="loginRoute()">{{ $t("Log in") }}</RouterLink>
        </template>
      </nav>
    </div>
    <div class="site-container">
      <p class="site-footer__copy">© {{ year }} Rien English</p>
    </div>
  </footer>
</template>

<style lang="scss" scoped>
.site-footer {
  border-block-start: 1px solid var(--rien-line);
  background: var(--rien-bg-alt);
  padding-block: 40px 28px;
  color: var(--rien-grey);
  font-size: 15px;
}

.site-footer__inner {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 24px;
}

.site-footer__tagline {
  margin-block-start: 10px;
}

.site-footer__links {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 28px;

  a {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    text-decoration: none;

    &:hover {
      color: var(--rien-blue);
    }
  }
}

.site-footer__copy {
  margin-block-start: 24px;
  font-size: 13px;
}
</style>
