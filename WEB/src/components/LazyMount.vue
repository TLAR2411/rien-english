<script setup>
// Renders its slot only once it scrolls near the viewport. Used for
// below-the-fold charts so ApexCharts isn't downloaded, parsed and drawn
// while the first screen is still loading. Once mounted it stays mounted.
import { useIntersectionObserver } from "@vueuse/core";

const props = defineProps({
  // Reserve roughly the final height so the page doesn't jump when the
  // real content replaces the placeholder.
  minHeight: { type: [Number, String], default: 300 },
  // Start mounting a little before the section is actually on screen.
  rootMargin: { type: String, default: "200px" },
});

const target = ref(null);
const isVisible = ref(false);

const { stop } = useIntersectionObserver(
  target,
  ([entry]) => {
    if (entry?.isIntersecting) {
      isVisible.value = true;
      stop();
    }
  },
  { rootMargin: props.rootMargin },
);

const placeholderStyle = computed(() => ({
  minHeight:
    typeof props.minHeight === "number"
      ? `${props.minHeight}px`
      : props.minHeight,
}));
</script>

<template>
  <slot v-if="isVisible" />
  <div v-else ref="target" :style="placeholderStyle" />
</template>
