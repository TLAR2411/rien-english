<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { api } from "@/utils/api";
import { useDisplay, useTheme } from "vuetify";
import { hexToRgb } from "@layouts/utils";
import { useSettingStore } from "@/stores/settingStore";
import { useI18n } from "vue-i18n";

const vuetifyTheme = useTheme();
const { smAndDown } = useDisplay();
const settingStore = useSettingStore();
const { t } = useI18n();

const currentTheme = computed(() => vuetifyTheme.current.value.colors);
const variableTheme = computed(() => vuetifyTheme.current.value.variables);
const fontFamily = "'inter', 'kantumruy', sans-serif";

// Sample data shown until the real API response arrives (or if it comes back empty),
// so the chart is never blank while the Hr dashboard is still being wired up.
const sampleLabels = [
  "",
  "",
  "",
  "",
  "",
];
const sampleMale = [0, 0, 0,0, 0];
const sampleFemale = [0, 0, 0, 0, 0];

const labels = ref([...sampleLabels]);
const maleCounts = ref([...sampleMale]);
const femaleCounts = ref([...sampleFemale]);

const totalCounts = computed(() =>
  labels.value.map(
    (_, i) => (maleCounts.value[i] ?? 0) + (femaleCounts.value[i] ?? 0),
  ),
);
const totalEmployee = computed(() =>
  totalCounts.value.reduce((sum, n) => sum + Number(n || 0), 0),
);

const series = computed(() => [
  { name: t("Male"), data: maleCounts.value },
  { name: t("Female"), data: femaleCounts.value },
]);

const optionConfigs = computed(() => {
  const colors = currentTheme.value;
  const labelColor = `rgba(${hexToRgb(colors["on-surface"])},${
    variableTheme.value["disabled-opacity"]
  })`;
  const borderColor = `rgba(${hexToRgb(
    String(variableTheme.value["border-color"]),
  )},${variableTheme.value["border-opacity"]})`;
  const legendColor = `rgba(${hexToRgb(colors["on-background"])},${
    variableTheme.value["high-emphasis-opacity"]
  })`;

  return {
    chart: {
      type: "bar",
      stacked: true,
      parentHeightOffset: 0,
      toolbar: { show: false },
      zoom: { enabled: false },
    },
    colors: ["#2196F3", "#F0459A"],
    plotOptions: {
      bar: {
        borderRadius: 0,
        borderRadiusApplication: "end",
        borderRadiusWhenStacked: "last",
        columnWidth: "45%",
      },
    },
    dataLabels: { enabled: false },
    legend: {
      show: true,
      position: "top",
      horizontalAlign: "left",
      fontSize: "13px",
      fontFamily,
      markers: { width: 8, height: 8, offsetX: -3 },
      labels: { colors: legendColor },
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
      padding: { top: 30, left: 0, right: 10 },
    },
    tooltip: {
      shared: true,
      intersect: false,
      style: { fontFamily },
      y: {
        formatter(val) {
          return `${val} នាក់`;
        },
      },
    },
    annotations: {
      points: labels.value.map((label, i) => ({
        x: label,
        y: totalCounts.value[i] ?? 0,
        label: {
          text: `${totalCounts.value[i] ?? 0}`,
          borderWidth: 0,
          offsetY: -6,
          style: {
            fontSize: "13px",
            fontFamily,
            fontWeight: 600,
            color: labelColor,
            background: "transparent",
          },
        },
      })),
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
  const res = await api.get("employees-count-by-position");
  if (res.data.status && (res.data.data.labels ?? []).length) {
    labels.value = res.data.data.labels;
    maleCounts.value = res.data.data.male_counts ?? [];
    femaleCounts.value = res.data.data.female_counts ?? [];
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
  <VRow>
    <VCol cols="12">
      <VCard :title="$t('Total Employee By Position')">
        <!-- <template #append>
          <div class="d-flex gap-2">
            <VBtn
              icon
              size="small"
              color="success"
              variant="text"
              @click="downloadExcel"
            >
              <VIcon icon="tabler-file-spreadsheet" />
              <VTooltip activator="parent" location="top">
                Download Excel
              </VTooltip>
            </VBtn>
          </div>
          <div class="ml-2">
            <VSelect
              v-model="selectedYear"
              :items="yearOptions"
              :label="$t('Year')"
              variant="outlined"
              hide-details
            />
          </div>
        </template> -->
        <VCardText>
          <VueApexCharts
            v-if="labels.length"
            type="bar"
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
    </VCol>
  </VRow>
</template>
