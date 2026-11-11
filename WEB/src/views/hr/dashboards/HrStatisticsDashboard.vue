<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { api } from "@/utils/api";
import formatCurrency from "@/utils/formater/formatCurrency";
import { useSettingStore } from "@/stores/settingStore";

const settingStore = useSettingStore();
const items = ref({});
const loading = ref(false);

const getData = async () => {
  loading.value = true;
  try {
    const res = await api.get("employees-dashboard-summary");
    if (res.data.status) {
      items.value = res.data.data;
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  getData();
});

watch(
  () => settingStore.branch_id,
  () => {
    getData();
  },
);

const num = (v) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
};

const total = computed(() => num(items.value?.total_employee_count));
// Share of all staff, for the small line under a count
const shareOf = (n) => (total.value > 0 ? Math.round((n / total.value) * 1000) / 10 : 0);

const hrData = computed(() => {
  const fresh = num(items.value?.new_employee_count);
  return [
    {
      icon: "tabler-users",
      color: "primary",
      title: "Total Employee",
      value: formatCurrency(total.value),
      unit: "នាក់",
      note: "បុគ្គលិកកំពុងធ្វើការ",
    },
    {
      icon: "tabler-user-plus",
      color: "success",
      title: "New Employee",
      value: formatCurrency(fresh),
      unit: "នាក់",
      note: "ចូលថ្មីខែនេះ",
      badge: fresh ? `${shareOf(fresh)}%` : null,
    },
  ];
});
</script>

<template>
  <VRow>
    <VCol v-for="(data, index) in hrData" :key="index" cols="12" sm="6">
      <!-- Solid colour card: --tone = the colour, --on-tone = its text colour -->
      <VCard
        class="stat"
        :style="{
          '--tone': `var(--v-theme-${data.color})`,
          '--on-tone': `var(--v-theme-on-${data.color})`,
        }"
        flat
      >
        <div class="stat__top">
          <span class="stat__title">{{ $t(data.title) }}</span>
          <span class="stat__icon"><VIcon :icon="data.icon" size="24" /></span>
        </div>

        <!-- Value: skeleton while loading -->
        <div v-if="loading" class="stat__skeleton" aria-hidden="true" />
        <div v-else class="stat__value">
          <span class="stat__number">{{ data.value }}</span>
          <span class="stat__unit">{{ data.unit }}</span>
        </div>

        <div class="stat__foot">
          <span>{{ data.note }}</span>
          <span v-if="data.badge && !loading" class="stat__badge" :title="$t('Share of all staff')">
            {{ data.badge }}
          </span>
        </div>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss" scoped>
/* Solid card in its colour, white text; translucent white for details */
.stat {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  height: 100%;
  padding: 1.125rem 1.25rem;
  overflow: hidden;
  border-radius: 6px;
  color: rgb(var(--on-tone));
  background: rgb(var(--tone)) !important;
  transition:
    transform 0.2s ease-out,
    box-shadow 0.2s ease-out;

  /* large soft ring in the corner for depth */
  &::after {
    position: absolute;
    inset-block-end: -70px;
    inset-inline-end: -50px;
    width: 170px;
    height: 170px;
    border-radius: 50%;
    content: "";
    background: rgba(255, 255, 255, 0.1);
    pointer-events: none;
  }

  &:hover {
    box-shadow: 0 10px 24px rgba(var(--tone), 0.35);
    transform: translateY(-2px);
  }
}

.stat__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
}

.stat__title {
  padding-top: 2px;
  font-size: 14.5px;
  // font-weight: 600;
}

.stat__icon {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
}

.stat__value {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: baseline;
  gap: 0.375rem;
}

.stat__number {
  font-family: inter, kantumruy, sans-serif;
  font-size: 32px;
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: -0.5px;
  font-variant-numeric: tabular-nums;
}

.stat__unit {
  font-size: 14px;
  font-weight: 500;
  opacity: 0.85;
}

.stat__foot {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding-top: 0.625rem;
  border-top: 1px solid rgba(255, 255, 255, 0.25);
  font-size: 12.5px;
  opacity: 0.95;
}

.stat__badge {
  padding: 1px 8px;
  border-radius: 999px;
  font-family: inter, sans-serif;
  font-size: 12px;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.22);
}

.stat__skeleton {
  width: 55%;
  height: 35px;
  border-radius: 6px;
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.15),
    rgba(255, 255, 255, 0.32),
    rgba(255, 255, 255, 0.15)
  );
  background-size: 200% 100%;
  animation: shimmer 1.2s linear infinite;
}

@keyframes shimmer {
  to {
    background-position: -200% 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .stat,
  .stat__skeleton {
    animation: none;
    transition: none;
  }
}
</style>
