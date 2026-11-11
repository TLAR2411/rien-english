<script setup>
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { resetPassword } from "@/services/password";
import { forgotPasswordRoute, loginRoute } from "@/utils/siteRoutes";
import { debounce } from "lodash";

/**
 * "Set a new password": opened from the link in the reset email
 * (/reset-password?token=...&email=...). Each link works one time only.
 */
definePage({
  meta: {
    title: "Set a new password",
    layout: "public",
    public: true,
    unauthenticatedOnly: true,
  },
});

const { t } = useI18n();
const route = useRoute();

const token = typeof route.query.token === "string" ? route.query.token : "";
const email = typeof route.query.email === "string" ? route.query.email : "";

const refForm = ref();
const isLoading = ref(false);
const isPasswordVisible = ref(false);
const isDone = ref(false);
// The link is broken, used or too old: no form, only "ask for a new link"
const isLinkBad = ref(!token || !email);
const formError = ref("");
const fieldErrors = ref({});

const form = ref({ password: "", password_confirmation: "" });

// Same messages as API/app/Http/Requests/Auth/ResetPasswordRequest.php
const PASSWORD_HINT = "Use at least 8 characters, with letters and numbers.";

const rules = {
  password: [(v) => (v?.length >= 8 && /[a-z]/i.test(v) && /\d/.test(v)) || t(PASSWORD_HINT)],
  confirm: [(v) => v === form.value.password || t("The two passwords are not the same.")],
};

watch(form, () => (fieldErrors.value = {}), { deep: true });

const showServerErrors = (error) => {
  const { status, data } = error.response ?? {};

  if (status === 422 && data?.errors?.password) {
    fieldErrors.value = { password: data.errors.password.map((m) => t(m)) };
  } else if (status === 422) {
    isLinkBad.value = true; // wrong, used or expired link
  } else if (status === 429) {
    formError.value = t("Too many tries. Please wait one minute and try again.");
  } else {
    formError.value = t("Something went wrong. Please try again.");
  }
};

const submit = async () => {
  formError.value = "";
  const { valid } = await refForm.value.validate();
  if (!valid) return;

  isLoading.value = true;
  try {
    await resetPassword({ token, email, ...form.value });
    isDone.value = true;
  } catch (error) {
    showServerErrors(error);
  } finally {
    isLoading.value = false;
  }
};

const onSubmit = debounce(submit, 400, { leading: true, trailing: false });
</script>

<template>
  <div class="site-section">
    <div class="site-container auth-page">
      <!-- Password changed -->
      <template v-if="isDone">
        <h1 class="site-h2">{{ $t("Your password is changed") }}</h1>
        <p class="site-lead">{{ $t("Log in with your email and your new password.") }}</p>
        <RouterLink :to="loginRoute()" class="site-btn auth-page__btn">{{ $t("Log in") }}</RouterLink>
      </template>

      <!-- Broken, used or expired link -->
      <template v-else-if="isLinkBad">
        <h1 class="site-h2">{{ $t("This link does not work any more") }}</h1>
        <p class="site-lead">{{ $t("Each link works one time, for 60 minutes. Please ask for a new one.") }}</p>
        <RouterLink :to="forgotPasswordRoute(email)" class="site-btn auth-page__btn">
          {{ $t("Send me a new link") }}
        </RouterLink>
      </template>

      <!-- New password form -->
      <template v-else>
        <h1 class="site-h2">{{ $t("Set a new password") }}</h1>
        <p class="site-lead">{{ $t("For the account") }} <strong class="auth-page__email">{{ email }}</strong></p>

        <VForm ref="refForm" class="auth-page__form" @submit.prevent="onSubmit">
          <!-- Lets password managers save the new password for this email -->
          <input type="email" :value="email" autocomplete="username" hidden />

          <AppTextField
            v-model="form.password"
            label="New password"
            :type="isPasswordVisible ? 'text' : 'password'"
            autocomplete="new-password"
            autofocus
            :hint="$t(PASSWORD_HINT)"
            :rules="rules.password"
            :error-messages="fieldErrors.password"
            :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
            @click:append-inner="isPasswordVisible = !isPasswordVisible"
          />

          <AppTextField
            v-model="form.password_confirmation"
            label="Confirm password"
            :type="isPasswordVisible ? 'text' : 'password'"
            autocomplete="new-password"
            :rules="rules.confirm"
          />

          <p v-if="formError" class="auth-page__error" role="alert">{{ formError }}</p>

          <button type="submit" class="site-btn auth-page__btn" :disabled="isLoading">
            {{ isLoading ? $t("Saving...") : $t("Save new password") }}
          </button>
        </VForm>
      </template>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use "@/assets/styles/auth-page";

.auth-page__email {
  word-break: break-all;
}
</style>
