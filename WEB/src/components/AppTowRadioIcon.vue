<script setup>
const props = defineProps({
  selectedRadio: {
    type: String,
    required: true,
  },
  radioContent: {
    type: Array,
    required: true,
  },
  gridColumn: {
    type: null,
    required: false,
  },
  color: {
    type: String,
    default: "primary",
  },
});

const emit = defineEmits(["update:selectedRadio"]);

const updateSelectedOption = (value) => {
  if (value !== null) emit("update:selectedRadio", value);
};

const getItemStyle = (item) => {
  const base = { padding: "5px" };
  if (props.selectedRadio !== item.value) return base;
  const c = item.color ?? props.color;
  return {
    ...base,
    backgroundColor: `rgba(var(--v-theme-${c}), 0.16)`,
    borderColor: `rgba(var(--v-theme-${c}), 0.24)`,
    color: `rgb(var(--v-theme-${c}))`,
  };
};

// Helper to resolve icon color based on selection state
const getIconColor = (item) => {
  if (props.selectedRadio === item.value) {
    const c = item.color ?? props.color;

    // If it's a Vuetify theme color → convert to CSS var
    return `rgb(var(--v-theme-${c}))`;
  }

  return undefined;
};
</script>

<template>
  <VRadioGroup
    v-if="radioContent"
    :model-value="selectedRadio"
    class="custom-input-wrapper"
    @update:model-value="updateSelectedOption"
    variant="tonal"
  >
    <VRow>
      <VCol
        v-for="item in props.radioContent"
        :key="item.title"
        v-bind="gridColumn"
      >
        <VLabel
          class="custom-input custom-radio-icon rounded cursor-pointer"
          :style="getItemStyle(item)"
          variant="tonal"
        >
          <slot :item="item">
            <div
              class="d-flex flex-column align-center text-center"
              style="align-items: center"
            >
              <VIcon
                :icon="item.icon.icon"
                :size="item.icon.size"
                :color="getIconColor(item)"
              />
              <span>
                {{ $t(item.title) }}
              </span>
            </div>
          </slot>

          <div class="d-none">
            <VRadio :value="item.value" :color="item.color ?? color" />
          </div>
        </VLabel>
      </VCol>
    </VRow>
  </VRadioGroup>
</template>

<style lang="scss" scoped>
.custom-radio-icon {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;

  .v-radio {
    margin-block-end: -0.25rem;

    .v-selection-control__wrapper {
      margin-inline-start: 0;
    }
  }
}
</style>
