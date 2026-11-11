<script setup>
import SiteFooter from "@/components/site/SiteFooter.vue";
import SiteHeader from "@/components/site/SiteHeader.vue";
import "@/assets/styles/site.scss";

/**
 * Layout of the public website (landing page, lessons): top bar + footer.
 * It looks like a normal website, not like the admin system.
 * A page uses it with definePage({ meta: { layout: "public", public: true } }).
 */
const { locale } = useI18n({ useScope: "global" });
</script>

<template>
  <div class="site" :class="{ 'site--km': locale === 'km' }">
    <SiteHeader />
    <main class="site-main">
      <RouterView v-slot="{ Component }">
        <Suspense>
          <Component :is="Component" />
        </Suspense>
      </RouterView>
    </main>
    <SiteFooter />
  </div>
</template>
