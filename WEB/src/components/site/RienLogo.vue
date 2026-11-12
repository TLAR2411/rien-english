<script setup>
/**
 * The Rien wordmark: "rien" with a speech bubble as the dot of the i.
 * Same drawing as public/logo/learning-english/logo.svg. Takes the text color
 * (blue by default), so it also works white on a blue background.
 */
defineProps({
  height: { type: [Number, String], default: 28 },
});
</script>

<template>
  <svg
    class="rien-logo"
    viewBox="0 0 112 52"
    :height="height"
    role="img"
    aria-label="Rien English"
  >
    <g fill="none" stroke="currentColor" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round">
      <path d="M8 46V22M8 33c0-6.6 4.9-11 11.5-11" />
      <path d="M34 46V25" />
      <path d="M46.5 34h23A11.5 11.5 0 1 0 66.8 41.4" />
      <path d="M82 46V22M82 33c0-6.6 4.9-11 11-11s11 4.4 11 11v13" />
    </g>
    <path
      fill="currentColor"
      d="M31.5 2h5a5 5 0 0 1 5 5v2a5 5 0 0 1-5 5h-3.2l-3.6 3.4a.6.6 0 0 1-1-.5V13.4A5 5 0 0 1 26.5 9V7a5 5 0 0 1 5-5z"
    />
  </svg>
</template>

<style scoped>
.rien-logo {
  display: block;
  width: auto;
  color: var(--rien-blue, #2563eb);
}
</style>
