<script setup>
import { computed, ref } from "vue";
import { useDisplay } from "vuetify";
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { api } from "@/utils/api";

const { mdAndUp } = useDisplay();

const show1 = ref(false);
const show2 = ref(false);
const show3 = ref(false);
const isLoading = ref(false);

const emptyForm = () => ({
  old_password: "",
  new_password: "",
  new_password_confirmation: "",
});

const formData = ref(emptyForm());

const resetForm = () => {
  formData.value = emptyForm();
};

const requirements = computed(() => {
  const { old_password, new_password, new_password_confirmation } =
    formData.value;
  return [
    { label: "At least 8 characters", met: new_password.length >= 8 },
    { label: "Uppercase letter", met: /[A-Z]/.test(new_password) },
    { label: "Lowercase letter", met: /[a-z]/.test(new_password) },
    { label: "A number or symbol", met: /[\d\W_]/.test(new_password) },
    {
      label: "Different from old password",
      met: !!new_password && new_password !== old_password,
    },
    {
      label: "Passwords match",
      met: !!new_password && new_password === new_password_confirmation,
    },
  ];
});

// Score uses only the character-based rules (first four).
const strength = computed(() => {
  if (!formData.value.new_password) return null;
  const score = requirements.value.slice(0, 4).filter((r) => r.met).length;
  return [
    { label: "Weak", color: "error", value: 25 },
    { label: "Weak", color: "error", value: 25 },
    { label: "Fair", color: "warning", value: 50 },
    { label: "Good", color: "info", value: 75 },
    { label: "Strong", color: "success", value: 100 },
  ][score];
});

const canSubmit = computed(
  () =>
    formData.value.old_password.length >= 8 &&
    requirements.value.every((r) => r.met),
);

const metCount = computed(
  () => requirements.value.filter((r) => r.met).length,
);

const confirmMismatch = computed(
  () =>
    !!formData.value.new_password_confirmation &&
    formData.value.new_password !== formData.value.new_password_confirmation,
);

const onSubmit = async () => {
  if (!canSubmit.value) return;
  try {
    isLoading.value = true;
    const res = await api.post("users-password", formData.value);
    if (res.data.status) {
      resetForm();
    } else {
      console.error("Error with the response:", res.data);
    }
  } catch (error) {
    console.error("Failed to change password:", error);
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <AppCard :is-header="false" border="border-none">
    <div class="security-layout">
      <!-- Form -->
      <VCard variant="outlined" class="security-card">
        <div class="security-card__header">
          <VAvatar color="primary" variant="tonal" rounded size="40">
            <VIcon icon="tabler-key" size="22" />
          </VAvatar>
          <div class="d-flex flex-column">
            <span class="security-card__title">{{
              $t("Change Password")
            }}</span>
            <span
              class="text-body-2 text-medium-emphasis"
              style="font-family: inter, kantumruy, sans-serif !important"
            >
              {{
                $t("Update your password regularly to keep your account secure")
              }}
            </span>
          </div>
        </div>
        <VDivider />

        <form class="security-card__body" @submit.prevent="onSubmit">
          <AppTextField
            v-model="formData.old_password"
            prepend-inner-icon="tabler-lock"
            autocomplete="current-password"
            :append-inner-icon="show1 ? 'tabler-eye-off' : 'tabler-eye'"
            :type="show1 ? 'text' : 'password'"
            @click:append-inner="show1 = !show1"
          >
            <template #label>{{ $t("Old Password") }}</template>
          </AppTextField>

          <VDivider class="my-1" />

          <div>
            <AppTextField
              v-model="formData.new_password"
              prepend-inner-icon="tabler-lock-password"
              autocomplete="new-password"
              :append-inner-icon="show2 ? 'tabler-eye-off' : 'tabler-eye'"
              :type="show2 ? 'text' : 'password'"
              @click:append-inner="show2 = !show2"
            >
              <template #label>{{ $t("New Password") }}</template>
            </AppTextField>
            <div v-if="strength" class="strength mt-2">
              <VProgressLinear
                :model-value="strength.value"
                :color="strength.color"
                height="6"
                rounded
              />
              <span
                class="text-caption"
                :class="`text-${strength.color}`"
                style="font-family: inter, kantumruy, sans-serif !important"
              >
                {{ $t(strength.label) }}
              </span>
            </div>

            <!-- Compact checklist (mobile) -->
            <div v-if="!mdAndUp" class="checklist-inline mt-3">
              <div class="checklist-inline__head">
                <span>{{ $t("Ensure that these requirements are met") }}</span>
                <VChip
                  size="x-small"
                  label
                  :color="
                    metCount === requirements.length ? 'success' : 'primary'
                  "
                >
                  {{ metCount }}/{{ requirements.length }}
                </VChip>
              </div>
              <ul class="checklist-inline__list">
                <li
                  v-for="r in requirements"
                  :key="r.label"
                  :class="r.met ? 'text-success' : 'text-medium-emphasis'"
                >
                  <VIcon
                    :icon="
                      r.met ? 'tabler-circle-check-filled' : 'tabler-circle'
                    "
                    size="15"
                  />
                  <span>{{ $t(r.label) }}</span>
                </li>
              </ul>
            </div>
          </div>

          <AppTextField
            v-model="formData.new_password_confirmation"
            prepend-inner-icon="tabler-lock-check"
            autocomplete="new-password"
            :error="confirmMismatch"
            :append-inner-icon="show3 ? 'tabler-eye-off' : 'tabler-eye'"
            :type="show3 ? 'text' : 'password'"
            @click:append-inner="show3 = !show3"
          >
            <template #label>{{ $t("Confirm Password") }}</template>
          </AppTextField>

          <div class="form-actions">
            <VBtn
              variant="tonal"
              color="secondary"
              :disabled="isLoading"
              @click="resetForm"
            >
              <VIcon start icon="tabler-refresh" />
              {{ $t("Clear") }}
            </VBtn>
            <VBtn
              type="submit"
              :loading="isLoading"
              :disabled="!canSubmit"
              class="form-actions__submit"
            >
              <VIcon start icon="tabler-check" />
              {{ $t("Submit") }}
            </VBtn>
          </div>
        </form>
      </VCard>

      <!-- Requirements (desktop) -->
      <VCard
        v-if="mdAndUp"
        variant="tonal"
        color="primary"
        class="requirements"
      >
        <div class="requirements__header">
          <VIcon icon="tabler-shield-lock" size="20" />
          <span>{{ $t("Ensure that these requirements are met") }}</span>
        </div>
        <ul class="requirements__list">
          <li
            v-for="r in requirements"
            :key="r.label"
            :class="r.met ? 'text-success' : 'text-medium-emphasis'"
          >
            <VIcon
              :icon="r.met ? 'tabler-circle-check-filled' : 'tabler-circle'"
              size="18"
            />
            <span>{{ $t(r.label) }}</span>
          </li>
        </ul>
      </VCard>
    </div>
  </AppCard>
</template>

<style scoped>
.security-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 1.25rem;
  align-items: start;
}

.security-card__header {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.25rem;
}

.security-card__title {
  font-size: 15px;
  font-weight: 600;
}

.security-card__body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.25rem;
  max-width: 560px;
}

.strength {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.strength .text-caption {
  min-width: 48px;
  font-weight: 600;
}

.requirements {
  padding: 1.25rem;
}

.requirements__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.requirements__list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.requirements__list li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 14px;
  transition: color 0.2s ease;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

/* Compact inline checklist (mobile) */
.checklist-inline {
  padding: 0.75rem;
  border-radius: 8px;
  background: rgba(var(--v-theme-primary), 0.06);
}

.checklist-inline__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  font-size: 12.5px;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.checklist-inline__list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.375rem 0.75rem;
}

.checklist-inline__list li {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 12.5px;
  line-height: 1.35;
  transition: color 0.2s ease;
}

.checklist-inline__list li .v-icon {
  flex-shrink: 0;
}

@media (max-width: 959px) {
  .security-layout {
    grid-template-columns: 1fr;
  }

  .security-card__body {
    max-width: none;
  }
}

@media (max-width: 599px) {
  /* Flatten the inner card so the form uses the full width */
  .security-card {
    border: none !important;
  }

  .security-card__header {
    padding: 0.25rem 0 0.875rem;
  }

  .security-card__body {
    padding: 1rem 0 0.25rem;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .form-actions .v-btn {
    width: 100%;
  }

  .form-actions__submit {
    height: 44px !important;
  }
}

@media (max-width: 359px) {
  .checklist-inline__list {
    grid-template-columns: 1fr;
  }
}
</style>
