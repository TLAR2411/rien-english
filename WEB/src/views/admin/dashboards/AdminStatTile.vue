<script setup>
// A single headline number with context.
// `color` tints only the accent edge, icon and bar; the number and labels stay
// in text colours so every tile reads the same.
const props = defineProps({
  title: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  icon: { type: String, required: true },
  color: { type: String, default: "primary" },
  caption: { type: String, default: null },
  // 0..1 — optional share bar under the number (e.g. active / total)
  progress: { type: Number, default: null },
  // "warn" | "ok" — attention tiles show a status line with an icon
  status: { type: String, default: null },
  to: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  // Smaller tile (less padding, smaller number and icon) for pages where the numbers are only a summary
  dense: { type: Boolean, default: false },
});

const statusInfo = computed(() =>
  props.status === "warn"
    ? { icon: "tabler-alert-triangle", color: "warning", text: "Needs review" }
    : props.status === "ok"
      ? { icon: "tabler-circle-check", color: "success", text: "All good" }
      : null,
);
</script>

<template>
  <VCard
    class="stat-tile h-100"
    :class="{ 'stat-tile--dense': dense }"
    :style="{ '--tile-color': `var(--v-theme-${color})` }"
    :to="to ?? undefined"
    :ripple="!!to"
  >
    <VCardText class="d-flex flex-column h-100 gap-2">
      <div class="d-flex align-start justify-space-between gap-3">
        <div class="overflow-hidden">
          <div class="stat-tile__title text-body-1 text-medium-emphasis">
            {{ $t(title) }}
          </div>
          <VSkeletonLoader
            v-if="loading"
            type="heading"
            width="72"
            class="mt-1"
          />
          <div v-else class="stat-tile__value text-high-emphasis">
            {{ value.toLocaleString() }}
          </div>
        </div>
        <VAvatar :color="color" variant="tonal" rounded="lg" :size="dense ? 36 : 48" class="stat-tile__icon">
          <VIcon :icon="icon" :size="dense ? 20 : 26" />
        </VAvatar>
      </div>

      <VProgressLinear
        v-if="progress != null && !loading"
        :model-value="Math.min(1, Math.max(0, progress)) * 100"
        :color="color"
        bg-color="on-surface"
        bg-opacity="0.08"
        :height="dense ? 4 : 6"
        rounded
      />

      <!-- Footer only when it has something in it, so plain tiles stay short -->
      <div v-if="statusInfo || caption || to" class="d-flex align-center justify-space-between gap-2 mt-auto">
        <span
          v-if="statusInfo && !loading"
          class="d-inline-flex align-center gap-1 text-body-2"
          :class="`text-${statusInfo.color}`"
        >
          <VIcon :icon="statusInfo.icon" size="16" />
          {{ $t(statusInfo.text) }}
        </span>
        <span
          v-else-if="caption"
          class="text-body-2 text-medium-emphasis text-truncate"
        >
          {{ caption }}
        </span>
        <VIcon
          v-if="to"
          icon="tabler-chevron-right"
          size="18"
          class="stat-tile__go text-disabled ms-auto"
        />
      </div>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.stat-tile {
  border-inline-start: 4px solid rgb(var(--tile-color));
  transition:
    box-shadow 0.15s ease,
    transform 0.15s ease;

  &:hover {
    box-shadow: 0 6px 18px rgba(var(--v-shadow-key-umbra-color), 0.12);
  }

  &:hover .stat-tile__go {
    transform: translateX(3px);
  }
}

.stat-tile__title {
  overflow-wrap: anywhere;
}

/* Two tiles sit side by side on a phone: leave the room for the label */
@media (max-width: 599.98px) {
  .stat-tile :deep(.v-card-text) {
    padding: 12px;
  }

  .stat-tile__icon {
    block-size: 36px !important;
    inline-size: 36px !important;
  }
}

.stat-tile__value {
  font-size: 2rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
  margin-block-start: 2px;
}

.stat-tile__go {
  transition: transform 0.15s ease;
}

.stat-tile--dense {
  :deep(.v-card-text) {
    padding: 14px 16px;
  }

  .stat-tile__title {
    font-size: 0.875rem !important;
  }

  .stat-tile__value {
    font-size: 1.5rem;
  }
}
</style>
