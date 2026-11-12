<script setup>
import { LISTENING_SPEEDS } from "@/constants/learning";

/**
 * Audio player of a listening lesson: play / pause, back 5 seconds, play again
 * from the start, a time bar to jump, and the speed (0.75x / 1x).
 * The chosen speed is remembered in this browser.
 */
const props = defineProps({
  src: { type: String, required: true },
  title: { type: String, default: "" },
});

const SPEED_KEY = "rien-listening-speed";
const readSpeed = () => {
  try {
    const saved = Number(localStorage.getItem(SPEED_KEY));
    return LISTENING_SPEEDS.includes(saved) ? saved : 1;
  } catch {
    return 1;
  }
};

const audio = ref(null);
const isPlaying = ref(false);
const isLoading = ref(true);
const hasError = ref(false);
const current = ref(0);
const duration = ref(0);
const speed = ref(readSpeed());

const progress = computed(() => (duration.value ? (current.value / duration.value) * 100 : 0));

const formatTime = (seconds) => {
  const total = Math.max(0, Math.floor(seconds || 0));
  return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, "0")}`;
};

const toggle = async () => {
  if (!audio.value) return;
  if (audio.value.paused) {
    try {
      await audio.value.play();
    } catch {
      hasError.value = true;
    }
  } else {
    audio.value.pause();
  }
};

const seek = (seconds) => {
  if (!audio.value || !duration.value) return;
  audio.value.currentTime = Math.min(Math.max(seconds, 0), duration.value);
  current.value = audio.value.currentTime;
};

const back = () => seek(current.value - 5);
const restart = async () => {
  seek(0);
  if (audio.value?.paused) await toggle();
};

watch(speed, (value) => {
  if (audio.value) audio.value.playbackRate = value;
  try {
    localStorage.setItem(SPEED_KEY, String(value));
  } catch {
    /* private window: the speed is just not remembered */
  }
});

const onLoaded = () => {
  duration.value = audio.value.duration || 0;
  audio.value.playbackRate = speed.value;
  isLoading.value = false;
};

// A new lesson / file: start again
watch(
  () => props.src,
  () => {
    isPlaying.value = false;
    isLoading.value = true;
    hasError.value = false;
    current.value = 0;
    duration.value = 0;
  },
);

onBeforeUnmount(() => audio.value?.pause());
</script>

<template>
  <div class="listen" :class="{ 'listen--playing': isPlaying }">
    <audio
      ref="audio"
      :src="src"
      preload="metadata"
      @loadedmetadata="onLoaded"
      @timeupdate="current = audio.currentTime"
      @play="isPlaying = true"
      @pause="isPlaying = false"
      @ended="isPlaying = false"
      @error="(hasError = true), (isLoading = false)"
    />

    <div class="listen__main">
      <button
        type="button"
        class="listen__play"
        :disabled="hasError"
        :aria-label="isPlaying ? $t('Pause') : $t('Play')"
        @click="toggle"
      >
        <VIcon :icon="isPlaying ? 'tabler-player-pause-filled' : 'tabler-player-play-filled'" size="26" />
      </button>

      <div class="listen__track">
        <p class="listen__title">{{ title || $t("Listen") }}</p>
        <input
          type="range"
          class="listen__bar"
          min="0"
          :max="duration || 0"
          step="0.1"
          :value="current"
          :disabled="!duration"
          :style="{ '--listen-progress': `${progress}%` }"
          :aria-label="$t('Audio position')"
          :aria-valuetext="`${formatTime(current)} / ${formatTime(duration)}`"
          @input="seek(Number($event.target.value))"
        />
        <p class="listen__time">
          <span>{{ formatTime(current) }}</span>
          <span>{{ isLoading ? "–:––" : formatTime(duration) }}</span>
        </p>
      </div>
    </div>

    <p v-if="hasError" class="listen__error">
      <VIcon icon="tabler-alert-circle" size="18" />
      {{ $t("The audio could not be loaded. Please try again later.") }}
    </p>

    <div class="listen__controls">
      <div class="listen__buttons">
        <VBtn variant="text" size="small" prepend-icon="tabler-rewind-backward-5" :disabled="!duration" @click="back">
          {{ $t("Back 5s") }}
        </VBtn>
        <VBtn variant="text" size="small" prepend-icon="tabler-player-skip-back" :disabled="!duration" @click="restart">
          {{ $t("Play again") }}
        </VBtn>
      </div>

      <div class="listen__speed" role="group" :aria-label="$t('Speed')">
        <span class="listen__speed-label">{{ $t("Speed") }}</span>
        <button
          v-for="value in LISTENING_SPEEDS"
          :key="value"
          type="button"
          :class="{ 'is-on': speed === value }"
          :aria-pressed="speed === value"
          @click="speed = value"
        >
          {{ value }}x
        </button>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
$muted: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
$line: rgba(var(--v-border-color), var(--v-border-opacity));
$primary: rgb(var(--v-theme-primary));

.listen {
  padding: clamp(1rem, 3vw, 1.5rem);
  border: 1px solid $line;
  border-radius: 12px;
  background: rgba(var(--v-theme-primary), 0.04);
}

.listen__main {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.listen__play {
  display: grid;
  flex: 0 0 auto;
  place-items: center;
  width: 60px;
  height: 60px;
  border: 0;
  border-radius: 50%;
  background: $primary;
  color: rgb(var(--v-theme-on-primary));
  cursor: pointer;
  transition: transform 0.15s, background-color 0.15s;

  &:hover:not(:disabled) {
    transform: scale(1.04);
  }

  &:focus-visible {
    outline: 3px solid rgba(var(--v-theme-primary), 0.35);
    outline-offset: 2px;
  }

  &:disabled {
    opacity: 0.4;
    cursor: default;
  }
}

.listen__track {
  flex: 1 1 auto;
  min-width: 0;
}

.listen__title {
  margin: 0 0 0.375rem;
  overflow: hidden;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

// Thin time bar: blue part = played
.listen__bar {
  width: 100%;
  height: 20px;
  margin: 0;
  background: transparent;
  cursor: pointer;
  appearance: none;

  &::-webkit-slider-runnable-track {
    height: 4px;
    border-radius: 999px;
    background: linear-gradient(to right, $primary var(--listen-progress), $line var(--listen-progress));
  }

  &::-moz-range-track {
    height: 4px;
    border-radius: 999px;
    background: linear-gradient(to right, $primary var(--listen-progress), $line var(--listen-progress));
  }

  &::-webkit-slider-thumb {
    width: 14px;
    height: 14px;
    margin-top: -5px;
    border: 0;
    border-radius: 50%;
    background: $primary;
    appearance: none;
  }

  &::-moz-range-thumb {
    width: 14px;
    height: 14px;
    border: 0;
    border-radius: 50%;
    background: $primary;
  }

  &:disabled {
    cursor: default;
  }
}

.listen__time {
  display: flex;
  justify-content: space-between;
  margin: 0.125rem 0 0;
  font-size: 0.8125rem;
  font-variant-numeric: tabular-nums;
  color: $muted;
}

.listen__error {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  margin: 0.75rem 0 0;
  font-size: 0.875rem;
  color: rgb(var(--v-theme-error));
}

.listen__controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem 1rem;
  margin-block-start: 0.75rem;
  padding-block-start: 0.75rem;
  border-block-start: 1px solid $line;
}

.listen__buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-inline-start: -0.5rem;
}

// Two-button switch: 0.75x | 1x
.listen__speed {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 3px;
  border-radius: 999px;
  background: rgba(var(--v-theme-on-surface), 0.06);

  button {
    min-width: 56px;
    min-height: 36px;
    padding: 0 12px;
    border: 0;
    border-radius: 999px;
    background: none;
    color: $muted;
    font: inherit;
    font-size: 0.875rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    cursor: pointer;

    &.is-on {
      background: rgb(var(--v-theme-surface));
      color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    }
  }
}

.listen__speed-label {
  padding-inline: 10px 4px;
  font-size: 0.8125rem;
  color: $muted;
}
</style>
