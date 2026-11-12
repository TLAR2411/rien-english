<script setup>
import { CategoryScale, Chart as ChartJS, Filler, LineElement, LinearScale, PointElement, Tooltip } from "chart.js";
import { Line } from "vue-chartjs";
import { useTheme } from "vuetify";

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip);

/**
 * "Scores over time": one point per quiz try, oldest on the left.
 * Green point = passed, amber = not passed yet. `attempts` = history from GET student/progress.
 */
const props = defineProps({
  attempts: { type: Array, required: true },
});

const { t, locale } = useI18n({ useScope: "global" });
const theme = useTheme();

const shortDate = (value) =>
  new Date(value).toLocaleDateString(locale.value === "km" ? "km-KH" : "en-GB", { day: "numeric", month: "short" });
const lessonTitle = (attempt) => (locale.value === "km" && attempt.lesson?.title_km) || attempt.lesson?.title_en;

const chartData = computed(() => {
  const { primary, success, warning } = theme.current.value.colors;

  return {
    labels: props.attempts.map((attempt) => shortDate(attempt.finished_at)),
    datasets: [
      {
        data: props.attempts.map((attempt) => attempt.percent),
        borderColor: primary,
        backgroundColor: `${primary}14`,
        fill: true,
        // smooth, but never curves above / below a real score
        cubicInterpolationMode: "monotone",
        borderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
        pointBackgroundColor: props.attempts.map((attempt) => (attempt.passed ? success : warning)),
        pointBorderColor: theme.current.value.colors.surface,
        pointBorderWidth: 2,
      },
    ],
  };
});

const chartOptions = computed(() => {
  const colors = theme.current.value.colors;
  const grid = `${colors["on-surface"]}14`;
  const label = `${colors["on-surface"]}8A`;

  return {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 400 },
    scales: {
      x: { grid: { display: false }, ticks: { color: label, maxRotation: 0, autoSkipPadding: 12 } },
      y: {
        min: 0,
        max: 100,
        grid: { color: grid },
        border: { display: false },
        ticks: { stepSize: 25, color: label, callback: (value) => `${value}%` },
      },
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        displayColors: false,
        callbacks: {
          title: (items) => lessonTitle(props.attempts[items[0].dataIndex]),
          label: (item) => {
            const attempt = props.attempts[item.dataIndex];

            return `${attempt.percent}% · ${t(attempt.passed ? "Passed" : "Not passed yet")}`;
          },
        },
      },
    },
  };
});
</script>

<template>
  <div class="score-chart">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>

<style scoped>
.score-chart {
  block-size: 260px;
  position: relative;
}
</style>
