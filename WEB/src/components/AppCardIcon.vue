<script setup>
// import { IconMail } from "vue-tabler-icons";

const props = defineProps({
  value: { type: [String, Number], required: true },
  title: { type: String, default: "New Messages" },
  caption: { type: String, default: "messages per day." },
  color: { type: String, default: "primary" },
  height: { type: [Number, String], default: 100 },
  rounded: { type: String, default: "md" },
  icon: { type: String, default: "tabler-mail" },
});
</script>

<template>
  <v-card
    :rounded="rounded"
    class="stat-card overflow-hidden"
    variant="tonal"
    :color="color"
    :style="{
      height: typeof height === 'number' ? height + 'px' : height,
      color: 'white',
    }"
  >
    <div class="h-100 d-flex flex-column justify-space-between">
      <div class="pa-4">
        <div class="text-h3 font-weight-bold" :class="`text-${color}`">
          {{ value }}
        </div>
        <div class="text-subtitle-1 mt-1" :class="`text-${color}`">
          {{ title }}
        </div>
        <!-- <div class="text-caption text-white" style="opacity: 0.9">{{ caption }}</div> -->
      </div>
      <VIcon
        class="corner-icon"
        size="90"
        stroke-width="1.5"
        :icon="icon"
      />
    </div>
  </v-card>
</template>

<style scoped>
.stat-card {
  position: relative;
}
.corner-icon {
  position: absolute;
  right: 8px;
  bottom: -25px;
  opacity: 0.2;
  pointer-events: none;
}
</style>
