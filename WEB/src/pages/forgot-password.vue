<script setup>
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { sendResetLink } from "@/services/password";
import { loginRoute } from "@/utils/siteRoutes";
import { debounce } from "lodash";

/**
 * "Forgot password?": the student enters their email and gets a link to set
 * a new password (pages/reset-password.vue). The reply is the same for every
 * email, so this page never shows whether an account exists.
 */
definePage({
  meta: {
    title: "Forgot password",
    layout: "public",
    public: true,
    unauthenticatedOnly: true,
  },
});

const { t } = useI18n();
const route = useRoute();

const refForm = ref();
const email = ref(typeof route.query.email === "string" ? route.query.email : "");
const isLoading = ref(false);
const isSent = ref(false);
const formError = ref("");
const fieldError = ref([]);

// Same messages as API/app/Http/Requests/Auth/ForgotPasswordRequest.php
const emailRules = [
  (v) => !!v?.trim() || t("Please enter your email."),
  (v) => /^\S+@\S+\.\S+$/.test(v?.trim()) || t("Please enter a valid email."),
];

watch(email, () => (fieldError.value = []));

const submit = async () => {
  formError.value = "";
  const { valid } = await refForm.value.validate();
  if (!valid) return;

  isLoading.value = true;
  try {
    await sendResetLink(email.value.trim());
    isSent.value = true;
  } catch (error) {
    const { status, data } = error.response ?? {};
    if (status === 422 && data?.errors?.email) fieldError.value = data.errors.email.map((m) => t(m));
    else if (status === 429) formError.value = t("Too many tries. Please wait one minute and try again.");
    else formError.value = t("Something went wrong. Please try again.");
  } finally {
    isLoading.value = false;
  }
};

const onSubmit = debounce(submit, 400, { leading: true, trailing: false });
</script>

<template>
  <div class="site-section">
    <div class="site-container auth-page">
      <!-- Step 2: link sent -->
      <template v-if="isSent">
        <h1 class="site-h2">{{ $t("Check your email") }}</h1>
        <p class="site-lead">
          {{ $t("If this email has an account, we sent a link to reset the password.") }}
        </p>
        <p class="auth-page__email">{{ email.trim() }}</p>
        <p class="auth-page__note">
          {{ $t("The link works for 60 minutes. Can't find the email? Look in your Spam folder.") }}
        </p>

        <RouterLink :to="loginRoute(route.query.redirect)" class="site-btn auth-page__btn">
          {{ $t("Back to log in") }}
        </RouterLink>
        <p class="auth-page__switch">
          <button type="button" class="site-link auth-page__again" @click="isSent = false">
            {{ $t("Use a different email") }}
          </button>
        </p>
      </template>

      <!-- Step 1: ask for the email -->
      <template v-else>
        <h1 class="site-h2">{{ $t("Forgot your password?") }}</h1>
        <p class="site-lead">{{ $t("Enter the email you signed up with. We will send you a link to set a new password.") }}</p>

        <VForm ref="refForm" class="auth-page__form" @submit.prevent="onSubmit">
          <AppTextField
            v-model="email"
            label="Email"
            type="email"
            autocomplete="email"
            inputmode="email"
            autofocus
            :rules="emailRules"
            :error-messages="fieldError"
          />

          <p v-if="formError" class="auth-page__error" role="alert">{{ formError }}</p>

          <button type="submit" class="site-btn auth-page__btn" :disabled="isLoading">
            {{ isLoading ? $t("Sending...") : $t("Send the link") }}
          </button>
        </VForm>

        <p class="auth-page__switch">
          {{ $t("Remember your password?") }}
          <RouterLink :to="loginRoute(route.query.redirect)" class="site-link">{{ $t("Log in") }}</RouterLink>
        </p>
      </template>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use "@/assets/styles/auth-page";

.auth-page__email {
  margin-block-start: 16px;
  font-weight: 600;
  word-break: break-all;
}

.auth-page__again {
  border: 0;
  background: none;
  font: inherit;
  cursor: pointer;
}
</style>
