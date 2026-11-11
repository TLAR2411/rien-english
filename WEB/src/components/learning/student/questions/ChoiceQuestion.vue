<script setup>
// Pick one option. v-model = the chosen option id.
// `inline` shows the options as sentence parts in one line (Find the error).
const props = defineProps({
  question: { type: Object, required: true },
  modelValue: { type: Number, default: null },
  inline: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);
</script>

<template>
  <div :class="inline ? 'choice choice--inline' : 'choice'" role="radiogroup">
    <button
      v-for="option in question.options"
      :key="option.id"
      type="button"
      role="radio"
      :aria-checked="modelValue === option.id"
      class="choice__option"
      :class="{ 'choice__option--picked': modelValue === option.id }"
      :disabled="disabled"
      @click="emit('update:modelValue', option.id)"
    >
      {{ $t(option.text) }}
    </button>
  </div>
</template>

<style lang="scss" scoped>
.choice {
  display: grid;
  gap: 0.5rem;
}

.choice--inline {
  display: flex;
  flex-wrap: wrap;
}

.choice__option {
  min-height: 48px;
  padding: 0.625rem 1rem;
  border: 1.5px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
  color: inherit;
  font: inherit;
  font-size: 1rem;
  text-align: start;
  cursor: pointer;
  transition: border-color 0.15s ease, background-color 0.15s ease;

  &:hover:not(:disabled) {
    border-color: rgba(var(--v-theme-primary), 0.5);
  }

  &:focus-visible {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
  }

  &:disabled {
    cursor: default;
  }
}

.choice__option--picked {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);
  font-weight: 500;
}
</style>
