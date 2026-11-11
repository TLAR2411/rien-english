<script setup>
// Ranked horizontal bars for one measure (a count per category).
// Single series → one hue, no legend; the card title names the measure.
const props = defineProps({
  title: { type: String, required: true },
  icon: { type: String, required: true },
  items: { type: Array, default: () => [] }, // [{ key, label, sub, value }]
  loading: { type: Boolean, default: false },
});

const max = computed(() => Math.max(1, ...props.items.map((i) => i.value)));
const total = computed(() => props.items.reduce((s, i) => s + i.value, 0));

const pct = (v) => (total.value ? Math.round((v / total.value) * 100) : 0);
</script>

<template>
  <VCard class="h-100">
    <VCardItem>
      <template #prepend>
        <VIcon :icon="icon" color="primary" />
      </template>
      <VCardTitle>{{ $t(title) }}</VCardTitle>
    </VCardItem>

    <VCardText>
      <VSkeletonLoader v-if="loading" type="list-item@4" />

      <div v-else-if="!items.length" class="text-medium-emphasis py-6 text-center">
        {{ $t("No data available") }}
      </div>

      <ul v-else class="bar-list">
        <li v-for="item in items" :key="item.key" class="bar-row">
          <div class="d-flex justify-space-between align-baseline gap-2 mb-1">
            <span class="text-body-2 text-high-emphasis text-truncate">
              {{ item.label }}
              <span v-if="item.sub" class="text-caption text-disabled">
                {{ item.sub }}
              </span>
            </span>
            <span class="text-body-2 font-weight-medium text-high-emphasis">
              {{ item.value }}
            </span>
          </div>
          <div class="bar-track">
            <div
              class="bar-fill"
              :style="{ inlineSize: `${(item.value / max) * 100}%` }"
            />
          </div>
          <VTooltip activator="parent" location="top">
            {{ item.label }}: {{ item.value }} ({{ pct(item.value) }}%)
          </VTooltip>
        </li>
      </ul>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.bar-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 0;
  margin: 0;
  list-style: none;
}

// The whole row is the hover target, not just the thin bar
.bar-row {
  border-radius: 6px;
  margin-inline: -6px;
  padding-block: 2px;
  padding-inline: 6px;

  &:hover {
    background: rgba(var(--v-theme-on-surface), 0.04);
  }
}

.bar-track {
  overflow: hidden;
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.06);
  block-size: 8px;
}

.bar-fill {
  border-radius: 4px;
  background: rgb(var(--v-theme-primary));
  block-size: 100%;
  min-inline-size: 4px;
  transition: inline-size 0.3s ease;
}
</style>
