<script setup>
// Tap the words in the right order. v-model = the list of words in the order chosen.
const props = defineProps({
  question: { type: Object, required: true },
  modelValue: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

// Words are picked by their position, so the same word can appear twice
const picked = ref([]);
watch(
  () => props.modelValue,
  (value) => {
    if (!value.length) picked.value = [];
  },
);

const sentence = computed(() => picked.value.map((index) => props.question.words[index]));
const update = () => emit("update:modelValue", sentence.value);

const pick = (index) => {
  picked.value.push(index);
  update();
};
const unpick = (position) => {
  picked.value.splice(position, 1);
  update();
};
</script>

<template>
  <div class="word-order">
    <div class="word-order__answer" :aria-label="$t('Your sentence')">
      <button
        v-for="(index, position) in picked"
        :key="`a-${position}`"
        type="button"
        class="word word--picked"
        :disabled="disabled"
        @click="unpick(position)"
      >
        {{ question.words[index] }}
      </button>
      <span v-if="!picked.length" class="word-order__empty">{{ $t("Tap the words in the right order") }}</span>
    </div>

    <div class="word-order__bank">
      <button
        v-for="(word, index) in question.words"
        :key="`w-${index}`"
        type="button"
        class="word"
        :disabled="disabled || picked.includes(index)"
        @click="pick(index)"
      >
        {{ word }}
      </button>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.word-order__answer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  min-height: 60px;
  padding: 0.5rem;
  margin-block-end: 0.75rem;
  border-block-end: 2px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.word-order__empty {
  font-size: 0.9375rem;
  color: rgba(var(--v-theme-on-surface), var(--v-disabled-opacity));
}

.word-order__bank {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.word {
  min-height: 44px;
  padding: 0.5rem 0.875rem;
  border: 1.5px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  background: rgb(var(--v-theme-surface));
  color: inherit;
  font: inherit;
  font-size: 1rem;
  cursor: pointer;

  &:disabled {
    opacity: 0.35;
    cursor: default;
  }

  &:focus-visible {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
  }
}

.word--picked {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);

  &:disabled {
    opacity: 1;
  }
}
</style>
