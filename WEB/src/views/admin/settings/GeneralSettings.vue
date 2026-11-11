<script setup>
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import hasPermission from "@/utils/hasPermission";

/**
 * Admin › Generals › Settings: values from the `settings` table, grouped.
 * Secrets (passwords, tokens) are write-only: the server never sends them;
 * leave the field empty to keep the current value.
 */
const { t } = useI18n();

const isLoading = ref(false);
const isSaving = ref(false);
const settings = ref([]);
const form = ref({}); // key -> value being edited
const original = ref({}); // key -> value as loaded
const reveal = ref({}); // key -> show typed secret
const canEdit = hasPermission("edit-settings");

// Known keys: section, label, icon, hint. Anything else goes to "Other".
const KNOWN = {
  company_name: { group: "company", label: "Company Name", icon: "tabler-building" },
  company_email: { group: "company", label: "Company Email Domain", icon: "tabler-at", hint: "Used to make new users' email, e.g. @bpk.com" },
  system_url: { group: "company", label: "System URL", icon: "tabler-world", hint: "Shown in Telegram messages" },
  default_password: { group: "users", label: "Default Password", icon: "tabler-key", hint: "Given to new users and on password reset" },
  default_sub_user_password: { group: "users", label: "Default Sub User Password", icon: "tabler-key" },
  telegram_bot_token: { group: "telegram", label: "Telegram Bot Token", icon: "tabler-robot", hint: "From @BotFather" },
  default_login_alert_telegram: { group: "telegram", label: "Login Alert Chat ID", icon: "tabler-bell", hint: "Group that receives login alerts" },
  default_request_user_group_id: { group: "telegram", label: "User Request Group ID", icon: "tabler-users", hint: "Group that receives new user requests" },
};

const GROUPS = [
  { key: "company", title: "Company", icon: "tabler-building" },
  { key: "users", title: "Users", icon: "tabler-users" },
  { key: "telegram", title: "Telegram", icon: "tabler-brand-telegram" },
  { key: "other", title: "Other", icon: "tabler-adjustments" },
];

const sections = computed(() =>
  GROUPS.map((g) => ({
    ...g,
    items: settings.value.filter((s) => (KNOWN[s.key]?.group || "other") === g.key),
  })).filter((g) => g.items.length),
);

const label = (s) => t(KNOWN[s.key]?.label || s.name || s.key);

const load = async () => {
  isLoading.value = true;
  try {
    const res = await api.get("settings-all");
    if (res.data.status) {
      settings.value = res.data.data;
      const values = {};
      for (const s of settings.value) {
        // Secrets start empty (= keep); bools as real booleans
        values[s.key] = s.is_secret ? "" : s.type === "bool" ? s.value === "1" || s.value === "true" : s.value ?? "";
      }
      form.value = { ...values };
      original.value = { ...values };
      reveal.value = {};
    }
  } catch (error) {
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(load);

const changedKeys = computed(() =>
  settings.value
    .filter((s) => (s.is_secret ? form.value[s.key] !== "" : form.value[s.key] !== original.value[s.key]))
    .map((s) => s.key),
);

const reset = () => {
  form.value = { ...original.value };
};

const save = async () => {
  if (!changedKeys.value.length || isSaving.value) return;
  isSaving.value = true;
  try {
    const values = {};
    for (const key of changedKeys.value) values[key] = form.value[key];
    const res = await api.put("settings", { values });
    if (res.data.status) await load();
  } catch (error) {
    console.error(error);
  } finally {
    isSaving.value = false;
  }
};
</script>

<template>
  <AppCard title="Settings" title-icon="tabler-settings" :is-back="false" :loading="isLoading">
    <div class="settings">
      <VCard v-for="section in sections" :key="section.key" variant="outlined" class="section">
        <div class="section__head">
          <span class="section__icon"><VIcon :icon="section.icon" size="18" /></span>
          {{ $t(section.title) }}
        </div>
        <VDivider />

        <div class="section__body">
          <div v-for="s in section.items" :key="s.key" class="row" :class="{ 'row--changed': changedKeys.includes(s.key) }">
            <div class="row__label">
              <VIcon :icon="KNOWN[s.key]?.icon || 'tabler-point'" size="18" />
              <div class="d-flex flex-column">
                <span>{{ label(s) }}</span>
                <span v-if="KNOWN[s.key]?.hint" class="row__hint">{{ $t(KNOWN[s.key].hint) }}</span>
              </div>
            </div>

            <div class="row__field">
              <!-- Secret: write-only -->
              <template v-if="s.is_secret">
                <AppTextField
                  v-model="form[s.key]"
                  :type="reveal[s.key] ? 'text' : 'password'"
                  :placeholder="s.has_value ? $t('Leave empty to keep the current value') : $t('Not set')"
                  :disabled="!canEdit"
                  autocomplete="new-password"
                  :append-inner-icon="form[s.key] ? (reveal[s.key] ? 'tabler-eye-off' : 'tabler-eye') : undefined"
                  @click:append-inner="reveal[s.key] = !reveal[s.key]"
                />
                <VChip size="x-small" label :color="s.has_value ? 'success' : 'warning'" class="mt-1">
                  <VIcon start :icon="s.has_value ? 'tabler-lock' : 'tabler-alert-triangle'" size="12" />
                  {{ $t(s.has_value ? "Set (hidden)" : "Not set") }}
                </VChip>
              </template>

              <VSwitch v-else-if="s.type === 'bool'" v-model="form[s.key]" color="primary" inset hide-details :disabled="!canEdit" />

              <AppSelect v-else-if="s.type === 'enum'" v-model="form[s.key]" :items="s.enum_value || []" :disabled="!canEdit" />

              <AppTextField
                v-else
                v-model="form[s.key]"
                :type="['int', 'decimal'].includes(s.type) ? 'number' : s.type === 'time' ? 'time' : s.type === 'date' ? 'date' : 'text'"
                :disabled="!canEdit"
              />
            </div>
          </div>
        </div>
      </VCard>
    </div>

    <!-- Save bar -->
    <div v-if="canEdit" class="save-bar">
      <span class="text-body-2 text-medium-emphasis">
        <template v-if="changedKeys.length">{{ changedKeys.length }} {{ $t("unsaved changes") }}</template>
        <template v-else>{{ $t("No changes") }}</template>
      </span>
      <VSpacer />
      <VBtn variant="tonal" color="secondary" :disabled="!changedKeys.length || isSaving" @click="reset">
        {{ $t("Reset") }}
      </VBtn>
      <VBtn color="primary" prepend-icon="tabler-device-floppy" :disabled="!changedKeys.length" :loading="isSaving" @click="save">
        {{ $t("Save") }}
      </VBtn>
    </div>
  </AppCard>
</template>

<style scoped>
.settings {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.section__head {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.75rem 1.25rem;
  font-size: 15px;
  font-weight: 500;
}

.section__icon {
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.1);
}

.row {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 3fr);
  align-items: start;
  gap: 1rem;
  padding: 0.875rem 1.25rem;
  border-inline-start: 3px solid transparent;
}

.row + .row {
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.row--changed {
  border-inline-start-color: rgb(var(--v-theme-warning));
  background: rgba(var(--v-theme-warning), 0.04);
}

.row__label {
  display: flex;
  align-items: flex-start;
  gap: 0.625rem;
  padding-top: 0.5rem;
  font-size: 14px;
}

.row__label > .v-icon {
  margin-top: 2px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.row__hint {
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.save-bar {
  position: sticky;
  bottom: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1.25rem;
  padding: 0.75rem 0;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
}

@media (max-width: 599px) {
  .row {
    grid-template-columns: 1fr;
    gap: 0.25rem;
  }

  .row__label {
    padding-top: 0;
  }
}
</style>
