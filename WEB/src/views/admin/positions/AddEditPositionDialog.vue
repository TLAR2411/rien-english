<script setup>
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import AppDialog from "@/components/AppDialog.vue";
import AppAutocomplete from "@core/components/app-form-elements/AppAutocomplete.vue";
import { requiredValidator } from "@/@core/utils/validators";

/**
 * Create / edit a position: details, salary amounts and its loan-system role.
 * Saving is done by the list (onCreate / onUpdate events, same as before).
 */
const props = defineProps({
  itemData: { type: Object, default: () => ({}) },
  departments: { type: Array, default: () => [] },
  isDialogVisible: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["onCreate", "onUpdate", "update:isDialogVisible"]);

const { t } = useI18n();

const emptyForm = () => ({
  name_kh: "",
  name_en: "",
  abbr: "",
  level: 1,
  department_id: null,
  insurance_amount: 10000,
  position_fee: 0,
  is_member: false,
  is_leader: false,
});

const fromItem = (data) => ({
  ...emptyForm(),
  ...data,
  is_member: !!Number(data?.is_member ?? 0),
  is_leader: !!Number(data?.is_leader ?? 0),
});

const itemData = ref(fromItem(props.itemData));
const isEdit = computed(() => !!itemData.value.id);

watch(
  () => props.itemData,
  (data) => {
    itemData.value = fromItem(data);
  },
);

// Loan-system roles: used by the loan reports and credit officer checks
const ROLES = [
  {
    key: "is_member",
    title: "Credit Officer",
    sub: "Manages clients and loans; counted in credit officer reports",
    icon: "tabler-user-dollar",
  },
  {
    key: "is_leader",
    title: "Team Leader",
    sub: "Leads a group of credit officers; sees their loans",
    icon: "tabler-users-group",
  },
];

// ---------- Rules ----------
const money = (v) => Number(String(v ?? "").replace(/,/g, ""));
const abbrRule = (v) => /^[A-Za-z0-9]{1,10}$/.test(v || "") || t("Letters or numbers, up to 10");
const levelRule = (v) => (Number.isInteger(Number(v)) && Number(v) >= 1 && Number(v) <= 255) || t("Between 1 and 255");
const moneyRule = (v) => money(v) >= 0 || t("Must be 0 or more");

const onCloseDialog = () => {
  emit("update:isDialogVisible", false);
};

const onFormSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (!valid || props.loading) return;

  emit(isEdit.value ? "onUpdate" : "onCreate", {
    ...itemData.value,
    name_kh: itemData.value.name_kh.trim(),
    name_en: itemData.value.name_en.trim(),
    abbr: (itemData.value.abbr || "").toUpperCase(),
    level: Number(itemData.value.level),
    insurance_amount: money(itemData.value.insurance_amount),
    position_fee: money(itemData.value.position_fee),
  }, () => {});
};
</script>

<template>
  <AppDialog
    :title="isEdit ? 'Update Position' : 'Create Position'"
    title-icon="tabler-id-badge-2"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="(v) => !v && onCloseDialog()"
    :width="780"
    is-footer
    is-submit
    :submit-text="isEdit ? 'Update' : 'Create'"
    :submit-icon="isEdit ? 'tabler-device-floppy' : 'tabler-plus'"
    :submit-color="isEdit ? 'warning' : 'primary'"
    :loading="loading"
    @on-submit="onFormSubmit"
  >
    <div class="position-form">
      <!-- Position -->
      <section>
        <div class="section-title">
          <span class="section-title__icon"><VIcon icon="tabler-id-badge-2" size="15" /></span>
          {{ $t("Position") }}
        </div>
        <VRow>
          <VCol cols="12" sm="6">
            <AppTextField v-model="itemData.name_kh" placeholder="មន្ត្រីឥណទាន" :rules="[requiredValidator]">
              <template #label>{{ $t("Name Khmer") }} <span class="text-error">*</span></template>
            </AppTextField>
          </VCol>
          <VCol cols="12" sm="6">
            <AppTextField v-model="itemData.name_en" placeholder="Credit Officer" :rules="[requiredValidator]">
              <template #label>{{ $t("Name English") }} <span class="text-error">*</span></template>
            </AppTextField>
          </VCol>
          <VCol cols="6" sm="3">
            <AppTextField
              v-model="itemData.abbr"
              placeholder="CO"
              maxlength="10"
              class="abbr-field"
              :rules="[requiredValidator, abbrRule]"
              @update:model-value="(v) => (itemData.abbr = (v || '').toUpperCase())"
            >
              <template #label>{{ $t("Abbr") }} <span class="text-error">*</span></template>
            </AppTextField>
          </VCol>
          <VCol cols="6" sm="3">
            <AppTextField
              v-model="itemData.level"
              type="number"
              min="1"
              max="255"
              placeholder="1"
              :rules="[requiredValidator, levelRule]"
              :hint="$t('Higher = more senior')"
              persistent-hint
            >
              <template #label>{{ $t("Level") }} <span class="text-error">*</span></template>
            </AppTextField>
          </VCol>
          <VCol cols="12" sm="6">
            <AppAutocomplete
              v-model="itemData.department_id"
              :items="departments"
              item-value="id"
              item-title="name_kh"
              prepend-inner-icon="tabler-hierarchy-3"
              :rules="[requiredValidator]"
            >
              <template #label>{{ $t("Department") }} <span class="text-error">*</span></template>
            </AppAutocomplete>
          </VCol>
        </VRow>
      </section>

      <!-- Salary -->
      <section>
        <div class="section-title">
          <span class="section-title__icon"><VIcon icon="tabler-cash" size="15" /></span>
          {{ $t("Salary") }}
        </div>
        <VRow>
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="itemData.position_fee"
              format-currency
              prepend-inner-icon="tabler-coin"
              :rules="[moneyRule]"
              :hint="$t('Added to the gross salary of everyone in this position')"
              persistent-hint
            >
              <template #label>{{ $t("Position Fee") }}</template>
            </AppTextField>
          </VCol>
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="itemData.insurance_amount"
              format-currency
              prepend-inner-icon="tabler-shield-heart"
              :rules="[moneyRule]"
              :hint="$t('Amount used for staff insurance')"
              persistent-hint
            >
              <template #label>{{ $t("Insurance Amount") }}</template>
            </AppTextField>
          </VCol>
        </VRow>
      </section>

      <!-- Loan-system role -->
      <section>
        <div class="section-title">
          <span class="section-title__icon"><VIcon icon="tabler-briefcase" size="15" /></span>
          {{ $t("Loan System Role") }}
        </div>
        <div class="roles">
          <label
            v-for="role in ROLES"
            :key="role.key"
            class="role"
            :class="{ 'role--on': itemData[role.key] }"
          >
            <VAvatar :color="itemData[role.key] ? 'primary' : 'secondary'" variant="tonal" rounded size="38">
              <VIcon :icon="role.icon" size="20" />
            </VAvatar>
            <div class="d-flex flex-column flex-grow-1 min-w-0">
              <span class="role__title">{{ $t(role.title) }}</span>
              <span class="role__sub">{{ $t(role.sub) }}</span>
            </div>
            <VSwitch v-model="itemData[role.key]" color="primary" density="compact" hide-details inset />
          </label>
        </div>
      </section>
    </div>
  </AppDialog>
</template>

<style scoped>
.position-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Section headings (same style as the other dialogs) */
.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  font-size: 14px;
  font-weight: 500;
}

.section-title::after {
  content: "";
  flex: 1;
  height: 1px;
  background: rgba(var(--v-border-color), var(--v-border-opacity));
}

.section-title__icon {
  display: grid;
  place-items: center;
  width: 26px;
  height: 26px;
  border-radius: 7px;
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.1);
}

.abbr-field :deep(input) {
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
}

/* Role switches */
.roles {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.role {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}

.role--on {
  border-color: rgba(var(--v-theme-primary), 0.5);
  background: rgba(var(--v-theme-primary), 0.05);
}

.role__title {
  font-size: 14px;
  font-weight: 500;
}

.role__sub {
  font-size: 12px;
  line-height: 1.4;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

@media (max-width: 599px) {
  .roles {
    grid-template-columns: 1fr;
  }
}
</style>
