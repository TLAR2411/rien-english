<script setup>
/**
 * Pick one option. v-model = the chosen option id.
 * layout:
 *   list   — big rows with a letter A, B, C … (multiple choice)
 *   pair   — two big buttons side by side (True / False)
 *   inline — sentence parts in one line (Find the error)
 */
const props = defineProps({
  question: { type: Object, required: true },
  modelValue: { type: Number, default: null },
  layout: { type: String, default: "list" },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const letter = (index) => String.fromCharCode(65 + index);
</script>

<template>
  <div class="choice" :class="`choice--${layout}`" role="radiogroup">
    <button
      v-for="(option, index) in question.options"
      :key="option.id"
      type="button"
      role="radio"
      :aria-checked="modelValue === option.id"
      class="choice__option"
      :class="{ 'choice__option--picked': modelValue === option.id }"
      :disabled="disabled"
      @click="emit('update:modelValue', option.id)"
    >
      <span v-if="layout === 'list'" class="choice__letter" aria-hidden="true">{{ letter(index) }}</span>
      <span class="choice__text">{{ $t(option.text) }}</span>
      <VIcon
        v-if="layout === 'list' && modelValue === option.id"
        icon="tabler-circle-check"
        size="24"
        class="choice__check"
      />
    </button>
  </div>
</template>

<style lang="scss" scoped>
$primary: rgb(var(--v-theme-primary));
$line: rgba(var(--v-border-color), var(--v-border-opacity));

.choice {
  display: grid;
  gap: 0.75rem;
}

.choice--pair {
  grid-template-columns: 1fr 1fr;
}

.choice--inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.choice__option {
  display: flex;
  align-items: center;
  gap: 1rem;
  min-height: 56px;
  padding: 0.75rem 1rem;
  border: 1.5px solid $line;
  border-radius: 14px;
  background: rgb(var(--v-theme-surface));
  color: inherit;
  font: inherit;
  font-size: 1.0625rem;
  text-align: start;
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    background-color 0.15s ease;

  &:hover:not(:disabled) {
    border-color: rgba(var(--v-theme-primary), 0.5);
  }

  &:focus-visible {
    outline: 2px solid $primary;
    outline-offset: 2px;
  }

  &:disabled {
    cursor: default;
  }
}

.choice--list .choice__option {
  min-height: 64px;
}

.choice--pair .choice__option {
  justify-content: center;
  min-height: 64px;
  font-weight: 500;
}

.choice--inline .choice__option {
  min-height: 48px;
  border-radius: 10px;
}

.choice__letter {
  display: grid;
  flex: none;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(var(--v-theme-on-surface), 0.05);
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.9375rem;
  font-weight: 500;
  transition:
    background-color 0.15s ease,
    color 0.15s ease;
}

.choice__text {
  flex: 1 1 auto;
}

.choice__check {
  flex: none;
  color: $primary;
}

.choice__option--picked {
  border-color: rgba(var(--v-theme-primary), 0.6);
  background: rgba(var(--v-theme-primary), 0.1);
  font-weight: 500;

  .choice__letter {
    background: $primary;
    color: rgb(var(--v-theme-on-primary));
  }
}
</style>
