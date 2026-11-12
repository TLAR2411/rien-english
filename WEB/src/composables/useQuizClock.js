/**
 * The clock on the quiz page.
 * - With a time limit: counts down ("04:32"); `isOver` turns true at 00:00.
 * - Without: shows the time spent so far.
 * Starts from `startedAt` (ISO date), so a page refresh keeps the right time.
 * Only for show: the server saves the real start and finish times.
 */
export function useQuizClock(startedAt, limitMinutes) {
  const now = ref(Date.now());
  const timer = setInterval(() => (now.value = Date.now()), 1000);
  onBeforeUnmount(() => clearInterval(timer));

  const spentSeconds = computed(() => {
    const start = Date.parse(toValue(startedAt));
    return Number.isNaN(start) ? 0 : Math.max(0, Math.floor((now.value - start) / 1000));
  });

  const hasLimit = computed(() => Boolean(toValue(limitMinutes)));
  const leftSeconds = computed(() => Math.max(0, toValue(limitMinutes) * 60 - spentSeconds.value));

  const seconds = computed(() => (hasLimit.value ? leftSeconds.value : spentSeconds.value));
  const label = computed(() => {
    const minutes = String(Math.floor(seconds.value / 60)).padStart(2, "0");
    return `${minutes}:${String(seconds.value % 60).padStart(2, "0")}`;
  });

  return {
    label,
    hasLimit,
    isOver: computed(() => hasLimit.value && leftSeconds.value === 0),
    isLow: computed(() => hasLimit.value && leftSeconds.value <= 60),
  };
}
