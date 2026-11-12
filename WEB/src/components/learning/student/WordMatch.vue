<script setup>
import { useI18n } from "vue-i18n";
import { checkWordMatch, getWordMatch } from "@/services/site";

/**
 * Matching game of a vocabulary lesson (student system). Tap a word, then its
 * meaning (or the other way round); tap a matched card to undo. When every word
 * is matched, "Check answers" sends the pairs — the API grades them, because
 * the meanings only carry a secret token, not their word.
 * `khmer`: meanings show Khmer first (else simple English).
 */
const props = defineProps({
  lesson: { type: Object, required: true },
  khmer: { type: Boolean, default: false },
});

const { t } = useI18n();

const game = ref(null);
const isLoading = ref(true);
const isChecking = ref(false);
const result = ref(null);
const error = ref("");
const notAvailable = ref(false);

// pairs: [{ wordId, token }] in the order they were made; picked: { side: "word" | "meaning", key }
const pairs = ref([]);
const picked = ref(null);

const load = async () => {
  isLoading.value = true;
  result.value = null;
  error.value = "";
  pairs.value = [];
  picked.value = null;
  try {
    game.value = await getWordMatch(props.lesson.slug);
  } catch {
    notAvailable.value = true;
  } finally {
    isLoading.value = false;
  }
};
watch(() => props.lesson.slug, load, { immediate: true });

const total = computed(() => game.value?.words.length ?? 0);
const matched = computed(() => pairs.value.length);
const best = computed(() => result.value?.best_percent ?? game.value?.best_percent ?? null);

// Number badge of each pair (1, 2, 3 … in the order they were made)
const numberOf = (index) => (index < 0 ? null : index + 1);
const numberOfWord = (wordId) => numberOf(pairs.value.findIndex((pair) => pair.wordId === wordId));
const numberOfMeaning = (token) => numberOf(pairs.value.findIndex((pair) => pair.token === token));
const unpair = (index) => (pairs.value = pairs.value.filter((_, i) => i !== index));

const meaningText = (meaning) => (props.khmer ? meaning.text : meaning.text_en || meaning.text);
const meaningSub = (meaning) => (props.khmer && meaning.text_en ? meaning.text_en : null);

const pickWord = (wordId) => {
  if (result.value) return;
  const paired = pairs.value.findIndex((pair) => pair.wordId === wordId);
  if (paired >= 0) return unpair(paired); // tap a matched card: undo
  if (picked.value?.side === "meaning") {
    pairs.value = [...pairs.value, { wordId, token: picked.value.key }];
    picked.value = null;
  } else {
    picked.value = picked.value?.key === wordId ? null : { side: "word", key: wordId };
  }
};

const pickMeaning = (token) => {
  if (result.value) return;
  const paired = pairs.value.findIndex((pair) => pair.token === token);
  if (paired >= 0) return unpair(paired);
  if (picked.value?.side === "word") {
    pairs.value = [...pairs.value, { wordId: picked.value.key, token }];
    picked.value = null;
  } else {
    picked.value = picked.value?.key === token ? null : { side: "meaning", key: token };
  }
};

const check = async () => {
  isChecking.value = true;
  error.value = "";
  try {
    result.value = await checkWordMatch(
      props.lesson.slug,
      pairs.value.map((pair) => ({ word_id: pair.wordId, token: pair.token })),
    );
  } catch (e) {
    const response = e?.response?.data;
    error.value = response?.message ?? t("Something went wrong. Please try again.");
  } finally {
    isChecking.value = false;
  }
};

const resultOf = (wordId) => result.value?.pairs.find((pair) => pair.word_id === wordId);
const resultText = (meaning) => (props.khmer ? meaning.text : meaning.text_en || meaning.text);
</script>

<template>
  <section v-if="!notAvailable" class="match">
    <header class="match__header">
      <div>
        <h2 class="match__title">{{ $t("Match the words") }}</h2>
        <p class="match__lead">{{ $t("Tap a word, then its meaning. Tap a matched card to undo.") }}</p>
      </div>
      <VChip v-if="best !== null" color="success" variant="tonal" size="small">
        <VIcon icon="tabler-trophy" size="16" start />
        {{ $t("Best {percent}%", { percent: best }) }}
      </VChip>
    </header>

    <VProgressLinear v-if="isLoading" indeterminate color="primary" />

    <template v-else-if="game">
      <!-- Result -->
      <div v-if="result" class="match__result">
        <strong>{{ result.score }} / {{ result.total }}</strong>
        <span>{{ result.percent === 100 ? $t("Perfect! Every word is right.") : $t("{percent}% right", { percent: result.percent }) }}</span>
      </div>

      <div v-if="result" class="match__answers">
        <div
          v-for="word in game.words"
          :key="word.id"
          class="match__answer"
          :class="resultOf(word.id)?.correct ? 'is-right' : 'is-wrong'"
        >
          <VIcon
            :icon="resultOf(word.id)?.correct ? 'tabler-circle-check-filled' : 'tabler-circle-x-filled'"
            size="22"
          />
          <div class="flex-grow-1">
            <p class="match__answer-word" lang="en">{{ word.word }}</p>
            <p class="match__answer-meaning">
              <template v-if="resultOf(word.id)?.correct">{{ resultText(resultOf(word.id).right_meaning) }}</template>
              <template v-else>
                <s>{{ resultText(resultOf(word.id).your_meaning) }}</s>
                → <strong>{{ resultText(resultOf(word.id).right_meaning) }}</strong>
              </template>
            </p>
          </div>
        </div>
      </div>

      <!-- The board -->
      <div v-else class="match__board">
        <div class="match__column" role="group" :aria-label="$t('Words')">
          <button
            v-for="word in game.words"
            :key="word.id"
            type="button"
            class="match__tile"
            :class="{ 'is-picked': picked?.key === word.id, 'is-paired': numberOfWord(word.id) }"
            :aria-pressed="picked?.key === word.id || !!numberOfWord(word.id)"
            lang="en"
            @click="pickWord(word.id)"
          >
            <span v-if="numberOfWord(word.id)" class="match__badge">{{ numberOfWord(word.id) }}</span>
            <span class="match__text match__text--word">{{ word.word }}</span>
          </button>
        </div>
        <div class="match__column" role="group" :aria-label="$t('Meanings')">
          <button
            v-for="meaning in game.meanings"
            :key="meaning.token"
            type="button"
            class="match__tile"
            :class="{ 'is-picked': picked?.key === meaning.token, 'is-paired': numberOfMeaning(meaning.token) }"
            :aria-pressed="picked?.key === meaning.token || !!numberOfMeaning(meaning.token)"
            @click="pickMeaning(meaning.token)"
          >
            <span v-if="numberOfMeaning(meaning.token)" class="match__badge">{{ numberOfMeaning(meaning.token) }}</span>
            <span class="match__text">
              {{ meaningText(meaning) }}
              <small v-if="meaningSub(meaning)">{{ meaningSub(meaning) }}</small>
            </span>
          </button>
        </div>
      </div>

      <p v-if="error" class="match__error" role="alert">
        <VIcon icon="tabler-alert-circle" size="18" />
        {{ error }}
      </p>

      <div class="match__footer">
        <template v-if="result">
          <span />
          <VBtn color="primary" prepend-icon="tabler-refresh" @click="load">{{ $t("New game") }}</VBtn>
        </template>
        <template v-else>
          <span class="match__progress">{{ $t("{done} of {total} matched", { done: matched, total }) }}</span>
          <div class="d-flex gap-2">
            <VBtn v-if="matched" variant="text" @click="(pairs = []), (picked = null)">{{ $t("Clear") }}</VBtn>
            <VBtn color="primary" :disabled="matched < total" :loading="isChecking" @click="check">
              {{ $t("Check answers") }}
            </VBtn>
          </div>
        </template>
      </div>
    </template>
  </section>
</template>

<style lang="scss" scoped>
$muted: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
$line: rgba(var(--v-border-color), var(--v-border-opacity));
$primary: rgb(var(--v-theme-primary));

.match__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-block-end: 1.25rem;
}

.match__title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.match__lead {
  margin: 0.375rem 0 0;
  color: $muted;
}

.match__board {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 0.75rem;
}

.match__column {
  display: grid;
  align-content: start;
  gap: 0.5rem;
}

.match__tile {
  position: relative;
  display: flex;
  align-items: center;
  min-height: 56px;
  padding: 0.625rem 0.875rem;
  border: 1.5px solid $line;
  border-radius: 10px;
  background: rgb(var(--v-theme-surface));
  color: inherit;
  font: inherit;
  text-align: start;
  cursor: pointer;
  transition: border-color 0.15s, background-color 0.15s;

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.5);
  }

  &:focus-visible {
    outline: 3px solid rgba(var(--v-theme-primary), 0.3);
    outline-offset: 1px;
  }

  &.is-picked {
    border-color: $primary;
    background: rgba(var(--v-theme-primary), 0.08);
  }

  &.is-paired {
    border-color: rgba(var(--v-theme-primary), 0.35);
    background: rgba(var(--v-theme-primary), 0.04);
    color: $muted;
  }
}

.match__badge {
  display: inline-grid;
  flex: 0 0 auto;
  place-items: center;
  width: 22px;
  height: 22px;
  margin-inline-end: 0.625rem;
  border-radius: 50%;
  background: $primary;
  color: rgb(var(--v-theme-on-primary));
  font-size: 0.75rem;
  font-weight: 700;
}

.match__text {
  line-height: 1.5;

  &--word {
    font-weight: 600;
  }

  small {
    display: block;
    font-size: 0.8125rem;
    color: $muted;
  }
}

.match__footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-block-start: 1.25rem;
}

.match__progress {
  font-weight: 600;
  color: $muted;
}

.match__error {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  margin: 0.75rem 0 0;
  color: rgb(var(--v-theme-error));
}

// Result
.match__result {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
  margin-block-end: 1rem;

  strong {
    font-size: 2.25rem;
    line-height: 1;
  }

  span {
    color: $muted;
  }
}

.match__answers {
  display: grid;
  gap: 0.5rem;
}

.match__answer {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid $line;
  border-radius: 10px;

  p {
    margin: 0;
    line-height: 1.6;
  }

  &.is-right > .v-icon {
    color: rgb(var(--v-theme-success));
  }

  &.is-wrong {
    border-color: rgba(var(--v-theme-error), 0.35);

    > .v-icon {
      color: rgb(var(--v-theme-error));
    }
  }
}

.match__answer-word {
  font-weight: 600;
}

.match__answer-meaning {
  color: $muted;

  s {
    color: rgb(var(--v-theme-error));
  }

  strong {
    color: rgb(var(--v-theme-success));
  }
}
</style>
