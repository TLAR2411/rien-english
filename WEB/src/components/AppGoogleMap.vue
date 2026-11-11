<script setup>
import { computed } from "vue";

/**
 * Google map with a pin at `center` — no API key needed.
 *
 * Uses Google's embed link (same as "Share → Embed a map") in an iframe, so
 * there is no Google Cloud project, key or billing. It is display-only: drag,
 * zoom, satellite and full screen work inside the map; "Open" opens Google Maps.
 *
 * `center` can be { lat, lng } or a "lat,lng" string.
 */
const props = defineProps({
  center: { type: [Object, String], default: null },
  zoom: { type: [Number, String], default: 15 },
  // Show the "Open" button (opens the place in Google Maps)
  isOpen: { type: Boolean, default: true },
});

// Accept both shapes; null when the value is missing or not a valid position
const point = computed(() => {
  const c = props.center;
  if (!c) return null;
  const [lat, lng] =
    typeof c === "string" ? c.split(",").map((v) => parseFloat(v)) : [parseFloat(c.lat), parseFloat(c.lng)];
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
  if (Math.abs(lat) > 90 || Math.abs(lng) > 180) return null;
  return { lat, lng };
});

const embedUrl = computed(() =>
  point.value
    ? `https://maps.google.com/maps?q=${point.value.lat},${point.value.lng}&z=${props.zoom}&output=embed`
    : null,
);

const mapsUrl = computed(() =>
  point.value ? `https://www.google.com/maps/search/?api=1&query=${point.value.lat},${point.value.lng}` : null,
);
</script>

<template>
  <div class="app-map">
    <template v-if="embedUrl">
      <iframe
        :src="embedUrl"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen
        :title="`${point.lat}, ${point.lng}`"
      />
      <VBtn
        v-if="isOpen"
        size="x-small"
        color="surface"
        variant="flat"
        prepend-icon="tabler-external-link"
        class="app-map__open"
        :href="mapsUrl"
        target="_blank"
        rel="noopener"
      >
        {{ $t("Open") }}
      </VBtn>
    </template>

    <div v-else class="app-map__empty">
      <VIcon icon="tabler-map-pin-off" size="28" />
      <span>{{ $t("Location Not Found") }}</span>
    </div>
  </div>
</template>

<style scoped>
/* Fills the box the parent gives it (same as before) */
.app-map {
  position: relative;
  width: 100%;
  height: 100%;
  min-height: 180px;
  overflow: hidden;
  border-radius: inherit;
}

.app-map iframe {
  display: block;
  width: 100%;
  height: 100%;
  min-height: 180px;
  border: 0;
}

.app-map__open {
  position: absolute;
  top: 10px;
  inset-inline-end: 10px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}

.app-map__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  width: 100%;
  height: 100%;
  min-height: 180px;
  font-size: 13px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  background: rgba(var(--v-theme-on-surface), 0.04);
}
</style>
