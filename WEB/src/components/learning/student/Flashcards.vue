<script setup>
import { useSpeech } from "@/composables/useSpeech";

/**
 * Flashcards of a vocabulary lesson (no login needed): one card at a time,
 * tap (or Space / Enter) to flip from the word to its meaning,
 * arrows / swipe / ← → to move, shuffle, and 🔊 for every word.
 * `words` = the lesson's words ({ id, word, meaning_en, meaning_km, example, audio_url }).
 * `khmer`: the back shows the Khmer meaning first (else only simple English).
 */
const props = defineProps({
  words: { type: Array, required: true },
  khmer: { type: Boolean, default: false },
});

const order = ref(props.words.map((_, index) => index));
const position = ref(0);
const flipped = ref(false);
const isShuffled = ref(false);
watch(
  () => props.words,
  (words) => {
    order.value = words.map((_, index) => index);
    position.value = 0;
    flipped.value = false;
  },
);

const card = computed(() => props.words[order.value[position.value]] ?? null);
const total = computed(() => props.words.length);
const progress = computed(() => ((position.value + 1) / Math.max(1, total.value)) * 100);
const mainMeaning = computed(() => (props.khmer && card.value?.meaning_km) || card.value?.meaning_en || card.value?.meaning_km);
const otherMeaning = computed(() => (props.khmer && card.value?.meaning_km ? card.value?.meaning_en : null));

const go = (step) => {
  const next = position.value + step;
  if (next < 0 || next >= total.value) return;
  flipped.value = false;
  position.value = next;
};

const shuffle = () => {
  const list = props.words.map((_, index) => index);
  if (!isShuffled.value) {
    for (let i = list.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [list[i], list[j]] = [list[j], list[i]];
    }
  }
  isShuffled.value = !isShuffled.value;
  order.value = list;
  position.value = 0;
  flipped.value = false;
};

const onKey = (event) => {
  if (event.key === "ArrowRight") go(1);
  else if (event.key === "ArrowLeft") go(-1);
  else if (event.key === " " || event.key === "Enter") flipped.value = !flipped.value;
  else return;
  event.preventDefault();
};

// Swipe left / right on phones
let touchX = null;
const onTouchStart = (event) => (touchX = event.touches[0].clientX);
const onTouchEnd = (event) => {
  if (touchX === null) return;
  const dx = event.changedTouches[0].clientX - touchX;
  touchX = null;
  if (Math.abs(dx) > 50) go(dx < 0 ? 1 : -1);
};

// 🔊 the word (uploaded audio, else the browser voice)
const speech = useSpeech();
const sound = computed(() => card.value && { id: `card-${card.value.id}`, sentence: card.value.word, audio_url: card.value.audio_url });
</script>

<template>
  <div v-if="card" class="cards">
    <div class="cards__bar">
      <span class="cards__count" aria-live="polite">{{ position + 1 }} / {{ total }}</span>
      <VBtn
        variant="text"
        size="small"
        :color="isShuffled ? 'primary' : undefined"
        prepend-icon="tabler-arrows-shuffle"
        :aria-pressed="isShuffled"
        @click="shuffle"
      >
        {{ $t(isShuffled ? "Shuffled" : "Shuffle") }}
      </VBtn>
    </div>
    <VProgressLinear :model-value="progress" color="primary" height="3" rounded class="mb-4" />

    <!-- The card: front = word, back = meaning -->
    <div class="cards__stage" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
      <button
        type="button"
        class="card"
        :class="{ 'card--flipped': flipped }"
        :aria-label="flipped ? $t('Show the word') : $t('Show the meaning')"
        @click="flipped = !flipped"
        @keydown="onKey"
      >
        <span class="card__face card__face--front" :aria-hidden="flipped">
          <span class="card__word" lang="en">{{ card.word }}</span>
          <span class="card__hint">{{ $t("Tap to see the meaning") }}</span>
        </span>
        <span class="card__face card__face--back" :aria-hidden="!flipped">
          <span class="card__meaning" :lang="khmer && card.meaning_km ? 'km' : 'en'">{{ mainMeaning }}</span>
          <span v-if="otherMeaning" class="card__other" lang="en">{{ otherMeaning }}</span>
          <span v-if="card.example" class="card__example" lang="en">“{{ card.example }}”</span>
        </span>
      </button>

      <VBtn
        v-if="speech.canPlay(sound)"
        icon
        variant="tonal"
        color="primary"
        size="44"
        class="cards__sound"
        :aria-label="$t('Listen')"
        @click="speech.toggle(sound)"
      >
        <VIcon :icon="speech.isPlaying(sound) ? 'tabler-player-stop' : 'tabler-volume'" size="22" />
      </VBtn>
    </div>

    <div class="cards__nav">
      <VBtn icon variant="outlined" size="48" :disabled="position === 0" :aria-label="$t('Previous')" @click="go(-1)">
        <VIcon icon="tabler-arrow-left" />
      </VBtn>
      <VBtn variant="text" prepend-icon="tabler-rotate-clockwise-2" @click="flipped = !flipped">
        {{ $t("Flip") }}
      </VBtn>
      <VBtn icon variant="outlined" size="48" :disabled="position === total - 1" :aria-label="$t('Next')" @click="go(1)">
        <VIcon icon="tabler-arrow-right" />
      </VBtn>
    </div>
    <p class="cards__keys">{{ $t("Keyboard: Space to flip, ← → to move.") }}</p>
  </div>
</template>

<style lang="scss" scoped>
$muted: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
$line: rgba(var(--v-border-color), var(--v-border-opacity));

.cards {
  max-width: 560px;
  margin-inline: auto;
}

.cards__bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block-end: 0.5rem;
}

.cards__count {
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  color: $muted;
}

.cards__stage {
  position: relative;
  perspective: 1200px;
}

.card {
  position: relative;
  display: block;
  width: 100%;
  min-height: 260px;
  padding: 0;
  border: 0;
  background: none;
  cursor: pointer;
  transform-style: preserve-3d;
  transition: transform 0.45s cubic-bezier(0.2, 0.7, 0.2, 1);

  &--flipped {
    transform: rotateY(180deg);
  }

  &:focus-visible .card__face {
    outline: 3px solid rgba(var(--v-theme-primary), 0.35);
    outline-offset: 2px;
  }
}

.card__face {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem 1.5rem;
  border: 1px solid $line;
  border-radius: 16px;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), 0 8px 24px rgba(0, 0, 0, 0.06);
  backface-visibility: hidden;
  text-align: center;

  &--back {
    background: rgba(var(--v-theme-primary), 0.04);
    transform: rotateY(180deg);
  }
}

.card__word {
  font-size: clamp(2rem, 7vw, 2.75rem);
  font-weight: 700;
  letter-spacing: -0.02em;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.card__hint {
  font-size: 0.875rem;
  color: $muted;
}

.card__meaning {
  font-size: clamp(1.5rem, 5vw, 2rem);
  font-weight: 600;
  line-height: 1.6;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.card__other {
  font-size: 1rem;
  color: $muted;
}

.card__example {
  max-width: 26rem;
  font-size: 1rem;
  font-style: italic;
  line-height: 1.6;
  color: $muted;
}

.cards__sound {
  position: absolute;
  inset-block-start: 12px;
  inset-inline-end: 12px;
  z-index: 1;
}

.cards__nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block-start: 1.25rem;
}

.cards__keys {
  margin: 0.75rem 0 0;
  font-size: 0.8125rem;
  text-align: center;
  color: $muted;

  @media (hover: none) {
    display: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .card {
    transition: none;
  }
}
</style>
