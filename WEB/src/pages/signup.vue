<script setup>
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { useAuthStore } from "@/stores/authStore";
import { loginRoute } from "@/utils/siteRoutes";
import { debounce } from "lodash";

/**
 * Student sign-up. Only the English name, email and password are needed.
 * After sign-up the student is logged in and goes back to the page that sent
 * them here (?redirect=, e.g. a lesson's quiz), else to their home page.
 */
definePage({
  meta: {
    title: "Sign up",
    layout: "public",
    public: true,
    unauthenticatedOnly: true,
  },
});

const { t } = useI18n();
const route = useRoute();

const refForm = ref();
const isLoading = ref(false);
const isPasswordVisible = ref(false);
const formError = ref("");
const fieldErrors = ref({});

const form = ref({
  name_en: "",
  name_kh: "",
  email: "",
  password: "",
  password_confirmation: "",
  website: "", // hidden spam trap: people never fill it in
});

// Same messages as API/app/Http/Requests/Auth/RegisterRequest.php
const PASSWORD_HINT = "Use at least 8 characters, with letters and numbers.";

const rules = {
  name: [(v) => !!v?.trim() || t("Please enter your name.")],
  email: [
    (v) => !!v?.trim() || t("Please enter your email."),
    (v) => /^\S+@\S+\.\S+$/.test(v?.trim()) || t("Please enter a valid email."),
  ],
  password: [(v) => (v?.length >= 8 && /[a-z]/i.test(v) && /\d/.test(v)) || t(PASSWORD_HINT)],
  confirm: [(v) => v === form.value.password || t("The two passwords are not the same.")],
};

// A field's server error disappears as soon as the student changes it
watch(
  form,
  (now, before) => {
    Object.keys(fieldErrors.value).forEach((key) => {
      if (now[key] !== before?.[key]) delete fieldErrors.value[key];
    });
  },
  { deep: true },
);

const showServerErrors = (error) => {
  const { status, data } = error.response ?? {};

  if (status === 422 && data?.errors) {
    fieldErrors.value = Object.fromEntries(
      Object.entries(data.errors).map(([field, messages]) => [field, messages.map((m) => t(m))]),
    );
    formError.value = data.errors.website ? t(data.errors.website[0]) : "";
  } else if (status === 429) {
    formError.value = t("Too many tries. Please wait one minute and try again.");
  } else {
    formError.value = t(data?.message || "Something went wrong. Please try again.");
  }
};

const submit = async () => {
  formError.value = "";
  const { valid } = await refForm.value.validate();
  if (!valid) return;

  isLoading.value = true;
  try {
    await useAuthStore().register(form.value);
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
      <h1 class="site-h2">{{ $t("Create your free account") }}</h1>
      <p class="site-lead">{{ $t("Take quizzes and keep your progress. Reading lessons stays free.") }}</p>

      <VForm ref="refForm" class="auth-page__form" @submit.prevent="onSubmit">
        <AppTextField
          v-model="form.name_en"
          label="Your name (English)"
          autocomplete="name"
          :rules="rules.name"
          :error-messages="fieldErrors.name_en"
        />

        <AppTextField
          v-model="form.name_kh"
          label="Your name (Khmer) — optional"
          class="site-km"
          :error-messages="fieldErrors.name_kh"
        />

        <AppTextField
          v-model="form.email"
          label="Email"
          type="email"
          autocomplete="email"
          inputmode="email"
          :rules="rules.email"
          :error-messages="fieldErrors.email"
        />

        <AppTextField
          v-model="form.password"
          label="Password"
          :type="isPasswordVisible ? 'text' : 'password'"
          autocomplete="new-password"
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

        <!-- Spam trap: hidden from people and screen readers -->
        <div class="signup__trap" aria-hidden="true">
          <label>
            Website
            <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" />
          </label>
        </div>

        <p v-if="formError" class="auth-page__error" role="alert">{{ formError }}</p>

        <button type="submit" class="site-btn auth-page__btn" :disabled="isLoading">
          {{ isLoading ? $t("Creating your account...") : $t("Sign up free") }}
        </button>
      </VForm>

      <p class="auth-page__switch">
        {{ $t("Already have an account?") }}
        <RouterLink :to="loginRoute(route.query.redirect)" class="site-link">{{ $t("Log in") }}</RouterLink>
      </p>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use "@/assets/styles/auth-page";

// Off screen, not display:none — some bots skip fields that are display:none
.signup__trap {
  position: absolute;
  inset-inline-start: -10000px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}
</style>
