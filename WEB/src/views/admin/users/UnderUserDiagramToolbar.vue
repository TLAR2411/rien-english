<script setup>
import { useI18n } from "vue-i18n";

defineProps({
  zoom: { type: Number, required: true },
  loading: { type: Boolean, default: false },
  fullscreen: { type: Boolean, default: false },
  floating: { type: Boolean, default: false },
});

const emit = defineEmits([
  "expand",
  "collapse",
  "zoom-in",
  "zoom-out",
  "zoom-reset",
  "fit",
  "refresh",
  "fullscreen",
]);

const { t } = useI18n();
</script>

<template>
  <div class="diagram-toolbar" :class="{ 'diagram-toolbar--floating': floating }">
    <VBtn variant="text" size="small" icon @click="emit('expand')">
      <VIcon icon="tabler-arrows-maximize" />
      <VTooltip activator="parent">{{ t("Expand All") }}</VTooltip>
    </VBtn>
    <VBtn variant="text" size="small" icon @click="emit('collapse')">
      <VIcon icon="tabler-arrows-minimize" />
      <VTooltip activator="parent">{{ t("Collapse All") }}</VTooltip>
    </VBtn>

    <VDivider vertical />

    <VBtn variant="text" size="small" icon @click="emit('zoom-out')">
      <VIcon icon="tabler-zoom-out" />
      <VTooltip activator="parent">{{ t("Zoom Out") }}</VTooltip>
    </VBtn>
    <VBtn
      variant="text"
      size="small"
      class="zoom-value px-1"
      @click="emit('zoom-reset')"
    >
      {{ Math.round(zoom * 100) }}%
      <VTooltip activator="parent">{{ t("Reset Zoom") }}</VTooltip>
    </VBtn>
    <VBtn variant="text" size="small" icon @click="emit('zoom-in')">
      <VIcon icon="tabler-zoom-in" />
      <VTooltip activator="parent">{{ t("Zoom In") }}</VTooltip>
    </VBtn>
    <VBtn variant="text" size="small" icon @click="emit('fit')">
      <VIcon icon="tabler-focus-centered" />
      <VTooltip activator="parent">{{ t("Fit to Screen") }}</VTooltip>
    </VBtn>

    <VDivider vertical />

    <VBtn
      variant="text"
      size="small"
      icon
      :loading="loading"
      @click="emit('refresh')"
    >
      <VIcon icon="tabler-refresh" />
      <VTooltip activator="parent">{{ t("Refresh") }}</VTooltip>
    </VBtn>
    <VBtn
      variant="text"
      size="small"
      icon
      :color="fullscreen ? 'primary' : undefined"
      @click="emit('fullscreen')"
    >
      <VIcon
        :icon="fullscreen ? 'tabler-window-minimize' : 'tabler-window-maximize'"
      />
      <VTooltip activator="parent">
        {{ fullscreen ? t("Exit Full Screen") : t("Full Screen") }}
      </VTooltip>
    </VBtn>
  </div>
</template>

<style lang="scss" scoped>
.diagram-toolbar {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  block-size: 38px;
  padding-inline: 4px;

  :deep(.v-divider--vertical) {
    align-self: center;
    block-size: 20px;
    min-block-size: 0;
    margin-inline: 2px;
  }
}

// Floating over the diagram (phones)
.diagram-toolbar--floating {
  border-radius: 999px;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 4px 16px rgba(var(--v-shadow-key-umbra-color), 0.2);
  block-size: 44px;
  padding-inline: 6px;
}

.zoom-value {
  min-inline-size: 52px;
  font-variant-numeric: tabular-nums;
}
</style>
