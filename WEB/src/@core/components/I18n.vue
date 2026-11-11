<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import FlagKh from "@/assets/images/flags/kh.svg?url";
import FlagEn from "@/assets/images/flags/en.svg?url";

// Map language codes to flags

const props = defineProps({
  languages: {
    type: Array,
    required: true,
  },
  location: {
    type: String,
    default: "bottom end",
  },
});
const flagMap = {
  km: FlagKh,
  en: FlagEn,
};
const { locale } = useI18n({ useScope: "global" });

// Computed current flag
const currentFlag = computed(() => flagMap[locale.value.toLowerCase()] || null);

// Change locale method
const changeLocale = (lang) => {
  locale.value = lang;
};
</script>

<template>
  <IconBtn>
    <!-- Current Language Flag -->
    <img
      v-if="currentFlag"
      :src="currentFlag"
      alt="Current Language Flag"
      class="flag-icon"
      width="24"
      height="24"
    />
    <span v-else>No Flag</span>

    <!-- Language Selection Menu -->
    <VMenu
      activator="parent"
      :location="props.location"
      offset="12px"
      width="175"
    >
      <VList :selected="[locale]" color="primary">
        <VListItem
          v-for="lang in props.languages"
          :key="lang.i18nLang"
          :value="lang.i18nLang"
          @click="changeLocale(lang.i18nLang)"
        >
          <div class="d-flex align-center">
            <img
              v-if="flagMap[lang.i18nLang.toLowerCase()]"
              :src="flagMap[lang.i18nLang.toLowerCase()]"
              :alt="`${lang.label} Flag`"
              class="me-2"
              width="24"
              height="24"
            />
            <VListItemTitle>{{ $t(lang.label) }}</VListItemTitle>
          </div>
        </VListItem>
      </VList>
    </VMenu>
  </IconBtn>
</template>

<style scoped>
.flag-icon {
  display: block;
}

</style>
