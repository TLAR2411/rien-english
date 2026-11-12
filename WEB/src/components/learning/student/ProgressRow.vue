<script setup>
/**
 * One line of "My Progress": a skill or a level, "3 of 10 lessons", a bar and the %.
 * `item` = { done, total, percent } from GET student/progress.
 */
defineProps({
  item: { type: Object, required: true },
  title: { type: String, required: true },
  subtitle: { type: String, default: null },
  icon: { type: String, default: null },
  // Short text in a box instead of an icon, e.g. the level code "A1"
  badge: { type: String, default: null },
});
</script>

<template>
  <div class="progress-row d-flex align-center gap-3" :class="{ 'progress-row--empty': !item.total }">
    <VAvatar v-if="badge" color="primary" variant="tonal" rounded="lg" size="40" class="font-weight-bold">
      {{ badge }}
    </VAvatar>
    <VAvatar v-else color="primary" variant="tonal" rounded="lg" size="40">
      <VIcon :icon="icon" size="22" />
    </VAvatar>

    <div class="flex-grow-1 overflow-hidden">
      <div class="d-flex align-baseline justify-space-between gap-2">
        <span class="text-body-1 font-weight-medium text-truncate">
          {{ title }}<span v-if="subtitle" class="text-medium-emphasis font-weight-regular"> · {{ subtitle }}</span>
        </span>
        <span v-if="item.total" class="progress-row__percent text-body-1 font-weight-bold">{{ item.percent }}%</span>
      </div>

      <template v-if="item.total">
        <VProgressLinear
          :model-value="item.percent"
          :color="item.percent === 100 ? 'success' : 'primary'"
          bg-color="on-surface"
          bg-opacity="0.08"
          height="6"
          rounded
          class="my-1"
        />
        <span class="text-body-2 text-medium-emphasis">
          {{ $t("{done} of {total} lessons", { done: item.done, total: item.total }) }}
        </span>
      </template>
      <span v-else class="text-body-2 text-disabled">{{ $t("No lessons yet") }}</span>
    </div>
  </div>
</template>

<style scoped>
.progress-row {
  min-block-size: 56px;
}

.progress-row--empty .v-avatar {
  opacity: 0.5;
}

.progress-row__percent {
  font-variant-numeric: tabular-nums;
}
</style>
