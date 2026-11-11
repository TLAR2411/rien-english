<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { api } from "@/utils/api";
import { useDisplay, useTheme } from "vuetify";
import { hexToRgb } from "@layouts/utils";
import { useSettingStore } from "@/stores/settingStore";

const vuetifyTheme = useTheme();
const { smAndDown } = useDisplay();
const settingStore = useSettingStore();

const currentTheme = computed(() => vuetifyTheme.current.value.colors);
const variableTheme = computed(() => vuetifyTheme.current.value.variables);
const fontFamily = "'inter', 'kantumruy', sans-serif";

const khmerMonths = [
  "មករា",
  "កុម្ភៈ",
  "មីនា",
  "មេសា",
  "ឧសភា",
  "មិថុនា",
  "កក្កដា",
  "សីហា",
  "កញ្ញា",
  "តុលា",
  "វិច្ឆិកា",
  "ធ្នូ",
];

const monthLabel = ({ month, year }) => `${khmerMonths[month - 1]} ${year}`;

// Sample 6-month trend shown until the real API response arrives (or if it comes
// back empty), so the chart is never blank while the Hr dashboard is being wired up.
const buildSampleLabels = () => {
  const now = new Date();
  return Array.from({ length: 6 }, (_, i) => {
    const d = new Date(now.getFullYear(), now.getMonth() - (5 - i), 1);
    return { month: d.getMonth() + 1, year: d.getFullYear() };
  });
};

const rawLabels = ref(buildSampleLabels());
const counts = ref([0, 0, 0, 0, 0, 0]);

const labels = computed(() => rawLabels.value.map(monthLabel));

const growth = computed(() => {
  if (counts.value.length < 2) return 0;
  return (
    counts.value[counts.value.length - 1] -
    counts.value[counts.value.length - 2]
  );
});

const series = computed(() => [{ name: "សរុបបុគ្គលិក", data: counts.value }]);

const optionConfigs = computed(() => {
  const colors = currentTheme.value;
  const labelColor = `rgba(${hexToRgb(colors["on-surface"])},${
    variableTheme.value["disabled-opacity"]
  })`;
  const borderColor = `rgba(${hexToRgb(
    String(variableTheme.value["border-color"]),
  )},${variableTheme.value["border-opacity"]})`;

  return {
    chart: {
      type: "area",
      parentHeightOffset: 0,
      toolbar: { show: false },
      zoom: { enabled: false },
    },
    colors: [colors.primary],
    stroke: {
      curve: "smooth",
      width: 3,
    },
    fill: {
      type: "gradient",
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.35,
        opacityTo: 0.02,
        stops: [0, 90, 100],
      },
    },
    markers: {
      size: 5,
      colors: "#fff",
      strokeColors: colors.primary,
      strokeWidth: 2,
      hover: { size: 7 },
    },
    dataLabels: {
      enabled: true,
      offsetY: -10,
      style: {
        fontSize: "12px",
        fontFamily,
        fontWeight: 600,
        colors: [colors.primary],
      },
    },
    xaxis: {
      categories: labels.value,
      axisBorder: { show: true, color: borderColor },
      axisTicks: { show: false },
      labels: {
        style: { colors: labelColor, fontSize: "12px", fontFamily },
      },
    },
    yaxis: {
      labels: {
        formatter(val) {
          return Math.round(val);
        },
        style: { colors: labelColor, fontSize: "12px", fontFamily },
      },
    },
    grid: {
      show: true,
      borderColor,
      strokeDashArray: 3,
      padding: { top: 20, left: 0, right: 10 },
    },
    tooltip: {
      style: { fontFamily },
      y: {
        formatter(val) {
          return `${val} នាក់`;
        },
      },
    },
    noData: {
      text: "គ្មានទិន្នន័យ",
    },
    responsive: [
      {
        breakpoint: 590,
        options: {
          xaxis: { labels: { rotate: -45, style: { fontSize: "10px" } } },
        },
      },
    ],
  };
});

const getData = async () => {
  const res = await api.get("employees-monthly-growth");
  if (res.data.status && (res.data.data.labels ?? []).length) {
    rawLabels.value = res.data.data.labels;
    counts.value = res.data.data.counts ?? [];
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
</script>

<template>
  <VCard :title="$t('Employee Growth')">
    <VCardText class="pt-0">
      <VueApexCharts
        v-if="counts.length"
        type="area"
        :height="!smAndDown ? 377 : 300"
        :options="optionConfigs"
        :series="series"
      />
      <div
        v-else
        class="d-flex align-center justify-center text-medium-emphasis"
        style="block-size: 300px"
      >
        {{ $t("No data") }}
      </div>
    </VCardText>
  </VCard>
</template>
