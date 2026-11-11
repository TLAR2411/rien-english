<script setup>
// Typed answers: every line is an answer the student may give (all correct).
const props = defineProps({
  modelValue: { type: Array, required: true },
  limits: { type: Array, default: () => [1, 10] },
  placeholder: { type: String, default: "Accepted answer" },
});

const emit = defineEmits(["update:modelValue"]);

const update = (options) => emit("update:modelValue", options);
const setText = (index, text) =>
  update(props.modelValue.map((option, i) => (i === index ? { text, is_correct: true } : option)));
const add = () => update([...props.modelValue, { text: "", is_correct: true }]);
const removeAt = (index) => update(props.modelValue.filter((_, i) => i !== index));
</script>

<template>
  <div v-for="(option, index) in modelValue" :key="index" class="d-flex align-center gap-2 mb-2">
    <VIcon icon="tabler-circle-check" color="success" size="20" />
    <AppTextField
      :model-value="option.text"
      :placeholder="$t(placeholder)"
      :rules="[(v) => !!String(v ?? '').trim() || $t('Required')]"
      @update:model-value="(text) => setText(index, text)"
    />
    <IconBtn size="small" :disabled="modelValue.length <= limits[0]" :aria-label="$t('Remove')" @click="removeAt(index)">
      <VIcon icon="tabler-x" />
    </IconBtn>
  </div>
  <VBtn
    v-if="modelValue.length < limits[1]"
    variant="text"
    size="small"
    prepend-icon="tabler-plus"
    @click="add"
  >
    {{ $t("Add another accepted answer") }}
  </VBtn>
</template>
