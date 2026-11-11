<script setup>
const props = defineProps({
  item: {
    type: Object,
    required: true,
    default: () => ({ name: "", value: "", id: null }),
  },
  loading: {
    type: Boolean,
    default: false,
  },
  // Edit value inside the card (emits onSave) instead of emitting onEdit
  inline: {
    type: Boolean,
    default: false,
  },
  // Options for select settings: [{ title, value, subtitle? }]
  options: {
    type: Array,
    default: null,
  },
});

const emit = defineEmits(["onEdit", "onToggle", "onSave"]);

const isBool = computed(() => props.item.type === "bool");
const isNumber = computed(() => ["int", "decimal"].includes(props.item.type));
const isSelect = computed(() => Array.isArray(props.options));

const isOn = computed(
  () =>
    props.item.value === true ||
    props.item.value === 1 ||
    props.item.value === "1",
);

const selectedOption = computed(() =>
  isSelect.value
    ? props.options.find((o) => String(o.value) === String(props.item.value))
    : null,
);

const displayValue = computed(() => {
  const value = props.item.value;
  if (value === null || value === undefined || value === "") return "—";
  return selectedOption.value ? selectedOption.value.title : value;
});

const optionTitle = (o) =>
  o.subtitle ? `${o.subtitle} - ${o.title}` : o.title;

// Inline edit state
const isEditing = ref(false);
const draft = ref(null);

const isValid = computed(() => {
  if (draft.value === null || draft.value === "") return false;
  if (props.item.type === "int") return Number.isInteger(Number(draft.value));
  if (props.item.type === "decimal") return !Number.isNaN(Number(draft.value));
  return true;
});

const isChanged = computed(
  () => String(draft.value) !== String(props.item.value),
);

const handleEdit = () => {
  if (!props.inline) {
    emit("onEdit", props.item.id);
    return;
  }
  draft.value = selectedOption.value?.value ?? props.item.value;
  isEditing.value = true;
};

const cancelEdit = () => {
  isEditing.value = false;
};

const saveEdit = () => {
  if (!isValid.value || props.loading) return;
  if (!isChanged.value) {
    isEditing.value = false;
    return;
  }
  const value = isNumber.value ? Number(draft.value) : draft.value;
  emit("onSave", props.item, value, (success) => {
    if (success) isEditing.value = false;
  });
};

const handleToggle = (value) => {
  emit("onToggle", props.item, value);
};
</script>

<template>
  <VCol cols="12" sm="6" md="4" lg="3">
    <VCard
      variant="outlined"
      class="card-hover-effect rounded-lg h-100"
      :class="{
        'card-on': isBool && isOn,
        'card-editing': isEditing,
      }"
    >
      <VCardText class="pa-4">
        <!-- Editing mode -->
        <div v-if="isEditing" class="d-flex flex-column ga-2">
          <span
            class="text-caption text-medium-emphasis text-uppercase font-weight-medium text-truncate"
            style="font-family: inter, kantumruy, sans-serif !important"
            :title="item.name"
          >
            {{ item.name }}
          </span>

          <div class="d-flex align-center ga-2">
            <AppAutocomplete
              v-if="isSelect"
              v-model="draft"
              :items="options"
              :item-title="optionTitle"
              item-value="value"
              density="compact"
              class="flex-grow-1"
              :disabled="loading"
              autofocus
              @keydown.esc="cancelEdit"
            />
            <AppTextField
              v-else
              v-model="draft"
              :numbersOnly="isNumber"
              :step="item.type === 'decimal' ? 'any' : 1"
              density="compact"
              class="flex-grow-1"
              :disabled="loading"
              :error="!isValid"
              autofocus
              @keydown.enter="saveEdit"
              @keydown.esc="cancelEdit"
            />

            <VBtn
              icon="tabler-check"
              variant="tonal"
              color="success"
              size="small"
              rounded
              aria-label="Save"
              :loading="loading"
              :disabled="!isValid"
              @click="saveEdit"
            />
            <VBtn
              icon="tabler-x"
              variant="tonal"
              color="secondary"
              size="small"
              rounded
              aria-label="Cancel"
              :disabled="loading"
              @click="cancelEdit"
            />
          </div>
        </div>

        <!-- View mode -->
        <div v-else class="d-flex align-center justify-space-between ga-3">
          <div class="d-flex flex-column overflow-hidden">
            <span
              style="font-family: inter, kantumruy, sans-serif !important"
              class="text-caption text-medium-emphasis text-uppercase font-weight-medium text-truncate"
              :title="item.name"
            >
              {{ item.name }}
            </span>

            <div v-if="isBool" class="mt-2">
              <VChip
                size="small"
                label
                :color="isOn ? 'success' : 'secondary'"
                :prepend-icon="isOn ? 'tabler-check' : 'tabler-x'"
              >
                {{ isOn ? "Enabled" : "Disabled" }}
              </VChip>
            </div>
            <span
              v-else
              class="text-h6 text-high-emphasis mt-1 text-truncate"
              :class="{ 'value-clickable': inline }"
              style="font-family: inter, kantumruy, sans-serif; font-size: 16px !important;"
              :title="String(displayValue)"
              @click="inline && handleEdit()"
            >
              {{ displayValue }}
            </span>
            <span
              v-if="selectedOption?.subtitle"
              class="text-caption text-disabled text-truncate"
            >
              {{ selectedOption.subtitle }}
            </span>
          </div>

          <!-- Action -->
          <VSwitch
            v-if="isBool"
            :model-value="isOn"
            :loading="loading ? 'primary' : false"
            :disabled="loading"
            color="success"
            density="compact"
            hide-details
            inset
            class="flex-grow-0"
            :aria-label="item.name"
            @update:model-value="handleToggle"
          />
          <VBtn
            v-else
            icon="tabler-pencil"
            variant="tonal"
            color="warning"
            size="small"
            rounded
            aria-label="Edit item"
            @click="handleEdit"
          />
        </div>
      </VCardText>
    </VCard>
  </VCol>
</template>

<style scoped>
.card-hover-effect {
  transition:
    transform 0.2s ease-in-out,
    box-shadow 0.2s ease-in-out,
    border-color 0.2s ease-in-out;
}

.card-hover-effect:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 18px 0 rgba(0, 0, 0, 0.08) !important;
}

.card-editing {
  border-color: rgb(var(--v-theme-primary)) !important;
  box-shadow: 0 0 0 3px rgba(var(--v-theme-primary), 0.12) !important;
  transform: none !important;
}

.value-clickable {
  cursor: pointer;
}

.value-clickable:hover {
  color: rgb(var(--v-theme-primary)) !important;
}
</style>
