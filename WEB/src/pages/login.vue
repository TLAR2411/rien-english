<script setup>
import authV1BottomShape from "@images/svg/auth-v1-bottom-shape.svg?raw";
import authV1TopShape from "@images/svg/auth-v1-top-shape.svg?raw";
import { VNodeRenderer } from "@layouts/components/VNodeRenderer";
import { themeConfig } from "@themeConfig";
import { useAuthStore } from "@/stores/authStore";
import { api } from "@/utils/api";
import { debounce } from "lodash";

import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { requiredValidator } from "@/@core/utils/validators";
import { usePwaInstall } from "@/composables/usePwaInstall";

const { canInstall, needsIosInstructions } = usePwaInstall();
const showInstallRow = computed(
  () => canInstall.value || needsIosInstructions.value,
);

definePage({
  meta: {
    title: "Login",
    layout: "blank",
    unauthenticatedOnly: true,
  },
});

const company = import.meta.env.VITE_BASE_COMPANY;
const MainLogo = `/logo/${company}/logo-64.png`;

// Stays empty until loaded, so the built-in name doesn't flash first.
const appName = ref("");

const loadAppName = async () => {
  try {
    const res = await api.get("app-name");
    appName.value = res.data.data || themeConfig.app.mainTitle;
  } catch {
    appName.value = themeConfig.app.mainTitle;
  }
};

onMounted(loadAppName);

const refForm = ref();
const isLoading = ref(false);
const isPasswordVisible = ref(false);

const form = ref({
  username: import.meta.env.VITE_LOGIN_USERNAME || null,
  password: import.meta.env.VITE_LOGIN_PASSWORD || null,
  remember: false,
});

// Fires the native browser permission prompt as soon as the page loads.
// Best-effort only: whatever the user answers, login still proceeds —
// coords are attached when we have them, left null otherwise.
const coords = ref({ latitude: null, longitude: null });

const requestLocation = () => {
  if (!navigator.geolocation) return;

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      coords.value = {
        latitude: pos.coords.latitude,
        longitude: pos.coords.longitude,
      };
    },
    () => {}, // denied / timeout / unavailable — login still works without it
    { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 },
  );
};

onMounted(requestLocation);

const submit = async () => {
  const { valid } = await refForm.value.validate();
  if (!valid) return;

  isLoading.value = true;
  try {
    await useAuthStore().login({
      ...form.value,
      latitude: coords.value.latitude,
      longitude: coords.value.longitude,
    });
  } finally {
    isLoading.value = false;
  }
};

const onSubmit = debounce(submit, 400, { leading: true, trailing: false });
</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center">
    <div class="position-relative my-sm-16">
      <!-- 👉 Top shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1TopShape })"
        class="text-primary auth-v1-top-shape d-none d-sm-block"
      />

      <!-- 👉 Bottom shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1BottomShape })"
        class="text-primary auth-v1-bottom-shape d-none d-sm-block"
      />

      <!-- 👉 Auth Card -->
      <VCard class="auth-card" width="350" max-width="90vw">
        <VCardItem class="justify-center text-center pb-2">
          <!-- Logo Container -->
          <div class="logo-wrapper mx-auto mb-1">
            <img
              class="app-logo-img"
              :src="MainLogo"
              alt="App Logo"
              style="width: 50px !important"
            />
          </div>

          <!-- App Title -->
          <VCardTitle
            class="text-primary font-weight-bold"
            style="font-family: moul; font-size: 16px !important"
          >
            {{ appName || '\u00A0' }}
          </VCardTitle>
          <VCardSubtitle
            class="text-medium-emphasis mt-1"
            style="font-size: 13px !important"
          >
            សូមបញ្ចូលគណនីរបស់អ្នកដើម្បីបន្ត
          </VCardSubtitle>
        </VCardItem>
        <VDivider class="mb-2" />

        <VCardText class="pt-2">
          <VForm ref="refForm" @submit.prevent="onSubmit">
            <VRow>
              <!-- username -->
              <VCol cols="12" class="mb-0 pb-0">
                <AppTextField
                  v-model="form.username"
                  autofocus
                  type="text"
                  autocomplete="username"
                  prepend-inner-icon="tabler-user"
                  :rules="[requiredValidator]"
                >
                  <template #label>{{ $t("Email or Username") }}</template>
                </AppTextField>
              </VCol>

              <!-- password -->
              <VCol cols="12" class="mt-1 mb-0 pb-0">
                <AppTextField
                  v-model="form.password"
                  prepend-inner-icon="tabler-key"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="current-password"
                  :rules="[requiredValidator]"
                  :append-inner-icon="
                    isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
                  "
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                >
                  <template #label>{{ $t("Password") }}</template>
                </AppTextField>
              </VCol>

              <VCol cols="12" class="mt-0">
                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                  :disabled="isLoading"
                  class="mt-0 mb-1"
                >
                  {{ $t("Login") }}
                </VBtn>
              </VCol>

              <VCol v-if="showInstallRow" cols="12" class="pt-0">
                <PwaInstallButton />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth";

.auth-card {
  width: 350px !important;
  max-width: 90vw !important;
}
</style>
