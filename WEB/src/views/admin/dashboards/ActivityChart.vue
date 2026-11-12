<script setup>
import { BarElement, CategoryScale, Chart as ChartJS, LinearScale, Tooltip } from "chart.js";
import { Bar } from "vue-chartjs";
import { useTheme } from "vuetify";

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip);

/**
 * Quiz tries and new students per day (last 14 days).
 * `days` = activity from GET admin/dashboard: [{ date, quizzes, sign_ups }].
 */
const props = defineProps({
  days: { type: Array, required: true },
});

const { t, locale } = useI18n({ useScope: "global" });
const theme = useTheme();

const SERIES = [
  { key: "quizzes", label: "Quiz tries", color: "primary" },
  { key: "sign_ups", label: "Sign-ups", color: "success" },
];

const isEmpty = computed(() => props.days.every((day) => !day.quizzes && !day.sign_ups));

const label = (date) =>
  new Date(`${date}T00:00:00`).toLocaleDateString(locale.value === "km" ? "km-KH" : "en-GB", { day: "numeric", month: "short" });

const chartData = computed(() => ({
  labels: props.days.map((day) => label(day.date)),
  datasets: SERIES.map((series) => ({
    label: t(series.label),
    data: props.days.map((day) => day[series.key]),
    backgroundColor: theme.current.value.colors[series.color],
    borderRadius: 4,
    maxBarThickness: 14,
    categoryPercentage: 0.6,
    barPercentage: 0.9,
  })),
}));

const chartOptions = computed(() => {
  const onSurface = theme.current.value.colors["on-surface"];

  return {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 400 },
    scales: {
      x: { grid: { display: false }, ticks: { color: `${onSurface}8A`, maxRotation: 0, autoSkipPadding: 16 } },
      y: {
        beginAtZero: true,
        border: { display: false },
        grid: { color: `${onSurface}14` },
        ticks: { color: `${onSurface}8A`, precision: 0, maxTicksLimit: 5 },
      },
    },
    plugins: {
      legend: { display: false },
      tooltip: { mode: "index", intersect: false },
    },
  };
});
</script>

<template>
  <VCard class="h-100">
    <VCardItem>
      <VCardTitle>{{ $t("Activity") }}</VCardTitle>
      <VCardSubtitle>{{ $t("Last {days} days", { days: days.length }) }}</VCardSubtitle>
      <template #append>
        <div class="d-flex gap-4 text-body-2 text-medium-emphasis">
          <span v-for="series in SERIES" :key="series.key" class="d-inline-flex align-center gap-2">
            <span class="legend-dot" :class="`bg-${series.color}`" />{{ $t(series.label) }}
          </span>
        </div>
      </template>
    </VCardItem>

    <VCardText>
      <div class="activity-chart">
        <Bar :data="chartData" :options="chartOptions" />
        <p v-if="isEmpty" class="activity-chart__empty text-body-1 text-medium-emphasis">
          {{ $t("No activity in these days yet.") }}
        </p>
      </div>
    </VCardText>
  </VCard>
</template>

<style scoped>
.activity-chart {
  block-size: 240px;
  position: relative;
}

.activity-chart__empty {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  margin: 0;
}

.legend-dot {
  block-size: 8px;
  border-radius: 50%;
  inline-size: 8px;
}
</style>
