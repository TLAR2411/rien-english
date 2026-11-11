<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import moment from "moment-timezone";
import formatContact from "@/utils/formater/formatContact";

/**
 * Employee profile header: identity, status, where they work and key figures.
 */
const props = defineProps({
  itemData: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
});

const router = useRouter();
const { t } = useI18n();

const employee = computed(() => props.itemData || {});

const isTerminated = computed(
  () => !!employee.value.terminated_date || employee.value.is_active === 0,
);

const initials = computed(() =>
  (employee.value.name_kh || "?")
    .split(" ")
    .map((part) => part.charAt(0))
    .slice(0, 2)
    .join(""),
);

// Time worked: hired → last working day (or today)
const serviceLength = computed(() => {
  const hired = employee.value.hired_date;
  if (!hired) return "-";
  const start = moment(hired);
  const end = employee.value.last_working_date
    ? moment(employee.value.last_working_date)
    : moment().tz("Asia/Phnom_Penh");
  if (end.isBefore(start)) return "-";

  const years = end.diff(start, "years");
  const months = end.diff(start.clone().add(years, "years"), "months");
  if (!years && !months) return `${end.diff(start, "days")} ${t("Days")}`;
  return [
    years && `${years} ${t("Years")}`,
    months && `${months} ${t("Months")}`,
  ]
    .filter(Boolean)
    .join(" ");
});

const workplace = computed(() =>
  [
    { icon: "tabler-id-badge-2", text: employee.value.position?.name_kh },
    {
      icon: "tabler-hierarchy-3",
      text: employee.value.position?.department?.name_kh,
    },
    { icon: "tabler-building", text: employee.value.branch?.name_kh },
  ].filter((item) => item.text),
);

const stats = computed(() => [
  {
    label: "Hired Date",
    icon: "tabler-calendar-plus",
    color: "primary",
    value: employee.value.hired_date
      ? moment(employee.value.hired_date).format("DD-MM-YYYY")
      : "-",
    sub: serviceLength.value,
  },
  {
    label: "Contact",
    icon: "tabler-phone",
    color: "info",
    value: employee.value.contact ? formatContact(employee.value.contact) : "-",
    sub: employee.value.work_email || null,
  },
  {
    label: "Bank Account",
    icon: "tabler-building-bank",
    color: "warning",
    value: employee.value.bank_account_number || "-",
    sub: null,
  },
]);

const onEdit = () =>
  router.push({ name: "hr-employees-edit", query: { id: employee.value.id } });
</script>

<template>
  <VCard :loading="loading">
    <VCardText class="pa-5">
      <!-- Identity -->
      <div class="d-flex align-center flex-wrap gap-4">
        <AppAvatar size="110" :title="employee.name_kh" />

        <div class="d-flex flex-column flex-grow-1" style="min-inline-size: 0">
          <div class="d-flex align-center flex-wrap gap-2">
            <span class="fs-name font-weight-bold">
              {{ employee.name_kh || "..." }}
            </span>
            <VChip
              size="small"
              :color="isTerminated ? 'error' : 'success'"
              variant="tonal"
              label
            >
              {{ isTerminated ? $t("Terminated") : $t("Working") }}
            </VChip>
          </div>
          <span class="fs-label text-medium-emphasis">
            {{ employee.name_en }}
            <template v-if="employee.code"> · {{ employee.code }}</template>
          </span>

          <div class="d-flex align-center flex-wrap gap-4 mt-2">
            <span
              v-for="place in workplace"
              :key="place.icon"
              class="d-flex align-center gap-1 fs-label"
            >
              <VIcon
                :icon="place.icon"
                size="18"
                class="text-medium-emphasis"
              />
              {{ place.text }}
            </span>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-2">
            <VChip
              v-if="employee.employee_type"
              size="small"
              variant="outlined"
            >
              {{ $t(employee.employee_type) }}
            </VChip>
            <!-- <VChip
              v-if="employee.contract_type"
              size="small"
              variant="outlined"
            >
              {{ employee.contract_type }}
            </VChip> -->
          </div>
        </div>

        <VBtn
          density="comfortable"
          color="warning"
          prepend-icon="tabler-pencil"
          class="align-self-start"
          @click="onEdit"
        >
          {{ $t("Edit") }}
        </VBtn>
      </div>
    </VCardText>

    <VDivider />

    <!-- Key figures -->
    <div class="stats">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="stats__item d-flex align-center gap-3 px-5 py-4"
      >
        <VAvatar :color="stat.color" variant="tonal" size="40" rounded>
          <VIcon :icon="stat.icon" size="22" />
        </VAvatar>
        <div class="d-flex flex-column" style="min-inline-size: 0">
          <span class="fs-caption text-medium-emphasis">
            {{ $t(stat.label) }}
          </span>
          <span
            class="fs-value font-weight-bold text-truncate"
            :class="{ 'value-blur': stat.hidden }"
            :title="stat.hidden ? $t('No permission') : undefined"
          >
            {{ stat.value }}
          </span>
          <span
            v-if="stat.sub"
            class="fs-caption text-medium-emphasis text-truncate"
            :class="{ 'value-blur': stat.hidden }"
          >
            {{ stat.sub }}
          </span>
        </div>
      </div>
    </div>
  </VCard>
</template>

<style scoped>
.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
}
.stats__item + .stats__item {
  border-inline-start: 1px solid
    rgba(var(--v-border-color), var(--v-border-opacity));
}
@media (max-width: 959px) {
  .stats {
    grid-template-columns: repeat(2, 1fr);
  }
  .stats__item {
    border-inline-start: none !important;
    border-block-end: 1px solid
      rgba(var(--v-border-color), var(--v-border-opacity));
  }
}

.value-blur {
  cursor: not-allowed;
  filter: blur(5px);
  user-select: none;
}

/* Sizes only — keep the app font so Khmer renders with kantumruy */
.fs-initials {
  font-size: 1.75rem;
}
.fs-name {
  font-size: 1.375rem;
}
.fs-value {
  font-size: 1rem;
}
.fs-label {
  font-size: 0.875rem;
}
.fs-caption {
  font-size: 0.8125rem;
}
</style>
