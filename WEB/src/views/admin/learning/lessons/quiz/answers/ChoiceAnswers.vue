<script setup>
// Options to pick from; the radio marks the one correct option.
// `fixed`: the texts can't change and no option can be added (True / False).
const props = defineProps({
  modelValue: { type: Array, required: true },
  limits: { type: Array, default: () => [2, 6] },
  fixed: { type: Boolean, default: false },
  placeholder: { type: String, default: "Answer" },
});

const emit = defineEmits(["update:modelValue"]);

const update = (options) => emit("update:modelValue", options);
const correctIndex = computed(() => props.modelValue.findIndex((option) => option.is_correct));

const setCorrect = (index) =>
  update(props.modelValue.map((option, i) => ({ ...option, is_correct: i === index })));
const setText = (index, text) =>
  update(props.modelValue.map((option, i) => (i === index ? { ...option, text } : option)));
const add = () => update([...props.modelValue, { text: "", is_correct: false }]);
const removeAt = (index) => {
  const options = props.modelValue.filter((_, i) => i !== index);
  if (!options.some((option) => option.is_correct) && options.length) options[0] = { ...options[0], is_correct: true };
  update(options);
};
</script>

<template>
  <VRadioGroup :model-value="correctIndex" hide-details @update:model-value="setCorrect">
    <div v-for="(option, index) in modelValue" :key="index" class="d-flex align-center gap-2 mb-2">
      <VRadio :value="index" :aria-label="$t('Correct answer')" color="success" class="flex-grow-0" />
      <AppTextField
        :model-value="option.text"
        :placeholder="$t(placeholder)"
        :readonly="fixed"
        :rules="[(v) => !!String(v ?? '').trim() || $t('Required')]"
        :class="{ 'answer--correct': option.is_correct }"
        @update:model-value="(text) => setText(index, text)"
      />
      <IconBtn
        v-if="!fixed"
        size="small"
        :disabled="modelValue.length <= limits[0]"
        :aria-label="$t('Remove')"
        @click="removeAt(index)"
      >
        <VIcon icon="tabler-x" />
      </IconBtn>
    </div>
  </VRadioGroup>
  <VBtn
    v-if="!fixed && modelValue.length < limits[1]"
    variant="text"
    size="small"
    prepend-icon="tabler-plus"
    @click="add"
  >
    {{ $t("Add answer") }}
  </VBtn>
</template>

<style scoped>
.answer--correct :deep(.v-field) {
  background: rgba(var(--v-theme-success), 0.06);
}
</style>
